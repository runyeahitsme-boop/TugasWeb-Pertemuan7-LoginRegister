<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require_login();
$u = current_user();

$sel = g('d'); if ($sel !== '' && !valid_date($sel)) $sel = '';
$m = g('m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) $m = $sel ? substr($sel, 0, 7) : date('Y-m');

$all = array_values(array_filter(jread('entries.json'), fn($x) => $x['uid'] === $u['id']));
usort($all, fn($a, $b) => [$b['date'], $b['created']] <=> [$a['date'], $a['created']]);

$moodByDay = [];
foreach ($all as $x) if (str_starts_with($x['date'], $m)) $moodByDay[$x['date']] ??= $x['mood'];

$list = $sel ? array_values(array_filter($all, fn($x) => $x['date'] === $sel)) : $all;
$perMonth = array_count_values(array_map(fn($x) => substr($x['date'], 0, 7), $list));

$first = strtotime("$m-01"); $days = (int)date('t', $first); $off = (int)date('N', $first) - 1;
$prev = date('Y-m', strtotime("$m-01 -1 month")); $next = date('Y-m', strtotime("$m-01 +1 month"));
$hour = (int)date('G');
$greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
$today = date('Y-m-d');

page_start('Beranda'); ?>
<section class="hello">
  <h1><?= $greet ?>, <?= h(explode(' ', html_entity_decode($u['name']))[0]) ?></h1>
  <p class="muted">Satu hari buruk tidak harus membuat seluruh hidupmu terasa buruk.</p>
</section>

<div class="dash">
  <section class="card cal" id="kalender" aria-label="Kalender suasana hati">
    <div class="cal-head">
      <a class="icon-btn" href="?m=<?= $prev ?>" aria-label="Bulan sebelumnya"><?= icon('left') ?></a>
      <h2><?= MON[(int)date('n', $first)] . ' ' . date('Y', $first) ?></h2>
      <a class="icon-btn" href="?m=<?= $next ?>" aria-label="Bulan berikutnya"><?= icon('right') ?></a>
    </div>
    <div class="cal-grid">
      <?php foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d): ?><b class="dow"><?= $d ?></b><?php endforeach; ?>
      <?php for ($i = 0; $i < $off; $i++): ?><span></span><?php endfor; ?>
      <?php for ($d = 1; $d <= $days; $d++):
          $ds = sprintf('%s-%02d', $m, $d); $mk = $moodByDay[$ds] ?? null;
          $cls = trim(($mk ? 'has ' : '') . ($ds === $today ? 'today ' : '') . ($ds === $sel ? 'sel' : '')); ?>
        <a class="day <?= $cls ?>" href="?d=<?= $ds ?>#daftar" <?= $mk ? 'style="--c:' . MOODS[$mk][1] . '" title="' . MOODS[$mk][0] . '"' : '' ?>>
          <?= $mk ? mood_icon($mk, 24) : $d ?>
        </a>
      <?php endfor; ?>
    </div>
    <?php if ($sel): ?><a class="link" href="dashboard.php?m=<?= $m ?>">Tampilkan semua catatan</a><?php endif; ?>
  </section>

  <section id="daftar" aria-label="Daftar catatan">
    <div class="list-head">
      <h2><?= $sel ? date('j', strtotime($sel)) . ' ' . MON[(int)date('n', strtotime($sel))] . ' ' . date('Y', strtotime($sel)) : 'Catatanmu' ?></h2>
      <a class="btn sm" href="entry.php<?= $sel ? '?date=' . $sel : '' ?>"><?= icon('plus', 16) ?> Tulis</a>
    </div>
    <?php if (!$list): ?>
      <div class="empty card">
        <?= mood_icon('puas', 44) ?>
        <h3><?= $sel ? 'Belum ada catatan di hari ini' : 'Belum ada catatan' ?></h3>
        <p class="muted">Ceritakan bagaimana harimu dan apa yang terjadi.</p>
        <a class="btn" href="entry.php<?= $sel ? '?date=' . $sel : '' ?>">Tulis catatan pertama</a>
      </div>
    <?php else: $cur = ''; foreach ($list as $x):
        $ym = substr($x['date'], 0, 7); $ts = strtotime($x['date']);
        if ($ym !== $cur && !$sel): $cur = $ym; ?>
          <div class="month-label"><span><?= MON[(int)date('n', $ts)] . ' ' . date('Y', $ts) ?></span><em><?= $perMonth[$ym] ?> catatan</em></div>
        <?php endif; ?>
      <a class="memo" style="--c:<?= MOODS[$x['mood']][1] ?>" href="entry.php?id=<?= h($x['id']) ?>">
        <div class="memo-date"><b><?= date('d', $ts) ?></b><span><?= mb_substr(MON[(int)date('n', $ts)], 0, 3) ?></span></div>
        <div class="memo-body">
          <h3><?= h($x['title']) ?></h3>
          <p><?= h(mb_strimwidth(html_entity_decode($x['body']), 0, 90, '...')) ?></p>
          <?php if (!empty($x['tags'])): ?><div class="tags"><?php foreach ($x['tags'] as $t) if (isset(TAGS[$t])): ?><span><?= TAGS[$t] ?></span><?php endif; ?></div><?php endif; ?>
        </div>
        <span class="memo-mood" title="<?= MOODS[$x['mood']][0] ?>"><?= mood_icon($x['mood'], 32) ?></span>
      </a>
    <?php endforeach; endif; ?>
  </section>
</div>
<?php page_end();
