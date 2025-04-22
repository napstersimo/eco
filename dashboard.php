<?php
// Fichier: dashboard.php (anciennement index.php)
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect(APP_URL . '/login.php');
}

// Obtenir les statistiques du tableau de bord
$stats = getDashboardStats();

// Inclure l'en-tête
include_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h2 class="text-end">لوحة التحكم</h2>
        <hr>
    </div>
</div>

<!-- Cartes statistiques -->
<div class="row mb-4">
    <!-- Carte des étudiants -->
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <h5 class="card-title text-end">المتعلمين</h5>
                        <h1 class="display-4 text-end"><?php echo $stats['students']; ?></h1>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-user-graduate fa-3x mt-3"></i>
                    </div>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/modules/students/list.php" class="card-footer text-white text-decoration-none bg-primary-dark text-end">
                <span>عرض التفاصيل</span>
                <i class="fas fa-arrow-circle-left"></i>
            </a>
        </div>
    </div>

    <!-- Carte des professeurs -->
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <h5 class="card-title text-end">الأساتذة</h5>
                        <h1 class="display-4 text-end"><?php echo $stats['teachers']; ?></h1>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x mt-3"></i>
                    </div>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/modules/teachers/list.php" class="card-footer text-white text-decoration-none bg-success-dark text-end">
                <span>عرض التفاصيل</span>
                <i class="fas fa-arrow-circle-left"></i>
            </a>
        </div>
    </div>

    <!-- Carte des cours -->
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <h5 class="card-title text-end">المواد</h5>
                        <h1 class="display-4 text-end"><?php echo $stats['courses']; ?></h1>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-book fa-3x mt-3"></i>
                    </div>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/modules/courses/list.php" class="card-footer text-white text-decoration-none bg-info-dark text-end">
                <span>عرض التفاصيل</span>
                <i class="fas fa-arrow-circle-left"></i>
            </a>
        </div>
    </div>

    <!-- Après les cartes existantes, ajoutez ces nouvelles cartes -->
<div class="col-md-3 mb-3">
    <div class="card bg-indigo text-white">
        <div class="card-body">
            <div class="row">
                <div class="col-8">
                    <h5 class="card-title text-end">الفصول النشطة</h5>
                    <h1 class="display-4 text-end">
                        <?php
                        $db->query("SELECT COUNT(*) as count FROM classes WHERE status IN ('scheduled', 'ongoing')");
                        $active_classes = $db->single();
                        echo $active_classes['count'];
                        ?>
                    </h1>
                </div>
                <div class="col-4 text-center">
                    <i class="fas fa-chalkboard fa-3x mt-3"></i>
                </div>
            </div>
        </div>
        <a href="<?php echo APP_URL; ?>/modules/classes/list.php" class="card-footer text-white text-decoration-none bg-indigo-dark text-end">
            <span>عرض التفاصيل</span>
            <i class="fas fa-arrow-circle-left"></i>
        </a>
    </div>
</div>

    <!-- Carte des inscriptions -->
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <h5 class="card-title text-end">الإشتراكات النشطة</h5>
                        <h1 class="display-4 text-end"><?php echo $stats['enrollments']; ?></h1>
                    </div>
                    <div class="col-4 text-center">
                        <i class="fas fa-user-plus fa-3x mt-3"></i>
                    </div>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/modules/payments/student_payments.php" class="card-footer text-white text-decoration-none bg-warning-dark text-end">
                <span>عرض التفاصيل</span>
                <i class="fas fa-arrow-circle-left"></i>
            </a>
        </div>
    </div>
</div>

<!-- Bilans financiers -->
<div class="row mb-4">
    <!-- Total des revenus -->
    <div class="col-md-4 mb-3">
        <div class="card border-success">
            <div class="card-header bg-success text-white text-end">
                <h5 class="mb-0">إجمالي المداخيل</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 text-center">
                        <i class="fas fa-money-bill-wave text-success fa-3x"></i>
                    </div>
                    <div class="col-8 text-end">
                        <h2 class="text-success"><?php echo formatAmount($stats['income']); ?> د.م</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total des dépenses -->
    <div class="col-md-4 mb-3">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white text-end">
                <h5 class="mb-0">إجمالي المصاريف</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 text-center">
                        <i class="fas fa-file-invoice-dollar text-danger fa-3x"></i>
                    </div>
                    <div class="col-8 text-end">
                        <h2 class="text-danger"><?php echo formatAmount($stats['expenses']); ?> د.م</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Solde actuel -->
    <div class="col-md-4 mb-3">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white text-end">
                <h5 class="mb-0">الرصيد الحالي</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 text-center">
                        <i class="fas fa-wallet text-primary fa-3x"></i>
                    </div>
                    <div class="col-8 text-end">
                        <h2 class="<?php echo $stats['balance'] >= 0 ? 'text-primary' : 'text-danger'; ?>">
                            <?php echo formatAmount(abs($stats['balance'])); ?> د.م
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Derniers paiements et alertes -->
<div class="row">
    <!-- Derniers paiements -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-secondary text-white text-end">
                <h5 class="mb-0">آخر المدفوعات</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">المتعلم</th>
                                <th class="text-end">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $db = new Database();
                            $db->query("SELECT sp.*, s.first_name, s.last_name 
                                        FROM student_payments sp
                                        JOIN students s ON sp.student_id = s.id
                                        WHERE sp.status = 'completed'
                                        ORDER BY sp.payment_date DESC
                                        LIMIT 5");
                            $payments = $db->resultSet();
                            
                            if(count($payments) > 0):
                                foreach($payments as $payment):
                            ?>
                            <tr>
                                <td class="text-end"><?php echo formatAmount($payment['amount']); ?> د.م</td>
                                <td class="text-end"><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                                <td class="text-end"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="3" class="text-center">لا توجد مدفوعات حديثة</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo APP_URL; ?>/modules/payments/student_payments.php" class="btn btn-sm btn-secondary">
                    عرض جميع المدفوعات <i class="fas fa-arrow-circle-left"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Alertes -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-danger text-white text-end">
                <h5 class="mb-0">تنبيهات</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php
                    // Étudiants avec paiements en retard
                    $db->query("SELECT s.id, s.first_name, s.last_name, c.course_name, e.id as enrollment_id
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
                                LIMIT 5");
                    $late_payments = $db->resultSet();
                    
                    if(count($late_payments) > 0):
                        foreach($late_payments as $alert):
                    ?>
                    <a href="<?php echo APP_URL; ?>/modules/students/edit.php?id=<?php echo $alert['id']; ?>" class="list-group-item list-group-item-action text-end">
                        <div class="d-flex w-100 justify-content-between">
                            <small class="text-danger"><i class="fas fa-exclamation-circle"></i> متأخر في الدفع</small>
                            <h6 class="mb-1"><?php echo $alert['first_name'] . ' ' . $alert['last_name']; ?></h6>
                        </div>
                        <small><?php echo $alert['course_name']; ?></small>
                    </a>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="list-group-item text-center">
                        لا توجد تنبيهات حالية
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo APP_URL; ?>/modules/reports/late_payments.php" class="btn btn-sm btn-danger">
                    عرض جميع التنبيهات <i class="fas fa-arrow-circle-left"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once 'includes/footer.php';
?>