<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
guest_only();

$error = ''; $email = p('email');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    if (!csrf_ok()) $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    elseif ($email === '' || $pass === '') $error = 'Email dan password wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    else {
        $u = find_user('email', $email);
        if ($u && is_string($pass) && password_verify($pass, $u['password'])) {
            login_user($u);
            if (p('remember') === '1') remember_set($u['id']);
            flash('success', 'Selamat datang kembali, ' . $u['name'] . '.');
            redirect('dashboard.php');
        }
        $error = 'Email atau password salah.';
    }
}
page_start('Masuk', 'auth-page'); ?>
<section class="auth">
  <?php auth_art(); ?>
  <div class="auth-card card">
    <h1>Masuk</h1>
    <p class="muted">Lanjutkan catatan harianmu.</p>
    <?php if ($error): ?><div class="alert err"><?= icon('alert') ?><span><?= h($error) ?></span></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="field"><label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= h($email) ?>" autocomplete="email" required></div>
      <div class="field"><label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required></div>
      <label class="check"><input type="checkbox" name="remember" value="1"><span>Ingat saya selama 30 hari</span></label>
      <button class="btn" type="submit">Masuk <?= icon('arrow', 18) ?></button>
    </form>
    <p class="switch">Belum punya akun? <a href="register.php">Daftar</a></p>
  </div>
</section>
<?php page_end();
