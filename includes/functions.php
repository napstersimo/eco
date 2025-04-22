


<?php
require_once 'config.php';
require_once 'db.php';

// Fonction pour nettoyer les entrées
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour rediriger
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Fonction pour vérifier si un utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier le rôle de l'utilisateur
function hasRole($role) {
    if(!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['user_role'] == $role;
}

// Fonction pour générer un numéro d'inscription
function generateRegistrationNumber() {
    $prefix = 'STD';
    $year = date('Y');
    $db = new Database();
    
    $db->query("SELECT MAX(CAST(SUBSTRING(registration_number, 8) AS UNSIGNED)) as max_id FROM students WHERE registration_number LIKE :prefix");
    $db->bind(':prefix', $prefix . $year . '%');
    $result = $db->single();
    
    $next_id = 1;
    if($result && $result['max_id']) {
        $next_id = $result['max_id'] + 1;
    }
    
    return $prefix . $year . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Fonction pour générer un ID d'employé pour un professeur
function generateEmployeeId() {
    $prefix = 'TCH';
    $year = date('Y');
    $db = new Database();
    
    $db->query("SELECT MAX(CAST(SUBSTRING(employee_id, 8) AS UNSIGNED)) as max_id FROM teachers WHERE employee_id LIKE :prefix");
    $db->bind(':prefix', $prefix . $year . '%');
    $result = $db->single();
    
    $next_id = 1;
    if($result && $result['max_id']) {
        $next_id = $result['max_id'] + 1;
    }
    
    return $prefix . $year . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Fonction pour formater les montants
function formatAmount($amount) {
    return number_format($amount, 2);
}

// Fonction pour obtenir le solde actuel
function getCurrentBalance() {
    $db = new Database();
    
    // Total des paiements des étudiants
    $db->query("SELECT SUM(amount) as total FROM student_payments WHERE status = 'completed'");
    $student_payments = $db->single();
    $income = $student_payments['total'] ? $student_payments['total'] : 0;
    
    // Total des paiements aux professeurs
    $db->query("SELECT SUM(amount) as total FROM teacher_payments WHERE status = 'completed'");
    $teacher_payments = $db->single();
    $teacher_expense = $teacher_payments['total'] ? $teacher_payments['total'] : 0;
    
    // Total des autres dépenses
    $db->query("SELECT SUM(amount) as total FROM expenses");
    $other_expenses = $db->single();
    $expense = $other_expenses['total'] ? $other_expenses['total'] : 0;
    
    // Calcul du solde
    return $income - ($teacher_expense + $expense);
}

// Fonction pour obtenir les statistiques du tableau de bord
function getDashboardStats() {
    $db = new Database();
    
    // Nombre d'étudiants actifs
    $db->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $students = $db->single();
    
    // Nombre de professeurs actifs
    $db->query("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'");
    $teachers = $db->single();
    
    // Nombre de cours actifs
    $db->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $courses = $db->single();
    
    // Nombre d'inscriptions actives
    $db->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
    $enrollments = $db->single();
    
    // Total des revenus
    $db->query("SELECT SUM(amount) as total FROM student_payments WHERE status = 'completed'");
    $income = $db->single();
    
    // Total des dépenses
    $db->query("SELECT SUM(amount) as total FROM expenses");
    $expense_result = $db->single();
    $expenses = $expense_result['total'] ? $expense_result['total'] : 0;
    
    $db->query("SELECT SUM(amount) as total FROM teacher_payments WHERE status = 'completed'");
    $teacher_payments = $db->single();
    $teacher_expenses = $teacher_payments['total'] ? $teacher_payments['total'] : 0;
    
    $total_expenses = $expenses + $teacher_expenses;
    
    // Solde actuel
    $current_balance = $income['total'] ? $income['total'] - $total_expenses : 0 - $total_expenses;
    
    return [
        'students' => $students['count'],
        'teachers' => $teachers['count'],
        'courses' => $courses['count'],
        'enrollments' => $enrollments['count'],
        'income' => $income['total'] ? $income['total'] : 0,
        'expenses' => $total_expenses,
        'balance' => $current_balance
    ];
}

// Fonction pour calculer le paiement d'un professeur selon sa commission
function calculateTeacherPayment($teacher_id, $class_id) {
    $db = new Database();
    
    // Récupérer le pourcentage de commission du professeur
    $db->query("SELECT commission_percentage FROM teachers WHERE id = :teacher_id");
    $db->bind(':teacher_id', $teacher_id);
    $teacher = $db->single();
    
    if(!$teacher) {
        return 0;
    }
    
    $commission = $teacher['commission_percentage'];
    
    // Récupérer le total des frais de cours pour cette classe
    $db->query("SELECT c.fee, COUNT(e.id) as student_count 
                FROM classes cl
                JOIN courses c ON cl.course_id = c.id
                JOIN enrollments e ON cl.id = e.class_id
                WHERE cl.id = :class_id AND cl.teacher_id = :teacher_id
                GROUP BY c.id");
    $db->bind(':class_id', $class_id);
    $db->bind(':teacher_id', $teacher_id);
    $class = $db->single();
    
    if(!$class) {
        return 0;
    }
    
    $total_fees = $class['fee'] * $class['student_count'];
    
    // Calculer le paiement du professeur
    return ($total_fees * $commission) / 100;
    
}
