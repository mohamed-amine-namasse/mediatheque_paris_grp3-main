<?php

/**
 * Retourne le nombre d'emprunts actifs pour un utilisateur.

 */
function loan_count_active_by_user(int $user_id): int
{
    $row = db_select_one(
        "SELECT COUNT(*) AS c
           FROM loans
          WHERE user_id = ?
            AND status = 'En cours'",
        [$user_id]
    );
    return (int)($row['c'] ?? 0);
}

/**
 * Indique si l'utilisateur a déjà un emprunt en cours pour le même média.
 */
function loan_has_active_same_media(int $user_id, int $media_id): bool
{
    $row = db_select_one(
        "SELECT 1
           FROM loans
          WHERE user_id = ?
            AND media_id = ?
            AND status = 'En cours'
          LIMIT 1",
        [$user_id, $media_id]
    );
    return (bool)$row;
}

/**
 * Récupère un prêt par id.
 */
function loan_get_by_id(int $loan_id): ?array
{
    $row = db_select_one("SELECT * FROM loans WHERE id = ?", [$loan_id]);
    return $row ?: null;
}

/**
 * Création d’un emprunt avec respect du cahier des charges.
 */
function loan_create(int $user_id, int $media_id): array
{
    if (loan_count_active_by_user($user_id) >= 3) {
        return ['ok' => false, 'error' => "Limite atteinte : 3 emprunts simultanés."];
    }
    if (loan_has_active_same_media($user_id, $media_id)) {
        return ['ok' => false, 'error' => "Vous avez déjà un emprunt en cours pour ce média."];
    }

    $date_borrow = date('Y-m-d');                         //  date() https://www.php.net/date
    $date_due    = date('Y-m-d', strtotime('+14 days'));  // strtotime() https://www.php.net/strtotime

    $ok = db_execute(
        "INSERT INTO loans (user_id, media_id, date_borrow, date_due, status, late_days)
         VALUES (?, ?, ?, ?, 'En cours', 0)",
        [$user_id, $media_id, $date_borrow, $date_due]
    );

    if (!$ok) {
        return ['ok' => false, 'error' => "Ce média n'est plus disponible."];
    }

    return ['ok' => true, 'due' => $date_due];
}

/**
 * Calcule le retard et met à jour le statut.
 */
function loan_return_common(int $loan_id): array
{
    $loan = loan_get_by_id($loan_id);
    if (!$loan) {
        return ['ok' => false, 'error' => "Emprunt introuvable."];
    }
    if ($loan['status'] !== 'En cours' && $loan['status'] !== 'En retard') {
        return ['ok' => false, 'error' => "Emprunt déjà rendu."];
    }

    $date_returned = date('Y-m-d'); // date() https://www.php.net/date

    $ok = db_execute(
        "UPDATE loans
            SET date_returned = ?,
                status        = 'Rendu',
                late_days     = GREATEST(DATEDIFF(?, date_due), 0) /* https://dev.mysql.com/doc/refman/8.0/en/comparison-operators.html#function_greatest */
          WHERE id = ?
            AND status IN ('En cours','En retard')",
        [$date_returned, $date_returned, $loan_id]
    );

    if (!$ok) {
        return ['ok' => false, 'error' => "Erreur lors de la mise à jour du prêt."];
    }

    return ['ok' => true];
}

function loan_return_service(int $loan_id): array       { return loan_return_common($loan_id); }
function loan_force_return_service(int $loan_id): array { return loan_return_common($loan_id); }

/**
 * KPIs 
 */

/** 
 * Compteur global de prêts 
 */
function count_loans(): int
{
    $row = db_select_one("SELECT COUNT(*) as total FROM loans");
    return (int)($row['total'] ?? 0);
}

/** Compte les emprunts en cours total */
function count_loans_in_progress(): int
{
    $row = db_select_one("SELECT COUNT(*) AS total FROM loans WHERE status = 'En cours'");
    return (int)($row['total'] ?? 0);
}

/** Emprunts les emprunts en retard */
function count_loans_late(): int
{
    $row = db_select_one("SELECT COUNT(*) AS total FROM loans WHERE status = 'En retard'");
    return (int)($row['total'] ?? 0);
}

