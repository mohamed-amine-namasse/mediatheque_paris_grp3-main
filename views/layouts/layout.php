<!DOCTYPE html>
<html lang="fr">

<header id="top" class="header">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($title) ? esc($title) . ' - ' . APP_NAME : APP_NAME; ?></title>

  <!-- CSS  -->
  <link rel="stylesheet" href="<?= url('assets/css/style.css'); ?>">

  <!-- Police  -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Icônes -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

  <?php
                    if (isset($_COOKIE['logout_message'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_COOKIE['logout_message']) . '</div>';
                        setcookie('logout_message', '', time() - 3600, '/'); // Delete the cookie
                    }
                    check_and_logout_if_session_expired();
                ?>

<body>
  <header class="header">
    <nav class="navbar">
      <div class="nav-brand">
        <a href="<?= url(); ?>" class="brand">
          <img class="brand-mark" src="<?= url('assets/images/logo.svg'); ?>" alt="">
          <span class="brand-text"><?= APP_NAME; ?></span>
        </a>
      </div>

      <!-- Toggle -->
      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Ouvrir le menu">
      <label class="nav-burger" for="nav-toggle" aria-hidden="true"><span></span></label>

      <ul class="nav-menu nav-pills">
        <li><a href="<?= url(); ?>"><i class="fas fa-home"></i> Accueil</a></li>
        <li><a href="<?= url('home/about'); ?>">À propos</a></li>

        <?php if (is_logged_in()): ?>
          <?php if ($_SESSION['role'] != 'admin'): ?>
            <li><a href="<?= url('home/profile'); ?>">Profil</a></li>
          <?php endif; ?>

          <?php if ($_SESSION['role'] != 'admin'): ?>
            <li><a href="<?= url('loan/history'); ?>">Historique</a></li>
          <?php endif; ?>

          <?php if (isset($_SESSION['user_email']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="<?= url('home/admin'); ?>">Admin</a></li>
          <?php endif; ?>

          <li><a href="<?= url('media/index'); ?>">Catalogue</a></li>
          <li><a href="<?= url('auth/logout'); ?>">Déconnexion</a></li>
        <?php else: ?>
          <li><a href="<?= url('auth/login'); ?>">Connexion</a></li>
          <li><a href="<?= url('auth/register'); ?>">Inscription</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main class="main-content">
    <?php if (function_exists('flash_messages')) { flash_messages(); } ?>
    <?= $content ?? ''; ?>
  </main>


<footer class="footer">
  <div class="footer-content">
    <p>&copy; <?= date('Y'); ?> <?= APP_NAME; ?>. Tous droits réservés.</p>
    <p>Version <?= APP_VERSION; ?></p>
    <div>
      <a href="#top" class="back-to-top">Remonter</a>
    </div>
  </div>
</footer>

  <script src="<?= url('assets/js/app.js'); ?>"></script>
</body>

</html>
