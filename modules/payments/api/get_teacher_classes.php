<?php
// API pour récupérer les classes d'un professeur
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Vérifier si l'ID du professeur est fourni
if (!isset($_GET['teacher_id']) || empty($_GET['teacher_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Teacher ID is required']);
    exit;
}

$teacher_id = (int)$_GET['teacher_id'];

// Récupérer les classes du professeur
$db = new Database();
$db->query("SELECT cl.id, c.course_name, cl.class_name
           FROM classes cl
           JOIN courses c ON cl.course_id = c.id
           WHERE cl.teacher_id = :teacher_id AND cl.status IN ('scheduled', 'ongoing')
           ORDER BY c.course_name, cl.class_name");
$db->bind(':teacher_id', $teacher_id);
$classes = $db->resultSet();

// Récupérer le pourcentage de commission du professeur
$db->query("SELECT commission_percentage FROM teachers WHERE id = :teacher_id");
$db->bind(':teacher_id', $teacher_id);
$teacher = $db->single();
$commission_percentage = $teacher ? $teacher['commission_percentage'] : 0;

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode([
    'classes' => $classes,
    'commission_percentage' => $commission_percentage
]);
exit;
?>