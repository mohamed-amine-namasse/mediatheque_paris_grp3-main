<?php

/**
 * Page de connexion
 */
function auth_login() {

    // Rediriger si déjà connecté
    if (is_logged_in()) {
        redirect('home');
    }
    
    $data = [
        'title' => 'Connexion'
    ];
    
    if (is_post()) {
        $email = clean_input(post('email'));
        $password = post('password');
        
        if (empty($email) || empty($password)) {
            set_flash('error', 'Email et mot de passe obligatoires.');
        } else {
            if (!get_user_by_email($email)) {
                set_flash('error', 'Adresse email invalide.');
                load_view_with_layout('auth/login', $data);
                return;
            } else {
                // Rechercher l'utilisateur
                $user = get_user_by_email($email);
                $is_blocked = blocked_check($email);
                if ($is_blocked == 1) {
                    $current_time = time();
                    $logout_time = fetch_logout_time($email);
                    $logout_timestamp = strtotime($logout_time);
                    $timeout = password_timeout_setting();
                    if (($current_time - $logout_timestamp) + 7200 > $timeout) {
                        blocked_reset($email);
                        login_attempts_reset($email);
                        set_flash('success', 'Le compte a été débloqué. Vous pouvez réessayer de vous connecter.');
                    } else {
                    $rt = ($current_time - $logout_timestamp) + 7200;
                    $tr = ($timeout - $rt);
                    set_flash('error','patienté un peu avant de recomencer.'.'il reste ' . $tr . ' seconde a attendre');
                    }
                } else {

                    
                    if ($user && verify_password($password, $user['password'])) {
                        // Connexion réussie
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_first_name'] = $user['first_name'];
                        $_SESSION['user_last_name'] = $user['last_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                
                        login_attempts_reset($email);
                        blocked_reset($email);
                        set_flash('success', 'Connexion réussie !');
                        redirect('home/index');
                    } else {
                        update_logout_time($email);
                        login_attempts_increase($email);
                        $_SESSION['login_attempts'] = login_attempts($email);
                        $_SESSION['last_activity'] = time(); // Met à jour l'heure de la dernière activité
                        if ($_SESSION['login_attempts'] == max_login_attempts()) {
                            blocked_set($email);
                            set_flash('error','Ce compte est bloqué pour 1 minute pour trop de mot de passe incorrect .');
                        } else {
                            $remaining_attempts = max_login_attempts() - $_SESSION['login_attempts'];
                            set_flash('error', 'Il vous reste ' . $remaining_attempts . ' tentative(s) avant le blocage du compte.');
                        }
                    }
                }
            }
        }
        
    }
    load_view_with_layout('auth/login', $data);
}

/**
 * Page d'inscription
 */
function auth_register()
{
    // Rediriger si déjà connecté
    if (is_logged_in()) {
        redirect('home');
    }

    $data = [
        'title' => 'Inscription'
    ];

    if (is_post()) {
        $first_name = clean_input(post('first_name'));
        $last_name = clean_input(post('last_name'));
        $email = clean_input(post('email'));
        $password = post('password');
        $confirm_password = post('confirm_password');
        $re = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$.!%(+;)\*\/\-_{}#~$*%:!,<²°>ù^`|@[\]*?&]).{8,}$/';

        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            set_flash('error', 'Tous les champs sont obligatoires.');
        } elseif (!validate_email($email)) {
            set_flash('error', 'Adresse email invalide.');
        } elseif (!preg_match($re, $password)) {
            set_flash('error', "Mot de passe non sécurisé ! Veuillez ajouter au moins une minuscule, une majuscule, un chiffre, un caractère spécial ainsi qu'un minimum de 8 caractères au total");
        } elseif ($password !== $confirm_password) {
            set_flash('error', 'Les mots de passe ne correspondent pas.');
        } elseif (get_user_by_email($email)) {
            set_flash('error', 'Cette adresse email est déjà utilisée.');
        } else {
            // Créer l'utilisateur
            $user_id = create_user($first_name, $last_name, $email, $password);
            $user = get_user_by_email($email);
            if ($user_id) {
                set_flash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
                redirect('auth/login');
            } else {
                set_flash('error', 'Erreur lors de l\'inscription.');
            }
        }
    }

    load_view_with_layout('auth/register', $data);
}


/**
 * Déconnexion
 */
function auth_logout()
{
    logout();
}
