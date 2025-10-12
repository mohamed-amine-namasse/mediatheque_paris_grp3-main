<?php

$title        = isset($title) ? (string)$title : 'Historique des emprunts';
$loans        = is_array($loans ?? null) ? $loans : [];
$is_admin     = !empty($is_admin);

// KPI 
$count_films       = (int)($count_films       ?? 0);
$count_livres      = (int)($count_livres      ?? 0);
$count_jeux        = (int)($count_jeux        ?? 0);
$loans_in_progress = (int)($loans_in_progress ?? 0);
$loans_late        = (int)($loans_late        ?? 0);

// Pagination /paramètres
$current_page = (int)($current_page ?? (int)($_GET['page'] ?? 1));
$pages        = (int)($pages ?? 1);
$total        = (int)($total ?? count($loans));
$per_page     = (int)($per_page ?? (int)($_GET['per_page'] ?? 10));

// Filtres
$f      = $filters ?? ['q'=>'','status'=>''];
$q      = (string)($f['q']      ?? ($_GET['q']      ?? ''));
$status = (string)($f['status'] ?? ($_GET['status'] ?? ''));

// Helpers
$keep = function(array $extra = []) use ($q,$status,$per_page) {
  $base = ['q'=>$q,'status'=>$status,'per_page'=>$per_page];
  return '?' . http_build_query(array_merge($base, $extra));
};
$pagelink = function(int $p) use ($q,$status,$per_page) {
  $qs = http_build_query(['q'=>$q,'status'=>$status,'per_page'=>$per_page,'page'=>$p]);
  return url('loan/history') . '?' . $qs;
};
?>
<div class="page-header">
  <div class="container">
    <h1><?php e($title); ?></h1>
  </div>
</div>

