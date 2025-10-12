<div class="hero hero-modern hero-landing">
  <div class="container">


    <div class="landing-grid">
      <!-- Colonne gauche -->
      <div class="landing-left">
        <h1 class="landing-title">
          Media <span class="accent">Online</span>
        </h1>
        <p class="landing-text">
          Découvrez, empruntez et gérez vos médias en ligne. Une interface fluide et moderne pour tout faire en quelques clics.
        </p>
        <?php if (is_logged_in()): ?>
          <p class="welcome-message">
            <i class="fas fa-user"></i>
            Bienvenue, <?php e($_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']); ?> !
          </p>
        <?php endif; ?>
        <div class="hero-buttons">
          <?php if (!is_logged_in()): ?>
            <a href="<?= url('auth/register'); ?>" class="btn btn-primary">Commencer</a>
          <?php endif; ?>
          <a href="<?= url('home/about'); ?>" class="btn btn-link-about">À propos</a>
        </div>
      </div>

      <!-- Colonne droite  -->
      <div class="landing-right">
        <div class="shape-blob"></div>

        <!-- Table  -->
        <div class="desk">
          <div class="open-book">
            <div class="page left"></div>
            <div class="page right"></div>
          </div>
          <div class="glasses">
            <span></span><span></span>
          </div>
        </div>

        <!-- Téléphone avec icônes -->
        <div class="phone">
          <div class="phone-notch"></div>
        </div>
      </div>

      <!-- Nuages décoratifs -->
      <div class="cloud c1"></div>
      <div class="cloud c2"></div>
    </div>
  </div>
</div>
</div>
<!--  cartes -->
<section class="landing-tiles">
  <div class="container">
    <ul class="landing-tiles-grid">
      <li class="tile-card">
        <div class="tile-icon"><i class="fas fa-cog"></i></div>
        <h3>Gestion simplifiée</h3>
        <p>Ajoutez, gérez et suivez vos prêts en un clin d’œil.</p>
      </li>
      <li class="tile-card">
        <div class="tile-icon"><i class="fas fa-play"></i></div>
        <h3>Médias variés</h3>
        <p>Films, livres, jeux — tout au même endroit.</p>
      </li>
      <li class="tile-card">
        <div class="tile-icon"><i class="fas fa-pencil-alt"></i></div>
        <h3>Suivi précis</h3>
        <p>Historique, retards, retours : tout est clair.</p>
      </li>
    </ul>
  </div>
</section>

<!-- SECTION : grand visuel + texte -->
<section class="reader-section">
  <div class="container reader-grid">
    <div class="reader-copy">
      <h2 class="reader-title">La lecture pour tous</h2>
      <p class="reader-text">
        Découvrez la médiathèque en ligne : un catalogue riche, une interface
        moderne et des emprunts gérés en toute simplicité. Reprenez vos lectures
        là où vous les avez laissées et explorez de nouveaux horizons.
      </p>
      <?php if (is_logged_in()): ?>
        <a href="<?= url('media/index'); ?>" class="btn btn-primary">Voir le catalogue</a>
      <?php endif; ?>
    </div>

    <figure class="reader-illustration reader-illustration--img" aria-hidden="true">
      <img src="<?= url('assets/images/library.svg'); ?>" alt="" />
    </figure>


  </div>
</section>