<?php


/**
 * Récupère un utilisateur par son email
 */
function get_user_by_email($email) {
    $query = "SELECT * FROM users WHERE email = ? LIMIT 1"; // MySQL SELECT/LIMIT : https://dev.mysql.com/doc/refman/8.0/en/select.html
    return db_select_one($query, [$email]);
}

/**
 * Récupère un utilisateur par son ID
 */
function get_user_by_id($id) {
    $query = "SELECT * FROM users WHERE id = ? LIMIT 1"; // MySQL SELECT/LIMIT : https://dev.mysql.com/doc/refman/8.0/en/select.html
    return db_select_one($query, [$id]);
}

/**
 * Crée un nouvel utilisateur
 */
function create_user($first_name, $last_name, $email, $password) {
    $hashed_password = hash_password($password); // password_hash : https://www.php.net/password_hash
    $query = "INSERT INTO users (first_name, last_name, email, password, role, created_at) 
              VALUES (?, ?, ?, ?, 'user', NOW())"; // INSERT : https://dev.mysql.com/doc/refman/8.0/en/insert.html | NOW() : https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_now
    if (db_execute($query, [$first_name, $last_name, $email, $hashed_password])) {
        return db_last_insert_id(); // PDO::lastInsertId : https://www.php.net/pdo.lastinsertid
    }
    return false;
}

/**
 * Met à jour un utilisateur
 */
function update_user($id, $first_name, $last_name, $email) {
    $query = "UPDATE users 
                 SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() 
               WHERE id = ?"; // UPDATE : https://dev.mysql.com/doc/refman/8.0/en/update.html | NOW() : https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_now
    return db_execute($query, [$first_name, $last_name, $email, $id]);
}

/**
 * Met à jour le mot de passe d'un utilisateur
 */
function update_user_password($id, $password) {
    $hashed_password = hash_password($password); // password_hash : https://www.php.net/password_hash
    $query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?"; // UPDATE : https://dev.mysql.com/doc/refman/8.0/en/update.html | NOW() : https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_now
    return db_execute($query, [$hashed_password, $id]);
}

/**
 * met à jour la date et l'heure de déconnexion (par email)
 */
function update_logout_time($email) {
    $query = "UPDATE users SET logged_out_at = NOW() WHERE email = ?"; // NOW() : https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_now
    return db_execute($query, [$email]);
}

/**
 * Variante utile si on a l'id en session
 */
function update_logout_time_by_user_id($user_id) {
    $query = "UPDATE users SET logged_out_at = NOW() WHERE id = ?"; // NOW() : https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_now
    return db_execute($query, [$user_id]);
}

/**
 * récupère la date/heure de déconnexion
 */
function fetch_logout_time($email) {
    $query = "SELECT logged_out_at FROM users WHERE email = ?"; 
    $result = db_select_one($query, [$email]);
    return $result['logged_out_at'] ?? null;
}

/**
 * Supprime un utilisateur
 */
function delete_user($id) {
    $query = "DELETE FROM users WHERE id = ?"; 
    return db_execute($query, [$id]);
}

/**
 * Récupère tous les utilisateurs (liste simple)
 */
function get_all_users($limit = null, $offset = 0) {
    $query = "SELECT id, first_name, last_name, email, role, created_at 
                FROM users 
            ORDER BY created_at DESC"; 
    if ($limit !== null) {
        $offset = (int)$offset;
        $limit  = (int)$limit;
        $query .= " LIMIT $offset, $limit"; 
    }
    return db_select($query);
}

/**
 * Compte le nombre total d'utilisateurs
 */
function count_users() {
    $row = db_select_one("SELECT COUNT(*) AS total FROM users"); 
    return (int)($row['total'] ?? 0);
}

/**
 * Vérifie si un email existe déjà 
 */
function email_exists($email, $exclude_id = null) {
    $query  = "SELECT COUNT(*) AS count FROM users WHERE email = ?"; 
    $params = [$email];
    if ($exclude_id) {
        $query .= " AND id != ?"; // opérateur != https://dev.mysql.com/doc/refman/8.0/en/comparison-operators.html
        $params[] = (int)$exclude_id;
    }
    $result = db_select_one($query, $params);
    return ((int)($result['count'] ?? 0)) > 0;
}

/**
 * Statistiques globales nouveaux utilisateurs
 */
