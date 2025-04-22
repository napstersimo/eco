<?php
// Fichier: modules/courses/view.php
// Affichage des détails d'un cours
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/courses/list.php');
}

$course_id = (int)$_GET['id'];

// Récupérer les informations du cours
$db = new Database();
$db->query("SELECT * FROM courses WHERE id = :id");
$db->bind(':id', $course_id);
$course = $db->single();

// Vérifier si le cours existe
if (!$course) {
    redirect(APP_URL . '/modules/courses/list.php');
}

// Vérifier si un message de succès est passé
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_message = 'تمت إضافة المادة بنجاح!';
    } elseif ($_GET['success'] == 'update') {
        $success_message = 'تم تحديث بيانات المادة بنجاح!';
    }
}

// Récupérer les statistiques des classes
$db->query("SELECT 
           COUNT(*) as total_classes,
           SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as active_classes,
           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_classes,
           SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_classes
           FROM classes
           WHERE course_id = :course_id");
$db->bind(':course_id', $course_id);
$class_stats = $db->single();

// Récupérer les statistiques des étudiants
$db->query("SELECT COUNT(DISTINCT e.student_id) as total_students
           FROM enrollments e
           JOIN classes cl ON e.class_id = cl.id
           WHERE cl.course_id = :course_id AND e.status = 'active'");
$db->bind(':course_id', $course_id);
$student_stats = $db->single();

// Récupérer les statistiques financières
$db->query("SELECT SUM(sp.amount) as total_income
           FROM student_payments sp
           JOIN enrollments e ON sp.enrollment_id = e.id
           JOIN classes cl ON e.class_id = cl.id
           WHERE cl.course_id = :course_id AND sp.status = 'completed'");
$db->bind(':course_id', $course_id);
$income_stats = $db->single();

// Récupérer les classes associées à ce cours
$db->query("SELECT cl.*, 
           CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
           (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = 'active') as student_count
           FROM classes cl
           LEFT JOIN teachers t ON cl.teacher_id = t.id
           WHERE cl.course_id = :course_id
           ORDER BY cl.start_date DESC");
$db->bind(':course_id', $course_id);
$classes = $db->resultSet();

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
                    <a href="<?php echo APP_URL; ?>/modules/courses/edit.php?id=<?php echo $course_id; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-edit"></i> تعديل
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/classes/add.php?course_id=<?php echo $course_id; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> إضافة فصل
                    </a>
                </div>
                <h5 class="mb-0">بيانات المادة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-end">معلومات المادة</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">رمز المادة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $course['course_code']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">اسم المادة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $course['course_name']; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الوصف:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $course['description'] ?: '-'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-end">تفاصيل المادة</h5>
                        <hr>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">المدة (ساعات):</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $course['duration']; ?> ساعة</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الرسوم:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo formatAmount($course['fee']); ?> د.م</p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحد الأقصى للطلاب:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static"><?php echo $course['max_students'] ?: 'غير محدود'; ?></p>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label text-end">الحالة:</label>
                            <div class="col-sm-8">
                                <p class="form-control-static">
                                    <?php 
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch($course['status']) {
                                        case 'active':
                                            $status_class = 'success';
                                            $status_text = 'نشط';
                                            break;
                                        case 'inactive':
                                            $status_class = 'secondary';
                                            $status_text = 'غير نشط';
                                            break;
                                        case 'upcoming':
                                            $status_class = 'primary';
                                            $status_text = 'قادم';
                                            break;
                                        case 'completed':
                                            $status_class = 'info';
                                            $status_text = 'مكتمل';
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
                        <h2><?php echo $class_stats['total_classes']; ?></h2>
                        <p>
                            <span class="badge bg-success"><?php echo $class_stats['active_classes']; ?> نشط</span>
                            <span class="badge bg-primary"><?php echo $class_stats['scheduled_classes']; ?> مجدول</span>
                            <span class="badge bg-secondary"><?php echo $class_stats['completed_classes']; ?> مكتمل</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h5>عدد الطلاب</h5>
                        <h2><?php echo $student_stats['total_students']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h5>إجمالي الدخل</h5>
                        <h2><?php echo formatAmount($income_stats['total_income'] ? $income_stats['total_income'] : 0); ?> د.م</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h5>متوسط الدخل للفصل</h5>
                        <h2>
                            <?php 
                            $avg_income = 0;
                            if ($class_stats['total_classes'] > 0 && $income_stats['total_income'] > 0) {
                                $avg_income = $income_stats['total_income'] / $class_stats['total_classes'];
                            }
                            echo formatAmount($avg_income); 
                            ?> د.م
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Liste des classes -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/modules/classes/add.php?course_id=<?php echo $course_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة فصل جديد
                </a>
                <h5 class="mb-0">الفصول</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">الفصل</th>
                                <th class="text-end">الأستاذ</th>
                                <th class="text-end">الفترة</th>
                                <th class="text-end">الجدول</th>
                                <th class="text-end">عدد الطلاب</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($classes) > 0): ?>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $class['class_name']; ?></td>
                                        <td class="text-end">
                                            <?php if ($class['teacher_name']): ?>
                                                <a href="<?php echo APP_URL; ?>/modules/teachers/view.php?id=<?php echo $class['teacher_id']; ?>">
                                                    <?php echo $class['teacher_name']; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">غير محدد</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($class['start_date'] && $class['end_date']): ?>
                                                <?php echo date('d/m/Y', strtotime($class['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($class['end_date'])); ?>
                                            <?php elseif ($class['start_date']): ?>
                                                من <?php echo date('d/m/Y', strtotime($class['start_date'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?php echo $class['schedule'] ?: '-'; ?></td>
                                        <td class="text-end">
                                            <?php echo $class['student_count']; ?>
                                            <?php echo $course['max_students'] ? '/' . $course['max_students'] : ''; ?>
                                        </td>
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
                                            <a href="<?php echo APP_URL; ?>/modules/classes/edit.php?id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/list.php?class_id=<?php echo $class['id']; ?>" class="btn btn-success btn-sm" title="قائمة الطلاب">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?class_id=<?php echo $class['id']; ?>" class="btn btn-warning btn-sm" title="إضافة طالب">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد فصول لهذه المادة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Liste des inscriptions totales -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0 text-end">الطلاب المسجلين</h5>
            </div>
            <div class="card-body">
                <?php
                $db->query("SELECT s.id, s.first_name, s.last_name, s.registration_number, s.phone,
                          cl.class_name, e.enrollment_date, e.status as enrollment_status,
                          (SELECT SUM(amount) FROM student_payments sp WHERE sp.enrollment_id = e.id AND sp.status = 'completed') as paid_amount
                          FROM students s
                          JOIN enrollments e ON s.id = e.student_id
                          JOIN classes cl ON e.class_id = cl.id
                          WHERE cl.course_id = :course_id
                          ORDER BY s.first_name, s.last_name");
                $db->bind(':course_id', $course_id);
                $enrollments = $db->resultSet();
                ?>
                
                <?php if (count($enrollments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم التسجيل</th>
                                <th class="text-end">اسم الطالب</th>
                                <th class="text-end">الفصل</th>
                                <th class="text-end">تاريخ التسجيل</th>
                                <th class="text-end">المدفوع</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td class="text-end"><?php echo $enrollment['registration_number']; ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $enrollment['id']; ?>">
                                            <?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?php echo $enrollment['class_name']; ?></td>
                                    <td class="text-end"><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                    <td class="text-end"><?php echo formatAmount($enrollment['paid_amount'] ? $enrollment['paid_amount'] : 0); ?> د.م</td>
                                    <td class="text-end">
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch($enrollment['enrollment_status']) {
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
                                        <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $enrollment['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        لا يوجد طلاب مسجلين في هذه المادة
                    </div>
                <?php endif; ?>
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