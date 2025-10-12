<?php

$title = isset($title) ? (string)$title : 'Utilisateurs';

// KPI
$count_films       = (int)($count_films       ?? 0);
$count_livres      = (int)($count_livres      ?? 0);
$count_jeux        = (int)($count_jeux        ?? 0);
$loans_in_progress = (int)($loans_in_progress ?? 0);
$loans_late        = (int)($loans_late        ?? 0);

// Données liste
$users        = is_array($users ?? null) ? $users : [];
$current_page = (int)($current_page ?? (int)($_GET['page'] ?? 1));
$pages        = (int)($pages ?? 1);
$total        = (int)($total ?? count($users));

// Trie avec la pagination
$sort     = strtolower((string)($sort ?? ($_GET['sort'] ?? 'created_at')));
$dir      = strtolower((string)($dir  ?? ($_GET['dir']  ?? 'desc')));
$per_page = (int)($per_page ?? (int)($_GET['per_page'] ?? 10));

// Filtres
$f = $filters ?? ['q' => ''];
$q = (string)($f['q'] ?? ($_GET['q'] ?? ''));

// Helpers
$keep = function (array $extra = []) use ($q, $sort, $dir, $per_page) {
  $base = ['q' => $q, 'sort' => $sort, 'dir' => $dir, 'per_page' => $per_page];
  return '?' . http_build_query(array_merge($base, $extra));
};
$make_sort = function (string $col) use ($sort, $dir, $per_page, $q) {
  $next = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
  $qs = http_build_query(['q' => $q, 'sort' => $col, 'dir' => $next, 'per_page' => $per_page, 'page' => 1]);
  return url('home/users') . '?' . $qs;
};
$arrow = function (string $col) use ($sort, $dir) {
  if ($sort !== $col) return '';
  return $dir === 'asc' ? ' ▲' : ' ▼';
};
$pagelink = function (int $p) use ($q, $sort, $dir, $per_page) {
  $qs = http_build_query(['q' => $q, 'sort' => $sort, 'dir' => $dir, 'per_page' => $per_page, 'page' => $p]);
  return url('home/users') . '?' . $qs;
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

    <!--  KPI   -->
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
      <!-- Boutons raccourcis -->
      <div class="center-btn" style="gap:8px;">
        <a class="button" id="red" href="<?= url('media/medias') ?>">Médias</a>
        <a class="button" id="purple" href="<?= url('loan/history') ?>">Emprunts</a>
      </div>

      <!-- Barre de recherche -->
      <form method="get" action="<?= url('home/users') ?>" class="media-filter"
        style="background:#fff;border:1px solid var(--border-color);padding:12px;border-radius:12px;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));margin:12px 0;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div class="form-group" style="min-width:260px;flex:1;">
            <label for="q">Recherche</label>
            <input type="text" id="q" name="q" value="<?php e((string)$q); ?>" placeholder="Nom, prénom, email…">
          </div>

          <input type="hidden" name="sort" value="<?php e((string)$sort); ?>">
          <input type="hidden" name="dir" value="<?php e((string)$dir);  ?>">
          <input type="hidden" name="per_page" value="<?= (int)$per_page ?>">

          <div class="form-group">
            <button class="btn btn-primary btn-xxs" type="submit">Filtrer</button>
            <a class="btn btn-secondary btn-xxs" href="<?= url('home/users') ?>">Réinitialiser</a>
          </div>
        </div>
      </form>

      <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin:6px 0 10px;">
        <h2 style="margin:0;">La liste des utilisateurs</h2>
      </div>

      <!-- table -->
      <div class="admin-table-wrap" style="background:#fff;border:1px solid var(--border-color);border-radius:14px;overflow:hidden;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));">
        <table class="admin-table" style="width:100%;border-collapse:separate;border-spacing:0;">
          <thead style="background:#f8fafc;">
            <tr>
              <th><a href="<?= $make_sort('id') ?>">ID<?= $arrow('id') ?></a></th>
              <th><a href="<?= $make_sort('last_name') ?>">Nom<?= $arrow('last_name') ?></a></th>
              <th><a href="<?= $make_sort('first_name') ?>">Prénom<?= $arrow('first_name') ?></a></th>
              <th><a href="<?= $make_sort('email') ?>">Email<?= $arrow('email') ?></a></th>
              <th><a href="<?= $make_sort('role') ?>">Rôle<?= $arrow('role') ?></a></th>
              <th><a href="<?= $make_sort('created_at') ?>">Créé le<?= $arrow('created_at') ?></a></th>
              <th>Modifier</th>
              <th>Supprimer</th>
              <th>Statistiques</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="8" style="text-align:center;padding:16px;">Aucun utilisateur.</td>
              </tr>
              <?php else: foreach ($users as $u): ?>
                <tr style="border-top:1px solid var(--border-color);">
                  <td><?= (int)($u['id'] ?? 0) ?></td>
                  <td><?php e(isset($u['last_name'])  ? (string)$u['last_name']  : ''); ?></td>
                  <td><?php e(isset($u['first_name']) ? (string)$u['first_name'] : ''); ?></td>
                  <td><?php e(isset($u['email'])      ? (string)$u['email']      : ''); ?></td>
                  <td><?php e(isset($u['role'])      ? (string)$u['role']      : ''); ?></td>
                  <td><?php e(isset($u['created_at']) ? (string)$u['created_at'] : ''); ?></td>
                  <td class="image">
                    <a href="<?= url('home/profile_admin') ?>?id=<?= (int)($u['id'] ?? 0) ?>"
                      class="admin-iconbtn" title="Éditer"><i class="fas fa-pen"></i>
                    </a>
                  </td>
                  <td class="image">
                    <a href="<?= url('home/confirm_delete_user') ?>?id=<?= (int)($u['id'] ?? 0) ?>"
                      class="admin-iconbtn admin-danger" title="Supprimer"><i class="fas fa-trash"></i>
                    </a>
                  </td>
                  <td class="image">
                    <a href="<?= url('home/stats') ?>?id=<?= (int)($u['id'] ?? 0) ?>"
                      class="admin-iconbtn" title="Stats"><i class="fas fa-chart-pie"></i>
                    </a>
                  </td>
                </tr>
            <?php endforeach;
            endif; ?>
          </tbody>
        </table>
      </div>

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
