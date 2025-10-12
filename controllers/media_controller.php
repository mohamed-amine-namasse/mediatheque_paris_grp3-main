<?php

/**
 * Catalogue listé par type avec filtre
 */
function media_index()
{
    $role     = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    $is_admin = ($role === 'admin');

    // Nettoyage simple des filtres (trim) – https://www.php.net/trim
    $q              = trim((string)($_GET['q'] ?? ''));
    $type           = trim((string)($_GET['type'] ?? ''));
    $genre          = trim((string)($_GET['genre'] ?? ''));
    $available      = isset($_GET['available']) && $_GET['available'] !== '' ? 1 : 0;
    $classification = trim((string)($_GET['classification'] ?? ''));
    $plateforme     = trim((string)($_GET['plateforme'] ?? ''));
    $age_min        = (string)($_GET['age_min'] ?? '');
    $year_min       = (string)($_GET['year_min'] ?? '');
    $year_max       = (string)($_GET['year_max'] ?? '');
    $order          = trim((string)($_GET['order'] ?? ''));

    $filters = [
        'q'               => $q,
        'type'            => $type,
        'genre'           => $genre,
        'available'       => $available,
        'classification'  => $classification,
        'plateforme'      => $plateforme,
        'age_min'         => $age_min,
        'year_min'        => $year_min,
        'year_max'        => $year_max,
        'order'           => $order,
    ];

    // Regroupe par type si aucun type n'est sélectionné
    $group_mode = ($type === '');

    if ($group_mode) {
        $section_limit  = 5; // pagination locale par section
        $types          = ['film', 'jeu', 'livre'];
        $groups         = [];
        $totals_by_type = [];

        foreach ($types as $t) {
            $f = $filters;
            $f['type']  = $t;
            $f['order'] = $filters['order'] ?: 'title_asc';

            $totals_by_type[$t] = media_count_search($f); // Model
            $groups[$t]         = media_search($f, $section_limit, 0); // Model
        }

        load_view_with_layout('media/index', [
            'title'            => 'Catalogue des médias',
            'group_mode'       => true,
            'groups'           => $groups,
            'totals_by_type'   => $totals_by_type,
            'section_limit'    => $section_limit,
            'is_admin'         => $is_admin,

            'genres'           => media_allowed_genres(), // Model 
            'classifs'         => ['Tous publics', '-12', '-16', '-18'],
            'plateformes'      => ['PC', 'PlayStation', 'Xbox', 'Nintendo', 'Mobile'],
            'ages'             => [3, 7, 12, 16, 18],

            'filters'          => $filters,
            'medias'           => [],
            'pages'            => 1,
            'total'            => array_sum($totals_by_type), // https://www.php.net/array_sum
        ]);
        return;
    }

    // Listing classique 
    $limit = 12;
    $page  = max(1, (int)($_GET['page'] ?? 1)); // https://www.php.net/max

    $total  = media_count_search($filters); // Model
    $pages  = max(1, (int)ceil($total / $limit)); // https://www.php.net/ceil
    if ($page > $pages) $page = $pages;
    $offset = ($page - 1) * $limit;

    $medias = media_search($filters, $limit, $offset); // Model

    load_view_with_layout('media/index', [
        'title'     => 'Catalogue des médias',
        'group_mode' => false,
        'medias'    => $medias,
        'page'      => $page,
        'pages'     => $pages,
        'limit'     => $limit,
        'total'     => $total,
        'is_admin'  => $is_admin,

        'genres'      => media_allowed_genres(), // Model
        'classifs'    => ['Tous publics', '-12', '-16', '-18'],
        'plateformes' => ['PC', 'PlayStation', 'Xbox', 'Nintendo', 'Mobile'],
        'ages'        => [3, 7, 12, 16, 18],

        'filters'     => $filters,
    ]);
}

/**
 * Ajout d’un média pour l'admin et upload cover via $_FILES avec media_handle_cover_upload https://www.php.net/features.file-upload
 */
