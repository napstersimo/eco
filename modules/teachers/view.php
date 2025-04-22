<?php
// Fichier: modules/teachers/view.php
// Affichage des détails d'un professeur
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/teachers/list.php');
}

$teacher_id = (int)$_GET['id'];

// Récupérer les informations du professeur
$db = new Database();
$db->query("SELECT * FROM teachers WHERE id = :id");
$db->bind(':id', $teacher_id);
$teacher = $db->single();

// Vérifier si le professeur existe
if (!$teacher) {
    redirect(APP_URL . '/modules/teachers/list.php');
}

// Vérifier si un message de succès est passé
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_message = 'تمت إضافة الأستاذ بنجاح!';
    } elseif ($_GET['success'] == 'update') {
        $success_message = 'تم تحديث بيانات الأستاذ بنجاح!';
    }
}

// Récupérer les statistiques des classes (correction de l'erreur avec les paramètres)
// Première requête pour le nombre total de classes et classes actives
$db->query("SELECT 
           COUNT(*) as total_classes,
           SUM(CASE WHEN cl.status = 'ongoing' THEN 1 ELSE 0 END) as active_classes
           FROM classes cl
           WHERE cl.teacher_id = :teacher_id");
$db->bind(':teacher_id', $teacher_id);
$class_stats = $db->single();

// Deuxième requête pour le nombre total d'étudiants
$db->query("SELECT COUNT(DISTINCT e.student_id) as total_students 
           FROM enrollments e
           JOIN classes cl ON e.class_id = cl.id
           WHERE cl.teacher_id = :teacher_id AND e.status = 'active'");
$db->bind(':teacher_id', $teacher_id);
$student_stats = $db->single();

// Combiner les résultats
$total_classes = $class_stats['total_classes'] ? $class_stats['total_classes'] : 0;
$active_classes = $class_stats['active_classes'] ? $class_stats['active_classes'] : 0;
$total_students = $student_stats['total_students'] ? $student_stats['total_students'] : 0;

// Récupérer les statistiques financières
$db->query("SELECT SUM(amount) as total_paid 
           FROM teacher_payments 
           WHERE teacher_id = :teacher_id AND status = 'completed'");
$db->bind(':teacher_id', $teacher_id);
$payment_stats = $db->single();
$total_paid = $payment_stats['total_paid'] ? $payment_stats['total_paid'] : 0;

// Calculer le montant total estimé basé sur la commission
// Utilisation de deux paramètres distincts
$db->query("SELECT SUM(c.fee * 
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = 'active') * 
           :commission / 100) as total_due
           FROM classes cl
           JOIN courses c ON cl.course_id = c.id
           WHERE cl.teacher_id = :teacher_id AND cl.status IN ('scheduled', 'ongoing')");
$db->bind(':commission', $teacher['commission_percentage']);
$db->bind(':teacher_id', $teacher_id);
$due_stats = $db->single();
$total_due = $due_stats['total_due'] ? $due_stats['total_due'] : 0;

// Calculer le montant restant à payer
$remaining = $total_due - $total_paid;

// Récupérer la liste des classes enseignées
$db->query("SELECT cl.*, c.course_name, c.fee,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = 'active') as student_count
           FROM classes cl
           JOIN courses c ON cl.course_id = c.id
           WHERE cl.teacher_id = :teacher_id
           ORDER BY cl.start_date DESC");
$db->bind(':teacher_id', $teacher_id);
$classes = $db->resultSet();

// Récupérer la liste des paiements
$db->query("SELECT tp.*, c.course_name, cl.class_name
           FROM teacher_payments tp
           LEFT JOIN classes cl ON tp.class_id = cl.id
           LEFT JOIN courses c ON cl.course_id = c.id
           WHERE tp.teacher_id = :teacher_id
           ORDER BY tp.payment_date DESC
           LIMIT 5");
$db->bind(':teacher_id', $teacher_id);
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
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?php echo APP_URL; ?>/modules/teachers/edit.php?id=<?php echo $teacher_id; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $teacher_id; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-money-bill-wave"></i> إضافة دفعة
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/classes/add.php?teacher_id=<?php echo $teacher_id; ?>" class="btn btn-info btn-sm text-white">
                        <i class="fas fa-chalkboard-teacher"></i> إضافة فصل
                    </a>
                </div>
                <h5 class="mb-0">بيانات الأستاذ</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-end">المعلومات الشخصية</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم الموظف:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['employee_id']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الاسم الكامل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ الميلاد:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['date_of_birth'] ? date('d/m/Y', strtotime($teacher['date_of_birth'])) : '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الجنس:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php 
                                    switch($teacher['gender']) {
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
                                <p class="form-control-static"><?php echo $teacher['address'] ?: '-'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات الاتصال والعمل</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم الهاتف:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['phone']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">البريد الإلكتروني:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['email'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">التخصص:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['specialization']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المؤهل العلمي:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['qualification'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ التعيين:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo date('d/m/Y', strtotime($teacher['joining_date'])); ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">نسبة العمولة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $teacher['commission_percentage']; ?>%</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحالة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php 
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch($teacher['status']) {
                                        case 'active':
                                            $status_class = 'success';
                                            $status_text = 'نشط';
                                            break;
                                        case 'inactive':
                                            $status_class = 'secondary';
                                            $status_text = 'غير نشط';
                                            break;
                                        case 'on_leave':
                                            $status_class = 'warning';
                                            $status_text = 'في إجازة';
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
        
        <!-- Résumé statistique -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h5>عدد الفصول</h5>
                        <h2><?php echo $total_classes; ?></h2>
                        <p>منها <?php echo $active_classes; ?> فصل نشط</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>عدد الطلاب</h5>
                        <h2><?php echo $total_students; ?></h2>
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
        
        <!-- Fصول enseignées -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/modules/classes/add.php?teacher_id=<?php echo $teacher_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة فصل جديد
                </a>
                <h5 class="mb-0">الفصول المدرسة</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">المادة</th>
                                <th class="text-end">الفصل</th>
                                <th class="text-end">الفترة</th>
                                <th class="text-end">عدد الطلاب</th>
                                <th class="text-end">الرسوم</th>
                                <th class="text-end">الدخل المتوقع</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($classes) > 0): ?>
                                <?php foreach ($classes as $class): ?>
                                    <?php 
                                    $total_income = $class['fee'] * $class['student_count'];
                                    $teacher_income = ($total_income * $teacher['commission_percentage']) / 100;
                                    ?>
                                    <tr>
                                        <td class="text-end"><?php echo $class['course_name']; ?></td>
                                        <td class="text-end"><?php echo $class['class_name']; ?></td>
                                        <td class="text-end">
                                            <?php if ($class['start_date'] && $class['end_date']): ?>
                                                <?php echo date('d/m/Y', strtotime($class['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($class['end_date'])); ?>
                                            <?php elseif ($class['start_date']): ?>
                                                من <?php echo date('d/m/Y', strtotime($class['start_date'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?php echo $class['student_count']; ?></td>
                                        <td class="text-end"><?php echo formatAmount($class['fee']); ?> د.م</td>
                                        <td class="text-end text-success"><?php echo formatAmount($teacher_income); ?> د.م</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($class['status']) {
                                                case 'scheduled':
                                                    $status_class = 'primary';
                                                    $status_text = 'مجدول';
                                                    break;
                                                case 'ongoing':
                                                    $status_class = 'success';
                                                    $status_text = 'جاري';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'info';
                                                    $status_text = 'مكتمل';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'danger';
                                                    $status_text = 'ملغي';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/classes/view.php?id=<?php echo $class['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/list.php?class_id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm" title="قائمة الطلاب">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $teacher_id; ?>&class_id=<?php echo $class['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد فصول مدرسة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Historique des paiements -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $teacher_id; ?>" class="btn btn-light btn-sm">
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
                                <th class="text-end">الفصل</th>
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
                                                case 'cancelled':
                                                    $status_class = 'danger';
                                                    $status_text = 'ملغى';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/payments/view_teacher_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($payment['status'] == 'pending'): ?>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/edit_teacher_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
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
                <a href="<?php echo APP_URL; ?>/modules/payments/teacher_payments.php?teacher_id=<?php echo $teacher_id; ?>" class="btn btn-secondary">
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