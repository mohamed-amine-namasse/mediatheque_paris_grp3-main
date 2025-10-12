<?php
// Fonctions utilitaires

/**
 * Sécurise l'affichage d'une chaîne de caractères (protection XSS)
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Affiche une chaîne sécurisée (échappée)
 */
function e($string) {
    echo escape($string);
}

/**
 * Retourne une chaîne sécurisée sans l'afficher
 */
function esc($string) {
    return escape($string);
}

/**
 * Génère une URL absolue
 */
function url($path = '') {
    $base_url = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base_url . '/' . $path;
}

/**
 * Redirection HTTP
 */
function redirect($path = '') {
    $url = url($path);
    header("Location: $url");
    exit;
}

/**
 * Génère un token CSRF
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Définit un message flash
 */
function set_flash($type, $message) {
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Récupère et supprime les messages flash
 */
function get_flash_messages($type = null) {
    if (!isset($_SESSION['flash_messages'])) {
        return [];
    }

    if ($type) {
        $messages = $_SESSION['flash_messages'][$type] ?? [];
        unset($_SESSION['flash_messages'][$type]);
        return $messages;
    }

    $messages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Vérifie s'il y a des messages flash
 */
function has_flash_messages($type = null) {
    if (!isset($_SESSION['flash_messages'])) {
        return false;
    }

    if ($type) {
        return !empty($_SESSION['flash_messages'][$type]);
    }

    return !empty($_SESSION['flash_messages']);
}

/**
 * Nettoie une chaîne de caractères
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Valide une adresse email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Génère un mot de passe sécurisé
 */
function generate_password($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

/**
 * Hache un mot de passe
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Vérifie un mot de passe
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Formate une date
 */
function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Vérifie si une requête est en POST
 */
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Vérifie si une requête est en GET
 */
function is_get() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

/**
 * Retourne la valeur d'un paramètre POST
 */
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Retourne la valeur d'un paramètre GET
 */
function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Vérifie si un utilisateur est connecté
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Retourne l'ID de l'utilisateur connecté
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Déconnecte l'utilisateur
 */
function logout() {
    session_destroy();
    redirect('auth/login');
}

/**
 * Formate un nombre
 */
function format_number($number, $decimals = 2) {
    return number_format($number, $decimals, ',', ' ');
}

/**
 * Génère un slug à partir d'une chaîne
 */
function generate_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
} 

/**
 * Retourne le paramètre du nombre maximum de tentatives de connexion depuis la base de données
 */
function max_login_attempts() {
    $query = "SELECT value_int FROM settings WHERE key_name = 'max_login_attempts' LIMIT 1";
    $result = db_select_one($query);
    return $result['value_int']; 
}
/**
 * Retourne le paramètre du timeout de mot de passe depuis la base de données
 */
function password_timeout_setting() {
    $query = "SELECT value_int FROM settings WHERE key_name = 'password_timeout' LIMIT 1";
    $result = db_select_one($query);
    return $result['value_int']; 
}
/**
 * remet a 0 le nombre de tentatives de connexion
 */
function login_attempts_reset($email) {
    get_user_by_email($email);
    $query = "UPDATE users SET login_attempts = 0 WHERE email = ?";
    return db_execute($query , [$email]);
}
/**
 * retourne le nombre de tentatives de connexion
 */
function login_attempts($email) {
    $query = "SELECT login_attempts FROM users WHERE email = ?";
    $result = db_select_one($query , [$email]);
    return $result['login_attempts'];
}
/**
 * augmente le nombre de tentatives de connexion
 */
function login_attempts_increase($email) {
    get_user_by_email($email);
    $query = "UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?";
    return db_execute($query , [$email]);

}
/**
 * remet a 0 le statut de blocage
 */
function blocked_reset($email) {
    $query = "UPDATE users SET blocked = 0 WHERE email = ?";
    return db_execute($query , [$email]);
}
/**
 * bloque un utilisateur
 */
function blocked_set($email) {
    $query = "UPDATE users SET blocked = 1 WHERE email = ?";
    return db_execute($query , [$email]);
}
/**
 * vérifie si un utilisateur est bloqué
 */
function blocked_check($email) {
    $query = "SELECT blocked FROM users WHERE email = ?";
    $result = db_select_one($query , [$email]);
    return $result['blocked'];
}

function role_check($email){
    $query = "SELECT role FROM users WHERE email = ?";
    $result = db_select_one($query , [$email]);
    return $result ? $result['role'] : null;
}