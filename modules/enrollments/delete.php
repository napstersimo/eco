<?php
// Suppression d'une inscription
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/enrollments/list.php');
}

$enrollment_id = (int)$_GET['id'];

// Récupérer l'ID de classe pour la redirection
$db = new Database();
$db->query("SELECT class_id FROM enrollments WHERE id = :id");
$db->bind(':id', $enrollment_id);
$enrollment = $db->single();
$class_id = $enrollment ? $enrollment['class_id'] : null;

// Commencer une transaction
$db->beginTransaction();

try {
    // Supprimer les paiements associés
    $db->query("DELETE FROM student_payments WHERE enrollment_id = :enrollment_id");
    $db->bind(':enrollment_id', $enrollment_id);
    $db->execute();
    
    // Supprimer l'inscription
    $db->query("DELETE FROM enrollments WHERE id = :id");
    $db->bind(':id', $enrollment_id);
    $db->execute();
    
    // Valider la transaction
    $db->endTransaction();
    
    // Rediriger avec un message de succès
    if ($class_id) {
        redirect(APP_URL . '/modules/enrollments/list.php?class_id=' . $class_id . '&success=delete');
    } else {
        redirect(APP_URL . '/modules/enrollments/list.php?success=delete');
    }
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $db->cancelTransaction();
    
    // Rediriger avec un message d'erreur
    if ($class_id) {
        redirect(APP_URL . '/modules/enrollments/list.php?class_id=' . $class_id . '&error=delete_failed');
    } else {
        redirect(APP_URL . '/modules/enrollments/list.php?error=delete_failed');
    }
}