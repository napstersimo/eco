<?php
// Fichier: modules/enrollments/add.php
// Inscription d'un étudiant à un cours
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Récupérer les IDs s'ils sont fournis dans l'URL
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $student_id = clean($_POST['student_id']);
    $class_id = clean($_POST['class_id']);
    $enrollment_date = !empty($_POST['enrollment_date']) ? clean($_POST['enrollment_date']) : date('Y-m-d');
    $status = clean($_POST['status']);
    $remarks = clean($_POST['remarks']);
    
    // Validation
    if (empty($student_id)) {
        $errors[] = 'يجب اختيار متعلم';
    }
    
    if (empty($class_id)) {
        $errors[] = 'يجب اختيار فصل';
    }
    
    // Vérifier si l'étudiant est déjà inscrit dans cette classe
    $db = new Database();
    $db->query("SELECT id FROM enrollments WHERE student_id = :student_id AND class_id = :class_id");
    $db->bind(':student_id', $student_id);
    $db->bind(':class_id', $class_id);
    $existing_enrollment = $db->single();
    
    if ($existing_enrollment) {
        $errors[] = 'المتعلم مسجل بالفعل في هذا الفصل';
    }
    
    // Vérifier si la classe a atteint son nombre maximum d'étudiants
    $db->query("SELECT id FROM enrollments WHERE student_id = :student_id AND class_id = :class_id");
