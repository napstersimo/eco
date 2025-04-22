

<?php
// Configuration générale
define('APP_NAME', 'Système de Gestion d\'École');
define('APP_URL', 'http://ecogest.iceiy.com');
define('APP_VERSION', '1.0.0');

// Configuration de la base de données
define('DB_HOST', 'sql102.iceiy.com');
define('DB_USER', 'icei_38794935');
define('DB_PASS', 'H5W7vVa2cR3l');
define('DB_NAME', 'icei_38794935_ecogest');

// Configuration des dossiers
define('BASE_PATH', dirname(dirname(__FILE__)));
define('INCLUDE_PATH', BASE_PATH . '/includes');
define('MODULE_PATH', BASE_PATH . '/modules');
define('ASSET_PATH', BASE_PATH . '/assets');

// Configuration des sessions
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fuseau horaire
date_default_timezone_set('UTC');