function get_new_users_stats($limit = null, $offset = 0) {
    $query = "SELECT  
                 COUNT(*) AS total_users,
                 COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS new_users_30d,
                 COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)  THEN 1 END) AS new_users_7d
              FROM users"; // DATE_SUB et INTERVAL https://dev.mysql.com/doc/refman/8.0/en/date-and-time-functions.html#function_date-sub | CASE : https://dev.mysql.com/doc/refman/8.0/en/control-flow-functions.html#operator_case | COUNT(*) : https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html#function_count
    if ($limit !== null) {
        $offset = (int)$offset;
        $limit  = (int)$limit;
        $query .= " LIMIT $offset, $limit"; 
    }
    return db_select($query);
}

/**
 * Paramètre timeout de session en secondes
 */
function timeout_setting(){
    $row = db_select_one("SELECT value_int FROM settings WHERE key_name = 'session_timeout' LIMIT 1"); 
    return (int)($row['value_int'] ?? 0);
}

/**
 * Met à jour le paramètre timeout de session (POST['timeout'] attendu)
 */
function timeout_setting_update(){
    if (!isset($_POST['timeout'])) return false;
    $value = (int)$_POST['timeout'];
    return db_execute("UPDATE settings SET value_int = ? WHERE key_name = 'session_timeout'", [$value]); 
}

/**
 * Vérifie expiration session et déconnecte si besoin
 */
function check_and_logout_if_session_expired() {
    if (is_logged_in()) {
        $timeout = timeout_setting(); // en secondes
        if (isset($_SESSION['last_activity']) && $timeout > 0 && (time() - $_SESSION['last_activity'] > $timeout)) { // time() https://www.php.net/time
            $user_id = (int)($_SESSION['user_id'] ?? 0);
            if ($user_id > 0) {
                update_logout_time_by_user_id($user_id);
            }
            session_destroy(); 
            setcookie('logout_message', 'Votre session a expiré. Veuillez vous reconnecter.', time() + 5, '/'); 
            header('Location: ' . url('auth/login')); 
            exit(); // exit https://www.php.net/exit
        }
        $_SESSION['last_activity'] = time(); // time() https://www.php.net/time
    }
}

/**
 * Recherche filtré avec pagination
 */
function user_search_paginated(string $q, string $sort, string $dir, int $limit, int $offset): array
{
    $allowedSort = [
        'id'         => 'u.id',
        'last_name'  => 'u.last_name',
        'first_name' => 'u.first_name',
        'email'      => 'u.email',
        'created_at' => 'u.created_at',
    ];
    $orderCol = $allowedSort[$sort] ?? 'u.created_at';
    $orderDir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

    $where  = '';
    $params = [];

    if ($q !== '') {
        $where = "WHERE (u.last_name LIKE ? OR u.first_name LIKE ? OR u.email LIKE ? OR CAST(u.id AS CHAR) LIKE ?)"; // LIKE https://dev.mysql.com/doc/refman/8.0/en/pattern-matching.html | CAST(... AS CHAR) : https://dev.mysql.com/doc/refman/8.0/en/cast-functions.html#function_cast
        $like  = "%{$q}%";
        $params = [$like, $like, $like, $like];
    }

    $limit  = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $sql = "
        SELECT u.id, u.last_name, u.first_name, u.email, u.role, u.created_at
          FROM users u
          $where
         ORDER BY $orderCol $orderDir
         LIMIT $offset, $limit
    "; 
    return db_select($sql, $params);
}

/**
 * Compte total d’utilisateurs selon le filtre pour la pagination
 */
function user_count_filtered(string $q): int
{
    $where  = '';
    $params = [];

    if ($q !== '') {
        $where = "WHERE (u.last_name LIKE ? OR u.first_name LIKE ? OR u.email LIKE ? OR CAST(u.id AS CHAR) LIKE ?)"; // LIKE  https://dev.mysql.com/doc/refman/8.0/en/pattern-matching.html et CAST : https://dev.mysql.com/doc/refman/8.0/en/cast-functions.html#function_cast
        $like  = "%{$q}%";
        $params = [$like, $like, $like, $like];
    }

    $row = db_select_one("SELECT COUNT(*) AS c FROM users u $where", $params); 
    return (int)($row['c'] ?? 0);
}
