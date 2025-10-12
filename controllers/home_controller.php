<?php

/**
 * Page d'accueil charge message
 */
function home_index()
{
    load_view_with_layout('home/index', [
        'title'   => 'Accueil',
        'message' => 'Bienvenue sur votre application Online library !',
    ]);
}

/**
 * Page À propos charge content
 */
function home_about()
{
    load_view_with_layout('home/about', [
        'title'   => 'À propos',
        'content' => 'Cette application est un starter kit PHP MVC développé avec une approche procédurale.',
    ]);
}

/**
 * Profil édition du profil par user
 */
function home_profile()
{
    if (!is_logged_in()) { // https://www.php.net/session
        return redirect('auth/login');
    }

    $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0) {
        set_flash('error', "Session invalide. Merci de vous reconnecter.");
        return redirect('auth/login');
    }

    $user = get_user_by_id($user_id);
    if (!$user) {
        set_flash('error', "Utilisateur introuvable.");
        return redirect('auth/login');
    }

    if (is_post()) {
        $posted_token = (string)($_POST['csrf_token'] ?? '');
        if (!verify_csrf_token($posted_token)) {
            set_flash('error', "Jeton CSRF invalide ou expiré.");
            return redirect('home/profile');
        }

        $first_name = clean_input(post('first_name'));
        $last_name  = clean_input(post('last_name'));
        $email      = clean_input(post('email'));
        $password   = (string)post('password');
        if (!$first_name || !$last_name || !$email) {
            set_flash('error', "Prénom, Nom et Email sont obligatoires.");
            return redirect('home/profile');
        }
        if (!validate_email($email)) { //  https://www.php.net/filter_var
            set_flash('error', "Adresse email invalide.");
            return redirect('home/profile');
        }
        if (email_exists($email, $user_id)) { // Unicité email côté model
            set_flash('error', "Cet email est déjà utilisé par un autre compte.");
            return redirect('home/profile');
        }

        if ($password !== '') {
            $re = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$.!%(+;)\*\/\-_{}#~$*%:!,<²°>ù^`|@\[\]*?&]).{8,}$/';
            if (!preg_match($re, $password)) { //  https://www.php.net/preg_match
                set_flash('error', "Mot de passe non sécurisé ! Ajoute : 8+ caractères, 1 minuscule, 1 majuscule, 1 chiffre, 1 spécial.");
                return redirect('home/profile');
            }
        }

        $ok = update_user($user_id, $first_name, $last_name, $email); // maj qui est dans le model
        if ($ok && $password !== '') {
            $ok = update_user_password($user_id, $password) && $ok; //  https://www.php.net/password_hash
        }

        if ($ok) {
            $_SESSION['user_first_name'] = $first_name; //  https://www.php.net/session
            $_SESSION['user_last_name']  = $last_name;
            $_SESSION['user_email']      = $email;
            set_flash('success', 'Profil mis à jour avec succès.');
        } else {
            set_flash('error', "Erreur lors de la mise à jour du profil.");
        }
        return redirect('home/profile');
    }

    load_view_with_layout('home/profile', [
        'title'   => 'Profil',
        'message' => 'Bienvenue sur votre profil',
        'content' => 'Cette application est un starter kit PHP MVC développé avec une approche procédurale.',
        'user'    => $user,
    ]);
}

/**
 * Profil admin édition du profil par admin
 */
