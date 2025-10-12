<?php
// C:\wamp64\www\mediatheque_paris_grp3\views\media\medias.php

$title = isset($title) ? (string)$title : 'Médias';

$count_films       = (int)($count_films       ?? 0);
$count_livres      = (int)($count_livres      ?? 0);
$count_jeux        = (int)($count_jeux        ?? 0);
$loans_in_progress = (int)($loans_in_progress ?? 0);
$loans_late        = (int)($loans_late        ?? 0);

$medias       = is_array($medias ?? null) ? $medias : [];
$current_page = (int)($current_page ?? 1);
$pages        = (int)($pages ?? 1);
$total        = (int)($total ?? count($medias));

$sort     = strtolower((string)($sort ?? ($_GET['sort'] ?? 'created_at')));
$dir      = strtolower((string)($dir  ?? ($_GET['dir']  ?? 'desc')));
$per_page = (int)($per_page ?? (int)($_GET['per_page'] ?? 10));

$f        = $filters ?? ['q' => '', 'type' => ''];
$q        = (string)($f['q']    ?? ($_GET['q']    ?? ''));
$type     = (string)($f['type'] ?? ($_GET['type'] ?? ''));

// helpers locaux
$keep = function (array $extra = []) use ($q, $type, $sort, $dir, $per_page) {
  $base = ['q' => $q, 'type' => $type, 'sort' => $sort, 'dir' => $dir, 'per_page' => $per_page];
  return '?' . http_build_query(array_merge($base, $extra));
};
$make_sort = function (string $col) use ($sort, $dir, $per_page, $q, $type) {
  $next = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
  $qs = http_build_query(['q' => $q, 'type' => $type, 'sort' => $col, 'dir' => $next, 'per_page' => $per_page, 'page' => 1]);
  return url('media/medias') . '?' . $qs;
};
$arrow = function (string $col) use ($sort, $dir) {
  if ($sort !== $col) return '';
  return $dir === 'asc' ? ' ▲' : ' ▼';
};
$pagelink = function (int $p) use ($q, $type, $sort, $dir, $per_page) {
  $qs = http_build_query(['q' => $q, 'type' => $type, 'sort' => $sort, 'dir' => $dir, 'per_page' => $per_page, 'page' => $p]);
  return url('media/medias') . '?' . $qs;
};
$chip = function (string $label, $val) {
  $v = is_null($val) ? '' : (string)$val;
  if ($v === '' || $v === '0') return '';
  return '<span style="display:inline-block;border:1px solid #000000ff;color:#334155;padding:0.01px 8px;margin:2px;border-radius:9999px;font-size:.78rem;line-height:1;">'
    . e($label . ': ' . $v, false)
    . '</span>';
};
?>
<div class="page-header">
  <div class="container">
    <h1><?php e($title); ?></h1>
  </div>
</div>