/**
 * Met le status en retard pour tous les prêts non rendus dont la date_due est passée.

 */
function loan_mark_overdue_now(): int
{
    $sql = "UPDATE loans
               SET status = 'En retard'
             WHERE status = 'En cours'
               AND date_returned IS NULL
               AND date_due < CURDATE()";
    $pdo  = db_connect();                  
    $stmt = $pdo->prepare($sql);           
    $stmt->execute();                      
    return (int)$stmt->rowCount();         
}

/**
 * Prêts d'un utilisateur avec titre du média
 */
function loan_get_by_user(int $user_id): array
{
    $sql = "SELECT l.*,
                   m.title AS media_title,
                   l.media_id AS media_id
              FROM loans l
         LEFT JOIN medias m ON m.id = l.media_id
             WHERE l.user_id = ?
          ORDER BY l.date_borrow DESC, l.id DESC";
    return db_select($sql, [$user_id]);
}

/**
 * Liste globale pour l'admin avec infos utilisateur et média.
 */
function loan_get_all_with_users(): array
{
    $sql = "SELECT l.*,
                   m.title      AS media_title,
                   l.media_id   AS media_id,
                   l.user_id    AS user_id,
                   u.first_name AS user_first_name,
                   u.last_name  AS user_last_name
              FROM loans l
         LEFT JOIN medias m ON m.id = l.media_id
         LEFT JOIN users  u ON u.id = l.user_id
          ORDER BY l.date_borrow DESC, l.id DESC";
    return db_select($sql);
}

/** 
 * Compte tous les prêts  
 */
function loan_count_all(): int
{
    $row = db_select_one("SELECT COUNT(*) AS c FROM loans");
    return (int)($row['c'] ?? 0);
}

/**
 *  Compte tous les prêts d’un utilisateur 
 */
function loan_count_by_user(int $user_id): int
{
    $row = db_select_one("SELECT COUNT(*) AS c FROM loans WHERE user_id = ?", [$user_id]);
    return (int)($row['c'] ?? 0);
}

/**
 * Historique global avec la pagination
 */
function loan_get_all_with_users_paginated(int $limit, int $offset): array
{
    $limit  = max(1, (int)$limit);   // PHP max : https://www.php.net/max
    $offset = max(0, (int)$offset);

    $sql = "SELECT l.*,
                   m.title      AS media_title,
                   l.media_id   AS media_id,
                   l.user_id    AS user_id,
                   u.first_name AS user_first_name,
                   u.last_name  AS user_last_name
              FROM loans l
         LEFT JOIN medias m ON m.id = l.media_id
         LEFT JOIN users  u ON u.id = l.user_id
          ORDER BY l.date_borrow DESC, l.id DESC
             LIMIT $offset, $limit";
    return db_select($sql);
}

/**
 * Historique utilisateur paginé — conserve orphelins.
 */
function loan_get_by_user_paginated(int $user_id, int $limit, int $offset): array
{
    $limit  = max(1, (int)$limit);   // PHP max : https://www.php.net/max
    $offset = max(0, (int)$offset);

    $sql = "SELECT l.*,
                   m.title    AS media_title,
                   l.media_id AS media_id
              FROM loans l
         LEFT JOIN medias m ON m.id = l.media_id
             WHERE l.user_id = ?
          ORDER BY l.date_borrow DESC, l.id DESC
             LIMIT $offset, $limit";
    return db_select($sql, [$user_id]);
}

/**
 * Listing global simple avec pagination
 */
function loan_get_all(?int $limit = null, int $offset = 0): array
{
    $sql = "SELECT l.id,
                   u.first_name,
                   u.last_name,
                   l.media_id,
                   l.date_borrow,
                   l.date_due,
                   l.date_returned,
                   l.status,
                   l.late_days
              FROM loans l
              JOIN users u ON u.id = l.user_id
          ORDER BY l.date_borrow DESC, l.id DESC"; // tri temporel
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$offset . ", " . (int)$limit; 
    }
    return db_select($sql);
}




/**
 * Statistiques d'emprunts par catégories pour un utilisateur.
 */
