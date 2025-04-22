

<?php
// API pour récupérer les inscriptions d'un étudiant
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Vérifier si l'ID de l'étudiant est fourni
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Student ID is required']);
    exit;
}

$student_id = (int)$_GET['student_id'];

// Récupérer les inscriptions de l'étudiant
$db = new Database();
$db->query("SELECT e.id, c.course_name, cl.class_name
           FROM enrollments e
           JOIN classes cl ON e.class_id = cl.id
           JOIN courses c ON cl.course_id = c.id
           WHERE e.student_id = :student_id AND e.status = 'active'");
$db->bind(':student_id', $student_id);
$enrollments = $db->resultSet();

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode($enrollments);
exit;

/*** 2. Fichier modules/api/get_teacher_classes.php ***/

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
           WHERE cl.teacher_id = :teacher_id AND cl.status IN ('scheduled', 'ongoing')");
$db->bind(':teacher_id', $teacher_id);
$classes = $db->resultSet();

// Récupérer le pourcentage de commission du professeur
$db->query("SELECT commission_percentage FROM teachers WHERE id = :teacher_id");
$db->bind(':teacher_id', $teacher_id);
$teacher = $db->single();
$commission_percentage = $teacher['commission_percentage'];

// Calculer le paiement suggéré pour chaque classe
foreach ($classes as &$class) {
    $class['suggested_payment'] = calculateTeacherPayment($teacher_id, $class['id']);
}

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode([
    'classes' => $classes,
    'commission_percentage' => $commission_percentage
]);
exit;

/*** 3. Fichier modules/api/get_course_fee.php ***/

<?php
// API pour récupérer les frais d'un cours
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Vérifier si l'ID du cours est fourni
if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Course ID is required']);
    exit;
}

$course_id = (int)$_GET['course_id'];

// Récupérer les frais du cours
$db = new Database();
$db->query("SELECT fee FROM courses WHERE id = :course_id");
$db->bind(':course_id', $course_id);
$course = $db->single();

if (!$course) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode(['fee' => $course['fee']]);
exit;

/*** 4. Fichier modules/api/get_students_by_class.php ***/

<?php
// API pour récupérer les étudiants d'une classe
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Vérifier si l'ID de la classe est fourni
if (!isset($_GET['class_id']) || empty($_GET['class_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Class ID is required']);
    exit;
}

$class_id = (int)$_GET['class_id'];

// Récupérer les étudiants de la classe
$db = new Database();
$db->query("SELECT s.id, s.first_name, s.last_name, s.registration_number, e.status
           FROM students s
           JOIN enrollments e ON s.id = e.student_id
           WHERE e.class_id = :class_id
           ORDER BY s.first_name, s.last_name");
$db->bind(':class_id', $class_id);
$students = $db->resultSet();

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode($students);
exit;

/*** 5. Fichier modules/api/check_attendance.php ***/

<?php
// API pour enregistrer la présence
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Récupérer et nettoyer les données
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['class_id']) || !isset($data['student_id']) || !isset($data['attendance_date']) || !isset($data['status'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$class_id = (int)$data['class_id'];
$student_id = (int)$data['student_id'];
$attendance_date = clean($data['attendance_date']);
$status = clean($data['status']);
$remarks = isset($data['remarks']) ? clean($data['remarks']) : '';

// Validation
if (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

// Vérifier si l'enregistrement existe déjà
$db = new Database();
$db->query("SELECT id FROM attendance WHERE class_id = :class_id AND student_id = :student_id AND attendance_date = :attendance_date");
$db->bind(':class_id', $class_id);
$db->bind(':student_id', $student_id);
$db->bind(':attendance_date', $attendance_date);
$existing = $db->single();

if ($existing) {
    // Mettre à jour l'enregistrement existant
    $db->query("UPDATE attendance 
               SET status = :status, remarks = :remarks, updated_at = NOW()
               WHERE class_id = :class_id AND student_id = :student_id AND attendance_date = :attendance_date");
    
    $db->bind(':status', $status);
    $db->bind(':remarks', $remarks);
    $db->bind(':class_id', $class_id);
    $db->bind(':student_id', $student_id);
    $db->bind(':attendance_date', $attendance_date);
    
    if ($db->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to update attendance']);
    }
} else {
    // Créer un nouvel enregistrement
    $db->query("INSERT INTO attendance (class_id, student_id, attendance_date, status, remarks)
               VALUES (:class_id, :student_id, :attendance_date, :status, :remarks)");
    
    $db->bind(':class_id', $class_id);
    $db->bind(':student_id', $student_id);
    $db->bind(':attendance_date', $attendance_date);
    $db->bind(':status', $status);
    $db->bind(':remarks', $remarks);
    
    if ($db->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Attendance recorded successfully']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to record attendance']);
    }
}
exit;