function media_store_from_catalog()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    if ($role !== 'admin') {
        set_flash('error', "Accès refusé.");
        return redirect('media/index');
    }
    if (!is_post()) {
        set_flash('error', "Requête invalide.");
        return redirect('media/index');
    }
    if (isset($_POST['csrf_token']) && !verify_csrf_token(post('csrf_token'))) {
        set_flash('error', "Token CSRF invalide.");
        return redirect('media/index');
    }

    // Champs communs https://www.php.net/trim
    $type        = trim(post('type', ''));
    $title       = trim(post('title', ''));
    $genre       = trim(post('genre', ''));
    $description = trim(post('description', ''));
    $stock       = (int)post('stock', 1);

    // Selon type
    $author            = trim(post('author', '')); // livre
    $isbn              = preg_replace('/[^0-9]/', '', (string)post('isbn', '')); // https://www.php.net/preg_replace
    $pages             = (int)post('pages', 0);
    $publication_year  = (string)post('publication_year', '');

    // Éditeur 
    $editor_book = trim(post('editor_book', ''));
    $editor_game = trim(post('editor_game', ''));
    $editor      = ($type === 'livre') ? $editor_book : (($type === 'jeu') ? $editor_game : '');

    $producer       = trim(post('producer', ''));
    $duration_min   = (int)post('duration_min', 0);
    $release_year   = (string)post('release_year', '');
    $classification = (string)post('classification', '');

    $plateforme = (string)post('plateforme', '');
    $age_min    = (int)post('age_min', 0);

    // Validations années – date : https://www.php.net/date
    $errors   = [];
    $year_now = (int)date('Y');

    if (!in_array($type, ['livre', 'film', 'jeu'], true)) $errors[] = "Type invalide (livre/film/jeu)."; // https://www.php.net/in_array
    if ($title === '' || mb_strlen($title) > 200)        $errors[] = "Titre requis (1–200).";           // https://www.php.net/mb_strlen
    if (!in_array($genre, media_allowed_genres(), true)) $errors[] = "Genre invalide (liste prédéfinie).";
    if ($stock < 1)                                      $errors[] = "Stock doit être un entier ≥ 1.";

    if ($type === 'livre') {
        if ($author === '' || mb_strlen($author) < 2 || mb_strlen($author) > 100)  $errors[] = "Auteur requis (2–100).";
        if ($editor === '' || mb_strlen($editor) < 2 || mb_strlen($editor) > 100)  $errors[] = "Éditeur requis (2–100).";
        if ($isbn !== '' && !preg_match('/^\d{10}(\d{3})?$/', $isbn))              $errors[] = "ISBN invalide (10 ou 13 chiffres)."; 
        if ($pages < 1 || $pages > 9999)                                          $errors[] = "Pages doit être entre 1 et 9999.";
        $an = (int)$publication_year;
        if ($an < 1900 || $an > $year_now)                                        $errors[] = "Année de publication entre 1900 et $year_now.";
    }

    if ($type === 'film') {
        if ($producer === '' || mb_strlen($producer) < 2 || mb_strlen($producer) > 100) $errors[] = "Réalisateur requis (2–100).";
        if ($duration_min < 1 || $duration_min > 999)                                   $errors[] = "Durée doit être entre 1 et 999 minutes.";
        $an = (int)$release_year;
        if ($an < 1900 || $an > $year_now)                                              $errors[] = "Année de sortie entre 1900 et $year_now.";
        if (!in_array($classification, ['Tous publics', '-12', '-16', '-18'], true))       $errors[] = "Classification (âge autorisé) obligatoire.";
    }

    if ($type === 'jeu') {
        if ($editor === '' || mb_strlen($editor) < 2 || mb_strlen($editor) > 100)             $errors[] = "Éditeur requis (2–100).";
        if (!in_array($plateforme, ['PC', 'PlayStation', 'Xbox', 'Nintendo', 'Mobile'], true))    $errors[] = "Plateforme obligatoire.";
        if (!in_array($age_min, [3, 7, 12, 16, 18], true))                                        $errors[] = "Âge minimum obligatoire (3,7,12,16,18).";
    }

    // Upload cover https://www.php.net/features.file-upload
    $cover_path = null;
    if (!empty($_FILES['cover']['name'])) {
        $u = media_handle_cover_upload('cover'); // Model de service d’upload
        if (!empty($u['error'])) $errors[] = $u['error'];
        else $cover_path = $u['path'];
    }

    // ISBN unique 
    if ($type === 'livre' && $isbn !== '') {
        $exists = db_select_one("SELECT id FROM medias WHERE isbn = ? LIMIT 1", [$isbn]); 
        if ($exists) $errors[] = "ISBN déjà présent dans la base.";
    }

    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        return redirect('media/index');
    }

    // Insert du model
    $ok = media_create([
        'type'               => $type,
        'title'              => $title,
        'genre'              => $genre,
        'description'        => $description ?: null,
        'stock'              => $stock,
        'cover_path'         => $cover_path,
        // LIVRE
        'author'             => $type === 'livre' ? $author : null,
        'isbn'               => $type === 'livre' ? ($isbn ?: null) : null,
        'pages'              => $type === 'livre' ? $pages : null,
        'publication_year'   => $type === 'livre' ? $publication_year : null,
        // ÉDITEUR (livre/jeu)
        'editor'             => in_array($type, ['livre', 'jeu'], true) ? $editor : null,
        // FILM
        'producer'           => $type === 'film'  ? $producer : null,
        'duration_min'       => $type === 'film'  ? $duration_min : null,
        'classification'     => $type === 'film'  ? $classification : null,
        'release_year'       => $type === 'film'  ? $release_year : null,
        // JEU
        'plateforme'         => $type === 'jeu'   ? $plateforme : null,
        'age_min'            => $type === 'jeu'   ? $age_min : null,
    ]);

    $ok ? set_flash('success', "Média ajouté avec succès.")
        : set_flash('error',   "Erreur lors de l'ajout du média.");

    return redirect('media/index');
}

