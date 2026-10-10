<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
guest_only();

$errors = []; $name = p('name'); $email = p('email');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? ''; $conf = $_POST['confirm'] ?? '';
    if (!csrf_ok()) $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    if (mb_strlen($name) < 2 || mb_strlen($name) > 50) $errors['name'] = 'Nama harus 2 sampai 50 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
    elseif (find_user('email', $email)) $errors['email'] = 'Email ini sudah terdaftar. Silakan masuk.';
    if (!is_string($pass) || strlen($pass) < 8) $errors['password'] = 'Password minimal 8 karakter.';
    elseif ($pass !== $conf) $errors['confirm'] = 'Konfirmasi password tidak sama.';

    if (!$errors) {
        $users = jread('users.json');
        $users[] = [
            'id' => bin2hex(random_bytes(6)), 'name' => clean($name), 'email' => strtolower($email),
            'bio' => '', 'password' => password_hash($pass, PASSWORD_DEFAULT), 'created' => date('c'),
        ];
        jwrite('users.json', $users);
        flash('success', 'Akun berhasil dibuat. Silakan masuk.');
        redirect('login.php');
    }
}
page_start('Daftar', 'auth-page'); ?>
<section class="auth">
  <?php auth_art(); ?>
  <div class="auth-card card">
    <h1>Buat akun</h1>
    <p class="muted">Mulai tulis diary pertamamu hari ini.</p>
    <?php foreach ($errors as $k => $e) if (is_int($k)): ?><div class="alert err"><?= icon('alert') ?><span><?= h($e) ?></span></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="name">Nama</label>
        <input id="name" name="name" value="<?= h($name) ?>" autocomplete="name" required>
        <?php if (isset($errors['name'])): ?><small class="err-t"><?= h($errors['name']) ?></small><?php endif; ?></div>
      <div class="field"><label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= h($email) ?>" autocomplete="email" required>
        <?php if (isset($errors['email'])): ?><small class="err-t"><?= h($errors['email']) ?></small><?php endif; ?></div>
      <div class="field"><label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="new-password" required>
        <?php if (isset($errors['password'])): ?><small class="err-t"><?= h($errors['password']) ?></small><?php endif; ?></div>
      <div class="field"><label for="confirm">Ulangi password</label>
        <input id="confirm" type="password" name="confirm" autocomplete="new-password" required>
        <?php if (isset($errors['confirm'])): ?><small class="err-t"><?= h($errors['confirm']) ?></small><?php endif; ?></div>
      <button class="btn" type="submit">Daftar <?= icon('arrow', 18) ?></button>
    </form>
    <p class="switch">Sudah punya akun? <a href="login.php">Masuk</a></p>
  </div>
</section>
<?php page_end();