$db->bind(':student_id', $student_id);
$db->bind(':class_id', $class_id); // Ajoutez cette ligne
$existing_enrollment = $db->single();
    
    if ($class_info && $class_info['max_students'] > 0 && $class_info['current_students'] >= $class_info['max_students']) {
        $errors[] = 'تم الوصول إلى الحد الأقصى لعدد الطلاب في هذا الفصل';
    }
    
    // Si aucune erreur, ajouter l'inscription
    if (empty($errors)) {
        // Commencer une transaction
        $db->beginTransaction();
        
        try {
            // Ajouter l'inscription dans la base de données
            $db->query("INSERT INTO enrollments (student_id, class_id, enrollment_date, status, remarks) 
                      VALUES (:student_id, :class_id, :enrollment_date, :status, :remarks)");
            
            $db->bind(':student_id', $student_id);
            $db->bind(':class_id', $class_id);
            $db->bind(':enrollment_date', $enrollment_date);
            $db->bind(':status', $status);
            $db->bind(':remarks', $remarks);
            
            $db->execute();
            $enrollment_id = $db->lastInsertId();
            
            // Récupérer les informations du cours pour le paiement
            $db->query("SELECT c.fee, c.course_name, cl.class_name 
                      FROM classes cl
                      JOIN courses c ON cl.course_id = c.id
                      WHERE cl.id = :class_id");
            $db->bind(':class_id', $class_id);
            $course_info = $db->single();
            
            // Vérifier si un paiement initial est requis
            if (isset($_POST['create_payment']) && $_POST['create_payment'] == 1) {
                $amount = clean($_POST['payment_amount']);
                
                if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
                    throw new Exception('المبلغ يجب أن يكون رقمًا موجبًا');
                }
                
                $payment_method = clean($_POST['payment_method']);
                $payment_date = !empty($_POST['payment_date']) ? clean($_POST['payment_date']) : date('Y-m-d');
                $payment_reference = clean($_POST['payment_reference']);
                $payment_description = "دفعة للتسجيل في {$course_info['course_name']} - {$course_info['class_name']}";
                
                // Ajouter le paiement
                $db->query("INSERT INTO student_payments (student_id, enrollment_id, amount, payment_date, payment_method, reference_number, description, status) 
                          VALUES (:student_id, :enrollment_id, :amount, :payment_date, :payment_method, :reference_number, :description, 'completed')");
                
                $db->bind(':student_id', $student_id);
                $db->bind(':enrollment_id', $enrollment_id);
                $db->bind(':amount', $amount);
                $db->bind(':payment_date', $payment_date);
                $db->bind(':payment_method', $payment_method);
                $db->bind(':reference_number', $payment_reference);
                $db->bind(':description', $payment_description);
                
                $db->execute();
            }
            
            // Valider la transaction
            $db->endTransaction();
            $success = true;
            
            // Rediriger vers la liste des inscriptions
            redirect(APP_URL . '/modules/enrollments/list.php?class_id=' . $class_id . '&success=add');
            
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $db->cancelTransaction();
            $errors[] = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}

// Récupérer la liste des étudiants
$db = new Database();
$db->query("SELECT id, first_name, last_name, registration_number FROM students WHERE status = 'active' ORDER BY first_name, last_name");
$students = $db->resultSet();

// Récupérer la liste des classes
$db->query("SELECT cl.id, cl.class_name, c.course_name, c.fee,
            (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = 'active') as current_students,
            c.max_students
            FROM classes cl
            JOIN courses c ON cl.course_id = c.id
            WHERE cl.status IN ('scheduled', 'ongoing')
            ORDER BY c.course_name, cl.class_name");
$classes = $db->resultSet();

// Récupérer les détails de l'étudiant et de la classe si fournis
$student = null;
$class = null;

if ($student_id) {
    $db->query("SELECT * FROM students WHERE id = :id");
    $db->bind(':id', $student_id);
    $student = $db->single();
}

if ($class_id) {
    $db->query("SELECT cl.*, c.course_name, c.fee, c.max_students,
               (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = cl.id AND e.status = 'active') as current_students,
               CONCAT(t.first_name, ' ', t.last_name) as teacher_name
               FROM classes cl
               JOIN courses c ON cl.course_id = c.id
               LEFT JOIN teachers t ON cl.teacher_id = t.id
               WHERE cl.id = :id");
    $db->bind(':id', $class_id);
    $class = $db->single();
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0 text-end">تسجيل متعلم في فصل</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تم تسجيل المتعلم بنجاح!
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger text-end">
                        <ul class="mb-0 pe-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label text-end d-block">المتعلم *</label>
                            <select id="student_id" name="student_id" class="form-select" required <?php echo $student_id ? 'disabled' : ''; ?>>
                                <option value="">-- اختر متعلم --</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo ($student_id == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo $s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['registration_number'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($student_id): ?>
                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="class_id" class="form-label text-end d-block">الفصل *</label>
                            <select id="class_id" name="class_id" class="form-select" required <?php echo $class_id ? 'disabled' : ''; ?>>
                                <option value="">-- اختر فصل --</option>
                                <?php foreach ($classes as $c): ?>
                                    <?php 
                                    $full = ($c['max_students'] > 0 && $c['current_students'] >= $c['max_students']) ? ' (ممتلئ)' : '';
                                    $disabled = ($c['max_students'] > 0 && $c['current_students'] >= $c['max_students'] && $c['id'] != $class_id) ? 'disabled' : '';
                                    ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($class_id == $c['id']) ? 'selected' : $disabled; ?>>
                                        <?php echo $c['course_name'] . ' - ' . $c['class_name'] . ' (' . $c['current_students'] . '/' . ($c['max_students'] ?: '∞') . ')' . $full; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($class_id): ?>
                                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($student && $class): ?>
                    <div class="alert alert-info text-end mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>معلومات المتعلم:</h5>
                                <p><strong>الاسم:</strong> <?php echo $student['first_name'] . ' ' . $student['last_name']; ?></p>
                                <p><strong>رقم التسجيل:</strong> <?php echo $student['registration_number']; ?></p>
                                <p><strong>الهاتف:</strong> <?php echo $student['phone']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5>معلومات الفصل:</h5>
                                <p><strong>المادة:</strong> <?php echo $class['course_name']; ?></p>
                                <p><strong>الفصل:</strong> <?php echo $class['class_name']; ?></p>
                                <p><strong>الأستاذ:</strong> <?php echo $class['teacher_name']; ?></p>
                                <p><strong>الرسوم:</strong> <?php echo formatAmount($class['fee']); ?> د.م</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="enrollment_date" class="form-label text-end d-block">تاريخ التسجيل</label>
                            <input type="date" id="enrollment_date" name="enrollment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" selected>نشط</option>
                                <option value="completed">مكتمل</option>
                                <option value="dropped">انسحب</option>
                                <option value="failed">راسب</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="remarks" class="form-label text-end d-block">ملاحظات</label>
                            <textarea id="remarks" name="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <?php if ($class && $class['fee'] > 0): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="create_payment" name="create_payment" value="1" checked>
                                <label class="form-check-label text-end w-100" for="create_payment">إضافة دفعة أولية</label>
                            </div>
                        </div>
                        <div class="card-body" id="payment_details">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_amount" class="form-label text-end d-block">المبلغ *</label>
                                    <input type="number" id="payment_amount" name="payment_amount" class="form-control" min="0.01" step="0.01" value="<?php echo $class['fee']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_date" class="form-label text-end d-block">تاريخ الدفع</label>
                                    <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label text-end d-block">طريقة الدفع *</label>
                                    <select id="payment_method" name="payment_method" class="form-select">
                                        <option value="cash" selected>نقدًا</option>
                                        <option value="bank_transfer">تحويل بنكي</option>
                                        <option value="credit_card">بطاقة ائتمان</option>
                                        <option value="check">شيك</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_reference" class="form-label text-end d-block">رقم المرجع</label>
                                    <input type="text" id="payment_reference" name="payment_reference" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <?php if ($student_id): ?>
                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $student_id; ?>" class="btn btn-secondary ms-2">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        <?php elseif ($class_id): ?>
                            <a href="<?php echo APP_URL; ?>/modules/enrollments/list.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary ms-2">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        <?php else: ?>
                            <a href="<?php echo APP_URL; ?>/modules/enrollments/list.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentSelect = document.getElementById('student_id');
        const classSelect = document.getElementById('class_id');
        const createPaymentCheckbox = document.getElementById('create_payment');
        const paymentDetailsDiv = document.getElementById('payment_details');
        
        // Recharger la page quand l'étudiant change
        studentSelect.addEventListener('change', function() {
            if (this.value) {
                const classId = classSelect.value;
                window.location.href = `<?php echo APP_URL; ?>/modules/enrollments/add.php?student_id=${this.value}${classId ? '&class_id=' + classId : ''}`;
            }
        });
        
        // Recharger la page quand la classe change
        classSelect.addEventListener('change', function() {
            if (this.value) {
                const studentId = studentSelect.value;
                window.location.href = `<?php echo APP_URL; ?>/modules/enrollments/add.php?class_id=${this.value}${studentId ? '&student_id=' + studentId : ''}`;
            }
        });
        
        // Afficher/masquer les détails de paiement
        if (createPaymentCheckbox) {
            createPaymentCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    paymentDetailsDiv.style.display = 'block';
                } else {
                    paymentDetailsDiv.style.display = 'none';
                }
            });
        }
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>