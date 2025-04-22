<?php
// Suppression d'une classe
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/classes/list.php');
}

$class_id = (int)$_GET['id'];

// Vérifier si des étudiants sont inscrits à cette classe
$db = new Database();
$db->query("SELECT COUNT(*) as count FROM enrollments WHERE class_id = :class_id");
$db->bind(':class_id', $class_id);
$result = $db->single();

if ($result['count'] > 0) {
    // Rediriger avec un message d'erreur
    redirect(APP_URL . '/modules/classes/list.php?error=has_students');
} else {
    // Supprimer la classe
    $db->query("DELETE FROM classes WHERE id = :id");
    $db->bind(':id', $class_id);
    
    if ($db->execute()) {
        // Rediriger avec un message de succès
        redirect(APP_URL . '/modules/classes/list.php?success=delete');
    } else {
        // Rediriger avec un message d'erreur
        redirect(APP_URL . '/modules/classes/list.php?error=delete_failed');
    }
}