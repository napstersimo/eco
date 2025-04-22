

<?php
// Rapport des paiements en retard
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Requête pour les étudiants avec des paiements en retard
$query = "SELECT s.id, s.first_name, s.last_name, s.registration_number, s.phone, s.email,
           c.id as course_id, c.course_name, c.fee, 
           cl.id as class_id, cl.class_name,
           e.id as enrollment_id,
           COALESCE(p.paid_amount, 0) as paid_amount,
           (c.fee - COALESCE(p.paid_amount, 0)) as due_amount
          FROM students s
          JOIN enrollments e ON s.id = e.student_id
          JOIN classes cl ON e.class_id = cl.id
          JOIN courses c ON cl.course_id = c.id
          LEFT JOIN (
              SELECT enrollment_id, SUM(amount) as paid_amount
              FROM student_payments
              WHERE status = 'completed'
              GROUP BY enrollment_id
          ) p ON e.id = p.enrollment_id
          WHERE (p.paid_amount IS NULL OR p.paid_amount < c.fee)
            AND e.status = 'active'
          ORDER BY s.first_name, s.last_name";

$db = new Database();
$db->query($query);
$late_payments = $db->resultSet();

// Calculer le total dû
$total_due = 0;
foreach ($late_payments as $payment) {
    $total_due += $payment['due_amount'];
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0 text-end">تقرير المدفوعات المتأخرة</h5>
            </div>
            <div class="card-body">
                <!-- Résumé -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-end">عدد الدفعات المتأخرة:</h5>
                                <h3 class="text-end text-danger"><?php echo count($late_payments); ?></h3>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-end">إجمالي المبلغ المستحق:</h5>
                                <h3 class="text-end text-danger"><?php echo formatAmount($total_due); ?> د.م</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des paiements en retard -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">المتعلم</th>
                                <th class="text-end">رقم الهاتف</th>
                                <th class="text-end">المادة</th>
                                <th class="text-end">الفصل</th>
                                <th class="text-end">الرسوم الكاملة</th>
                                <th class="text-end">المبلغ المدفوع</th>
                                <th class="text-end">المبلغ المستحق</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($late_payments) > 0): ?>
                                <?php foreach ($late_payments as $payment): ?>
                                    <tr>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $payment['id']; ?>">
                                                <?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?>
                                            </a>
                                            <small class="d-block text-muted"><?php echo $payment['registration_number']; ?></small>
                                        </td>
                                        <td class="text-end"><?php echo $payment['phone']; ?></td>
                                        <td class="text-end"><?php echo $payment['course_name']; ?></td>
                                        <td class="text-end"><?php echo $payment['class_name']; ?></td>
                                        <td class="text-end"><?php echo formatAmount($payment['fee']); ?> د.م</td>
                                        <td class="text-end"><?php echo formatAmount($payment['paid_amount']); ?> د.م</td>
                                        <td class="text-end text-danger"><?php echo formatAmount($payment['due_amount']); ?> د.م</td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $payment['id']; ?>&enrollment_id=<?php echo $payment['enrollment_id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض الملف الشخصي">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد مدفوعات متأخرة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Boutons d'export -->
                <div class="d-flex justify-content-end mt-3">
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_late_payments.php?format=excel" class="btn btn-success me-2">
                        <i class="fas fa-file-excel"></i> تصدير إلى Excel
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/reports/export_late_payments.php?format=pdf" class="btn btn-danger">
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
                                                        <td class="text-end"><?php echo $item['course_name']; ?></td>
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
                                                        <td class="text-end"><?php echo $item['category']; ?></td>
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