<section class="content">
  <div class="container">
    <?php if (function_exists('flash_messages')) { flash_messages(); } ?>
    <?php if (isset($_SESSION['user_email']) && $_SESSION['role'] === 'admin'): ?>
    <!--KPI -->
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
          <div class="admin-kpi-label">En cours</div>
          <div class="admin-kpi-value"><?= $loans_in_progress ?></div>
        </div>
      </article>
      <article class="admin-kpi-card admin-kpi-warn">
        <div class="admin-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
          <div class="admin-kpi-label">En retard</div>
          <div class="admin-kpi-value"><?= $loans_late ?></div>
        </div>
      </article>
    </section>
     
    <div class="content-main">
      <!-- Boutons raccourcis -->
      <div class="center-btn" style="gap:8px;">
        <a class="button" href="<?= url('home/users') ?>">Utilisateurs</a>
        <a class="button" id="red" href="<?= url('media/medias') ?>">Médias</a>
      </div>
       <?php endif; ?>
    <!-- Barre de recherche et filtres -->
    <form method="get" action="<?= url('loan/history') ?>" class="media-filter"
          style="background:#fff;border:1px solid var(--border-color);padding:12px;border-radius:12px;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));margin:12px 12px 0;">
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="min-width:260px;flex:1;">
          <label for="q">Recherche</label>
          <input type="text" id="q" name="q" value="<?php e((string)$q); ?>"
                 placeholder="<?= $is_admin ? 'Nom, prénom, ID emprunt, ID média, titre…' : 'ID emprunt, ID média, titre…' ?>">
        </div>

        <div class="form-group">
          <label for="status">Statut</label>
          <select id="status" name="status">
            <option value=""          <?= $status===''?'selected':''; ?>>Tous</option>
            <option value="En cours"  <?= $status==='En cours'?'selected':''; ?>>En cours</option>
            <option value="Rendu"     <?= $status==='Rendu'?'selected':''; ?>>Rendu</option>
            <option value="En retard" <?= $status==='En retard'?'selected':''; ?>>En retard</option>
          </select>
        </div>

        <input type="hidden" name="per_page" value="<?= (int)$per_page ?>">

        <div class="form-group">
          <button class="btn btn-primary btn-xxs" type="submit">Filtrer</button>
          <a class="btn btn-secondary btn-xxs btn-ren" href="<?= url('loan/history') ?>">Réinitialiser</a>
        </div>
      </div>
    </form>

    <?php if (empty($loans)): ?>
      <p style="margin:12px;">Aucun emprunt.</p>
      <p style="margin:12px;">
        <a class="btn btn-secondary" href="<?= url('media/index'); ?>">
          Retour au catalogue
        </a>
      </p>
    <?php else: ?>

      <div style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin:12px 12px 10px;">
        <h2 style="margin:0;">Historique</h2>
      </div>

      <div class="admin-table-wrap" style="background:#fff;border:1px solid var(--border-color);border-radius:14px;overflow:auto;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));">
        <table class="admin-table" style="min-width:860px;border-collapse:separate;border-spacing:0;">
          <thead style="background:var(--table-head,#f8fafc);">
            <tr>
              <th class="history-th">#</th>
              <?php if ($is_admin): ?>
                <th class="history-th">Utilisateur</th>
              <?php endif; ?>
              <th class="history-th">Média</th>
              <th class="history-th">Emprunté le</th>
              <th class="history-th">À rendre le</th>
              <th class="history-th">Rendu le</th>
              <th class="history-th">Retard (j)</th>
              <th class="history-th">Statut</th>
              <th class="history-th">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($loans as $l): ?>
            <?php
              $statusRow = isset($l['status']) ? (string)$l['status'] : '';
              $badge = 'display:inline-block;padding:.25rem .5rem;border-radius:9999px;font-weight:600;font-size:.8rem;';
              if ($statusRow === 'En retard') $badge .= 'background:#fee2e2;color:#991b1b;';
              elseif ($statusRow === 'En cours') $badge .= 'background:#e0f2fe;color:#075985;';
              elseif ($statusRow === 'Rendu') $badge .= 'background:#dcfce7;color:#166534;';
              else $badge .= 'background:#f1f5f9;color:#334155;';
            ?>
            <tr style="border-top:1px solid var(--border-color);">
              <td class="history-td"><?= (int)($l['id'] ?? 0) ?></td>

              <?php if ($is_admin): ?>
                <td class="history-td">
                  <?php
                    $fn = trim((string)($l['user_first_name'] ?? ''));
                    $ln = trim((string)($l['user_last_name']  ?? ''));
                    $label = trim($fn . ' ' . $ln);
                    if ($label === '') { $label = '#'.(int)($l['user_id'] ?? 0); }
                    e($label);
                  ?>
                </td>
              <?php endif; ?>

              <td class="history-td">
                <?php
                  $media_title = isset($l['media_title']) ? (string)$l['media_title'] : '';
                  if ($media_title !== '') {
                      e($media_title);
                  } else {
                      e('#' . (string)($l['media_id'] ?? ''));
                  }
                ?>
              </td>

              <td class="history-td"><?php e(isset($l['date_borrow'])   ? (string)$l['date_borrow']   : '-'); ?></td>
              <td class="history-td"><?php e(isset($l['date_due'])      ? (string)$l['date_due']      : '-'); ?></td>
              <td class="history-td"><?php e(isset($l['date_returned']) ? (string)$l['date_returned'] : '-'); ?></td>
              <td class="history-td"><?php e((string)($l['late_days'] ?? '0')); ?></td>
              <td class="history-td"><span style="<?= $badge ?>"><?php e($statusRow); ?></span></td>

              <td class="history-td">
                <?php if ($statusRow === 'En cours' || $statusRow === 'En retard'): ?>
                  <?php if ($is_admin): ?>
                    <a class="btn btn-primary btn-xxs"
                       href="<?= url('loan/force_return?loan_id=' . (int)($l['id'] ?? 0)); ?>">
                      Retour forcé
                    </a>
                  <?php else: ?>
                    <a class="btn btn-primary btn-xxs"
                       href="<?= url('loan/return?loan_id=' . (int)($l['id'] ?? 0)); ?>">
                      Rendre
                    </a>
                  <?php endif; ?>
                <?php else: ?>
                  -
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <?php
          $p   = $current_page; $win = 2;
          $start = max(1, $p - $win);
          $end   = min($pages, $p + $win);
          if ($start > 1)    $start = max(1, min($start, $pages - ($win*2)));
          if ($end < $pages) $end   = min($pages, max($end, 1 + ($win*2)));
        ?>
        <nav class="pagination" style="margin-top:16px;">
          <?php if ($p > 1): ?>
            <a class="edge" href="<?= $pagelink(1) ?>">Première</a>
            <a class="edge" href="<?= $pagelink($p-1) ?>">Précédente</a>
          <?php endif; ?>

          <?php if ($start > 1): ?><span class="ellipsis">…</span><?php endif; ?>
          <?php for ($i=$start; $i<=$end; $i++): ?>
            <?php if ($i === $p): ?>
              <span class="page-btn is-current"><?= $i ?></span>
            <?php else: ?>
              <a class="page-btn" href="<?= $pagelink($i) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          <?php if ($end < $pages): ?><span class="ellipsis">…</span><?php endif; ?>

          <?php if ($p < $pages): ?>
            <a class="edge" href="<?= $pagelink($p+1) ?>">Suivante</a>
            <a class="edge" href="<?= $pagelink($pages) ?>">Dernière</a>
          <?php endif; ?>

          <span style="margin-left:8px;color:#6b7280;font-size:.9rem;">
            Page <?= $p ?>/<?= $pages ?>
          </span>
        </nav>
      <?php endif; ?>

      <?php if (!empty($_SESSION['user_email']) && ($_SESSION['role'] ?? 'user') === 'user'): ?>
        <p style="margin-top:16px;">
          <a class="btn btn-secondary" href="<?= url('media/index'); ?>">
            Retour au catalogue
          </a>
        </p>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</section>
