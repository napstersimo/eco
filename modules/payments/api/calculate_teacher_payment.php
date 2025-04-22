<?php
// Fichier: modules/api/calculate_teacher_payment.php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Vérifier si les IDs sont fournis
if (!isset($_GET['teacher_id']) || empty($_GET['teacher_id']) || !isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Teacher ID and Class ID are required']);
    exit;
}

$teacher_id = (int)$_GET['teacher_id'];
$class_id = (int)$_GET['class_id'];

// Calculer le paiement
$payment = calculateTeacherPayment($teacher_id, $class_id);

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'payment' => $payment
]);
exit;
?>