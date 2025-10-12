<?php
$pages    = isset($pages) ? (int)$pages : 1;
$total    = isset($total) ? (int)$total : (is_array($medias ?? null) ? count($medias) : 0);
$is_admin = !empty($is_admin);

$genres      = $genres       ?? [];
$classifs    = $classifs     ?? [];
$plats       = $plateformes  ?? [];
$ages_min    = $ages         ?? [];
$f           = $filters      ?? [];
$group       = !empty($group_mode);

/** Helper local pagination (évite tout redeclare) */
if (!function_exists('build_page_url')) {
  function build_page_url($p) {
    $q = $_GET ?? [];
    $q['page'] = max(1, (int)$p);
    return url('media/index') . '?' . http_build_query($q);
  }
}
?>
<div class="page-header">
  <div class="container">
    <h1><?php e($title ?? 'Catalogue'); ?></h1>
  </div>
</div>

<section class="content">
  <div class="container">
    <?php if (function_exists('flash_messages')) { flash_messages(); } ?>

    <!-- ===== Barre de recherche / filtres (utilise .media-filter du CSS global) ===== -->
    <form method="get" action="<?php echo url('media/index'); ?>" class="media-filter" role="search" aria-label="Filtrer le catalogue">
      <div class="form-row">
        <div class="form-group" style="flex:1;min-width:260px;">
          <label for="q">Recherche</label>
          <input type="text" id="q" name="q" value="<?php echo esc($f['q'] ?? ''); ?>" placeholder="Titre, auteur, réalisateur, éditeur, ISBN…">
        </div>

        <div class="form-group">
          <label for="filter-type">Type</label>
          <select id="filter-type" name="type">
            <option value="">Tous</option>
            <option value="livre" <?php echo (($f['type']??'')==='livre'?'selected':''); ?>>Livre</option>
            <option value="film"  <?php echo (($f['type']??'')==='film'?'selected':''); ?>>Film</option>
            <option value="jeu"   <?php echo (($f['type']??'')==='jeu'?'selected':''); ?>>Jeu</option>
          </select>
        </div>

        <div class="form-group">
          <label for="genre">Genre</label>
          <select id="genre" name="genre">
            <option value="">Tous</option>
            <?php foreach ($genres as $g): ?>
              <option value="<?php e((string)$g); ?>" <?php echo (($f['genre']??'')===$g?'selected':''); ?>><?php e((string)$g); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="available">Disponibles</label>
          <input type="checkbox" id="available" name="available" value="1" <?php echo !empty($f['available'])?'checked':''; ?>>
        </div>

        <div class="form-group">
          <label for="classification">Âge autorisé (films)</label>
          <select id="classification" name="classification">
            <option value="">Tous</option>
            <?php foreach ($classifs as $c): ?>
              <option value="<?php e((string)$c); ?>" <?php echo (($f['classification']??'')===$c?'selected':''); ?>><?php e((string)$c); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="plateforme">Plateforme (jeux)</label>
          <select id="plateforme" name="plateforme">
            <option value="">Toutes</option>
            <?php foreach ($plats as $p): ?>
              <option value="<?php e((string)$p); ?>" <?php echo (($f['plateforme']??'')===$p?'selected':''); ?>><?php e((string)$p); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="age_min">Âge minimum (jeux)</label>
          <select id="age_min" name="age_min">
            <option value="">Tous</option>
            <?php foreach ($ages_min as $a): ?>
              <option value="<?php e((string)$a); ?>" <?php echo (($f['age_min']??'')==(string)$a?'selected':''); ?>><?php e((string)$a); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Année (min–max)</label>
          <div style="display:flex;gap:6px;">
            <input type="number" name="year_min" min="1900" max="<?php echo date('Y'); ?>" placeholder="min" value="<?php echo esc($f['year_min'] ?? ''); ?>" style="width:100px;">
            <input type="number" name="year_max" min="1900" max="<?php echo date('Y'); ?>" placeholder="max" value="<?php echo esc($f['year_max'] ?? ''); ?>" style="width:100px;">
          </div>
        </div>

        <div class="form-group">
          <label for="order">Tri</label>
          <select id="order" name="order">
            <option value="">Nouveautés</option>
            <option value="title_asc"  <?php echo (($f['order']??'')==='title_asc'?'selected':''); ?>>Titre A→Z</option>
            <option value="title_desc" <?php echo (($f['order']??'')==='title_desc'?'selected':''); ?>>Titre Z→A</option>
            <option value="date_asc"   <?php echo (($f['order']??'')==='date_asc'?'selected':''); ?>>Plus anciens</option>
            <option value="stock_desc" <?php echo (($f['order']??'')==='stock_desc'?'selected':''); ?>>Stock décroissant</option>
            <option value="stock_asc"  <?php echo (($f['order']??'')==='stock_asc'?'selected':''); ?>>Stock croissant</option>
          </select>
        </div>

        <div class="form-group">
          <button class="btn btn-primary btn-xxs btn-fil" type="submit">Filtrer</button>
          <a class="btn btn-secondary btn-xxs btn-ren" href="<?php echo url('media/index'); ?>">Réinitialiser</a>
        </div>
      </div>
    </form>

    <!-- ===== Liste ===== -->
    <?php if ($group): ?>
      <?php
        $labels = ['film' => 'Films', 'jeu' => 'Jeux vidéo', 'livre' => 'Livres'];
        $order_sections = ['film','jeu','livre'];
      ?>
      <?php foreach ($order_sections as $t): ?>
        <?php $list = $groups[$t] ?? []; $total_t = (int)($totals_by_type[$t] ?? 0); if (empty($list) && $total_t === 0) continue; ?>
        <h2 style="margin:12px 0 8px;"><?php e($labels[$t]); ?></h2>

        <?php if (empty($list)): ?>
          <p class="admin-muted">Aucun élément pour cette catégorie.</p>
        <?php else: ?>
          <ul class="media-list">
            <?php foreach ($list as $media): ?>
              <li class="media-item">
                <?php if (!empty($media['cover_path'])): ?>
                  <img class="media-cover" src="<?php echo e((string)$media['cover_path']); ?>" alt="cover">
                <?php else: ?>
                  <div class="media-cover" style="display:flex;align-items:center;justify-content:center;">
                    <span class="admin-muted" style="font-size:12px;">Sans image</span>
                  </div>
                <?php endif; ?>

                <div class="media-info">
                  <strong class="media-name"><?php e((string)($media['titre'] ?? '')); ?></strong>
                  <div class="admin-muted" style="font-size:.9rem;">
                    <?php e((string)($media['type'] ?? '')); ?> • <?php e((string)($media['genre'] ?? '')); ?> <?php if ($_SESSION['role'] != 'admin'): ?>• Stock <?php e((string)($media['stock'] ?? '0')); ?> <?php endif; ?>
                  </div>
                  <a class="btn btn-primary btn-xxs" href="<?php echo url('media/detail/' . (int)$media['id']); ?>">Voir détails</a>
                </div>

                <?php if ($is_admin): ?>
                  <div class="media-admin-actions" style="display:flex;gap:8px;">
                    <form method="post" action="<?php echo url('media/add_copy'); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                      <input type="hidden" name="media_id" value="<?php echo (int)$media['id']; ?>">
                      <button type="submit" class="btn btn-secondary btn-xxs">+1</button>
                    </form>
                    <form method="post" action="<?php echo url('media/remove_copy'); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                      <input type="hidden" name="media_id" value="<?php echo (int)$media['id']; ?>">
                      <button type="submit" class="btn btn-secondary btn-xxs">−1</button>
                    </form>
                  </div>
                  <div class="media-stock-line">Stock: <?php e((string)($media['stock'] ?? '0')); ?> • Total: <?php e((string)($media['total_copies'] ?? '0')); ?></div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($total_t > (int)($section_limit ?? 0)): ?>
            <?php
              $q = $_GET ?? [];
              $q['type'] = $t;
              unset($q['page']);
              $more_url = url('media/index') . '?' . http_build_query($q);
            ?>
            <div style="margin:10px 0 24px;">
              <a class="btn btn-secondary btn-xxs" href="<?php echo $more_url; ?>">Voir plus de <?php e(strtolower($labels[$t])); ?> →</a>
              <span class="admin-muted" style="margin-left:8px;font-size:.9rem;">(<?php echo (int)$total_t; ?> au total)</span>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endforeach; ?>

    <?php else: ?>
      <?php if (empty($medias)): ?>
        <p class="admin-muted">Aucun média ne correspond à votre recherche.</p>
      <?php else: ?>
        <ul class="media-list">
          <?php foreach ($medias as $media): ?>
            <li class="media-item">
              <?php if (!empty($media['cover_path'])): ?>
                <img class="media-cover" src="<?php e((string)$media['cover_path']); ?>" alt="cover">
              <?php else: ?>
                <div class="media-cover" style="display:flex;align-items:center;justify-content:center;">
                  <span class="admin-muted" style="font-size:12px;">Sans image</span>
                </div>
              <?php endif; ?>

              <div class="media-info">
                <strong class="media-name"><?php e((string)($media['titre'] ?? '')); ?></strong>
                <div class="admin-muted" style="font-size:.9rem;">
                  <?php e((string)($media['type'] ?? '')); ?> • <?php e((string)($media['genre'] ?? '')); ?>   • Stock <?php e((string)($media['stock'] ?? '0')); ?> 
                </div>
                <a class="btn btn-primary btn-xxs" href="<?php echo url('media/detail/' . (int)$media['id']); ?>">Voir détails</a>
              </div>

              <?php if ($is_admin): ?>
                <div class="media-admin-actions" style="display:flex;gap:8px;">
                  <form method="post" action="<?php echo url('media/add_copy'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="media_id" value="<?php echo (int)$media['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-xxs">+1</button>
                  </form>
                  <form method="post" action="<?php echo url('media/remove_copy'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="media_id" value="<?php echo (int)$media['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-xxs">−1</button>
                  </form>
                </div>
                <div class="media-stock-line">Stock: <?php e((string)($media['stock'] ?? '0')); ?> • Total: <?php e((string)($media['total_copies'] ?? '0')); ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php if ($pages > 1): ?>
          <?php
            $page = max(1, (int)($_GET['page'] ?? 1));
            $prev_disabled = ($page <= 1);
            $next_disabled = ($page >= $pages);
            $prev_url = build_page_url(max(1, $page - 1));
            $next_url = build_page_url(min($pages, $page + 1));
          ?>
          <nav class="pagination" style="margin-top:20px;display:flex;gap:8px;align-items:center;">
            <a class="btn btn-secondary btn-xxs" href="<?php echo $prev_disabled ? 'javascript:void(0)' : $prev_url; ?>" style="<?php echo $prev_disabled ? 'opacity:.5;pointer-events:none;' : ''; ?>">← Précédent</a>
            <span class="admin-muted" style="font-size:.9rem;">Page <?php e((string)$page); ?> / <?php e((string)$pages); ?> • <?php e((string)$total); ?> éléments</span>
            <a class="btn btn-secondary btn-xxs" href="<?php echo $next_disabled ? 'javascript:void(0)' : $next_url; ?>" style="<?php echo $next_disabled ? 'opacity:.5;pointer-events:none;' : ''; ?>">Suivant →</a>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($is_admin): ?>
      <hr style="margin:30px 0;">
      <h2 style="font-size:1.15rem;margin-bottom:12px;">Ajouter / Modifier un média</h2>

      <!-- Formulaire ADMIN : s’appuie sur .media-add-form du CSS global -->
      <form method="post" action="<?php echo url('media/store_from_catalog'); ?>" enctype="multipart/form-data" class="media-add-form" data-show="livre">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

        <!-- Commun -->
        <div class="form-row">
          <div class="form-group">
            <label for="form-type">Type *</label>
            <select name="type" id="form-type" required>
              <option value="livre" selected>Livre</option>
              <option value="film">Film</option>
              <option value="jeu">Jeu</option>
            </select>
          </div>

          <div class="form-group" style="flex:1;min-width:260px;">
            <label for="title">Titre *</label>
            <input type="text" name="title" id="title" maxlength="200" required>
          </div>

          <div class="form-group">
            <label for="form-genre">Genre *</label>
            <select name="genre" id="form-genre" required>
              <?php foreach ($genres as $g): ?>
                <option value="<?php e((string)$g); ?>"><?php e((string)$g); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="stock">Stock initial *</label>
            <input type="number" name="stock" id="stock" value="1" min="1" required>
          </div>
        </div>

        <div class="form-group" style="max-width:800px;">
          <label for="description">Description</label>
          <textarea name="description" id="description" rows="3" placeholder="Optionnel..."></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="cover">Couverture (JPG/PNG/GIF, max 2 Mo)</label>
            <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/gif">
            <small class="admin-muted">La couverture sera redimensionnée au max 300×400.</small>
          </div>
        </div>

        <!-- LIVRE -->
        <fieldset id="fs-livre" class="type-block" data-type="livre">
          <legend>Livre</legend>
          <div class="form-row">
            <div class="form-group">
              <label for="author">Auteur *</label>
              <input type="text" name="author" id="author" placeholder="2–100 caractères">
            </div>
            <div class="form-group">
              <label for="editor-book">Éditeur *</label>
              <input type="text" name="editor_book" id="editor-book" placeholder="2–100 caractères">
            </div>
            <div class="form-group">
              <label for="isbn">ISBN (10 ou 13)</label>
              <input type="text" name="isbn" id="isbn" placeholder="Sans tirets ni espaces">
            </div>
            <div class="form-group">
              <label for="pages">Pages *</label>
              <input type="number" name="pages" id="pages" min="1" max="9999">
            </div>
            <div class="form-group">
              <label for="publication_year">Année de publication *</label>
              <input type="number" name="publication_year" id="publication_year" min="1900" max="<?php echo date('Y'); ?>">
            </div>
          </div>
        </fieldset>

        <!-- FILM -->
        <fieldset id="fs-film" class="type-block" data-type="film">
          <legend>Film</legend>
          <div class="form-row">
            <div class="form-group">
              <label for="producer">Réalisateur *</label>
              <input type="text" name="producer" id="producer" placeholder="2–100 caractères">
            </div>
            <div class="form-group">
              <label for="duration_min">Durée (min) *</label>
              <input type="number" name="duration_min" id="duration_min" min="1" max="999">
            </div>
            <div class="form-group">
              <label for="release_year">Année *</label>
              <input type="number" name="release_year" id="release_year" min="1900" max="<?php echo date('Y'); ?>">
            </div>
            <div class="form-group">
              <label for="classification-film">Âge autorisé *</label>
              <select name="classification" id="classification-film">
                <?php foreach ($classifs as $c): ?>
                  <option value="<?php e((string)$c); ?>"><?php e((string)$c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </fieldset>

        <!-- JEU -->
        <fieldset id="fs-jeu" class="type-block" data-type="jeu">
          <legend>Jeu vidéo</legend>
          <div class="form-row">
            <div class="form-group">
              <label for="editor-game">Éditeur *</label>
              <input type="text" name="editor_game" id="editor-game" placeholder="2–100 caractères">
            </div>
            <div class="form-group">
              <div id="ancre"></div>
              <label for="plateforme-jeu">Plateforme *</label>
              <select name="plateforme" id="plateforme-jeu">
                <?php foreach ($plats as $p): ?>
                  <option value="<?php e((string)$p); ?>"><?php e((string)$p); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="age_min-jeu">Âge minimum *</label>
              <select name="age_min" id="age_min-jeu">
                <?php foreach ($ages_min as $a): ?>
                  <option value="<?php e((string)$a); ?>"><?php e((string)$a); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </fieldset>

        <button type="submit" class="btn btn-primary btn-xxs" style="margin-top:12px; padding:15px; border-radius:20px;">Enregistrer</button>
      </form>
    <?php endif; ?>
  </div>
</section>