/**
 * Détail d’un média pour l'admin
 */
function media_details_admin($id = null)
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }

    $id    = (int)$id;
    $media = media_get_by_id($id); // Model

    $available = $media ? ((int)$media['stock'] > 0) : false;
    $user_id   = current_user_id();
    $has_same  = ($user_id && $media) ? loan_has_active_same_media($user_id, $media['id']) : false; // Model
    $at_limit  = $user_id ? (loan_count_active_by_user($user_id) >= 3) : true; // Model

    $role     = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    $is_admin = ($role === 'admin');

    load_view_with_layout('media/details_admin', [
        'title'                      => 'Détails du média',
        'media'                      => $media,
        'available'                  => $available,
        'user_has_active_same_media' => $has_same,
        'user_at_global_limit'       => $at_limit,
        'is_admin'                   => $is_admin,
    ]);
}

/**
 * Détail d’un média user ou admin
 */
function media_detail($id = null)
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }

    $id    = (int)$id;
    $media = media_get_by_id($id); // Model
    if (!$media) {
        set_flash('error', "Média introuvable.");
        return redirect('media/index');
    }

    $available = (int)$media['stock'] > 0;
    $user_id   = current_user_id();
    $has_same  = $user_id ? loan_has_active_same_media($user_id, $media['id']) : false; // Model
    $at_limit  = $user_id ? (loan_count_active_by_user($user_id) >= 3) : true;          // Model

    $role     = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    $is_admin = ($role === 'admin');

    // Construction d’un tableau d’attributs 
    $fields = [];
    $fields['Type']             = $media['type'] ?? '-';
    $fields['Genre']            = $media['genre'] ?? '-';
    if (!empty($media['description'])) $fields['Description'] = $media['description'];
    $fields['Stock disponible'] = (string)(int)$media['stock'];
    if (isset($media['total_copies'])) $fields['Exemplaires totaux'] = (string)(int)$media['total_copies'];
    if ($is_admin && !empty($media['created_at'])) $fields['Ajouté le'] = $media['created_at'];

    if (($media['type'] ?? '') === 'livre') {
        $fields['Auteur']               = $media['author'] ?? '-';
        $fields['Éditeur']              = $media['editor'] ?? '-';
        $fields['ISBN']                 = $media['isbn'] ?? '-';
        $fields['Pages']                = $media['pages'] ?? '-';
        $fields['Année de publication'] = $media['publication_year'] ?? '-';
    } elseif (($media['type'] ?? '') === 'film') {
        $fields['Réalisateur']          = $media['producer'] ?? '-';
        $fields['Durée (min)']          = $media['duration_min'] ?? '-';
        $fields['Âge autorisé']         = $media['classification'] ?? '-';
        $fields['Année de sortie']      = $media['release_year'] ?? '-';
    } elseif (($media['type'] ?? '') === 'jeu') {
        $fields['Éditeur']              = $media['editor'] ?? '-';
        $fields['Plateforme']           = $media['plateforme'] ?? '-';
        $fields['Âge minimum']          = $media['age_min'] ?? '-';
    }

    load_view_with_layout('media/detail', [
        'title'                      => 'Détails du média',
        'media'                      => $media,
        'available'                  => $available,
        'user_has_active_same_media' => $has_same,
        'user_at_global_limit'       => $at_limit,
        'is_admin'                   => $is_admin,
        'fields'                     => $fields,
    ]);
}

