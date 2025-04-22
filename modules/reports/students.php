<?php
// Rapport des étudiants
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres
$status = isset($_GET['status']) ? clean($_GET['status']) : '';
$course_id = isset($_GET['course_id']) ? clean($_GET['course_id']) : '';

// Requête de base pour les statistiques
$query = "SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_students,
            SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated_students,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_students
          FROM students";

$db = new Database();
$db->query($query);
$stats = $db->single();

// Si stats est null, initialiser avec des valeurs par défaut
if (!$stats) {
    $stats = [
        'total_students' => 0,
        'active_students' => 0,
        'inactive_students' => 0,
        'graduated_students' => 0,
        'suspended_students' => 0
    ];
}

// Récupérer les cours pour le filtre
$db->query("SELECT id, course_name FROM courses ORDER BY course_name");
$courses = $db->resultSet();

// Requête pour les étudiants
$query = "SELECT s.*, 
           (SELECT COUNT(*) FROM enrollments WHERE student_id = s.id) as enrollment_count,
           (SELECT SUM(amount) FROM student_payments WHERE student_id = s.id AND status = 'completed') as total_payments
          FROM students s
          WHERE 1=1";

if (!empty($status)) {
    $query .= " AND s.status = :status";
}

if (!empty($course_id)) {
    $query .= " AND s.id IN (SELECT student_id FROM enrollments e JOIN classes cl ON e.class_id = cl.id WHERE cl.course_id = :course_id)";
}

$query .= " ORDER BY s.first_name, s.last_name";

$db->query($query);

if (!empty($status)) {
    $db->bind(':status', $status);
}

if (!empty($course_id)) {
    $db->bind(':course_id', $course_id);
}

$students = $db->resultSet();

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-end">تقرير المتعلمين</h5>
            </div>
            <div class="card-body">
                <!-- Formulaire de filtre -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label text-end d-block">الحالة</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">-- جميع الحالات --</option>
                                <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>نشط</option>
                                <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                                <option value="graduated" <?php echo ($status == 'graduated') ? 'selected' : ''; ?>>تخرج</option>
                                <option value="suspended" <?php echo ($status == 'suspended') ? 'selected' : ''; ?>>معلق</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="course_id" class="form-label text-end d-block">المادة</label>
                            <select id="course_id" name="course_id" class="form-select">
                                <option value="">-- جميع المواد --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo ($course_id == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo $course['course_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> تصفية
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Résumé des statistiques -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h5 class="text-center">العدد الإجمالي</h5>
                                <h3 class="text-center"><?php echo isset($stats['total_students']) ? $stats['total_students'] : 0; ?></h3>
                            </div>
                            <div class="col">
                                <h5 class="text-center">نشط</h5>
                                <h3 class="text-center text-success"><?php echo isset($stats['active_students']) ? $stats['active_students'] : 0; ?></h3>
                            </div>
                            <div class="col">
                                <h5 class="text-center">غير نشط</h5>
                                <h3 class="text-center text-secondary"><?php echo isset($stats['inactive_students']) ? $stats['inactive_students'] : 0; ?></h3>
                            </div>
                            <div class="col">
                                <h5 class="text-center">تخرج</h5>
                                <h3 class="text-center text-primary"><?php echo isset($stats['graduated_students']) ? $stats['graduated_students'] : 0; ?></h3>
                            </div>
                            <div class="col">
                                <h5 class="text-center">معلق</h5>
                                <h3 class="text-center text-danger"><?php echo isset($stats['suspended_students']) ? $stats['suspended_students'] : 0; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des étudiants -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم التسجيل</th>
                                <th class="text-end">الإسم الكامل</th>
                                <th class="text-end">الهاتف</th>
                                <th class="text-end">البريد الإلكتروني</th>
                                <th class="text-end">تاريخ التسجيل</th>
                                <th class="text-end">عدد الدورات</th>
                                <th class="text-end">إجمالي المدفوعات</th>
                                <th class="text-end">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $student['registration_number']; ?></td>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $student['id']; ?>">
                                                <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?php echo $student['phone']; ?></td>
                                        <td class="text-end"><?php echo $student['email']; ?></td>
                                        <td class="text-end"><?php echo $student['enrollment_date'] ? date('d/m/Y', strtotime($student['enrollment_date'])) : '-'; ?></td>
                                        <td class="text-end"><?php echo $student['enrollment_count']; ?></td>
                                        <td class="text-end"><?php echo formatAmount($student['total_payments'] ? $student['total_payments'] : 0); ?> د.م</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($student['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'secondary';
                                                    $status_text = 'غير نشط';
                                                    break;
                                                case 'graduated':
                                                    $status_class = 'primary';
                                                    $status_text = 'تخرج';
                                                    break;
                                                case 'suspended':
                                                    $status_class = 'danger';
                                                    $status_text = 'معلق';
                                                    break;
                                                default:
                                                    $status_class = 'secondary';
                                                    $status_text = 'غير محدد';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد بيانات</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Boutons d'export -->
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_students.php?status=<?php echo $status; ?>&course_id=<?php echo $course_id; ?>&format=excel" class="btn btn-success me-2">
                        <i class="fas fa-file-excel"></i> تصدير إلى Excel
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_students.php?status=<?php echo $status; ?>&course_id=<?php echo $course_id; ?>&format=pdf" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> تصدير إلى PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>
</div>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>