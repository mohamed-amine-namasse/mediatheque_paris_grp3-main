<?php ?>

<div class="admin-shell-fixed">
  <!-- Sidebar -->
  <aside class="admin-sidebar" role="navigation" aria-label="Menu admin">
    <div class="admin-sidebrand">
      <div class="admin-sideicon"><i class="fas fa-layer-group"></i></div>
      <div class="admin-sidetext">Admin</div>
    </div>

    <nav class="admin-sidenav">
      <a href="#kpis" class="admin-sidelink"><i class="fas fa-gauge-high"></i> Tableau de bord</a>
      <a href="<?= url('home/users'); ?>" class="admin-sidelink"><i class="fas fa-user-friends"></i> Utilisateurs</a>
      <a href="<?= url('media/medias'); ?>" class="admin-sidelink"><i class="fas fa-photo-video"></i> Médias</a>
      <a href="<?= url('loan/history'); ?>" class="admin-sidelink"><i class="fas fa-clipboard-list"></i> Emprunts</a>
      <a href="<?= url('media/index'); ?>" class="admin-sidelink"><i class="fas fa-book-open"></i> Catalogue</a>
    </nav>

    <div class="admin-sidelogout">
      <a href="<?= url('auth/logout'); ?>" class="admin-logoutbtn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
  </aside>

  <!-- Contenu -->
  <main class="admin-main">
    <header class="admin-topbar">
      <div class="admin-topbar-text">
        <h1 class="admin-title"><?php e(isset($title) ? (string)$title : 'Admin'); ?></h1>
        <p class="admin-subtitle"><?php e(isset($message) ? (string)$message : 'Dashboard'); ?></p>
      </div>
    </header>

    <?php if (function_exists('flash_messages')) {
      flash_messages();
    } ?>

    <!-- KPIs -->
    <section id="kpis" class="admin-section">
      <div class="admin-kpi-grid">
        <article class="admin-kpi-card">
          <div class="admin-kpi-icon"><i class="fas fa-film"></i></div>
          <div>
            <div class="admin-kpi-label">Films</div>
            <div class="admin-kpi-value"><?= (int)($count_films ?? 0) ?></div>
          </div>
        </article>
        <article class="admin-kpi-card">
          <div class="admin-kpi-icon"><i class="fas fa-book"></i></div>
          <div>
            <div class="admin-kpi-label">Livres</div>
            <div class="admin-kpi-value"><?= (int)($count_livres ?? 0) ?></div>
          </div>
        </article>
        <article class="admin-kpi-card">
          <div class="admin-kpi-icon"><i class="fas fa-gamepad"></i></div>
          <div>
            <div class="admin-kpi-label">Jeux</div>
            <div class="admin-kpi-value"><?= (int)($count_jeux ?? 0) ?></div>
          </div>
        </article>
        <article class="admin-kpi-card admin-kpi-accent">
          <div class="admin-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
          <div>
            <div class="admin-kpi-label">Emprunts en cours</div>
            <div class="admin-kpi-value"><?= (int)($loans_in_progress ?? 0) ?></div>
          </div>
        </article>
        <article class="admin-kpi-card admin-kpi-warn">
          <div class="admin-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <div>
            <div class="admin-kpi-label">Emprunts en retard</div>
            <div class="admin-kpi-value"><?= (int)($loans_late ?? 0) ?></div>
          </div>
        </article>
      </div>
    </section>

    <!-- Grille -->
    <section class="admin-grid">
      <div class="admin-col">
        <!-- Utilisateurs -->
        <article id="users" class="admin-card">
          <header class="admin-card-head">
            <h2 class="admin-card-title"><i class="fas fa-user-friends"></i> Utilisateurs</h2>
            <a class="admin-btn" href="<?= url('home/users'); ?>">Voir plus</a>
          </header>

          <div class="admin-table-wrap">
            <table class="admin-table" id="admin-table-users">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nom</th>
                  <th>Prénom</th>
                  <th>Email</th>
                  <th>Rôle</th>
                  <th>Créé le</th>
                  <th class="admin-col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($users_preview)): ?>
                  <tr>
                    <td colspan="6" class="admin-empty">Aucun utilisateur.</td>
                  </tr>
                  <?php else: foreach ($users_preview as $u): ?>
                    <tr>
                      <td><?= (int)($u['id'] ?? 0) ?></td>
                      <td><?php e($u['last_name']  ?? ''); ?></td>
                      <td><?php e($u['first_name'] ?? ''); ?></td>
                      <td><?php e($u['email']      ?? ''); ?></td>
                      <td><?php e($u['role']      ?? ''); ?></td>
                      <td><?php e($u['created_at'] ?? ''); ?></td>
                      <td class="admin-actions">
                        <a href="<?= url('home/profile_admin') ?>?id=<?= (int)($u['id'] ?? 0) ?>" class="admin-iconbtn" title="Éditer"><i class="fas fa-pen"></i></a>
                        <a href="<?= url('home/stats') ?>?id=<?= (int)($u['id'] ?? 0) ?>" class="admin-iconbtn" title="Stats"><i class="fas fa-chart-pie"></i></a>
                        <a href="<?= url('home/confirm_delete_user') ?>?id=<?= (int)($u['id'] ?? 0) ?>" class="admin-iconbtn admin-danger" title="Supprimer"><i class="fas fa-trash"></i></a>
                      </td>
                    </tr>
                <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($users_total)): ?>
            <div class="admin-card-foot"><span class="admin-muted"><?= (int)$users_total ?> au total</span></div>
          <?php endif; ?>
        </article>

        <!-- Emprunts -->
        <article id="loans" class="admin-card">
          <header class="admin-card-head">
            <h2 class="admin-card-title"><i class="fas fa-clipboard-list"></i> Emprunts récents</h2>
            <a class="admin-btn admin-btn-secondary" href="<?= url('loan/history'); ?>">Tout l’historique</a>
          </header>

          <div class="admin-table-wrap">
            <table class="admin-table" id="admin-table-loans">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nom</th>
                  <th>Prénom</th>
                  <th>ID média</th>
                  <th>Emprunt</th>
                  <th>Échéance</th>
                  <th>Retour</th>
                  <th>Statut</th>
                  <th>Retard (j)</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($loans_preview)): ?>
                  <tr>
                    <td colspan="9" class="admin-empty">Aucun emprunt.</td>
                  </tr>
                  <?php else: foreach ($loans_preview as $l):
                    $status = (string)($l['status'] ?? '');
                    $badge  = 'badge';
                    if ($status === 'En retard') $badge = 'badge badge--late';
                    elseif ($status === 'En cours') $badge = 'badge badge--progress';
                    elseif ($status === 'Rendu') $badge = 'badge badge--ok';
                  ?>
                    <tr>
                      <td><?= (int)($l['id'] ?? 0) ?></td>
                      <td><?php e($l['last_name']   ?? '-'); ?></td>
                      <td><?php e($l['first_name']  ?? '-'); ?></td>
                      <td><?php e($l['media_id']    ?? '-'); ?></td>
                      <td><?php e($l['date_borrow'] ?? '-'); ?> </td>
                      <td><?php e($l['date_due']    ?? '-' ); ?></td>
                      <td><?php e($l['date_returned'] ?? '-'); ?></td>
                      <td><span class="<?= $badge ?>"><?php e($status) ?></span></td>
                      <td class="late-day"><?php e($l['late_days'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($loans_total)): ?>
            <div class="admin-card-foot"><span class="admin-muted"><?= (int)$loans_total ?> au total</span></div>
          <?php endif; ?>
        </article>
      </div>

      <div class="admin-col">
        <!-- Médias -->
        <article id="medias" class="admin-card">
          <header class="admin-card-head">
            <h2 class="admin-card-title"><i class="fas fa-photo-video"></i> Médias</h2>
            <a class="admin-btn" href="<?= url('media/medias'); ?>">Voir plus</a>
          </header>

          <div class="admin-table-wrap">
            <table class="admin-table" id="admin-table-medias">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Type</th>
                  <th>Titre</th>
                  <th>Genre</th>
                  <th>Description</th>
                  <th>Stock</th>
                  <th class="admin-col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($medias_preview)): ?>
                  <tr>
                    <td colspan="7" class="admin-empty">Aucun média.</td>
                  </tr>
                  <?php else: foreach ($medias_preview as $m): ?>
                    <tr>
                      <td><?= (int)($m['id'] ?? 0) ?></td>
                      <td><?php e($m['type']  ?? ''); ?></td>
                      <td><?php e($m['title'] ?? ''); ?></td>
                      <td><?php e($m['genre'] ?? ''); ?></td>
                      <td>
                        <div class="admin-ellipsis">
                          <?php
                          $desc = $m['description'] ?? '';
                          // Coupe a 100 caractères et rajoute "..."
                          echo e(mb_strimwidth($desc, 0, 100, '...'));
                          ?>
                        </div>
                      </td>

                      <td><?= (int)($m['stock'] ?? 0) ?></td>
                      <td class="admin-actions">
                        <a href="<?= url('media/detail') ?>/<?= (int)($m['id'] ?? 0) ?>" class="admin-iconbtn" title="Éditer"><i class="fas fa-pen"></i></a>
                        <a href="<?= url('media/delete_media') ?>?id=<?= (int)($m['id'] ?? 0) ?>" class="admin-iconbtn admin-danger" title="Supprimer"><i class="fas fa-trash"></i></a>
                      </td>
                    </tr>
                <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($medias_total)): ?>
            <div class="admin-card-foot"><span class="admin-muted"><?= (int)$medias_total ?> au total</span></div>
          <?php endif; ?>
        </article>
      </div>
    </section>
  </main>
</div>
