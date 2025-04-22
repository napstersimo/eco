<?php
// Fichier: modules/payments/view_payment.php
// Affichage des détails d'un paiement étudiant
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/payments/student_payments.php');
}

$payment_id = (int)$_GET['id'];

// Récupérer les informations du paiement
$db = new Database();
$db->query("SELECT sp.*, 
           s.first_name as student_first_name, s.last_name as student_last_name, 
           s.registration_number, s.phone as student_phone, s.email as student_email,
           e.enrollment_date, e.status as enrollment_status,
           cl.class_name, c.course_name, c.course_code, c.fee
           FROM student_payments sp
           JOIN students s ON sp.student_id = s.id
           LEFT JOIN enrollments e ON sp.enrollment_id = e.id
           LEFT JOIN classes cl ON e.class_id = cl.id
           LEFT JOIN courses c ON cl.course_id = c.id
           WHERE sp.id = :id");
$db->bind(':id', $payment_id);
$payment = $db->single();

// Vérifier si le paiement existe
if (!$payment) {
    redirect(APP_URL . '/modules/payments/student_payments.php');
}

// Vérifier si un message de succès est passé
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'update') {
        $success_message = 'تم تحديث بيانات الدفعة بنجاح!';
    }
}

// Récupérer les autres paiements du même étudiant
$db->query("SELECT sp.*, c.course_name, cl.class_name
           FROM student_payments sp
           LEFT JOIN enrollments e ON sp.enrollment_id = e.id
           LEFT JOIN classes cl ON e.class_id = cl.id
           LEFT JOIN courses c ON cl.course_id = c.id
           WHERE sp.student_id = :student_id AND sp.id != :payment_id
           ORDER BY sp.payment_date DESC
           LIMIT 5");
$db->bind(':student_id', $payment['student_id']);
$db->bind(':payment_id', $payment_id);
$other_payments = $db->resultSet();

// Récupérer le total payé pour ce cours (si applicable)
$total_paid = 0;
if ($payment['enrollment_id']) {
    $db->query("SELECT SUM(amount) as total_paid 
               FROM student_payments 
               WHERE enrollment_id = :enrollment_id AND status = 'completed'");
    $db->bind(':enrollment_id', $payment['enrollment_id']);
    $payment_sum = $db->single();
    $total_paid = $payment_sum['total_paid'] ? $payment_sum['total_paid'] : 0;
}

// Calculer le montant restant (si applicable)
$remaining = 0;
if ($payment['fee']) {
    $remaining = $payment['fee'] - $total_paid;
}

// Calculer le pourcentage payé (si applicable)
$payment_percentage = 0;
if ($payment['fee'] > 0) {
    $payment_percentage = ($total_paid / $payment['fee']) * 100;
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success text-end">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <?php if ($payment['status'] == 'pending'): ?>
                        <a href="<?php echo APP_URL; ?>/modules/payments/edit_payment.php?id=<?php echo $payment_id; ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo APP_URL; ?>/modules/payments/print_receipt.php?id=<?php echo $payment_id; ?>" class="btn btn-info btn-sm text-white" target="_blank">
                        <i class="fas fa-print"></i> طباعة الإيصال
                    </a>
                </div>
                <h5 class="mb-0">تفاصيل الدفعة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات الدفعة</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم الدفعة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المبلغ:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static text-success fw-bold"><?php echo formatAmount($payment['amount']); ?> د.م</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ الدفع:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">طريقة الدفع:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php 
                                    switch($payment['payment_method']) {
                                        case 'cash': echo 'نقدًا'; break;
                                        case 'bank_transfer': echo 'تحويل بنكي'; break;
                                        case 'credit_card': echo 'بطاقة ائتمان'; break;
                                        case 'check': echo 'شيك'; break;
                                        case 'other': echo 'أخرى'; break;
                                        default: echo $payment['payment_method'];
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم المرجع:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $payment['reference_number'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحالة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php 
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch($payment['status']) {
                                        case 'pending':
                                            $status_class = 'warning';
                                            $status_text = 'قيد الانتظار';
                                            break;
                                        case 'completed':
                                            $status_class = 'success';
                                            $status_text = 'مكتمل';
                                            break;
                                        case 'failed':
                                            $status_class = 'danger';
                                            $status_text = 'فشل';
                                            break;
                                        case 'refunded':
                                            $status_class = 'info';
                                            $status_text = 'مسترجع';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">ملاحظات:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $payment['description'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ الإنشاء:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات المتعلم</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">اسم المتعلم:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $payment['student_id']; ?>">
                                        <?php echo $payment['student_first_name'] . ' ' . $payment['student_last_name']; ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم التسجيل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $payment['registration_number']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الهاتف:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $payment['student_phone']; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($payment['enrollment_id']): ?>
                        <h5 class="text-end mt-4">معلومات الدورة</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المادة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php if ($payment['course_name']): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/courses/view.php?id=<?php echo $payment['course_id']; ?>">
                                        <?php echo $payment['course_name']; ?> (<?php echo $payment['course_code']; ?>)
                                    </a>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الفصل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php if ($payment['class_name']): ?>
                                    <a href="<?php echo APP_URL; ?>/modules/classes/view.php?id=<?php echo $payment['class_id']; ?>">
                                        <?php echo $payment['class_name']; ?>
                                    </a>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($payment['fee']): ?>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">إجمالي الرسوم:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo formatAmount($payment['fee']); ?> د.م</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">إجمالي المدفوع:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static text-success"><?php echo formatAmount($total_paid); ?> د.م</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المتبقي:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static <?php echo $remaining > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo formatAmount(abs($remaining)); ?> د.م
                                </p>
                            </div>
                        </div>
                        
                        <!-- Barre de progression du paiement -->
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $payment_percentage); ?>%;" 
                                 aria-valuenow="<?php echo $payment_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($payment_percentage); ?>%
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Autres paiements du même étudiant -->
        <?php if (count($other_payments) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0 text-end">دفعات أخرى للمتعلم</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم الدفعة</th>
                                <th class="text-end">تاريخ الدفع</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">طريقة الدفع</th>
                                <th class="text-end">الدورة</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($other_payments as $other_payment): ?>
                                <tr>
                                    <td class="text-end">#<?php echo str_pad($other_payment['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td class="text-end"><?php echo date('d/m/Y', strtotime($other_payment['payment_date'])); ?></td>
                                    <td class="text-end"><?php echo formatAmount($other_payment['amount']); ?> د.م</td>
                                    <td class="text-end">
                                        <?php 
                                        switch($other_payment['payment_method']) {
                                            case 'cash': echo 'نقدًا'; break;
                                            case 'bank_transfer': echo 'تحويل بنكي'; break;
                                            case 'credit_card': echo 'بطاقة ائتمان'; break;
                                            case 'check': echo 'شيك'; break;
                                            case 'other': echo 'أخرى'; break;
                                            default: echo $other_payment['payment_method'];
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end"><?php echo $other_payment['course_name'] ? $other_payment['course_name'] . ' - ' . $other_payment['class_name'] : '-'; ?></td>
                                    <td class="text-end">
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch($other_payment['status']) {
                                            case 'pending':
                                                $status_class = 'warning';
                                                $status_text = 'قيد الانتظار';
                                                break;
                                            case 'completed':
                                                $status_class = 'success';
                                                $status_text = 'مكتمل';
                                                break;
                                            case 'failed':
                                                $status_class = 'danger';
                                                $status_text = 'فشل';
                                                break;
                                            case 'refunded':
                                                $status_class = 'info';
                                                $status_text = 'مسترجع';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo APP_URL; ?>/modules/payments/view_payment.php?id=<?php echo $other_payment['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo APP_URL; ?>/modules/payments/student_payments.php?student_id=<?php echo $payment['student_id']; ?>" class="btn btn-secondary">
                    عرض جميع المدفوعات <i class="fas fa-arrow-circle-left"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-end">الإجراءات</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <a href="<?php echo APP_URL; ?>/modules/payments/print_receipt.php?id=<?php echo $payment_id; ?>" class="btn btn-info text-white w-100 mb-2" target="_blank">
                            <i class="fas fa-print"></i> طباعة الإيصال
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $payment['student_id']; ?>" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-user"></i> عرض ملف المتعلم
                        </a>
                    </div>
                    <div class="col-md-4">
                        <?php if ($payment['status'] == 'pending'): ?>
                            <a href="<?php echo APP_URL; ?>/modules/payments/update_status.php?id=<?php echo $payment_id; ?>&status=completed" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-check"></i> تأكيد الدفعة
                            </a>
                        <?php elseif ($payment['status'] == 'completed'): ?>
                            <a href="<?php echo APP_URL; ?>/modules/payments/update_status.php?id=<?php echo $payment_id; ?>&status=refunded" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-undo"></i> استرجاع الدفعة
                            </a>
                        <?php endif; ?>
                    </div>
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