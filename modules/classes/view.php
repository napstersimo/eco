<?php
// Fichier: modules/classes/view.php
// Affichage des détails d'une classe
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/classes/list.php');
}

$class_id = (int)$_GET['id'];

// Récupérer les informations de la classe
$db = new Database();
$db->query("SELECT cl.*, c.course_name, c.course_code, c.fee, c.max_students, c.description as course_description,
           t.id as teacher_id, CONCAT(t.first_name, ' ', t.last_name) as teacher_name, t.phone as teacher_phone, 
           t.email as teacher_email, t.commission_percentage
           FROM classes cl
           JOIN courses c ON cl.course_id = c.id
           LEFT JOIN teachers t ON cl.teacher_id = t.id
           WHERE cl.id = :id");
$db->bind(':id', $class_id);
$class = $db->single();

// Vérifier si la classe existe
if (!$class) {
    redirect(APP_URL . '/modules/classes/list.php');
}

// Vérifier si un message de succès est passé
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_message = 'تمت إضافة الفصل بنجاح!';
    } elseif ($_GET['success'] == 'update') {
        $success_message = 'تم تحديث بيانات الفصل بنجاح!';
    } elseif ($_GET['success'] == 'enroll') {
        $success_message = 'تم تسجيل الطالب بنجاح!';
    }
}

