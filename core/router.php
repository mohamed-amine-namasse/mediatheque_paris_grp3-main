<?php
// Système de routing simple

/**
 * Parse l'URL et retourne le contrôleur, l'action et les paramètres
 */
function parse_request_url() {
    $url = $_GET['url'] ?? '';
    $url = rtrim($url, '/');
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (empty($url)) {
        return ['controller' => 'home', 'action' => 'index', 'params' => []];
    }
    
    $url_parts = explode('/', $url);
    
    $controller = $url_parts[0] ?? 'home';
    $action = $url_parts[1] ?? 'index';
    $params = array_slice($url_parts, 2);
    
    return [
        'controller' => $controller,
        'action' => $action,
        'params' => $params
    ];
}

/**
 * Charge et exécute le contrôleur approprié
 */
function dispatch() {
    $route = parse_request_url();
    
    $controller_name = $route['controller'];
    $action_name = $route['action'];
    $params = $route['params'];
    
    // Nom du fichier contrôleur
    $controller_file = CONTROLLER_PATH . '/' . $controller_name . '_controller.php';
    
    // Vérifier si le contrôleur existe
    if (!file_exists($controller_file)) {
        // Contrôleur par défaut pour les erreurs 404
        load_404();
        return;
    }
    
    // Charger le contrôleur
    require_once $controller_file;
    
    // Nom de la fonction d'action
    $action_function = $controller_name . '_' . $action_name;
    
    // Vérifier si l'action existe
    if (!function_exists($action_function)) {
        load_404();
        return;
    }
    
    // Exécuter l'action avec les paramètres
    call_user_func_array($action_function, $params);
}

/**
 * Charge la page 404
 */
function load_404() {
    http_response_code(404);
    require_once VIEW_PATH . '/errors/404.php';
}

 