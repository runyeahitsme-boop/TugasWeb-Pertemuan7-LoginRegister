<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();
$u = current_user();

$id = g('id'); $entries = jread('entries.json'); $idx = null;
if ($id !== '') {
    foreach ($entries as $i => $x) if ($x['id'] === $id && $x['uid'] === $u['id']) $idx = $i;
    if ($idx === null) { flash('error', 'Catatan tidak ditemukan.'); redirect('dashboard.php'); }
}
$edit = $idx !== null ? $entries[$idx] : null;
$def = g('date'); if (!valid_date($def)) $def = date('Y-m-d');
$v = $edit ?? ['title' => '', 'body' => '', 'mood' => '', 'tags' => [], 'date' => $def];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { flash('error', 'Sesi formulir tidak valid. Coba lagi.'); redirect('dashboard.php'); }

    if (p('action') === 'delete' && $edit) {
        unset($entries[$idx]); jwrite('entries.json', $entries);
        flash('success', 'Catatan dihapus.'); redirect('dashboard.php');
    }

    $v = [
        'title' => p('title'), 'body' => p('body'), 'mood' => p('mood'), 'date' => p('date'),
        'tags' => array_values(array_intersect(array_keys(TAGS), is_array($_POST['tags'] ?? null) ? $_POST['tags'] : [])),
    ];
    if (!isset(MOODS[$v['mood']])) $errors[] = 'Pilih suasana hatimu dulu.';
    if (!valid_date($v['date']) || $v['date'] > date('Y-m-d')) $errors[] = 'Tanggal tidak valid dan tidak boleh di masa depan.';
    if (mb_strlen($v['title']) < 2 || mb_strlen($v['title']) > 80) $errors[] = 'Judul harus 2 sampai 80 karakter.';
    if (mb_strlen($v['body']) < 5 || mb_strlen($v['body']) > 3000) $errors[] = 'Isi catatan harus 5 sampai 3000 karakter.';

    if (!$errors) {
        $row = [
            'id' => $edit['id'] ?? bin2hex(random_bytes(6)), 'uid' => $u['id'],
            'title' => clean($v['title']), 'body' => clean($v['body']), 'mood' => $v['mood'],
            'tags' => $v['tags'], 'date' => $v['date'], 'created' => $edit['created'] ?? date('c'), 'updated' => date('c'),
        ];
        if ($edit) $entries[$idx] = $row; else $entries[] = $row;
        jwrite('entries.json', $entries);
        flash('success', $edit ? 'Perubahan catatan disimpan.' : 'Catatan baru disimpan.');
        redirect('dashboard.php?d=' . $row['date'] . '#daftar');
    }
}
page_start($edit ? 'Ubah catatan' : 'Catatan baru'); ?>
<div class="narrow">
  <a class="link back" href="dashboard.php"><?= icon('left', 16) ?> Kembali</a>
  <h1><?= $edit ? 'Ubah catatan' : 'Bagaimana harimu?' ?></h1>
  <?php foreach ($errors as $e): ?><div class="alert err"><?= icon('alert') ?><span><?= h($e) ?></span></div><?php endforeach; ?>

  <form method="post" class="card form-card" novalidate>
    <?= csrf_field() ?>
    <fieldset class="moods"><legend>Suasana hati</legend>
      <?php foreach (MOODS as $k => [$lbl, $col]): ?>
        <label class="mood" style="--c:<?= $col ?>">
          <input type="radio" name="mood" value="<?= $k ?>" <?= $v['mood'] === $k ? 'checked' : '' ?>>
          <span><?= mood_icon($k, 34) ?><em><?= $lbl ?></em></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="field"><label for="date">Tanggal</label>
      <input id="date" type="date" name="date" max="<?= date('Y-m-d') ?>" value="<?= h($v['date']) ?>" required></div>
    <div class="field"><label for="title">Judul</label>
      <input id="title" name="title" maxlength="80" value="<?= h($v['title']) ?>" placeholder="Misalnya: Wawancara yang menyenangkan" required></div>
    <div class="field"><label for="body">Ceritakan harimu</label>
      <textarea id="body" name="body" rows="8" maxlength="3000" required><?= h($v['body']) ?></textarea>
      <small class="muted right" id="count"></small></div>

    <fieldset class="tagpick"><legend>Tentang apa?</legend>
      <?php foreach (TAGS as $k => $lbl): ?>
        <label class="pill"><input type="checkbox" name="tags[]" value="<?= $k ?>" <?= in_array($k, $v['tags'], true) ? 'checked' : '' ?>><span><?= $lbl ?></span></label>
      <?php endforeach; ?>
    </fieldset>

    <div class="actions">
      <button class="btn" type="submit">Simpan catatan <?= icon('arrow', 18) ?></button>
    </div>
  </form>

  <?php if ($edit): ?>
  <form method="post" class="danger-zone" onsubmit="return confirm('Hapus catatan ini secara permanen?')">
    <?= csrf_field() ?><input type="hidden" name="action" value="delete">
    <button class="btn ghost danger" type="submit"><?= icon('trash', 18) ?> Hapus catatan</button>
  </form>
  <?php endif; ?>
</div>
<?php page_end();