// Récupérer les statistiques des étudiants
$db->query("SELECT 
           COUNT(*) as total_students,
           SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active_students,
           SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_students,
           SUM(CASE WHEN e.status = 'dropped' THEN 1 ELSE 0 END) as dropped_students
           FROM enrollments e
           WHERE e.class_id = :class_id");
$db->bind(':class_id', $class_id);
$student_stats = $db->single();

// Récupérer les statistiques financières
$db->query("SELECT 
           SUM(sp.amount) as total_income,
           (SELECT SUM(tp.amount) FROM teacher_payments tp WHERE tp.class_id = :class_id1 AND tp.status = 'completed') as teacher_paid
           FROM student_payments sp
           JOIN enrollments e ON sp.enrollment_id = e.id
           WHERE e.class_id = :class_id2 AND sp.status = 'completed'");
$db->bind(':class_id1', $class_id);
$db->bind(':class_id2', $class_id);
$financial_stats = $db->single();

$total_income = $financial_stats['total_income'] ? $financial_stats['total_income'] : 0;
$teacher_paid = $financial_stats['teacher_paid'] ? $financial_stats['teacher_paid'] : 0;

// Calculer le revenu potentiel et le paiement potentiel au professeur
$student_count = $student_stats['total_students'] ? $student_stats['total_students'] : 0;
$potential_income = $class['fee'] * $student_count;
$potential_teacher_payment = ($potential_income * $class['commission_percentage']) / 100;
$teacher_remaining = $potential_teacher_payment - $teacher_paid;

// Récupérer la liste des étudiants inscrits
$db->query("SELECT e.*, s.registration_number, s.first_name, s.last_name, s.phone, s.email,
           (SELECT SUM(amount) FROM student_payments sp WHERE sp.enrollment_id = e.id AND sp.status = 'completed') as paid_amount
           FROM enrollments e
           JOIN students s ON e.student_id = s.id
           WHERE e.class_id = :class_id
           ORDER BY s.first_name, s.last_name");
$db->bind(':class_id', $class_id);
$enrollments = $db->resultSet();

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
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?php echo APP_URL; ?>/modules/classes/edit.php?id=<?php echo $class_id; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?class_id=<?php echo $class_id; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-user-plus"></i> إضافة طالب
                    </a>
                    <?php if ($class['teacher_id']): ?>
                    <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $class['teacher_id']; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-money-bill-wave"></i> دفع للأستاذ
                    </a>
                    <?php endif; ?>
                </div>
                <h5 class="mb-0">بيانات الفصل</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات المادة</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المادة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <a href="<?php echo APP_URL; ?>/modules/courses/view.php?id=<?php echo $class['course_id']; ?>">
                                        <?php echo $class['course_name']; ?> (<?php echo $class['course_code']; ?>)
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">اسم الفصل:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['class_name']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الرسوم:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo formatAmount($class['fee']); ?> د.م</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحد الأقصى للطلاب:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['max_students'] ?: 'غير محدود'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رقم القاعة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['room_number'] ?: '-'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات الجدول والأستاذ</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الأستاذ:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php if ($class['teacher_id']): ?>
                                        <a href="<?php echo APP_URL; ?>/modules/teachers/view.php?id=<?php echo $class['teacher_id']; ?>">
                                            <?php echo $class['teacher_name']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-danger">لم يتم تعيين أستاذ</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ البداية:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['start_date'] ? date('d/m/Y', strtotime($class['start_date'])) : '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">تاريخ النهاية:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['end_date'] ? date('d/m/Y', strtotime($class['end_date'])) : '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">جدول الحصص:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['schedule'] ?: '-'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحالة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
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
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>عدد الطلاب</h5>
                        <h2><?php echo $student_stats['total_students']; ?></h2>
                        <p>
                            <span class="badge bg-success"><?php echo $student_stats['active_students']; ?> نشط</span>
                            <span class="badge bg-info"><?php echo $student_stats['completed_students']; ?> مكتمل</span>
                            <span class="badge bg-warning"><?php echo $student_stats['dropped_students']; ?> انسحب</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>الدخل المحتمل</h5>
                        <h2><?php echo formatAmount($potential_income); ?> د.م</h2>
                        <p>
                            <span class="badge bg-light text-dark">
                                <?php echo formatAmount($total_income); ?> د.م تم تحصيله
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5>مستحقات الأستاذ</h5>
                        <h2><?php echo formatAmount($potential_teacher_payment); ?> د.م</h2>
                        <p>
                            <span class="badge bg-success">
                                نسبة العمولة: <?php echo $class['commission_percentage']; ?>%
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?php echo $teacher_remaining > 0 ? 'bg-danger' : 'bg-success'; ?> text-white">
                    <div class="card-body text-center">
                        <h5>المتبقي للأستاذ</h5>
                        <h2><?php echo formatAmount(abs($teacher_remaining)); ?> د.م</h2>
                        <p>
                            <span class="badge bg-light text-dark">
                                <?php echo formatAmount($teacher_paid); ?> د.م تم دفعه
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des étudiants -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?class_id=<?php echo $class_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-user-plus"></i> إضافة طالب
                </a>
                <h5 class="mb-0">الطلاب المسجلين</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم التسجيل</th>
                                <th class="text-end">اسم الطالب</th>
                                <th class="text-end">الهاتف</th>
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
                                    $remaining = $class['fee'] - $paid;
                                    ?>
                                    <tr>
                                        <td class="text-end"><?php echo $enrollment['registration_number']; ?></td>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $enrollment['student_id']; ?>">
                                                <?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?php echo $enrollment['phone']; ?></td>
                                        <td class="text-end"><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td class="text-end"><?php echo formatAmount($class['fee']); ?> د.م</td>
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
                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $enrollment['student_id']; ?>&enrollment_id=<?php echo $enrollment['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $enrollment['id']; ?>" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">لا يوجد طلاب مسجلين في هذا الفصل</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Informations de l'enseignant -->
        <?php if ($class['teacher_id']): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0 text-end">معلومات الأستاذ</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الاسم:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <a href="<?php echo APP_URL; ?>/modules/teachers/view.php?id=<?php echo $class['teacher_id']; ?>">
                                        <?php echo $class['teacher_name']; ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الهاتف:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['teacher_phone']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">البريد الإلكتروني:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['teacher_email'] ?: '-'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">نسبة العمولة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $class['commission_percentage']; ?>%</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المستحقات:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php echo formatAmount($potential_teacher_payment); ?> د.م
                                    <small class="text-muted">(<?php echo formatAmount($potential_income); ?> د.م × <?php echo $class['commission_percentage']; ?>%)</small>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المتبقي:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static <?php echo $teacher_remaining > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo formatAmount(abs($teacher_remaining)); ?> د.م
                                    <?php if ($teacher_paid > 0): ?>
                                        <small class="text-muted">(تم دفع <?php echo formatAmount($teacher_paid); ?> د.م)</small>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($teacher_remaining > 0): ?>
                        <div class="d-grid">
                            <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php?teacher_id=<?php echo $class['teacher_id']; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-success">
                                <i class="fas fa-money-bill-wave"></i> دفع المستحقات
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-end">
                هل أنت متأكد من رغبتك في حذف هذا التسجيل؟ هذا الإجراء لا يمكن التراجع عنه.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">حذف</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Script pour la suppression
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const enrollmentId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/enrollments/delete.php?id=${enrollmentId}&redirect=class&class_id=<?php echo $class_id; ?>`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>