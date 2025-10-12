# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [1.0.0] - 2024-01-XX

### Ajouté
- Architecture MVC procédurale complète
- Système de routing simple et efficace
- Gestion de base de données avec PDO et fonctions utilitaires
- Système d'authentification (connexion/inscription)
- Protection CSRF intégrée
- Système de templating avec layouts
- Messages flash pour les notifications
- Validation de formulaires côté serveur et client
- Design responsive avec CSS moderne
- Page d'erreur 404 personnalisée
- Fonctions utilitaires pour la sécurité et la validation
- Documentation complète avec exemples
- Structure de base de données avec schéma SQL
- Fichier d'amorçage pour les tests
- Configuration Apache avec .htaccess
- Animations et interactions JavaScript

### Sécurité
- Hachage sécurisé des mots de passe
- Protection contre l'injection SQL avec requêtes préparées
- Échappement automatique des données d'affichage
- Validation et nettoyage des données utilisateur
- Gestion sécurisée des sessions

### Structure
- `config/` - Configuration de l'application
- `controllers/` - Contrôleurs MVC
- `models/` - Modèles de données
- `views/` - Vues et templates
- `core/` - Système de routing et fonctions principales
- `includes/` - Fonctions utilitaires
- `public/` - Point d'entrée et assets statiques
- `database/` - Scripts SQL et schémas

### Fonctionnalités
- Page d'accueil avec présentation des fonctionnalités
- Page "À propos" avec informations techniques
- Formulaire de contact fonctionnel
- Système de connexion/inscription
- Gestion des erreurs avec page 404
- Interface responsive et moderne 