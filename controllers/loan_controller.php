<?php

/**
 * Emprunter un média
 */
function loan_borrow()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }

    if (!is_post()) {
        set_flash('error', "Requête invalide.");
        return redirect('media/index');
    }

    $media_id = (int)($_POST['media_id'] ?? 0);
    if ($media_id <= 0) {
        set_flash('error', "Média invalide.");
        return redirect('media/index');
    }

    // CSRF (
    if (isset($_POST['csrf_token']) && !verify_csrf_token($_POST['csrf_token'])) {
        set_flash('error', "Token CSRF invalide.");
        return redirect('media/index');
    }

    $user_id = (int)current_user_id();
    $res     = loan_create($user_id, $media_id);

    if (empty($res['ok'])) {
        set_flash('error', $res['error'] ?? "Erreur d'emprunt.");
        return redirect('media/index');
    }

    set_flash('success', "Emprunt enregistré. Date de retour prévue : " . ($res['due'] ?? ''));
    return redirect('loan/history');
}


/**
 * Historique d'emprunt
 */
function loan_history()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }

    loan_mark_overdue_now();

    // KPIs
    $count_films       = (int)count_medias_movie();
    $count_livres      = (int)count_medias_book();
    $count_jeux        = (int)count_medias_game();
    $loans_in_progress = (int)count_loans_in_progress();
    $loans_late        = (int)count_loans_late();

    // Rôle
    $role     = $_SESSION['role'] ?? 'user';
    $is_admin = ($role === 'admin');

    // Filtres
    $q      = trim((string)($_GET['q'] ?? ''));              // trim () : https://www.php.net/trim
    $status = trim((string)($_GET['status'] ?? ''));

    // Tri
    $sort = strtolower((string)($_GET['sort'] ?? 'date_borrow')); // strtolower  (): https://www.php.net/strtolower
    $dir  = strtolower((string)($_GET['dir']  ?? 'desc'));
    if (!in_array($dir, ['asc', 'desc'], true)) {
        $dir = 'desc';
    } // in_array () : https://www.php.net/in_array

    // Pagination
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page     = (int)($_GET['per_page'] ?? 10);
    $per_page     = in_array($per_page, [5, 10, 20, 50, 100], true) ? $per_page : 10;

    if ($is_admin) {
        // Comptage + bornage pages
        $total = loan_history_count_admin_filtered($q, $status);
        $pages = max(1, (int)ceil($total / $per_page));      // ceil() https://www.php.net/ceil
        if ($current_page > $pages) {
            return redirect('loan/history?' . http_build_query([ // http_build_query () : https://www.php.net/http_build_query
                'q'        => $q,
                'status'   => $status,
                'per_page' => $per_page,
                'page'     => $pages
            ]));
        }

        // Liste paginée
        $first = ($current_page - 1) * $per_page;
        $loans = loan_history_search_admin_paginated($q, $status, $per_page, $first);
    } else {
        $user_id = (int)current_user_id();

        $total = loan_history_count_user_filtered($user_id, $q, $status);
        $pages = max(1, (int)ceil($total / $per_page));
        if ($current_page > $pages) {
            return redirect('loan/history?' . http_build_query([
                'q'        => $q,
                'status'   => $status,
                'per_page' => $per_page,
                'page'     => $pages
            ]));
        }

        $first = ($current_page - 1) * $per_page;
        $loans = loan_history_search_user_paginated($user_id, $q, $status, $per_page, $first);
    }

    load_view_with_layout('loan/history', [
        'title'             => 'Historique des emprunts',
        'loans'             => $loans,
        'is_admin'          => $is_admin,

        // KPIs
        'count_films'       => $count_films,
        'count_livres'      => $count_livres,
        'count_jeux'        => $count_jeux,
        'loans_in_progress' => $loans_in_progress,
        'loans_late'        => $loans_late,

        // Pagination
        'current_page'      => $current_page,
        'pages'             => $pages,
        'total'             => $total,
        'per_page'          => $per_page,

        // Tri
        'sort'              => $sort,
        'dir'               => $dir,

        // Filtres
        'filters'           => ['q' => $q, 'status' => $status],
    ]);
}


/**
 * Retour utilisateur
 */
function loan_return()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }

    $loan_id = (int)($_GET['loan_id'] ?? 0);
    if ($loan_id <= 0) {
        set_flash('error', "Emprunt invalide.");
        return redirect('loan/history');
    }

    $res = loan_return_service($loan_id);

    if (!empty($res['ok'])) {
        set_flash('success', "Retour enregistré.");
    } else {
        set_flash('error', $res['error'] ?? "Erreur lors du retour.");
    }

    return redirect('loan/history');
}


/**
 * Retour forcé (admin)
 */
function loan_force_return()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        set_flash('error', "Accès refusé : droits administrateur requis.");
        return redirect('loan/history');
    }

    $loan_id = (int)($_GET['loan_id'] ?? 0);
    if ($loan_id <= 0) {
        set_flash('error', "Emprunt invalide.");
        return redirect('loan/history');
    }

    $res = loan_force_return_service($loan_id);

    if (!empty($res['ok'])) {
        set_flash('success', "Retour forcé enregistré.");
    } else {
        set_flash('error', $res['error'] ?? "Erreur lors du retour forcé.");
    }

    return redirect('loan/history');
}
