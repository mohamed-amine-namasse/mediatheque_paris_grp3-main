<div class="page-header">
    <div class="container">
        <h1><?php e($title); ?></h1>
    </div>
</div>

<section class="content">
    <div class="container">
        <div class="content-grid">
            <div class="content-main">
                <h2><?php e($message); ?></h2>

                <?php
                // Le contrôleur fournit $user
                $first_name = $user['first_name'] ?? '';
                $last_name  = $user['last_name']  ?? '';
                $email      = $user['email']      ?? '';
                ?>

                <form method="POST" action="<?php echo url('home/profile'); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

                    <div class="form-group">
                        <label for="first_name">Prénom</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php e($first_name); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Nom</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php e($last_name); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" required value="<?php e($email); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="password" name="password">
                        <small class="muted">
                            Au moins 8 caractères, 1 minuscule, 1 majuscule, 1 chiffre et 1 caractère spécial.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Modifier</button>
                </form>
            </div>
        </div>
    </div>
</section>