<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

const DATA = __DIR__ . '/../data/';
const MOODS = [
    'gembira' => ['Gembira', '#ffd54f'], 'senang' => ['Senang', '#ff94b8'],
    'puas'    => ['Puas', '#7fc8ff'],    'netral' => ['Netral', '#6fe3b8'],
    'kecewa'  => ['Kecewa', '#aebcff'],  'kesal'  => ['Kesal', '#cda2ff'],
    'marah'   => ['Marah', '#ff9d6e'],   'sedih'  => ['Sedih', '#69d7ec'],
];
const TAGS = [
    'kerja' => 'Kerja', 'keluarga' => 'Keluarga', 'hubungan' => 'Hubungan', 'teman' => 'Teman',
    'pribadi' => 'Diri sendiri', 'kuliah' => 'Kuliah', 'kesehatan' => 'Kesehatan', 'keuangan' => 'Keuangan',
];
const MON = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

if (!is_dir(DATA)) mkdir(DATA, 0755, true);
if (!is_file(DATA . '.htaccess')) file_put_contents(DATA . '.htaccess', "Require all denied\nDeny from all\n");


/* ---------- Fallback jika ekstensi mbstring belum aktif ---------- */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $s): int { return (int)preg_match_all('/./su', $s); }
    function mb_substr(string $s, int $a, ?int $l = null): string { preg_match_all('/./su', $s, $m); return implode('', array_slice($m[0], $a, $l)); }
    function mb_strtoupper(string $s): string { return strtoupper($s); }
    function mb_strimwidth(string $s, int $a, int $w, string $t = ''): string {
        return mb_strlen($s) <= $w ? $s : mb_substr($s, $a, $w - mb_strlen($t)) . $t;
    }
}

/* ---------- Input & output ---------- */
function p(string $k): string { return isset($_POST[$k]) && is_string($_POST[$k]) ? trim($_POST[$k]) : ''; }
function g(string $k): string { return isset($_GET[$k]) && is_string($_GET[$k]) ? trim($_GET[$k]) : ''; }
function clean(string $s): string { return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8'); }          // sanitasi input
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8', false); }    // aman untuk output
function redirect(string $to): never { header('Location: ' . $to); exit; }
function valid_date(string $s): bool { $d = DateTime::createFromFormat('Y-m-d', $s); return $d && $d->format('Y-m-d') === $s; }

/* ---------- Penyimpanan JSON ---------- */
function jread(string $f): array {
    $path = DATA . $f;
    if (!is_file($path)) return [];
    $d = json_decode((string)file_get_contents($path), true);
    return is_array($d) ? $d : [];
}
function jwrite(string $f, array $d): void {
    file_put_contents(DATA . $f, json_encode(array_values($d), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/* ---------- Flash & CSRF ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function csrf_field(): string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', p('csrf')); }

/* ---------- Auth ---------- */
function find_user(string $key, string $val): ?array {
    foreach (jread('users.json') as $u) if (strcasecmp((string)$u[$key], $val) === 0) return $u;
    return null;
}
function login_user(array $u): void { session_regenerate_id(true); $_SESSION['uid'] = $u['id']; }
function current_user(): ?array {
    static $cache = null;
    if (!isset($_SESSION['uid'])) return null;
    return $cache ??= find_user('id', $_SESSION['uid']);
}
function require_login(): void {
    if (!current_user()) { flash('error', 'Masuk dulu untuk membuka halaman itu.'); redirect('login.php'); }
}
function guest_only(): void { if (current_user()) redirect('dashboard.php'); }

/* ---------- Remember me (cookie dengan token ter-hash) ---------- */
function remember_set(string $uid): void {
    $sel = bin2hex(random_bytes(9)); $val = bin2hex(random_bytes(32)); $exp = time() + 60 * 60 * 24 * 30;
    $t = array_filter(jread('tokens.json'), fn($x) => $x['exp'] > time());
    $t[] = ['selector' => $sel, 'hash' => hash('sha256', $val), 'uid' => $uid, 'exp' => $exp];
    jwrite('tokens.json', $t);
    setcookie('remember', "$sel:$val", ['expires' => $exp, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}
function remember_clear(): void {
    if (!empty($_COOKIE['remember']) && is_string($_COOKIE['remember'])) {
        $sel = explode(':', $_COOKIE['remember'])[0];
        jwrite('tokens.json', array_filter(jread('tokens.json'), fn($x) => $x['selector'] !== $sel));
    }
    setcookie('remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}
if (!isset($_SESSION['uid']) && !empty($_COOKIE['remember']) && is_string($_COOKIE['remember']) && str_contains($_COOKIE['remember'], ':')) {
    [$sel, $val] = explode(':', $_COOKIE['remember'], 2);
    foreach (jread('tokens.json') as $t) {
        if ($t['selector'] === $sel && $t['exp'] > time() && hash_equals($t['hash'], hash('sha256', $val))) {
            if ($u = find_user('id', $t['uid'])) login_user($u);
            break;
        }
    }
}

/* ---------- Ikon SVG ---------- */
function icon(string $n, int $s = 20): string {
    static $i = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="3"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'left' => '<path d="M15 5l-7 7 7 7"/>', 'right' => '<path d="M9 5l7 7-7 7"/>',
        'book' => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 21.5V4.5M9 7h7"/>',
        'check' => '<circle cx="12" cy="12" r="10"/><path d="M8 12.5l3 3 5-6"/>',
        'alert' => '<circle cx="12" cy="12" r="10"/><path d="M12 7v6M12 16.5v.5"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($i[$n] ?? '') . '</svg>';
}
function mood_icon(string $k, int $s = 28): string {
    $dots = '<circle cx="9" cy="10" r=".7" fill="currentColor"/><circle cx="15" cy="10" r=".7" fill="currentColor"/>';
    $f = [
        'gembira' => '<path d="M7.5 9.8q1-1.8 2 0M14.5 9.8q1-1.8 2 0"/><path d="M7.8 13.3h8.4a4.2 4.2 0 0 1-8.4 0z" fill="currentColor"/>',
        'senang'  => $dots . '<path d="M8 13.8q4 3.6 8 0"/>',
        'puas'    => '<path d="M7.5 10q1.5 1.6 3 0M13.5 10q1.5 1.6 3 0"/><path d="M9 14.4q3 2 6 0"/>',
        'netral'  => $dots . '<path d="M9 14.8h6"/>',
        'kecewa'  => $dots . '<path d="M9 15.8q3-1.8 6 0"/>',
        'kesal'   => '<path d="M7.5 10h3M13.5 10h3"/><path d="M9 15h6"/>',
        'marah'   => $dots . '<path d="M7.3 7.6l3.2 1.6M16.7 7.6l-3.2 1.6"/><path d="M9 16q3-2.6 6 0"/>',
        'sedih'   => $dots . '<path d="M7.3 8.8l3.2-1.4M16.7 8.8l-3.2-1.4"/><path d="M9 16q3-2.4 6 0"/><path d="M8.6 12.2q-.9 1.3 0 2.2"/>',
    ];
    return '<svg class="face" viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/>' . ($f[$k] ?? '') . '</svg>';
}
