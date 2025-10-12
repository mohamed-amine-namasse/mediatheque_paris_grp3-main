<?php

$title   = isset($title)   ? (string)$title   : 'Stats';
$message = isset($message) ? (string)$message : '';

// KPI globaux
$total_movies      = (int)($total_movies      ?? 0);
$total_books       = (int)($total_books       ?? 0);
$total_games       = (int)($total_games       ?? 0);
$loans_in_progress = (int)($loans_in_progress ?? 0);
$loans_late        = (int)($loans_late        ?? 0);

// Sélecteur de user
$current_user_id = (int)($current_user_id ?? (int)($_GET['id'] ?? 0));

// Bloc stats user
$user_stats = is_array($user_stats ?? null) ? $user_stats : null;

// Utiles pour affichage
$u_total  = (int)($user_stats['total_loans']    ?? 0);
$u_first  = (string)($user_stats['first_name']  ?? '');
$u_last   = (string)($user_stats['last_name']   ?? '');
$u_movies = (int)($user_stats['movies_percent'] ?? 0);
$u_books  = (int)($user_stats['books_percent']  ?? 0);
$u_games  = (int)($user_stats['games_percent']  ?? 0);

// stat donuts en svg
$render_donut = function (string $label, int $percent, string $ringColor, string $fillColor) {
  $p = max(0, min(100, $percent));
  $dash = $p . ',' . (100 - $p);
  // card
  echo '<div style="border:1px solid var(--border-color);border-radius:14px;padding:16px;background:#fff;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));display:flex;gap:12px;align-items:center;">';
  // svg donut
  echo '<svg width="92" height="92" viewBox="0 0 40 40" aria-hidden="true">';
  echo '<circle cx="20" cy="20" r="15.915" fill="#fff"></circle>';
  echo '<circle cx="20" cy="20" r="15.915" fill="transparent" stroke="' . $ringColor . '" stroke-width="3.5"></circle>';
  if ($p > 0) {
    echo '<circle cx="20" cy="20" r="15.915" fill="transparent" stroke="' . $fillColor . '" stroke-width="3.5" stroke-dasharray="' . $dash . '" stroke-dashoffset="25" style="transition:stroke-dasharray .3s ease;"></circle>';
  }
  echo '<text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" style="font-weight:800;font-size:8px;fill:#0f172a;">' . $p . '%</text>';
  echo '</svg>';
  // légende
  echo '<div style="display:flex;flex-direction:column;gap:4px;">';
  echo '<div style="font-size:.92rem;color:#334155;">' . e($label, ENT_QUOTES, 'UTF-8') . '</div>';
  echo '<div style="height:8px;width:160px;background:#f1f5f9;border-radius:9999px;overflow:hidden;border:1px solid #e5e7eb;">';
  echo '<div style="height:100%;width:' . $p . '%;background:' . $fillColor . ';"></div>';
  echo '</div>';
  echo '</div>';
  echo '</div>';
};
?>
<div class="page-header">
  <div class="container">
    <h1><?php e($title); ?></h1>
  </div>
</div>

<section class="content">
  <div class="container">
    <div class="content-main">
      <h2 style="margin-top:0;"><?php e($message); ?></h2>

      <!-- KPI : mêmes classes que Médias/Admin pour un responsive identique -->
      <section class="admin-kpi-grid" style="margin:12px 0;">
        <article class="admin-kpi-card">
          <div class="admin-kpi-icon"><i class="fas fa-film"></i></div>
          <div>
            <div class="admin-kpi-label">Films</div>
            <div class="admin-kpi-value"><?= $total_movies ?></div>
          </div>
        </article>
        <article class="admin-kpi-card">
          <div class="admin-kpi-icon"><i class="fas fa-book"></i></div>
          <div>
            <div class="admin-kpi-label">Livres</div>
            <div class="admin-kpi-value"><?= $total_books ?></div>
          </div>
        </article>
        <article class="admin-kpi-card">
          <div class="admin-kpi-icon"><i class="fas fa-gamepad"></i></div>
          <div>
            <div class="admin-kpi-label">Jeux</div>
            <div class="admin-kpi-value"><?= $total_games ?></div>
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

      <!-- Raccourcis  -->
      <div class="center-btn" style="gap:8px;margin-bottom:8px;">
        <a class="button" href="<?= url('home/users') ?>">Utilisateurs</a>
        <a class="button" id="red" href="<?= url('media/medias') ?>">Médias</a>
        <a class="button" id="purple" href="<?= url('loan/history') ?>">Emprunts</a>
      </div>

      <!--  Sélecteur d’utilisateur  -->
      <form method="get" action="<?= url('home/stats') ?>"
        style="background:#fff;border:1px solid var(--border-color);padding:12px;border-radius:12px;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));margin:12px 0;">
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
          <div class="form-group" style="min-width:220px;">
            <label for="id">ID utilisateur</label>
            <input type="number" id="id" name="id" min="1" value="<?= (int)$current_user_id ?>" placeholder="ex: 12">
          </div>
          <div class="form-group">
            <button class="btn btn-primary btn-xxs" type="submit">Afficher</button>
            <a class="btn btn-secondary btn-xxs" href="<?= url('home/stats') ?>">Réinitialiser</a>
          </div>
        </div>
      </form>

      <?php if ($user_stats): ?>
        <!-- Bandeau infos du user  -->
        <div style="border:1px solid var(--border-color);border-radius:14px;padding:14px;background:#fff;box-shadow:var(--shadow,0 2px 12px rgba(0,0,0,.06));display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div>
            <div style="font-size:.9rem;color:#64748b;">Utilisateur</div>
            <div style="font-size:1.15rem;font-weight:700;">
              <?php
              $fullname = trim(($u_first !== '' ? $u_first : '') . ' ' . ($u_last !== '' ? $u_last : ''));
              e($fullname !== '' ? $fullname : '—');
              ?>
            </div>
          </div>
          <div style="display:flex;gap:12px;">
            <div style="border:1px dashed #e5e7eb;border-radius:10px;padding:10px;text-align:center;min-width:120px;">
              <div style="font-size:.85rem;color:#64748b;">ID</div>
              <div style="font-size:1.25rem;font-weight:800;"><?= (int)($user_stats['user_id'] ?? 0) ?></div>
            </div>
            <div style="border:1px dashed #e5e7eb;border-radius:10px;padding:10px;text-align:center;min-width:160px;">
              <div style="font-size:.85rem;color:#64748b;">Total emprunts</div>
              <div style="font-size:1.25rem;font-weight:800;"><?= $u_total ?></div>
            </div>
          </div>
        </div>

        <!--  Donut svg dans les cards  -->
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;">
          <?php
          $render_donut('Films',  $u_movies, '#eef2ff', '#c7d2fe');
          $render_donut('Livres', $u_books,  '#dcfce7', '#a7f3d0');
          $render_donut('Jeux',   $u_games,  '#fce7f3', '#fbcfe8');
          ?>
        </div>

      <?php else: ?>
        <div style="margin-top:8px;color:#475569;">
          Saisis un <strong>ID utilisateur</strong> ci-dessus puis clique sur «&nbsp;Afficher&nbsp;».
          Les donuts par catégorie s’afficheront dans des cartes propres, avec le pourcentage au centre.
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>