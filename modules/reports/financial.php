<?php
// Rapport financier
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de date
$start_date = isset($_GET['start_date']) ? clean($_GET['start_date']) : date('Y-m-01'); // Premier jour du mois en cours
$end_date = isset($_GET['end_date']) ? clean($_GET['end_date']) : date('Y-m-t'); // Dernier jour du mois en cours

// Récupérer les revenus (paiements des étudiants)
$db = new Database();
$db->query("SELECT SUM(amount) as total FROM student_payments WHERE status = 'completed' AND payment_date BETWEEN :start_date AND :end_date");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$income_result = $db->single();
$total_income = $income_result['total'] ? $income_result['total'] : 0;

// Récupérer les dépenses (paiements aux professeurs)
$db->query("SELECT SUM(amount) as total FROM teacher_payments WHERE status = 'completed' AND payment_date BETWEEN :start_date AND :end_date");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$teacher_expense_result = $db->single();
$total_teacher_expense = $teacher_expense_result['total'] ? $teacher_expense_result['total'] : 0;

// Récupérer les autres dépenses
$db->query("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN :start_date AND :end_date");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$other_expense_result = $db->single();
$total_other_expense = $other_expense_result['total'] ? $other_expense_result['total'] : 0;

// Calculer le total des dépenses
$total_expense = $total_teacher_expense + $total_other_expense;

// Calculer le résultat net
$net_result = $total_income - $total_expense;

// Récupérer les détails des revenus par catégorie (cours)
$db->query("SELECT c.course_name, SUM(sp.amount) as total
           FROM student_payments sp
           LEFT JOIN enrollments e ON sp.enrollment_id = e.id
           LEFT JOIN classes cl ON e.class_id = cl.id
           LEFT JOIN courses c ON cl.course_id = c.id
           WHERE sp.status = 'completed' AND sp.payment_date BETWEEN :start_date AND :end_date
           GROUP BY c.id
           ORDER BY total DESC");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$income_by_course = $db->resultSet();

// Récupérer les détails des dépenses par catégorie
$db->query("SELECT category, SUM(amount) as total FROM expenses WHERE expense_date BETWEEN :start_date AND :end_date GROUP BY category ORDER BY total DESC");
$db->bind(':start_date', $start_date);
$db->bind(':end_date', $end_date);
$expenses_by_category = $db->resultSet();

// Récupération des statistiques des étudiants pour éviter les erreurs
$db->query("SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_students,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_students,
            SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated_students,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_students
          FROM students");
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

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-end">التقرير المالي</h5>
            </div>
            <div class="card-body">
                <!-- Formulaire de filtre par date -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label text-end d-block">من تاريخ</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label text-end d-block">إلى تاريخ</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> تصفية
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Résumé financier -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5 class="text-end">إجمالي المداخيل:</h5>
                                <h3 class="text-end text-success"><?php echo formatAmount($total_income); ?> د.م</h3>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-end">إجمالي المصاريف:</h5>
                                <h3 class="text-end text-danger"><?php echo formatAmount($total_expense); ?> د.م</h3>
                            </div>
                            <div class="col-md-4">
                                <h5 class="text-end">النتيجة الصافية:</h5>
                                <h3 class="text-end <?php echo ($net_result >= 0) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo formatAmount(abs($net_result)); ?> د.م
                                    <?php echo ($net_result >= 0) ? '(ربح)' : '(خسارة)'; ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détails des revenus et dépenses -->
                <div class="row">
                    <!-- Revenus par cours -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white text-end">
                                <h5 class="mb-0">المداخيل حسب المواد</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-end">المادة</th>
                                                <th class="text-end">المبلغ</th>
                                                <th class="text-end">النسبة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($income_by_course) > 0): ?>
                                                <?php foreach ($income_by_course as $item): ?>
                                                    <tr>
                                                        <td class="text-end"><?php echo $item['course_name'] ? $item['course_name'] : 'غير محدد'; ?></td>
                                                        <td class="text-end"><?php echo formatAmount($item['total']); ?> د.م</td>
                                                        <td class="text-end">
                                                            <?php echo $total_income > 0 ? number_format(($item['total'] / $total_income) * 100, 2) : 0; ?>%
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">لا توجد بيانات</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <th class="text-end">المجموع</th>
                                                <th class="text-end"><?php echo formatAmount($total_income); ?> د.م</th>
                                                <th class="text-end">100%</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dépenses par catégorie -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white text-end">
                                <h5 class="mb-0">المصاريف حسب الفئة</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-end">الفئة</th>
                                                <th class="text-end">المبلغ</th>
                                                <th class="text-end">النسبة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Ajouter les paiements des professeurs comme une catégorie -->
                                            <tr>
                                                <td class="text-end">رواتب الأساتذة</td>
                                                <td class="text-end"><?php echo formatAmount($total_teacher_expense); ?> د.م</td>
                                                <td class="text-end">
                                                    <?php echo $total_expense > 0 ? number_format(($total_teacher_expense / $total_expense) * 100, 2) : 0; ?>%
                                                </td>
                                            </tr>
                                            
                                            <?php if (count($expenses_by_category) > 0): ?>
                                                <?php foreach ($expenses_by_category as $item): ?>
                                                    <tr>
                                                        <td class="text-end"><?php echo $item['category'] ? $item['category'] : 'غير محدد'; ?></td>
                                                        <td class="text-end"><?php echo formatAmount($item['total']); ?> د.م</td>
                                                        <td class="text-end">
                                                            <?php echo $total_expense > 0 ? number_format(($item['total'] / $total_expense) * 100, 2) : 0; ?>%
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">لا توجد بيانات</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <th class="text-end">المجموع</th>
                                                <th class="text-end"><?php echo formatAmount($total_expense); ?> د.م</th>
                                                <th class="text-end">100%</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'export -->
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_financial.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=excel" class="btn btn-success me-2">
                        <i class="fas fa-file-excel"></i> تصدير إلى Excel
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_financial.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&format=pdf" class="btn btn-danger">
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