function home_profile_admin()
{
    if (!is_logged_in()) { //  https://www.php.net/session
        return redirect('auth/login');
    }
    if (($_SESSION['role'] ?? 'user') !== 'admin') { // Contrôle d’accès 
        set_flash('error', "Accès refusé : droits administrateur requis.");
        return redirect('home/index');
    }

    $editing_id   = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;
    $user_to_edit = $editing_id > 0 ? get_user_by_id($editing_id) : null;

    if (is_post()) {
        $posted_token = (string)($_POST['csrf_token'] ?? '');
        if (!verify_csrf_token($posted_token)) {
            set_flash('error', "Jeton CSRF invalide ou expiré.");
            return redirect('home/profile_admin' . ($editing_id ? '?id=' . (int)$editing_id : ''));
        }

        $first_name = clean_input(post('first_name'));
        $last_name  = clean_input(post('last_name'));
        $email      = clean_input(post('email'));
        $password   = (string)post('password');

        if (!$first_name || !$last_name || !$email) {
            set_flash('error', "Prénom, Nom et Email sont obligatoires.");
            return redirect('home/profile_admin' . ($editing_id ? '?id=' . (int)$editing_id : ''));
        }
        if (!validate_email($email)) { // Validation d'email  https://www.php.net/filter_var
            set_flash('error', "Email invalide.");
            return redirect('home/profile_admin' . ($editing_id ? '?id=' . (int)$editing_id : ''));
        }

        $target_id = $editing_id > 0 ? $editing_id : (int)($_SESSION['user_id'] ?? 0);
        if ($target_id <= 0) {
            set_flash('error', "Utilisateur introuvable.");
            return redirect('home/profile_admin');
        }

        if (email_exists($email, $target_id)) { // mail unique
            set_flash('error', "Cet email est déjà utilisé par un autre compte.");
            return redirect('home/profile_admin' . ($editing_id ? '?id=' . (int)$editing_id : ''));
        }

        if ($password !== '') {
            $re = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$.!%(+;)\*\/\-_{}#~$*%:!,<²°>ù^`|@\[\]*?&]).{8,}$/';
            if (!preg_match($re, $password)) { // Regex et preg_match https://www.php.net/preg_match
                set_flash('error', "Mot de passe non sécurisé ! Ajoute : 8+ caractères, 1 minuscule, 1 majuscule, 1 chiffre, 1 spécial.");
                return redirect('home/profile_admin' . ($editing_id ? '?id=' . (int)$editing_id : ''));
            }
        }

        $ok = update_user($target_id, $first_name, $last_name, $email); // Maj du user
        if ($ok && $password !== '') {
            $ok = update_user_password($target_id, $password) && $ok; // password_hash en model https://www.php.net/password_hash
        }

        if ($ok) {
            if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $target_id) {
                $_SESSION['user_first_name'] = $first_name; // Session https://www.php.net/session
                $_SESSION['user_last_name']  = $last_name;
                $_SESSION['user_email']      = $email;
            }
            set_flash('success', 'Le profil a été mis à jour avec succès !');
        } else {
            set_flash('error', "Erreur lors de la mise à jour.");
        }
        return redirect('home/profile_admin' . ($editing_id ? '?id=' . (int)$editing_id : ''));
    }

    load_view_with_layout('home/profile_admin', [
        'title'   => 'Profil (admin)',
        'message' => 'Bienvenue sur votre profil',
        'content' => 'Cette application est un starter kit PHP MVC développé avec une approche procédurale.',
        'user'    => $user_to_edit,
    ]);
}

/**
 * Dashboard admin avec les KPIs et les aperçus avce les dernières infos
 */
function home_admin()
{
    loan_mark_overdue_now(); // Mise à jour status prêts en model

    $count_films       = (int)count_medias_movie();
    $count_livres      = (int)count_medias_book();
    $count_jeux        = (int)count_medias_game();
    $loans_in_progress = (int)count_loans_in_progress();
    $loans_late        = (int)count_loans_late();

    $users_preview_per_page  = 6;
    $medias_preview_per_page = 6;
    $loans_preview_per_page  = 6;

    $users_total   = (int)count_users();
    $users_preview = get_all_users($users_preview_per_page, 0);

    $new_users_stats = get_new_users_stats(null, 0);

    $medias_total   = (int)count_medias();
    $medias_preview = media_get_all2($medias_preview_per_page, 0);

    $loans_total   = (int)count_loans();
    $loans_preview = loan_get_all($loans_preview_per_page, 0);

    load_view_with_layout('home/admin', [
        'title'               => 'Admin',
        'message'             => 'Bienvenue sur votre dashboard admin',
        'count_films'         => $count_films,
        'count_livres'        => $count_livres,
        'count_jeux'          => $count_jeux,
        'loans_in_progress'   => $loans_in_progress,
        'loans_late'          => $loans_late,
        'users_total'         => $users_total,
        'users_preview'       => $users_preview,
        'new_users_stats'     => $new_users_stats,
        'medias_total'        => $medias_total,
        'medias_preview'      => $medias_preview,
        'loans_total'         => $loans_total,
        'loans_preview'       => $loans_preview,
        'users_preview_pp'    => $users_preview_per_page,
        'medias_preview_pp'   => $medias_preview_per_page,
        'loans_preview_pp'    => $loans_preview_per_page,
    ]);
}

/**
 * Liste des utilisateurs avec pagination et filtre
 */