/**
 * Supprimer la cover d'un media
 */
function media_delete_cover()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    if (($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user') !== 'admin') {
        set_flash('error', "Accès refusé.");
        return redirect('media/index');
    }

    if (!is_post()) {
        set_flash('error', "Requête invalide.");
        return redirect('media/index');
    }
    if (!verify_csrf_token(post('csrf_token'))) {
        set_flash('error', "Token CSRF invalide.");
        return redirect('media/index');
    }

    $media_id = (int)post('media_id', 0);
    if ($media_id <= 0) {
        set_flash('error', "Média invalide.");
        return redirect('media/index');
    }

    $media = media_get_by_id($media_id); // Model
    if (!$media) {
        set_flash('error', "Média introuvable.");
        return redirect('media/index');
    }

    $old = $media['cover_path'] ?? null;

    // devient null en base de donnée
    $ok = db_execute("UPDATE medias SET cover_path = NULL WHERE id = ?", [$media_id]);
    if (!$ok) {
        set_flash('error', "Échec de suppression de l’image.");
        return redirect('media/detail/' . $media_id);
    }

    // Suppression du fichier local si le chemin pointe chez nous
    if ($old) {
        $prefix = rtrim(url('uploads/covers/'), '/'); // https://www.php.net/rtrim
        if (strpos($old, $prefix) === 0) {            // https://www.php.net/strpos
            $fs_path = rtrim(PUBLIC_PATH, '/\\') . str_replace(url(''), '', $old); // https://www.php.net/str_replace
            @unlink($fs_path); // https://www.php.net/unlink
        }
    }

    set_flash('success', "Couverture supprimée.");
    return redirect('media/detail/' . $media_id);
}

/**
 * Mise à jour d’un média admin et remplacement de cover quasiment la même logique que l'upload
 */
