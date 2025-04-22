<?php
// Fichier: modules/reports/teacher_salaries.php
// Page pour le calcul et l'affichage des salaires des professeurs
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de date
$start_date = isset($_GET['start_date']) ? clean($_GET['start_date']) : date('Y-m-01'); // Premier jour du mois en cours
$end_date = isset($_GET['end_date']) ? clean($_GET['end_date']) : date('Y-m-t'); // Dernier jour du mois en cours
$teacher_id = isset($_GET['teacher_id']) ? (int)clean($_GET['teacher_id']) : '';

// Récupérer la liste des professeurs pour le filtre
$db = new Database();
$db->query("SELECT id, first_name, last_name, employee_id FROM teachers WHERE status = 'active' ORDER BY first_name, last_name");
$teachers = $db->resultSet();

// Construire la requête pour les salaires des professeurs
$query = "SELECT 
            t.id as teacher_id,
            t.first_name,
            t.last_name,
            t.employee_id,
            t.commission_percentage,
            c.id as class_id,
            c.class_name,
            co.course_name,
            co.fee,
            (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id AND e.status = 'active') as student_count,
            (SELECT SUM(amount) FROM teacher_payments tp WHERE tp.teacher_id = t.id AND tp.class_id = c.id AND tp.status = 'completed' AND tp.payment_date BETWEEN :start_date AND :end_date) as paid_amount
          FROM teachers t
          JOIN classes c ON t.id = c.teacher_id
          JOIN courses co ON c.course_id = co.id
          WHERE c.status IN ('scheduled', 'ongoing')";

if (!empty($teacher_id)) {
    $query .= " AND t.id = :teacher_id";
}

$query .= " ORDER BY t.first_name, t.last_name, co.course_name";

$db->query($query);
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);

if (!empty($teacher_id)) {
    $db->bind(':teacher_id', $teacher_id);
}

$salaries = $db->resultSet();

// Organiser les données par professeur
$teacher_salaries = [];
$total_due = 0;
$total_paid = 0;
$total_remaining = 0;