<section class="content">
  <div class="container">
    <?php if (function_exists('flash_messages')) {
      flash_messages();
    } ?>

    <!-- KPI  -->
    <section class="admin-kpi-grid" style="margin:12px 0;">
      <article class="admin-kpi-card">
        <div class="admin-kpi-icon"><i class="fas fa-film"></i></div>
        <div>
          <div class="admin-kpi-label">Films</div>
          <div class="admin-kpi-value"><?= $count_films ?></div>
        </div>
      </article>
      <article class="admin-kpi-card">
        <div class="admin-kpi-icon"><i class="fas fa-book"></i></div>
        <div>
          <div class="admin-kpi-label">Livres</div>
          <div class="admin-kpi-value"><?= $count_livres ?></div>
        </div>
      </article>
      <article class="admin-kpi-card">
        <div class="admin-kpi-icon"><i class="fas fa-gamepad"></i></div>
        <div>
          <div class="admin-kpi-label">Jeux</div>
          <div class="admin-kpi-value"><?= $count_jeux ?></div>
        </div>
      </article>
      <article class="admin-kpi-card admin-kpi-accent">
        <div class="admin-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
        <div>
          <div class="admin-kpi-label">Emprunts en cours</div>
          <div class="admin-kpi-value"><?= $loans_in_progress ?></div>
        </div>
      </article>
      <article class="admin-kpi-card admin-kpi-warn">
        <div class="admin-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
          <div class="admin-kpi-label">Emprunts en retard</div>
          <div class="admin-kpi-value"><?= $loans_late ?></div>
        </div>
      </article>
    </section>

    <div class="content-main">
      <div class="center-btn" style="gap:8px;">
        <a class="button" href="<?= url('home/users') ?>">Utilisateurs</a>
        <a class="button" id="purple" href="<?= url('loan/history') ?>">Emprunts</a>
      </div>

      <!-- Barre de recherche -->
      <form method="get" action="<?= url('media/medias') ?>" class="media-filter"
        style="background:#fff;border:1px solid var(--border-color);padding:12px;border-radius:12px;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));margin:12px 0;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div class="form-group" style="min-width:260px;flex:1;">
            <label for="q">Recherche</label>
            <input type="text" id="q" name="q" value="<?php e((string)$q); ?>" placeholder="Titre, auteur, réalisateur, éditeur, ISBN…">
          </div>

          <div class="form-group">
            <label for="type">Type</label>
            <select id="type" name="type">
              <option value="">Tous</option>
              <option value="livre" <?= $type === 'livre' ? 'selected' : ''; ?>>Livre</option>
              <option value="film" <?= $type === 'film'  ? 'selected' : ''; ?>>Film</option>
              <option value="jeu" <?= $type === 'jeu'   ? 'selected' : ''; ?>>Jeu</option>
            </select>
          </div>

          <input type="hidden" name="sort" value="<?php e((string)$sort); ?>">
          <input type="hidden" name="dir" value="<?php e((string)$dir);  ?>">
          <input type="hidden" name="per_page" value="<?= (int)$per_page ?>">

          <div class="form-group">
            <button class="btn btn-primary btn-xxs" type="submit">Filtrer</button>
            <a class="btn btn-secondary btn-xxs" href="<?= url('media/medias') ?>">Réinitialiser</a>
          </div>
        </div>
      </form>

      <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin:6px 0 10px;">
        <h2 style="margin:0;">La liste des médias</h2>
        <a class="button" href="<?= url('media/index#ancre') ?>">
          <img class="plus" src="<?= url('/assets/images/plus.png') ?>" alt="ajouter">Ajouter un média
        </a>
      </div>

      <!-- TABLEAU  -->
      <div class="admin-table-wrap" style="background:#fff;border:1px solid var(--border-color);border-radius:14px;overflow:hidden;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));">
        <table class="admin-table" style="width:100%;border-collapse:separate;border-spacing:0;">
          <thead style="background:#f8fafc;">
            <tr>
              <th style="white-space:nowrap;"><a href="<?= $make_sort('id') ?>">ID<?= $arrow('id') ?></a></th>
              <th style="white-space:nowrap;"><a href="<?= $make_sort('type') ?>">Type<?= $arrow('type') ?></a></th>
              <th style="white-space:nowrap;"><a href="<?= $make_sort('title') ?>">Titre<?= $arrow('title') ?></a></th>
              <th style="white-space:nowrap;"><a href="<?= $make_sort('genre') ?>">Genre<?= $arrow('genre') ?></a></th>
              <th style="min-width:360px;">Infos</th>
              <th style="white-space:nowrap;"><a href="<?= $make_sort('stock') ?>">Stock<?= $arrow('stock') ?></a></th>
              <th>Modifier</th>
              <th>Supprimer</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($medias)): ?>
              <tr>
                <td colspan="8" style="text-align:center;padding:16px;">Aucun média.</td>
              </tr>
              <?php else: foreach ($medias as $m): ?>
                <tr style="border-top:1px solid var(--border-color);vertical-align:top;">
                  <td><?= (int)($m['id'] ?? 0) ?></td>
                  <td><?php e(isset($m['type'])  ? (string)$m['type']  : ''); ?></td>
                  <td>
                    <div style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php e(isset($m['title']) ? (string)$m['title'] : ''); ?></div>
                    <?php
                    $desc = isset($m['description']) ? (string)$m['description'] : '';
                    if ($desc !== '') {
                      echo '<div style="color:#6b7280;font-size:.85rem;max-width:420px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">'
                        . e($desc, false)
                        . '</div>';
                    }
                    ?>
                  </td>
                  <td><?php e(isset($m['genre']) ? (string)$m['genre'] : ''); ?></td>
                  <td>
                    <?php
                    echo $chip('Créé le',          isset($m['created_at']) ? (string)$m['created_at'] : '');
                    echo $chip('Auteur',           isset($m['author']) ? (string)$m['author'] : '');
                    echo $chip('ISBN',             isset($m['isbn']) ? (string)$m['isbn'] : '');
                    echo $chip('Pages',            $m['pages'] ?? '');
                    echo $chip('Publ.',            $m['publication_year'] ?? '');
                    echo $chip('Réalisateur',      isset($m['producer']) ? (string)$m['producer'] : '');
                    echo $chip('Durée',            $m['duration_min'] ?? '');
                    echo $chip('Classif.',         isset($m['classification']) ? (string)$m['classification'] : '');
                    echo $chip('Sortie',           $m['release_year'] ?? '');
                    echo $chip('Éditeur',          isset($m['editor']) ? (string)$m['editor'] : '');
                    echo $chip('Plateforme',       isset($m['plateforme']) ? (string)$m['plateforme'] : '');
                    echo $chip('Âge min',          $m['age_min'] ?? '');
                    ?>
                  </td>

                  <td><?= (int)($m['stock'] ?? 0) ?></td>
                  <td class="image">
                    <a href="<?= url('media/detail') ?>/<?= (int)($m['id'] ?? 0) ?>"
                      class="admin-iconbtn" title="Éditer"><i class="fas fa-pen"></i>
                    </a>
                  </td>
                  <td class="image">
                    <a href="<?= url('media/delete_media') ?>/<?= (int)($m['id'] ?? 0) ?>"
                      class="admin-iconbtn admin-danger" title="Supprimer"><i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
            <?php endforeach;
            endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <?php
        $p = $current_page;
        $win = 2;
        $start = max(1, $p - $win);
        $end = min($pages, $p + $win);
        if ($start > 1)    $start = max(1, min($start, $pages - ($win * 2)));
        if ($end < $pages) $end   = min($pages, max($end, 1 + ($win * 2)));
        ?>
        <nav class="pagination" style="margin-top:16px;">
          <?php if ($p > 1): ?>
            <a class="edge" href="<?= $pagelink(1) ?>">Première</a>
            <a class="edge" href="<?= $pagelink($p - 1) ?>">Précédente</a>
          <?php endif; ?>

          <?php if ($start > 1): ?><span class="ellipsis">…</span><?php endif; ?>
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $p): ?>
              <span class="page-btn is-current"><?= $i ?></span>
            <?php else: ?>
              <a class="page-btn" href="<?= $pagelink($i) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($end < $pages): ?><span class="ellipsis">…</span><?php endif; ?>

          <?php if ($p < $pages): ?>
            <a class="edge" href="<?= $pagelink($p + 1) ?>">Suivante</a>
            <a class="edge" href="<?= $pagelink($pages) ?>">Dernière</a>
          <?php endif; ?>

          <span style="margin-left:8px;color:#6b7280;font-size:.9rem;">Page <?= $p ?>/<?= $pages ?></span>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</section>