function media_update_controller()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    if ($role !== 'admin') {
        set_flash('error', "Accès refusé.");
        return redirect('media/index');
    }
    if (!is_post()) {
        set_flash('error', "Requête invalide.");
        return redirect('media/index');
    }
    if (isset($_POST['csrf_token']) && !verify_csrf_token(post('csrf_token'))) {
        set_flash('error', "Token CSRF invalide.");
        return redirect('media/index');
    }

    $id = (int)post('id', 0);
    if ($id <= 0) {
        set_flash('error', "Média invalide.");
        return redirect('media/index');
    }

    $current = media_get_by_id($id); // Model
    if (!$current) {
        set_flash('error', "Média introuvable.");
        return redirect('media/index');
    }

    // Champs communs 
    $type        = trim(post('type', $current['type']));
    $title       = trim(post('title', $current['title']));
    $genre       = trim(post('genre', $current['genre']));
    $description = trim(post('description', $current['description'] ?? ''));
    $stock       = (int)post('stock', (int)$current['stock']);

    // Spécifiques
    $author           = trim(post('author', $current['author'] ?? ''));
    $isbn             = preg_replace('/[^0-9]/', '', (string)post('isbn', $current['isbn'] ?? '')); 
    $pages            = (int)post('pages', (int)($current['pages'] ?? 0));
    $publication_year = (string)post('publication_year', (string)($current['publication_year'] ?? ''));

    $editor_book = trim(post('editor_book', ''));
    $editor_game = trim(post('editor_game', ''));
    $editor = ($type === 'livre')
        ? ($editor_book !== '' ? $editor_book : ($current['editor'] ?? ''))
        : (($type === 'jeu')
            ? ($editor_game !== '' ? $editor_game : ($current['editor'] ?? ''))
            : '');

    $producer       = trim(post('producer', $current['producer'] ?? ''));
    $duration_min   = (int)post('duration_min', (int)($current['duration_min'] ?? 0));
    $release_year   = (string)post('release_year', (string)($current['release_year'] ?? ''));
    $classification = (string)post('classification', (string)($current['classification'] ?? ''));

    $plateforme = (string)post('plateforme', (string)($current['plateforme'] ?? ''));
    $age_min    = (int)post('age_min', (int)($current['age_min'] ?? 0));

    // Validation année via date https://www.php.net/date
    $errors   = [];
    $year_now = (int)date('Y');

    if (!in_array($type, ['livre', 'film', 'jeu'], true)) $errors[] = "Type invalide (livre/film/jeu)."; // https://www.php.net/in_array
    if ($title === '' || mb_strlen($title) > 200)        $errors[] = "Titre requis (1–200).";           // https://www.php.net/mb_strlen
    if (!in_array($genre, media_allowed_genres(), true)) $errors[] = "Genre invalide (liste prédéfinie).";
    if ($stock < 0)                                      $errors[] = "Stock doit être ≥ 0.";

    if ($type === 'livre') {
        if ($author === '' || mb_strlen($author) < 2 || mb_strlen($author) > 100)  $errors[] = "Auteur requis (2–100).";
        if ($editor === '' || mb_strlen($editor) < 2 || mb_strlen($editor) > 100)  $errors[] = "Éditeur requis (2–100).";
        if ($isbn !== '' && !preg_match('/^\d{10}(\d{3})?$/', $isbn))              $errors[] = "ISBN invalide (10 ou 13 chiffres)."; // https://www.php.net/preg_match
        if ($pages < 1 || $pages > 9999)                                          $errors[] = "Pages doit être entre 1 et 9999.";
        $an = (int)$publication_year;
        if ($publication_year !== '' && ($an < 1900 || $an > $year_now))          $errors[] = "Année de publication entre 1900 et $year_now.";
    }

    if ($type === 'film') {
        if ($producer === '' || mb_strlen($producer) < 2 || mb_strlen($producer) > 100) $errors[] = "Réalisateur requis (2–100).";
        if ($duration_min < 1 || $duration_min > 999)                                   $errors[] = "Durée doit être entre 1 et 999 minutes.";
        $anr = (int)$release_year;
        if ($release_year === '' || $anr < 1900 || $anr > $year_now)                    $errors[] = "Année de sortie entre 1900 et $year_now.";
        if (!in_array($classification, ['Tous publics', '-12', '-16', '-18'], true))       $errors[] = "Classification (âge autorisé) obligatoire.";
    }

    if ($type === 'jeu') {
        if ($editor === '' || mb_strlen($editor) < 2 || mb_strlen($editor) > 100)             $errors[] = "Éditeur requis (2–100).";
        if (!in_array($plateforme, ['PC', 'PlayStation', 'Xbox', 'Nintendo', 'Mobile'], true))    $errors[] = "Plateforme obligatoire.";
        if (!in_array($age_min, [3, 7, 12, 16, 18], true))                                        $errors[] = "Âge minimum obligatoire (3,7,12,16,18).";
    }

    // Upload cover – $_FILES – https://www.php.net/features.file-upload
    $cover_path = $current['cover_path'] ?? null;
    if (!empty($_FILES['cover']['name'])) {
        $u = media_handle_cover_upload('cover'); // Model/service
        if (!empty($u['error'])) $errors[] = $u['error'];
        else $cover_path = $u['path'];
    }

    if (!empty($errors)) {
        set_flash('error', implode(' ', $errors));
        return redirect('media/detail/' . $id);
    }

    // Update (Model)
    $ok = media_update($id, [
        'type'             => $type,
        'title'            => $title,
        'genre'            => $genre,
        'description'      => $description ?: null,
        'stock'            => $stock,
        'cover_path'       => $cover_path,
        // LIVRE
        'author'           => $type === 'livre' ? $author : null,
        'isbn'             => $type === 'livre' ? ($isbn ?: null) : null,
        'pages'            => $type === 'livre' ? $pages : null,
        'publication_year' => $type === 'livre' ? ($publication_year !== '' ? $publication_year : null) : null,
        // FILM
        'producer'         => $type === 'film'  ? $producer : null,
        'duration_min'     => $type === 'film'  ? $duration_min : null,
        'classification'   => $type === 'film'  ? $classification : null,
        'release_year'     => $type === 'film'  ? $release_year : null,
        // JEU
        'editor'           => in_array($type, ['livre', 'jeu'], true) ? $editor : null, // https://www.php.net/in_array
        'plateforme'       => $type === 'jeu'   ? $plateforme : null,
        'age_min'          => $type === 'jeu'   ? $age_min : null,
    ]);

    if ($ok) set_flash('success', "Média mis à jour.");
    else     set_flash('error',   "Échec de la mise à jour.");

    return redirect('media/detail' . "/" . $id);
}