function loans_percentage_by_category_by_id(int $user_id): ?array
{
    $query = "
        SELECT 
            u.id         AS user_id,
            u.first_name,
            u.last_name,
            SUM(CASE WHEN m.type = 'film'  THEN 1 ELSE 0 END) AS movies_count,
            SUM(CASE WHEN m.type = 'livre' THEN 1 ELSE 0 END) AS books_count,
            SUM(CASE WHEN m.type = 'jeu'   THEN 1 ELSE 0 END) AS games_count,
            COUNT(l.id)  AS total_loans
        FROM users u
        LEFT JOIN loans  l ON l.user_id = u.id
        LEFT JOIN medias m ON m.id      = l.media_id
        WHERE u.id = ?
        GROUP BY u.id, u.first_name, u.last_name
        LIMIT 1
    ";

    $row = db_select_one($query, [$user_id]);
    if (!$row) {
        return null;
    }

    $total  = (int)($row['total_loans'] ?? 0);
    $movies = (int)($row['movies_count'] ?? 0);
    $books  = (int)($row['books_count']  ?? 0);
    $games  = (int)($row['games_count']  ?? 0);

    $movies_p = $total > 0 ? (int)round(($movies / $total) * 100) : 0; // round() https://www.php.net/round
    $books_p  = $total > 0 ? (int)round(($books  / $total) * 100) : 0;
    $games_p  = $total > 0 ? (int)round(($games  / $total) * 100) : 0;

    return [
        'user_id'        => (int)$row['user_id'],
        'first_name'     => (string)$row['first_name'],
        'last_name'      => (string)$row['last_name'],
        'total_loans'    => $total,
        'movies_percent' => $movies_p,
        'books_percent'  => $books_p,
        'games_percent'  => $games_p,
    ];
}



/** 
 * Colonnes triables
 */
function loan_sort_columns(): array {
    return [
        'id'            => 'l.id',
        'last_name'     => 'u.last_name',
        'first_name'    => 'u.first_name',
        'media_id'      => 'l.media_id',
        'date_borrow'   => 'l.date_borrow',
        'date_due'      => 'l.date_due',
        'date_returned' => 'l.date_returned',
        'status'        => 'l.status',
        'late_days'     => 'l.late_days',
    ];
}

/**
 * Compte total avec filtre
 */
