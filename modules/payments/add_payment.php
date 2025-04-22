

<?php
// Ajouter un paiement étudiant
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Récupérer l'ID de l'étudiant s'il est fourni dans l'URL
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $student_id = clean($_POST['student_id']);
    $enrollment_id = !empty($_POST['enrollment_id']) ? clean($_POST['enrollment_id']) : null;
    $amount = clean($_POST['amount']);
    $payment_date = clean($_POST['payment_date']);
    $payment_method = clean($_POST['payment_method']);
    $reference_number = clean($_POST['reference_number']);
    $description = clean($_POST['description']);
    $status = clean($_POST['status']);
    
    // Validation
    if (empty($student_id)) {
        $errors[] = 'يجب اختيار متعلم';
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'المبلغ يجب أن يكون رقمًا موجبًا';
    }
    
    if (empty($payment_date)) {
        $errors[] = 'تاريخ الدفع مطلوب';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'طريقة الدفع مطلوبة';
    }
    
    // Si aucune erreur, ajouter le paiement
    if (empty($errors)) {
        $db = new Database();
        
        // Ajouter le paiement dans la base de données
        $db->query("INSERT INTO student_payments (student_id, enrollment_id, amount, payment_date, payment_method, reference_number, description, status) 
                   VALUES (:student_id, :enrollment_id, :amount, :payment_date, :payment_method, :reference_number, :description, :status)");
        
        $db->bind(':student_id', $student_id);
        $db->bind(':enrollment_id', $enrollment_id);
        $db->bind(':amount', $amount);
        $db->bind(':payment_date', $payment_date);
        $db->bind(':payment_method', $payment_method);
        $db->bind(':reference_number', $reference_number);
        $db->bind(':description', $description);
        $db->bind(':status', $status);
        
        if($db->execute()) {
            $payment_id = $db->lastInsertId();
            $success = true;
            
            // Rediriger vers la liste des paiements
            redirect(APP_URL . '/modules/payments/student_payments.php?success=add');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة الدفعة';
        }
    }
}

// Récupérer la liste des étudiants
$db = new Database();
$db->query("SELECT id, first_name, last_name, registration_number FROM students WHERE status = 'active' ORDER BY first_name, last_name");
$students = $db->resultSet();

// Récupérer les inscriptions de l'étudiant s'il est sélectionné
$enrollments = [];
if ($student_id) {
    $db->query("SELECT e.id, c.course_name, cl.class_name
               FROM enrollments e
               JOIN classes cl ON e.class_id = cl.id
               JOIN courses c ON cl.course_id = c.id
               WHERE e.student_id = :student_id AND e.status = 'active'");
    $db->bind(':student_id', $student_id);
    $enrollments = $db->resultSet();
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0 text-end">إضافة دفعة جديدة</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تمت إضافة الدفعة بنجاح!
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
                            <select id="student_id" name="student_id" class="form-select" required>
                                <option value="">-- اختر متعلم --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo ($student_id == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['registration_number'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="enrollment_id" class="form-label text-end d-block">الدورة (اختياري)</label>
                            <select id="enrollment_id" name="enrollment_id" class="form-select">
                                <option value="">-- اختر دورة --</option>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <option value="<?php echo $enrollment['id']; ?>">
                                        <?php echo $enrollment['course_name'] . ' - ' . $enrollment['class_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label text-end d-block">المبلغ *</label>
                            <input type="number" id="amount" name="amount" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label text-end d-block">تاريخ الدفع *</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label text-end d-block">طريقة الدفع *</label>
                            <select id="payment_method" name="payment_method" class="form-select" required>
                                <option value="">-- اختر طريقة الدفع --</option>
                                <option value="cash">نقدًا</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="credit_card">بطاقة ائتمان</option>
                                <option value="check">شيك</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label text-end d-block">رقم المرجع</label>
                            <input type="text" id="reference_number" name="reference_number" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="completed" selected>مكتمل</option>
                                <option value="pending">قيد الانتظار</option>
                                <option value="failed">فشل</option>
                                <option value="refunded">مسترجع</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label text-end d-block">ملاحظات</label>
                            <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/payments/student_payments.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                        <button type="submit" class="btn btn-warning text-white">
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
    // Script pour charger les inscriptions quand l'étudiant change
    document.addEventListener('DOMContentLoaded', function() {
        const studentSelect = document.getElementById('student_id');
        const enrollmentSelect = document.getElementById('enrollment_id');
        
        studentSelect.addEventListener('change', function() {
            const studentId = this.value;
            
            if (studentId) {
                // Vider le select des inscriptions
                enrollmentSelect.innerHTML = '<option value="">-- اختر دورة --</option>';
                
                // Faire une requête AJAX pour obtenir les inscriptions
                fetch(`<?php echo APP_URL; ?>/modules/api/get_enrollments.php?student_id=${studentId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(enrollment => {
                            const option = document.createElement('option');
                            option.value = enrollment.id;
                            option.textContent = `${enrollment.course_name} - ${enrollment.class_name}`;
                            enrollmentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                // Vider le select des inscriptions
                enrollmentSelect.innerHTML = '<option value="">-- اختر دورة --</option>';
            }
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>