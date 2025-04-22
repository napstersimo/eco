<?php
// Fichier: index.php (à la racine du site)
// Redirection vers la page de login si l'utilisateur n'est pas connecté
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    // S'il n'est pas connecté, rediriger vers la page de login
    // Utiliser une URL absolue plutôt que relative
    header('Location: http://ecogest.iceiy.com/login.php');
    exit;
} else {
    // S'il est connecté, rediriger vers le tableau de bord
    header('Location: http://ecogest.iceiy.com/dashboard.php');
    exit;
}
?>