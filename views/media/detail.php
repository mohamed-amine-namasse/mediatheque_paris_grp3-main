<?php

$cover     = !empty($media['cover_path']) ? $media['cover_path'] : url('assets/images/default.jpg');
$has_cover = !empty($media['cover_path']);
$available = (bool)($available ?? ((int)$media['stock'] > 0));
$is_admin  = !empty($is_admin);
$fields    = $fields ?? [];
?>
<section class="content">
  <div class="container">
    <?php flash_messages(); ?>

    <div class="content-main">
      <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div style="position:relative;display:inline-block;">
          <img class="detail-cover" src="<?php echo esc($cover); ?>" alt="cover" style="max-width:220px;height:auto;border-radius:6px;border:1px solid #e5e7eb;">

          <?php if ($is_admin && $has_cover): ?>
            <!-- Bouton poubelle  -->
            <form method="post" action="<?php echo url('media/delete_cover'); ?>" style="margin-top:8px;">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="media_id"   value="<?php echo (int)$media['id']; ?>">
              <button class="btn btn-secondary btn-xxs" type="submit" title="Supprimer l'image">
                🗑️ Supprimer l'image
              </button>
            </form>
          <?php endif; ?>
        </div>

        <div style="flex:1;min-width:260px;">
          <h2><?php e($media['title'] ?? ''); ?></h2>

          <p>
            <?php if ($available): ?>
              <span class="alert-success">Disponible (<?php echo (int)$media['stock']; ?>)</span>
            <?php else: ?>
              <span class="alert-error">Indisponible</span>
            <?php endif; ?>
          </p>

          <?php foreach ($fields as $label => $value): ?>
            <?php if ($value !== null && $value !== '' && $value !== '-'): ?>
              <p>
                <strong><?php e($label); ?> :</strong>
                <?php e((string)$value); ?>
              </p>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>

      <hr style="margin:16px 0;">

      <?php if (is_logged_in() && (($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user') !== 'admin')): ?>
        <?php if ($available && empty($user_has_active_same_media) && empty($user_at_global_limit)): ?>
          <form method="POST" action="<?php echo url('loan/borrow'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="media_id" value="<?php echo (int)$media['id']; ?>">
            <button class="btn btn-primary" type="submit">Emprunter ce média</button>
          </form>
        <?php else: ?>
          <?php if (!$available): ?>
            <p><em>Ce média n'est plus disponible pour le moment.</em></p>
          <?php elseif (!empty($user_has_active_same_media)): ?>
            <p><em>Vous avez déjà un emprunt en cours pour ce média.</em></p>
          <?php elseif (!empty($user_at_global_limit)): ?>
            <p><em>Limite atteinte : 3 emprunts simultanés maximum.</em></p>
          <?php endif; ?>
        <?php endif; ?>

      <?php elseif (is_logged_in() && $is_admin): ?>

        <!--  Formulaire d’édition ADMIN  -->
        <h3 style="margin-top:24px;">Modifier ce média</h3>
        <form method="post" action="<?php echo url('media/update_controller'); ?>" enctype="multipart/form-data" class="media-add-form" data-show="<?php echo esc($media['type']); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="id" value="<?php echo (int)$media['id']; ?>">

          <!-- Commun -->
          <div class="form-row">
            <div class="form-group">
              <label for="form-type">Type *</label>
              <select name="type" id="form-type" required>
                <option value="livre" <?php echo ($media['type']==='livre'?'selected':''); ?>>Livre</option>
                <option value="film"  <?php echo ($media['type']==='film' ?'selected':''); ?>>Film</option>
                <option value="jeu"   <?php echo ($media['type']==='jeu'  ?'selected':''); ?>>Jeu</option>
              </select>
            </div>

            <div class="form-group">
              <label for="title">Titre *</label>
              <input type="text" name="title" id="title" maxlength="200" required value="<?php echo esc($media['title']); ?>">
            </div>

            <div class="form-group">
              <label for="form-genre">Genre *</label>
              <select name="genre" id="form-genre" required>
                <?php foreach (media_allowed_genres() as $g): ?>
                  <option value="<?php e($g); ?>" <?php echo ($media['genre']===$g?'selected':''); ?>><?php e($g); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="stock">Stock *</label>
              <input type="number" name="stock" id="stock" min="0" value="<?php echo (int)$media['stock']; ?>" required>
            </div>
          </div>

          <div class="form-group" style="max-width:800px;">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" placeholder="Optionnel..."><?php echo esc($media['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="cover">Nouvelle couverture (JPG/PNG/GIF, max 2 Mo)</label>
              <input type="file" name="cover" id="cover" accept="image/jpeg,image/png,image/gif">
              <small>Si rien n’est sélectionné, l’image actuelle est conservée. La couverture sera redimensionnée au max 300×400.</small>
            </div>
          </div>

          <!-- LIVRE -->
          <fieldset id="fs-livre" class="type-block" data-type="livre" style="border:1px solid #e5e7eb;padding:12px;border-radius:8px;margin-top:12px;">
            <legend>Livre</legend>
            <div class="form-row">
              <div class="form-group">
                <label for="author">Auteur *</label>
                <input type="text" name="author" id="author" placeholder="2–100 caractères" value="<?php echo esc($media['author'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="editor-book">Éditeur *</label>
                <input type="text" name="editor_book" id="editor-book" placeholder="2–100 caractères" value="<?php echo esc($media['editor'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="isbn">ISBN (10 ou 13)</label>
                <input type="text" name="isbn" id="isbn" placeholder="Sans tirets ni espaces" value="<?php echo esc($media['isbn'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="pages">Pages *</label>
                <input type="number" name="pages" id="pages" min="1" max="9999" value="<?php echo esc($media['pages'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="publication_year">Année de publication *</label>
                <input type="number" name="publication_year" id="publication_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo esc($media['publication_year'] ?? ''); ?>">
              </div>
            </div>
          </fieldset>

          <!-- FILM -->
          <fieldset id="fs-film" class="type-block" data-type="film" style="border:1px solid #e5e7eb;padding:12px;border-radius:8px;margin-top:12px;">
            <legend>Film</legend>
            <div class="form-row">
              <div class="form-group">
                <label for="producer">Réalisateur *</label>
                <input type="text" name="producer" id="producer" placeholder="2–100 caractères" value="<?php echo esc($media['producer'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="duration_min">Durée (min) *</label>
                <input type="number" name="duration_min" id="duration_min" min="1" max="999" value="<?php echo esc($media['duration_min'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="release_year">Année *</label>
                <input type="number" name="release_year" id="release_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo esc($media['release_year'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="classification-film">Âge autorisé *</label>
                <select name="classification" id="classification-film">
                  <?php foreach (['Tous publics','-12','-16','-18'] as $c): ?>
                    <option value="<?php e($c); ?>" <?php echo (($media['classification'] ?? '')===$c?'selected':''); ?>><?php e($c); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <!-- JEU -->
          <fieldset id="fs-jeu" class="type-block" data-type="jeu" style="border:1px solid #e5e7eb;padding:12px;border-radius:8px;margin-top:12px;">
            <legend>Jeu vidéo</legend>
            <div class="form-row">
              <div class="form-group">
                <label for="editor-game">Éditeur *</label>
                <input type="text" name="editor_game" id="editor-game" placeholder="2–100 caractères" value="<?php echo esc($media['editor'] ?? ''); ?>">
              </div>
              <div class="form-group">
                <label for="plateforme-jeu">Plateforme *</label>
                <select name="plateforme" id="plateforme-jeu">
                  <?php foreach (['PC','PlayStation','Xbox','Nintendo','Mobile'] as $p): ?>
                    <option value="<?php e($p); ?>" <?php echo (($media['plateforme'] ?? '')===$p?'selected':''); ?>><?php e($p); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="age_min-jeu">Âge minimum *</label>
                <select name="age_min" id="age_min-jeu">
                  <?php foreach ([3,7,12,16,18] as $a): ?>
                    <option value="<?php e($a); ?>" <?php echo ((int)($media['age_min'] ?? 0)===$a?'selected':''); ?>><?php e($a); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <button type="submit" class="btn btn-primary btn-xxs" style="margin-top:12px; padding:15px; border-radius:20px;">Enregistrer les modifications</button>
        </form>
      <?php else: ?>
        <p>Connectez-vous pour emprunter ce média.</p>
      <?php endif; ?>

      <p class="detail-text" style="margin-top:16px;">
        <a class="btn btn-secondary" href="<?php echo url('media/index'); ?>">Retour au catalogue</a>
      </p>
      <div class="detail-div"></div>
    </div>
  </div>
</section>