function home_users()
{
    loan_mark_overdue_now();

    $count_films       = (int)count_medias_movie();
    $count_livres      = (int)count_medias_book();
    $count_jeux        = (int)count_medias_game();
    $loans_in_progress = (int)count_loans_in_progress();
    $loans_late        = (int)count_loans_late();

    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page     = (int)($_GET['per_page'] ?? 10);
    $per_page     = in_array($per_page, [5, 10, 20, 50, 100], true) ? $per_page : 10;

    $q    = trim((string)($_GET['q'] ?? ''));
    $sort = strtolower((string)($_GET['sort'] ?? 'created_at'));
    $dir  = strtolower((string)($_GET['dir']  ?? 'desc'));

    $nb_users = (int)user_count_filtered($q);
    $pages    = max(1, (int)ceil($nb_users / $per_page)); // ceil() https://www.php.net/ceil
    $first    = ($current_page - 1) * $per_page;

    $users = user_search_paginated($q, $sort, $dir, $per_page, $first);

    load_view_with_layout('home/users', [
        'title'             => 'Utilisateurs',
        'message'           => 'Liste des utilisateurs',
        'count_films'       => $count_films,
        'count_livres'      => $count_livres,
        'count_jeux'        => $count_jeux,
        'loans_in_progress' => $loans_in_progress,
        'loans_late'        => $loans_late,
        'users'             => $users,
        'current_page'      => $current_page,
        'pages'             => $pages,
        'per_page'          => $per_page,
        'sort'              => $sort,
        'dir'               => $dir,
        'filters'           => ['q' => $q],
    ]);
}

/**
 * Stats (globales + par utilisateur si id fourni)
 * - Agrégats côté model
 * - Gestion "utilisateur introuvable"
 */
function home_stats()
{
    loan_mark_overdue_now();

    $total_movies      = (int)count_medias_movie();
    $total_books       = (int)count_medias_book();
    $total_games       = (int)count_medias_game();

    $loans_in_progress = (int)count_loans_in_progress();
    $loans_late        = (int)count_loans_late();

    $current_user_id = isset($_GET['id'])   ? max(0, (int)$_GET['id'])   : 0;
    $current_page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $pages           = 1;

    $user_stats = null;
    if ($current_user_id > 0) {
        $user_stats = loans_percentage_by_category_by_id($current_user_id);
        if ($user_stats === null) {
            $user_stats = [
                'user_id'         => $current_user_id,
                'first_name'      => '',
                'last_name'       => '',
                'total_loans'     => 0,
                'movies_percent'  => 0,
                'books_percent'   => 0,
                'games_percent'   => 0,
            ];
            set_flash('error', "Utilisateur #$current_user_id introuvable.");
        }
    }

    load_view_with_layout('home/stats', [
        'title'             => 'Stats',
        'message'           => 'Bienvenue sur la page stats',
        'content'           => 'Cette application est un starter kit PHP MVC développé avec une approche procédurale.',
        'total_movies'      => $total_movies,
        'total_books'       => $total_books,
        'total_games'       => $total_games,
        'loans_in_progress' => $loans_in_progress,
        'loans_late'        => $loans_late,
        'current_user_id'   => $current_user_id,
        'current_page'      => $current_page,
        'pages'             => $pages,
        'user_stats'        => $user_stats,
    ]);
}

/**
 * Confirmation suppression utilisateur par l'admin
 */
function home_confirm_delete_user()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        set_flash('error', "Accès refusé : droits administrateur requis.");
        return redirect('home/users');
    }

    $id = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;
    if ($id <= 0) {
        set_flash('error', "Requête invalide.");
        return redirect('home/users');
    }

    $user = get_user_by_id($id);
    if (!$user) {
        set_flash('error', "Utilisateur #$id introuvable.");
        return redirect('home/users');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); //  random_bytes() https://www.php.net/random_bytes
    }
    $csrf_token = $_SESSION['csrf_token'];

    load_view_with_layout('home/confirm_delete_user', [
        'title'      => 'Confirmation de suppression',
        'user'       => $user,
        'id'         => $id,
        'csrf_token' => $csrf_token,
    ]);
}

/**
 * Suppression utilisateur pour l'admin via post
 */
function home_delete_user()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        set_flash('error', "Accès refusé : droits administrateur requis.");
        return redirect('home/users');
    }
    if (!is_post()) {
        return redirect('home/users');
    }

    $id = isset($_POST['id']) ? max(0, (int)$_POST['id']) : 0;
    if ($id <= 0) {
        set_flash('error', "Requête invalide.");
        return redirect('home/users');
    }

    $posted_token = (string)($_POST['csrf_token'] ?? '');
    if (!verify_csrf_token($posted_token)) {
        set_flash('error', "Jeton CSRF invalide ou expiré.");
        return redirect('home/confirm_delete_user?id=' . $id);
    }

    if ((int)($_SESSION['user_id'] ?? 0) === $id) {
        set_flash('error', "Vous ne pouvez pas supprimer votre propre compte.");
        return redirect('home/users');
    }

    if (!get_user_by_id($id)) {
        set_flash('error', "Utilisateur #$id introuvable.");
        return redirect('home/users');
    }

    $ok = delete_user($id); // Suppression dzns le modle
    if ($ok) {
        set_flash('success', "Utilisateur #$id supprimé.");
        unset($_SESSION['csrf_token']);
    } else {
        set_flash('error', "Suppression impossible (contrainte DB ou erreur).");
    }

    return redirect('home/users');
}