function loan_count_filtered(string $q = '', string $status = ''): int {
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(u.last_name LIKE ? OR u.first_name LIKE ? OR CAST(l.id AS CHAR) LIKE ? OR CAST(l.media_id AS CHAR) LIKE ?)";
        $like = "%{$q}%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($status !== '') {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : ''; // implode https://www.php.net/implode

    $row = db_select_one("
        SELECT COUNT(*) AS c
          FROM loans l
          JOIN users u ON u.id = l.user_id
        $whereSql
    ", $params);

    return (int)($row['c'] ?? 0);
}

/**
 * Recherche avec query la pagination et les filtres
 */
function loan_search_paginated(string $q, string $status, string $sort, string $dir, int $limit, int $offset): array {
    $allowed = loan_sort_columns();
    $orderCol = $allowed[$sort] ?? 'l.date_borrow';
    $orderDir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC'; // strtolower https://www.php.net/strtolower

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(u.last_name LIKE ? OR u.first_name LIKE ? OR CAST(l.id AS CHAR) LIKE ? OR CAST(l.media_id AS CHAR) LIKE ?)";
        $like = "%{$q}%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($status !== '') {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : ''; // PHP implode https://www.php.net/implode

    $limit  = max(1, (int)$limit);  // max https://www.php.net/max
    $offset = max(0, (int)$offset);

    $sql = "
        SELECT 
            l.id,
            u.first_name,
            u.last_name,
            l.media_id,
            l.date_borrow,
            l.date_due,
            l.date_returned,
            l.status,
            l.late_days
        FROM loans l
        JOIN users u ON u.id = l.user_id
        $whereSql
        ORDER BY $orderCol $orderDir
        LIMIT $offset, $limit
    ";

    return db_select($sql, $params);
}

/**
 * Compte filtré sur l’historique global avec query.
 */
function loan_history_count_admin_filtered(string $q = '', string $status = ''): int
{
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(u.last_name LIKE ? OR u.first_name LIKE ? OR CAST(l.id AS CHAR) LIKE ? OR CAST(l.media_id AS CHAR) LIKE ? OR m.title LIKE ?)";
        $like = "%{$q}%";
        $params = array_merge($params, [$like,$like,$like,$like,$like]); // array_merge https://www.php.net/array_merge
    }
    if ($status !== '') {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

    $row = db_select_one("
        SELECT COUNT(*) AS c
          FROM loans l
     LEFT JOIN users  u ON u.id = l.user_id
     LEFT JOIN medias m ON m.id = l.media_id
        $whereSql
    ", $params);

    return (int)($row['c'] ?? 0);
}

/**
 * Recherche avec pagination et filtrée par query.
 */
function loan_history_search_admin_paginated(string $q, string $status, int $limit, int $offset): array
{
    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(u.last_name LIKE ? OR u.first_name LIKE ? OR CAST(l.id AS CHAR) LIKE ? OR CAST(l.media_id AS CHAR) LIKE ? OR m.title LIKE ?)";
        $like = "%{$q}%";
        $params = array_merge($params, [$like,$like,$like,$like,$like]); // array_merge https://www.php.net/array_merge
    }
    if ($status !== '') {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

    $limit  = max(1, (int)$limit);  // max https://www.php.net/max
    $offset = max(0, (int)$offset);

    $sql = "
        SELECT 
            l.*,
            l.user_id    AS user_id,
            l.media_id   AS media_id,
            u.first_name AS user_first_name,
            u.last_name  AS user_last_name,
            m.title      AS media_title
        FROM loans l
   LEFT JOIN users  u ON u.id = l.user_id
   LEFT JOIN medias m ON m.id = l.media_id
        $whereSql
    ORDER BY l.date_borrow DESC, l.id DESC
       LIMIT $offset, $limit
    ";
    return db_select($sql, $params);
}

/**
 * Compte de user filtre par query avec pagination 
 */
function loan_history_count_user_filtered(int $user_id, string $q = '', string $status = ''): int
{
    $where = ["l.user_id = ?"];
    $params = [$user_id];

    if ($q !== '') {
        $where[] = "(CAST(l.id AS CHAR) LIKE ? OR CAST(l.media_id AS CHAR) LIKE ? OR m.title LIKE ?)";
        $like = "%{$q}%";
        $params = array_merge($params, [$like,$like,$like]); // array_merge https://www.php.net/array_merge
    }
    if ($status !== '') {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    $whereSql = 'WHERE '.implode(' AND ', $where); //  implode https://www.php.net/implode

    $row = db_select_one("
        SELECT COUNT(*) AS c
          FROM loans l
     LEFT JOIN medias m ON m.id = l.media_id
        $whereSql
    ", $params);

    return (int)($row['c'] ?? 0);
}

/**
 * Recherche avec pagination et filtrée par q (la query de recherche donc) et le status pour un utilisateur donné.
 */
function loan_history_search_user_paginated(int $user_id, string $q, string $status, int $limit, int $offset): array
{
    $where = ["l.user_id = ?"];
    $params = [$user_id];

    if ($q !== '') {
        $where[] = "(CAST(l.id AS CHAR) LIKE ? OR CAST(l.media_id AS CHAR) LIKE ? OR m.title LIKE ?)";
        $like = "%{$q}%";
        $params = array_merge($params, [$like,$like,$like]); // array_merge https://www.php.net/array_merge
    }
    if ($status !== '') {
        $where[] = "l.status = ?";
        $params[] = $status;
    }
    $whereSql = 'WHERE '.implode(' AND ', $where); //  implode https://www.php.net/implode

    $limit  = max(1, (int)$limit);  //  max  https://www.php.net/max
    $offset = max(0, (int)$offset);

    $sql = "
        SELECT 
            l.*,
            l.media_id   AS media_id,
            m.title      AS media_title
        FROM loans l
   LEFT JOIN medias m ON m.id = l.media_id
        $whereSql
    ORDER BY l.date_borrow DESC, l.id DESC
       LIMIT $offset, $limit
    ";
    return db_select($sql, $params);
}