/** ADMIN — +1 exemplaire */
function media_add_copy()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    if ($role !== 'admin') {
        set_flash('error', "Accès refusé.");
        return redirect('media/index');
    }
    if (!is_post() || !verify_csrf_token(post('csrf_token'))) {
        set_flash('error', "Requête invalide.");
        return redirect('media/index');
    }

    $media_id = (int)post('media_id', 0);
    if ($media_id <= 0) {
        set_flash('error', "Média invalide.");
        return redirect('media/index');
    }

    $m = media_get_by_id($media_id); // Model
    if (!$m) {
        set_flash('error', "Média introuvable.");
        return redirect('media/index');
    }

    // Incrément en base (DAL)
    $ok = db_execute("UPDATE medias SET stock = stock + 1, total_copies = total_copies + 1 WHERE id = ?", [$media_id]);

    if ($ok) set_flash('success', "Un exemplaire a été ajouté.");
    else     set_flash('error', "Impossible d’ajouter un exemplaire.");

    return redirect('media/index');
}

/** ADMIN — −1 exemplaire */
function media_remove_copy()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    if ($role !== 'admin') {
        set_flash('error', "Accès refusé.");
        return redirect('media/index');
    }
    if (!is_post() || !verify_csrf_token(post('csrf_token'))) {
        set_flash('error', "Requête invalide.");
        return redirect('media/index');
    }

    $media_id = (int)post('media_id', 0);
    if ($media_id <= 0) {
        set_flash('error', "Média invalide.");
        return redirect('media/index');
    }

    $m = media_get_by_id($media_id); // Model
    if (!$m) {
        set_flash('error', "Média introuvable.");
        return redirect('media/index');
    }

    $stock    = (int)$m['stock'];
    $total    = (int)$m['total_copies'];
    $borrowed = max(0, $total - $stock); // https://www.php.net/max

    if ($total <= $borrowed) {
        set_flash('error', "Impossible de retirer : tous les exemplaires sont empruntés.");
        return redirect('media/index');
    }

    // Décrément en base (DAL)
    $ok = ($stock > 0)
        ? db_execute("UPDATE medias SET total_copies = total_copies - 1, stock = stock - 1 WHERE id = ?", [$media_id])
        : db_execute("UPDATE medias SET total_copies = total_copies - 1 WHERE id = ?", [$media_id]);

    if ($ok) set_flash('success', "Un exemplaire a été retiré.");
    else     set_flash('error', "Impossible de retirer un exemplaire.");

    return redirect('media/index');
}