foreach ($salaries as $salary) {
    $teacher_id = $salary['teacher_id'];
    
    if (!isset($teacher_salaries[$teacher_id])) {
        $teacher_salaries[$teacher_id] = [
            'teacher_id' => $teacher_id,
            'first_name' => $salary['first_name'],
            'last_name' => $salary['last_name'],
            'employee_id' => $salary['employee_id'],
            'commission_percentage' => $salary['commission_percentage'],
            'classes' => [],
            'total_due' => 0,
            'total_paid' => 0,
            'total_remaining' => 0
        ];
    }
    
    // Calculer le montant dû pour cette classe
    $total_fees = $salary['fee'] * $salary['student_count'];
    $due_amount = ($total_fees * $salary['commission_percentage']) / 100;
    $paid_amount = $salary['paid_amount'] ? $salary['paid_amount'] : 0;
    $remaining_amount = $due_amount - $paid_amount;
    
    // Ajouter les données de la classe
    $teacher_salaries[$teacher_id]['classes'][] = [
        'class_id' => $salary['class_id'],
        'class_name' => $salary['class_name'],
        'course_name' => $salary['course_name'],
        'fee' => $salary['fee'],
        'student_count' => $salary['student_count'],
        'due_amount' => $due_amount,
        'paid_amount' => $paid_amount,
        'remaining_amount' => $remaining_amount
    ];
    
    // Mettre à jour les totaux du professeur
    $teacher_salaries[$teacher_id]['total_due'] += $due_amount;
    $teacher_salaries[$teacher_id]['total_paid'] += $paid_amount;
    $teacher_salaries[$teacher_id]['total_remaining'] += $remaining_amount;
    
    // Mettre à jour les totaux généraux
    $total_due += $due_amount;
    $total_paid += $paid_amount;
    $total_remaining += $remaining_amount;
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-end">رواتب الأساتذة</h5>
            </div>
            <div class="card-body">
                <!-- Formulaire de filtre -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="teacher_id" class="form-label text-end d-block">الأستاذ</label>
                            <select id="teacher_id" name="teacher_id" class="form-select">
                                <option value="">-- جميع الأساتذة --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo ($teacher_id == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label text-end d-block">من تاريخ</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label text-end d-block">إلى تاريخ</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> تصفية
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Résumé des salaires -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5 class="text-end">إجمالي المستحقات:</h5>
                                <h3 class="text-end text-primary"><?php echo formatAmount($total_due); ?> د.م</h3>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-end">إجمالي المدفوعات:</h5>
                                <h3 class="text-end text-success"><?php echo formatAmount($total_paid); ?> د.م</h3>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-end">إجمالي المتبقي:</h5>
                                <h3 class="text-end text-danger"><?php echo formatAmount($total_remaining); ?> د.م</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des salaires des professeurs -->
                <?php if (count($teacher_salaries) > 0): ?>
                    <?php foreach ($teacher_salaries as $teacher): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?> (<?php echo $teacher['employee_id']; ?>)</h5>
                                    <div>
                                        <span class="badge bg-info me-2">نسبة العمولة: <?php echo $teacher['commission_percentage']; ?>%</span>
                                        <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $teacher['teacher_id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> إضافة دفعة
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-end">المادة</th>
                                                <th class="text-end">الفصل</th>
                                                <th class="text-end">الرسوم</th>
                                                <th class="text-end">عدد الطلاب</th>
                                                <th class="text-end">المستحق</th>
                                                <th class="text-end">المدفوع</th>
                                                <th class="text-end">المتبقي</th>
                                                <th class="text-center">الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teacher['classes'] as $class): ?>
                                                <tr>
                                                    <td class="text-end"><?php echo $class['course_name']; ?></td>
                                                    <td class="text-end"><?php echo $class['class_name']; ?></td>
                                                    <td class="text-end"><?php echo formatAmount($class['fee']); ?> د.م</td>
                                                    <td class="text-end"><?php echo $class['student_count']; ?></td>
                                                    <td class="text-end text-primary"><?php echo formatAmount($class['due_amount']); ?> د.م</td>
                                                    <td class="text-end text-success"><?php echo formatAmount($class['paid_amount']); ?> د.م</td>
                                                    <td class="text-end text-danger"><?php echo formatAmount($class['remaining_amount']); ?> د.م</td>
                                                    <td class="text-center">
                                                        <?php if ($class['remaining_amount'] > 0): ?>
                                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $teacher['teacher_id']; ?>&class_id=<?php echo $class['class_id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                                <i class="fas fa-money-bill-wave"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="<?php echo APP_URL; ?>/modules/payments/teacher_payments.php?teacher_id=<?php echo $teacher['teacher_id']; ?>&class_id=<?php echo $class['class_id']; ?>" class="btn btn-info btn-sm text-white" title="عرض الدفعات">
                                                            <i class="fas fa-list"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <th class="text-end" colspan="4">المجموع</th>
                                                <th class="text-end text-primary"><?php echo formatAmount($teacher['total_due']); ?> د.م</th>
                                                <th class="text-end text-success"><?php echo formatAmount($teacher['total_paid']); ?> د.م</th>
                                                <th class="text-end text-danger"><?php echo formatAmount($teacher['total_remaining']); ?> د.م</th>
                                                <th class="text-center"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        لا توجد بيانات متاحة للعرض
                    </div>
                <?php endif; ?>

                <!-- Boutons d'export -->
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_teacher_salaries.php?teacher_id=<?php echo $teacher_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=excel" class="btn btn-success me-2">
                        <i class="fas fa-file-excel"></i> تصدير إلى Excel
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_teacher_salaries.php?teacher_id=<?php echo $teacher_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=pdf" class="btn btn-danger">
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