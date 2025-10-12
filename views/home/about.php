<div class="page-header">
    <div class="container">
        <h1><?php e($title ?? 'À propos de la Médiathèque'); ?></h1>
    </div>
</div>

<section class="content">
    <div class="container">
        <div class="content-grid">
            <div class="content-main">
                <h2>Notre mission</h2>
                <p>
                    La Médiathèque met à disposition des <strong>livres</strong>, <strong>films</strong> et <strong>jeux</strong>, 
                    accessibles à tous. Cette application vous permet de <strong>chercher</strong>, <strong>emprunter</strong> 
                    et <strong>suivre</strong> vos prêts en ligne, simplement.
                </p>

                <h3>Ce que vous pouvez faire</h3>
                <ul>
                    <li>Parcourir le catalogue par catégorie (livres, films, jeux)</li>
                    <li>Voir les fiches détaillées de chaque média</li>
                    <li>Créer un compte et vous connecter en toute sécurité</li>
                    <li>Emprunter un média et suivre vos emprunts en cours</li>
                    <li>Recevoir des <em>messages flash</em> pour vos actions (succès/erreurs)</li>
                </ul>

                <h3>Comment l’application fonctionne</h3>
                <ul>
                    <li><strong>Modèles (Models)</strong> : accès aux données (PDO) et règles métiers</li>
                    <li><strong>Vues (Views)</strong> : pages et gabarits HTML/CSS</li>
                    <li><strong>Contrôleurs (Controllers)</strong> : logique et navigation</li>
                </ul>

                <h3>Points techniques</h3>
                <ul class="about-list-two">
                    <li>Architecture MVC en PHP procédural</li>
                    <li>Routing simple</li>
                    <li>Templates avec <code>layouts</code></li>
                    <li>Protection CSRF &amp; validation de formulaires</li>
                    <li>Authentification &amp; messages flash</li>
                </ul>

                <h3>Besoin d’aide ?</h3>
                <p>
                    Signalez-le à l’accueil de la Médiathèque, une page contact est en cours de développement.
                </p>
            </div>
        </div>
    </div>
</section>
