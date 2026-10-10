<?php
function page_start(string $title, string $cls = ''): void {
    $u = current_user(); $f = get_flash(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> | Rasa</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= h($cls) ?>">
<?php if ($u): ?>
<header class="topbar">
  <a class="brand" href="dashboard.php"><?= icon('book', 22) ?><span>Rasa</span></a>
  <div class="topbar-right">
    <a class="avatar" href="profile.php" title="<?= h($u['name']) ?>"><?= h(mb_strtoupper(mb_substr(html_entity_decode($u['name']), 0, 1))) ?></a>
    <a class="icon-btn" href="logout.php" aria-label="Keluar"><?= icon('logout') ?></a>
  </div>
</header>
<?php endif; ?>
<main class="wrap">
<?php if ($f): ?>
  <div class="alert <?= $f[0] === 'success' ? 'ok' : 'err' ?>" role="status">
    <?= icon($f[0] === 'success' ? 'check' : 'alert') ?><span><?= h($f[1]) ?></span>
    <button type="button" class="alert-x" aria-label="Tutup"><?= icon('x', 16) ?></button>
  </div>
<?php endif;
}

function page_end(): void {
    $cur = basename($_SERVER['SCRIPT_NAME']); ?>
</main>
<?php if (current_user()): ?>
<nav class="dock" aria-label="Navigasi utama">
  <a href="dashboard.php" class="<?= $cur === 'dashboard.php' ? 'on' : '' ?>" aria-label="Beranda"><?= icon('home', 22) ?></a>
  <a href="dashboard.php#kalender" aria-label="Kalender"><?= icon('calendar', 22) ?></a>
  <a href="profile.php" class="<?= $cur === 'profile.php' ? 'on' : '' ?>" aria-label="Profil"><?= icon('user', 22) ?></a>
  <a href="entry.php" class="plus <?= $cur === 'entry.php' ? 'on' : '' ?>" aria-label="Tulis catatan"><?= icon('plus', 22) ?></a>
</nav>
<?php endif; ?>
<script>
document.querySelectorAll('.alert-x').forEach(b => b.addEventListener('click', () => b.parentElement.remove()));
const ta = document.querySelector('textarea[maxlength]'), ct = document.getElementById('count');
if (ta && ct) { const u = () => ct.textContent = ta.value.length + ' / ' + ta.maxLength; ta.addEventListener('input', u); u(); }
</script>
</body>
</html>
<?php }

function auth_art(): void { ?>
<aside class="auth-art" aria-hidden="true">
  <a class="brand" href="index.php"><?= icon('book', 24) ?><span>Rasa</span></a>
  <div>
    <h2>Tulis hari ini, pahami dirimu besok.</h2>
    <p>Catat suasana hati, kejadian, dan hal yang bikin harimu berarti.</p>
  </div>
  <div class="art-grid">
    <?php foreach (MOODS as $k => [$l, $c]): ?><span style="--c:<?= $c ?>"><?= mood_icon($k, 34) ?></span><?php endforeach; ?>
  </div>
</aside>
<?php }