/**
 * Suppression d’un média (admin)
 * - unlink + chemins (voir media_delete_cover pour refs) – https://www.php.net/unlink
 */
function media_delete_media()
{
    if (!is_logged_in()) {
        return redirect('auth/login');
    }
    if (($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user') !== 'admin') {
        set_flash('error', "Accès refusé.");
        return redirect('media/index');
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        set_flash('error', "ID média invalide.");
        return redirect('media/medias');
    }

    $result = media_get_by_id($id); // Model
    if (!$result) {
        set_flash('error', "Média introuvable.");
        return redirect('media/medias');
    }

    // Interdire la suppression si des emprunts sont en cours
    if ((int)$result['stock'] < (int)$result['total_copies']) {
        set_flash('error', "Emprunt en cours, on ne peut supprimer le média !");
    } else {
        // Suppression cover locale si présente
        $old = $result['cover_path'] ?? null;
        if ($old) {
            $prefix = rtrim(url('uploads/covers/'), '/'); // https://www.php.net/rtrim
            if (strpos($old, $prefix) === 0) {            // https://www.php.net/strpos
                $fs_path = rtrim(PUBLIC_PATH, '/\\') . str_replace(url(''), '', $old); // https://www.php.net/str_replace
                @unlink($fs_path); // https://www.php.net/unlink
            }
        }
        delete_media($id); // Model
        set_flash('success', 'Média supprimé !');
    }

    return redirect('media/medias');
}

/**
 * Liste admin des médias filtrée avec pagination
 */
function media_medias()
{
    // KPIs
    loan_mark_overdue_now();
    $count_films       = (int)count_medias_movie();
    $count_livres      = (int)count_medias_book();
    $count_jeux        = (int)count_medias_game();
    $loans_in_progress = (int)count_loans_in_progress();
    $loans_late        = (int)count_loans_late();

    // Filtres & tri
    $q     = trim((string)($_GET['q'] ?? '')); // https://www.php.net/trim
    $type  = trim((string)($_GET['type'] ?? ''));
    $sort  = strtolower(trim($_GET['sort'] ?? 'created_at')); // https://www.php.net/strtolower
    $dir   = strtolower(trim($_GET['dir']  ?? 'desc'));
    $dir   = $dir === 'asc' ? 'asc' : 'desc';

    // Pagination
    $per_page = (int)($_GET['per_page'] ?? 10);
    if ($per_page < 5)   $per_page = 5;
    if ($per_page > 100) $per_page = 100;

    $current_page = max(1, (int)($_GET['page'] ?? 1)); // https://www.php.net/max
    $offset       = ($current_page - 1) * $per_page;

    $filters = [
        'q'    => $q,
        'type' => in_array($type, ['livre', 'film', 'jeu'], true) ? $type : '', // https://www.php.net/in_array
    ];

    // Totaux & pages
    $total = (int)media_count_search($filters); // Model
    $pages = max(1, (int)ceil($total / $per_page)); // https://www.php.net/ceil
    if ($current_page > $pages) {
        $current_page = $pages;
        $offset = ($current_page - 1) * $per_page;
    }

    // Liste filtrée + triée (Model)
    $medias = media_search_admin($filters, $per_page, $offset, $sort, $dir);

    load_view_with_layout('media/medias', [
        'title'             => 'Médias',
        'message'           => 'La liste des médias',
        'count_films'       => $count_films,
        'count_livres'      => $count_livres,
        'count_jeux'        => $count_jeux,
        'loans_in_progress' => $loans_in_progress,
        'loans_late'        => $loans_late,

        'medias'            => $medias,
        'current_page'      => $current_page,
        'pages'             => $pages,
        'total'             => $total,

        // pour la vue
        'sort'              => $sort,
        'dir'               => $dir,
        'per_page'          => $per_page,
        'filters'           => $filters,
    ]);
}
