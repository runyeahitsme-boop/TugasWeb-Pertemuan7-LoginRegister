<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();
$u = current_user();
$errors = []; $pErrors = [];
$name = $_SERVER['REQUEST_METHOD'] === 'POST' ? p('name') : html_entity_decode($u['name']);
$email = $_SERVER['REQUEST_METHOD'] === 'POST' ? p('email') : $u['email'];
$bio = $_SERVER['REQUEST_METHOD'] === 'POST' ? p('bio') : html_entity_decode($u['bio'] ?? '');

function save_user(array $new): void {
    $users = jread('users.json');
    foreach ($users as $i => $x) if ($x['id'] === $new['id']) $users[$i] = $new;
    jwrite('users.json', $users);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('error', 'Sesi formulir tidak valid. Coba lagi.'); redirect('profile.php'); }

    if (p('action') === 'profile') {
        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) $errors['name'] = 'Nama harus 2 sampai 50 karakter.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
        else { $o = find_user('email', $email); if ($o && $o['id'] !== $u['id']) $errors['email'] = 'Email itu sudah dipakai akun lain.'; }
        if (mb_strlen($bio) > 160) $errors['bio'] = 'Bio maksimal 160 karakter.';
        if (!$errors) {
            save_user(array_merge($u, ['name' => clean($name), 'email' => strtolower($email), 'bio' => clean($bio)]));
            flash('success', 'Profil berhasil diperbarui.'); redirect('profile.php');
        }
    }

    if (p('action') === 'password') {
        $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $conf = $_POST['confirm'] ?? '';
        if (!password_verify((string)$cur, $u['password'])) $pErrors['current'] = 'Password saat ini salah.';
        if (strlen((string)$new) < 8) $pErrors['new'] = 'Password baru minimal 8 karakter.';
        elseif ($new !== $conf) $pErrors['confirm'] = 'Konfirmasi password tidak sama.';
        if (!$pErrors) {
            save_user(array_merge($u, ['password' => password_hash($new, PASSWORD_DEFAULT)]));
            flash('success', 'Password berhasil diganti.'); redirect('profile.php');
        }
    }
}
$mine = array_filter(jread('entries.json'), fn($x) => $x['uid'] === $u['id']);
$since = MON[(int)date('n', strtotime($u['created']))] . ' ' . date('Y', strtotime($u['created']));
page_start('Profil'); ?>
<div class="narrow">
  <h1>Profil</h1>
  <div class="card profile-head">
    <span class="avatar lg"><?= h(mb_strtoupper(mb_substr(html_entity_decode($u['name']), 0, 1))) ?></span>
    <div><h2><?= h($u['name']) ?></h2><p class="muted"><?= h($u['email']) ?></p></div>
    <dl><div><dt>Catatan</dt><dd><?= count($mine) ?></dd></div><div><dt>Bergabung</dt><dd><?= $since ?></dd></div></dl>
  </div>

  <form method="post" class="card form-card" novalidate>
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <h2>Data diri</h2>
    <div class="field"><label for="name">Nama</label><input id="name" name="name" value="<?= h($name) ?>" required>
      <?php if (isset($errors['name'])): ?><small class="err-t"><?= h($errors['name']) ?></small><?php endif; ?></div>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= h($email) ?>" required>
      <?php if (isset($errors['email'])): ?><small class="err-t"><?= h($errors['email']) ?></small><?php endif; ?></div>
    <div class="field"><label for="bio">Bio singkat</label><textarea id="bio" name="bio" rows="3" maxlength="160"><?= h($bio) ?></textarea>
      <?php if (isset($errors['bio'])): ?><small class="err-t"><?= h($errors['bio']) ?></small><?php endif; ?></div>
    <button class="btn" type="submit">Simpan profil</button>
  </form>

  <form method="post" class="card form-card" novalidate>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <h2>Ganti password</h2>
    <div class="field"><label for="current">Password saat ini</label><input id="current" type="password" name="current" autocomplete="current-password">
      <?php if (isset($pErrors['current'])): ?><small class="err-t"><?= h($pErrors['current']) ?></small><?php endif; ?></div>
    <div class="field"><label for="new">Password baru</label><input id="new" type="password" name="new" autocomplete="new-password">
      <?php if (isset($pErrors['new'])): ?><small class="err-t"><?= h($pErrors['new']) ?></small><?php endif; ?></div>
    <div class="field"><label for="confirm">Ulangi password baru</label><input id="confirm" type="password" name="confirm" autocomplete="new-password">
      <?php if (isset($pErrors['confirm'])): ?><small class="err-t"><?= h($pErrors['confirm']) ?></small><?php endif; ?></div>
    <button class="btn" type="submit">Ganti password</button>
  </form>
</div>
<?php page_end();
