<?php
$user  = $user ?? null;
$id    = (int)($id ?? 0);
$title = $title ?? 'Confirmation de suppression';
$role  = (string)($user['role'] ?? '');
?>
<div class="page-header">
  <div class="container">
    <h1><?php e($title); ?></h1>
  </div>
</div>

<section class="content">
  <div class="container" style="max-width:720px">
    <?php if (function_exists('flash_messages')) {
      flash_messages();
    } ?>

    <div style="background:#fff;border:1px solid var(--border-color);border-radius:8px;padding:16px;box-shadow:var(--shadow);">
      <p>
        <?php if ($role === 'admin'): ?>
          <strong>Attention :</strong> vous êtes sur le point de <strong>supprimer un compte administrateur</strong> (#<?= $id ?>).
          Cette action est potentiellement critique.<br><br>
        <?php endif; ?>
        Voulez-vous vraiment supprimer l’utilisateur
        <strong>#<?= $id ?></strong>
        <?php if (!empty($user['first_name']) || !empty($user['last_name'])): ?>
          (<?php e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>)
        <?php endif; ?>
        ?
      </p>

      <form method="post" action="<?= url('home/delete_user') ?>" style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
        <input type="hidden" name="id" value="<?= $id ?>">
        <?php if (!empty($csrf_token ?? '')): ?>
          <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <?php endif; ?>

        <button type="submit" name="confirm" value="yes" class="btn btn-danger">Oui, supprimer</button>
        <a class="btn btn-secondary" href="<?= url('home/users') ?>">Annuler</a>
      </form>
    </div>
  </div>
</section>