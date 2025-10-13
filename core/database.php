<?php
// Fonctions de base de données

/**
 * Établit une connexion à la base de données
 */
function db_connect() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Exécute une requête SELECT
 */
function db_select($query, $params = []) {
    $pdo = db_connect();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécute une requête SELECT pour un seul résultat
 */
function db_select_one($query, $params = []) {
    $pdo = db_connect();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Exécute une requête INSERT, UPDATE ou DELETE
 */
function db_execute($query, $params = []) {
    $pdo = db_connect();
    $stmt = $pdo->prepare($query);
    return $stmt->execute($params);
}

/**
 * Retourne l'ID du dernier enregistrement inséré
 */
function db_last_insert_id() {
    $pdo = db_connect();
    return $pdo->lastInsertId();
}

/**
 * Commence une transaction
 */
function db_begin_transaction() {
    $pdo = db_connect();
    return $pdo->beginTransaction();
}

/**
 * Valide une transaction
 */
function db_commit() {
    $pdo = db_connect();
    return $pdo->commit();
}

/**
 * Annule une transaction
 */
function db_rollback() {
    $pdo = db_connect();
    return $pdo->rollBack();
} 