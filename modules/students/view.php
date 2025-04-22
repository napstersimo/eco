<?php
// Fichier: modules/students/view.php
// Affichage des détails d'un étudiant
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/students/list.php');
}

$student_id = (int)$_GET['id'];

// Récupérer les informations de l'étudiant
$db = new Database();
$db->query("SELECT * FROM students WHERE id = :id");
$db->bind(':id', $student_id);
$student = $db->single();

// Vérifier si l'étudiant existe
if (!$student) {
    redirect(APP_URL . '/modules/students/list.php');
}

// Vérifier si un message de succès est passé
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_message = 'تمت إضافة المتعلم بنجاح!';
    } elseif ($_GET['success'] == 'update') {
        $success_message = 'تم تحديث بيانات المتعلم بنجاح!';
    }
}

// Récupérer les statistiques financières
$db->query("SELECT SUM(amount) as total_paid 
           FROM student_payments 
           WHERE student_id = :student_id AND status = 'completed'");
$db->bind(':student_id', $student_id);
$payment_stats = $db->single();
$total_paid = $payment_stats['total_paid'] ? $payment_stats['total_paid'] : 0;

// Récupérer le montant total dû pour toutes les inscriptions
$db->query("SELECT SUM(c.fee) as total_due 
           FROM enrollments e
           JOIN classes cl ON e.class_id = cl.id
           JOIN courses c ON cl.course_id = c.id
           WHERE e.student_id = :student_id AND e.status = 'active'");
$db->bind(':student_id', $student_id);
$due_stats = $db->single();
$total_due = $due_stats['total_due'] ? $due_stats['total_due'] : 0;

// Calculer le montant restant à payer
$remaining = $total_due - $total_paid;

// Récupérer le nombre d'inscriptions
$db->query("SELECT COUNT(*) as enrollment_count FROM enrollments WHERE student_id = :student_id");
$db->bind(':student_id', $student_id);
$enrollment_stats = $db->single();
$enrollment_count = $enrollment_stats['enrollment_count'];

// Récupérer la liste des inscriptions
$db->query("SELECT e.*, cl.class_name, c.course_name, c.fee,
           CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
           (SELECT SUM(amount) FROM student_payments sp WHERE sp.enrollment_id = e.id AND sp.status = 'completed') as paid_amount
           FROM enrollments e
           JOIN classes cl ON e.class_id = cl.id
           JOIN courses c ON cl.course_id = c.id
           LEFT JOIN teachers t ON cl.teacher_id = t.id
           WHERE e.student_id = :student_id
           ORDER BY e.enrollment_date DESC");
$db->bind(':student_id', $student_id);
$enrollments = $db->resultSet();

// Récupérer la liste des paiements
$db->query("SELECT sp.*, c.course_name, cl.class_name
           FROM student_payments sp
           LEFT JOIN enrollments e ON sp.enrollment_id = e.id
           LEFT JOIN classes cl ON e.class_id = cl.id
           LEFT JOIN courses c ON cl.course_id = c.id
           WHERE sp.student_id = :student_id
           ORDER BY sp.payment_date DESC
           LIMIT 5");
$db->bind(':student_id', $student_id);
$payments = $db->resultSet();

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
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?php echo APP_URL; ?>/modules/students/edit.php?id=<?php echo $student_id; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $student_id; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-money-bill-wave"></i> إضافة دفعة
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?student_id=<?php echo $student_id; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-user-plus"></i> تسجيل في دورة
                    </a>
                </div>
                <h5 class="mb-0">بيانات المتعلم</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-end">المعلومات الشخصية</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم التسجيل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['registration_number']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الاسم الكامل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ الميلاد:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['date_of_birth'] ? date('d/m/Y', strtotime($student['date_of_birth'])) : '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الجنس:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php 
                                    switch($student['gender']) {
                                        case 'male': echo 'ذكر'; break;
                                        case 'female': echo 'أنثى'; break;
                                        case 'other': echo 'آخر'; break;
                                        default: echo '-';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">العنوان:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['address'] ?: '-'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات الاتصال والتسجيل</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم الهاتف:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['phone']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">البريد الإلكتروني:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['email'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">اسم ولي الأمر:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['parent_name'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">هاتف ولي الأمر:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $student['parent_phone'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ التسجيل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo date('d/m/Y', strtotime($student['enrollment_date'])); ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحالة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
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
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Résumé financier -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5>عدد الدورات</h5>
                        <h2><?php echo $enrollment_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>إجمالي المستحق</h5>
                        <h2><?php echo formatAmount($total_due); ?> د.م</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>إجمالي المدفوع</h5>
                        <h2><?php echo formatAmount($total_paid); ?> د.م</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?php echo $remaining > 0 ? 'bg-danger' : 'bg-success'; ?> text-white">
                    <div class="card-body text-center">
                        <h5>المتبقي</h5>
                        <h2><?php echo formatAmount(abs($remaining)); ?> د.م</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dourses inscrites -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?student_id=<?php echo $student_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> تسجيل في دورة جديدة
                </a>
                <h5 class="mb-0">الدورات المسجلة</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">المادة</th>
                                <th class="text-end">الفصل</th>
                                <th class="text-end">الأستاذ</th>
                                <th class="text-end">تاريخ التسجيل</th>
                                <th class="text-end">الرسوم</th>
                                <th class="text-end">المدفوع</th>
                                <th class="text-end">المتبقي</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($enrollments) > 0): ?>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <?php 
                                    $paid = $enrollment['paid_amount'] ? $enrollment['paid_amount'] : 0;
                                    $remaining = $enrollment['fee'] - $paid;
                                    ?>
                                    <tr>
                                        <td class="text-end"><?php echo $enrollment['course_name']; ?></td>
                                        <td class="text-end"><?php echo $enrollment['class_name']; ?></td>
                                        <td class="text-end"><?php echo $enrollment['teacher_name']; ?></td>
                                        <td class="text-end"><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td class="text-end"><?php echo formatAmount($enrollment['fee']); ?> د.م</td>
                                        <td class="text-end text-success"><?php echo formatAmount($paid); ?> د.م</td>
                                        <td class="text-end <?php echo $remaining > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo formatAmount($remaining); ?> د.م</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($enrollment['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'primary';
                                                    $status_text = 'مكتمل';
                                                    break;
                                                case 'dropped':
                                                    $status_class = 'warning';
                                                    $status_text = 'انسحب';
                                                    break;
                                                case 'failed':
                                                    $status_class = 'danger';
                                                    $status_text = 'راسب';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/edit.php?id=<?php echo $enrollment['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($remaining > 0): ?>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $student_id; ?>&enrollment_id=<?php echo $enrollment['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">لا توجد دورات مسجلة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Historique des paiements -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $student_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة دفعة جديدة
                </a>
                <h5 class="mb-0">سجل المدفوعات</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">تاريخ الدفع</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">طريقة الدفع</th>
                                <th class="text-end">رقم المرجع</th>
                                <th class="text-end">الدورة</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="text-end"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                        <td class="text-end"><?php echo formatAmount($payment['amount']); ?> د.م</td>
                                        <td class="text-end">
                                            <?php 
                                            $payment_method = '';
                                            switch($payment['payment_method']) {
                                                case 'cash': echo 'نقدًا'; break;
                                                case 'bank_transfer': echo 'تحويل بنكي'; break;
                                                case 'credit_card': echo 'بطاقة ائتمان'; break;
                                                case 'check': echo 'شيك'; break;
                                                case 'other': echo 'أخرى'; break;
                                                default: echo $payment['payment_method'];
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end"><?php echo $payment['reference_number'] ?: '-'; ?></td>
                                        <td class="text-end"><?php echo $payment['course_name'] ? $payment['course_name'] . ' - ' . $payment['class_name'] : '-'; ?></td>
                                        <td class="text-end">
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
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/payments/view_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($payment['status'] == 'pending'): ?>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/edit_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد مدفوعات</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?php echo APP_URL; ?>/modules/payments/student_payments.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary">
                    عرض جميع المدفوعات <i class="fas fa-arrow-circle-left"></i>
                </a>
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