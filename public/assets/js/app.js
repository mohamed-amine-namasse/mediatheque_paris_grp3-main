/**
 * Fichier JavaScript principal de l'application
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion des messages flash avec auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Auto-hide après 5 secondes
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
        
        // Permettre de fermer manuellement
        alert.addEventListener('click', function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        });
    });
    
    // Validation de formulaire côté client
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // // Amélioration UX pour les boutons de soumission
    // const submitButtons = document.querySelectorAll('button[type="submit"]');
    // submitButtons.forEach(function(button) {
    //     button.addEventListener('click', function() {
    //         const form = button.closest('form');
    //         if (form && validateForm(form)) {
    //             button.disabled = true;
    //             button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                
    //             // Réactiver après 5 secondes en cas de problème
    //             setTimeout(function() {
    //                 button.disabled = false;
    //                 button.innerHTML = button.getAttribute('data-original-text') || 'Envoyer';
    //             }, 5000);
    //         }
    //     });
    // });
    
    // Smooth scroll pour les ancres
    const anchors = document.querySelectorAll('a[href^="#"]');
    anchors.forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Confirmation pour les actions de suppression
    const deleteLinks = document.querySelectorAll('a[href*="delete"], button[data-action="delete"]');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
    
    // Animation d'entrée pour les cartes
    const cards = document.querySelectorAll('.feature-card, .step, .info-box');
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    cards.forEach(function(card) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});

/**
 * Valide un formulaire
 */
function validateForm(form) {
    let isValid = true;
    
    // Validation des champs requis
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            showFieldError(field, 'Ce champ est obligatoire');
            isValid = false;
        } else {
            hideFieldError(field);
        }
    });
    
    // Validation des emails
    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(function(field) {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Adresse email invalide');
            isValid = false;
        }
    });
    
    // Validation des mots de passe
    const passwordField = form.querySelector('input[name="password"]');
    const confirmPasswordField = form.querySelector('input[name="confirm_password"]');
    
    if (passwordField && passwordField.value.length < 6) {
        showFieldError(passwordField, 'Le mot de passe doit contenir au moins 8 caractères');
        isValid = false;
    }
    
    if (confirmPasswordField && passwordField && 
        confirmPasswordField.value !== passwordField.value) {
        showFieldError(confirmPasswordField, 'Les mots de passe ne correspondent pas');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Affiche une erreur sur un champ
 */
function showFieldError(field, message) {
    hideFieldError(field);
    
    const error = document.createElement('div');
    error.className = 'field-error';
    error.textContent = message;
    error.style.color = '#ef4444';
    error.style.fontSize = '0.875rem';
    error.style.marginTop = '0.25rem';
    
    field.style.borderColor = '#ef4444';
    field.parentNode.appendChild(error);
}

/**
 * Cache l'erreur d'un champ
 */
function hideFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

/**
 * Valide une adresse email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Affiche un message de notification
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '1000';
    notification.style.minWidth = '300px';
    notification.style.cursor = 'pointer';
    
    document.body.appendChild(notification);
    
    // Auto-hide
    setTimeout(function() {
        notification.style.opacity = '0';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 5000);
    
    // Click to hide
    notification.addEventListener('click', function() {
        notification.remove();
    });
}

/**
 * Utilitaire AJAX simple
 */
function ajax(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const config = Object.assign({}, defaults, options);
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            showNotification('Une erreur est survenue', 'error');
            throw error;
        });
}

// ===== Gestion des formulaires "ajout/modification de média" =====
// (affiche le bon fieldset selon le type choisi : livre / film / jeu)
// -> version robuste: délégation + init, ne dépend pas d’un id spécifique
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser l’état à l’ouverture (index + détail)
    document.querySelectorAll('.media-add-form').forEach(function(form) {
        const sel = form.querySelector('select[name="type"]');
        if (sel) form.setAttribute('data-show', sel.value || 'livre');
    });

    // Réagir à tout changement de type, même si le DOM change
    document.addEventListener('change', function(e) {
        if (!(e.target instanceof Element)) return;
        if (!e.target.matches('.media-add-form select[name="type"]')) return;
        const form = e.target.closest('.media-add-form');
        if (form) form.setAttribute('data-show', e.target.value || 'livre');
    });
});


