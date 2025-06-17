<?php
// users_edit.php
// Edit User Page

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Only admin can access this page
if (!$is_admin) {
    header("Location: dashboard.php");
    exit();
}

// Get user ID from URL
$edit_user_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($edit_user_id)) {
    header("Location: users.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$id_pengguna = '';
$nama_pengguna = '';
$jenis_pengguna = '';
$kata_laluan = '';
$confirm_password = '';
$change_password = false;

$errors = [];
$success_message = '';
$user_data = null;

// Fetch existing user data
try {
    $stmt = $db->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
    $stmt->execute([$edit_user_id]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        header("Location: users.php");
        exit();
    }
    
    // Set form data
    $id_pengguna = $user_data['id_pengguna'];
    $nama_pengguna = $user_data['nama_pengguna'];
    $jenis_pengguna = $user_data['jenis_pengguna'];
    
} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data pengguna: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $nama_pengguna = trim($_POST['nama_pengguna']);
    $jenis_pengguna = $_POST['jenis_pengguna'];
    $change_password = isset($_POST['change_password']);
    
    if ($change_password) {
        $kata_laluan = $_POST['kata_laluan'];
        $confirm_password = $_POST['confirm_password'];
    }
    
    // Validation
    if (empty($nama_pengguna)) {
        $errors[] = "Nama Pengguna adalah wajib.";
    } elseif (strlen($nama_pengguna) < 3) {
        $errors[] = "Nama Pengguna mestilah sekurang-kurangnya 3 aksara.";
    }
    
    if (empty($jenis_pengguna)) {
        $errors[] = "Jenis Pengguna adalah wajib.";
    } elseif (!in_array($jenis_pengguna, ['admin', 'guru'])) {
        $errors[] = "Jenis Pengguna tidak sah.";
    }
    
    // Password validation (only if changing password)
    if ($change_password) {
        if (empty($kata_laluan)) {
            $errors[] = "Kata Laluan adalah wajib jika ingin menukar kata laluan.";
        } elseif (strlen($kata_laluan) < 6) {
            $errors[] = "Kata Laluan mestilah sekurang-kurangnya 6 aksara.";
        }
        
        if (empty($confirm_password)) {
            $errors[] = "Pengesahan Kata Laluan adalah wajib jika ingin menukar kata laluan.";
        } elseif ($kata_laluan !== $confirm_password) {
            $errors[] = "Kata Laluan dan Pengesahan Kata Laluan tidak sepadan.";
        }
    }
    
    // Check if trying to change own admin status
    if ($edit_user_id == $user_info['id'] && $user_info['type'] == 'admin' && $jenis_pengguna != 'admin') {
        $errors[] = "Anda tidak boleh menukar jenis pengguna anda sendiri daripada Admin.";
    }
    
    // If no errors, update the user
    if (empty($errors)) {
        try {
            if ($change_password) {
                // Update with password
                $hashed_password = password_hash($kata_laluan, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    UPDATE pengguna 
                    SET nama_pengguna = ?, kata_laluan = ?, jenis_pengguna = ? 
                    WHERE id_pengguna = ?
                ");
                
                $stmt->execute([
                    $nama_pengguna,
                    $hashed_password,
                    $jenis_pengguna,
                    $edit_user_id
                ]);
            } else {
                // Update without password
                $stmt = $db->prepare("
                    UPDATE pengguna 
                    SET nama_pengguna = ?, jenis_pengguna = ? 
                    WHERE id_pengguna = ?
                ");
                
                $stmt->execute([
                    $nama_pengguna,
                    $jenis_pengguna,
                    $edit_user_id
                ]);
            }
            
            $success_message = "Maklumat pengguna berjaya dikemas kini.";
            
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM pengguna WHERE id_pengguna = ?");
            $stmt->execute([$edit_user_id]);
            $user_data = $stmt->fetch();
            
            // Update form data
            $nama_pengguna = $user_data['nama_pengguna'];
            $jenis_pengguna = $user_data['jenis_pengguna'];
            
            // Reset password fields
            $kata_laluan = '';
            $confirm_password = '';
            $change_password = false;
            
        } catch(PDOException $e) {
            $errors[] = "Ralat mengemaskini pengguna: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna - Sistem Maklumat Pelajar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .logo p {
            font-size: 14px;
            opacity: 0.9;
        }

        .user-info {
            text-align: right;
        }

        .user-info h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 12px;
            opacity: 0.8;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: inline-block;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }

        /* Navigation */
        .nav {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            gap: 30px;
        }

        .nav-menu a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover {
            background: #f0f0f0;
            color: #667eea;
        }

        .nav-menu a.active {
            background: #667eea;
            color: white;
        }

        /* Main Content */
        .main-content {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* User Info Card */
        .user-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .user-info-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .user-info-card p {
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .user-info-card .user-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin-top: 10px;
        }

        /* Form Container */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.single {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group label .required {
            color: #dc3545;
            margin-left: 3px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group input:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .form-group .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Checkbox styling */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-group label {
            cursor: pointer;
            font-weight: 500;
            color: #495057;
            margin: 0;
        }

        /* Password fields container */
        .password-section {
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .password-section.active {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .password-section.inactive {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }

        .strength-weak {
            color: #dc3545;
        }

        .strength-medium {
            color: #ffc107;
        }

        .strength-strong {
            color: #28a745;
        }

        /* Form Buttons */
        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        /* Messages */
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        .error ul {
            margin: 0;
            padding-left: 20px;
        }

        .error li {
            margin-bottom: 5px;
        }

        .error li:last-child {
            margin-bottom: 0;
        }

        /* Warning messages */
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #ffeaa7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                gap: 10px;
            }

            .form-buttons {
                flex-direction: column;
                gap: 10px;
            }

            .main-content {
                padding: 0 15px;
            }

            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Sistem Maklumat Pelajar</h1>
                <p>Sekolah Agama Bukit Bandar</p>
            </div>
            <div class="user-info">
                <h3>Selamat Datang, <?php echo htmlspecialchars($user_info['name']); ?></h3>                
                <p><?php echo $is_admin ? 'Pentadbir' : 'Guru'; ?> | <?php echo date('d M Y'); ?></p>
                <a href="logout.php" class="logout-btn">Log Keluar</a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="students.php">Pelajar</a></li>
                <li><a href="classes.php">Kelas</a></li>
                <li><a href="school.php">Sekolah</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="users.php" class="active">Pengguna</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Edit Pengguna</h1>
            <a href="users.php" class="back-btn">← Kembali ke Senarai</a>
        </div>

        <?php if ($user_data): ?>
        <!-- User Info Card -->
        <div class="user-info-card">
            <h3>Maklumat Pengguna Semasa</h3>
            <p><strong>ID:</strong> <?php echo htmlspecialchars($user_data['id_pengguna']); ?></p>
            <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_data['nama_pengguna']); ?></p>
            <span class="user-badge">
                <?php echo ucfirst($user_data['jenis_pengguna']); ?>
            </span>
            <?php if ($edit_user_id == $user_info['id']): ?>
                <span class="user-badge">Akaun Anda</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <strong>Sila betulkan ralat berikut:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($edit_user_id == $user_info['id'] && $user_info['type'] == 'admin'): ?>
            <div class="warning">
                <strong>Amaran:</strong> Anda sedang mengedit profil anda sendiri. Berhati-hati ketika menukar jenis pengguna kerana ia boleh menjejaskan akses anda.
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" id="editUserForm">
                <div class="form-section">
                    <h3 class="section-title">Maklumat Pengguna</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_pengguna_display">ID Pengguna</label>
                            <input type="text" id="id_pengguna_display" 
                                   value="<?php echo htmlspecialchars($id_pengguna); ?>" 
                                   disabled>
                            <div class="help-text">ID Pengguna tidak boleh diubah</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="jenis_pengguna">Jenis Pengguna <span class="required">*</span></label>
                            <select id="jenis_pengguna" name="jenis_pengguna" required>
                                <option value="">Pilih Jenis Pengguna</option>
                                <option value="guru" <?php echo $jenis_pengguna == 'guru' ? 'selected' : ''; ?>>Guru</option>
                                <option value="admin" <?php echo $jenis_pengguna == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <?php if ($edit_user_id == $user_info['id'] && $user_info['type'] == 'admin'): ?>
                                <div class="help-text" style="color: #dc3545;">Berhati-hati menukar daripada Admin - anda mungkin kehilangan akses!</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row single">
                        <div class="form-group">
                            <label for="nama_pengguna">Nama Pengguna <span class="required">*</span></label>
                            <input type="text" id="nama_pengguna" name="nama_pengguna" 
                                   value="<?php echo htmlspecialchars($nama_pengguna); ?>" 
                                   required maxlength="100"
                                   placeholder="Contoh: Ustaz Ahmad bin Abdullah">
                            <div class="help-text">Nama penuh pengguna. Minimum 3 aksara.</div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Kata Laluan</h3>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="change_password" name="change_password" 
                               <?php echo $change_password ? 'checked' : ''; ?>>
                        <label for="change_password">Tukar Kata Laluan</label>
                    </div>
                    
                    <div class="password-section <?php echo $change_password ? 'active' : 'inactive'; ?>" id="passwordSection">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="kata_laluan">Kata Laluan Baru</label>
                                <input type="password" id="kata_laluan" name="kata_laluan" 
                                       minlength="6" maxlength="255"
                                       placeholder="Minimum 6 aksara">
                                <div class="password-strength" id="passwordStrength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Pengesahan Kata Laluan Baru</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       minlength="6" maxlength="255"
                                       placeholder="Masukkan semula kata laluan baru">
                                <div class="help-text" id="passwordMatch"></div>
                            </div>
                        </div>
                        <div class="help-text">
                            <strong>Nota:</strong> Kosongkan jika tidak mahu menukar kata laluan
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <a href="users.php" class="btn btn-secondary">Batal</a>
                    <button type="reset" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Kemaskini Pengguna</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Password change checkbox handler
        document.getElementById('change_password').addEventListener('change', function() {
            const passwordSection = document.getElementById('passwordSection');
            const passwordFields = passwordSection.querySelectorAll('input');
            
            if (this.checked) {
                passwordSection.classList.remove('inactive');
                passwordSection.classList.add('active');
                passwordFields.forEach(field => {
                    field.disabled = false;
                });
            } else {
                passwordSection.classList.remove('active');
                passwordSection.classList.add('inactive');
                passwordFields.forEach(field => {
                    field.disabled = true;
                    field.value = '';
                });
                // Clear password feedback
                document.getElementById('passwordStrength').innerHTML = '';
                document.getElementById('passwordMatch').innerHTML = '';
            }
        });

        // Initialize password section state
        window.addEventListener('load', function() {
            const changePasswordCheckbox = document.getElementById('change_password');
            const passwordSection = document.getElementById('passwordSection');
            const passwordFields = passwordSection.querySelectorAll('input');
            
            if (!changePasswordCheckbox.checked) {
                passwordFields.forEach(field => {
                    field.disabled = true;
                });
            }
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength < 3) {
                feedback = '<span class="strength-weak">Lemah</span>';
            } else if (strength < 5) {
                feedback = '<span class="strength-medium">Sederhana</span>';
            } else {
                feedback = '<span class="strength-strong">Kuat</span>';
            }
            
            return feedback;
        }

        // Password match checker
        function checkPasswordMatch(password, confirmPassword) {
            if (confirmPassword === '') {
                return '';
            } else if (password === confirmPassword) {
                return '<span style="color: #28a745;">Kata laluan sepadan ✓</span>';
            } else {
                return '<span style="color: #dc3545;">Kata laluan tidak sepadan ✗</span>';
            }
        }

        // Event listeners for password fields
        document.getElementById('kata_laluan').addEventListener('input', function() {
            const password = this.value;
            const strengthElement = document.getElementById('passwordStrength');
            strengthElement.innerHTML = checkPasswordStrength(password);
            
            // Check match with confirm password
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('passwordMatch');
            matchElement.innerHTML = checkPasswordMatch(password, confirmPassword);
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('kata_laluan').value;
            const confirmPassword = this.value;
            const matchElement = document.getElementById('passwordMatch');
            matchElement.innerHTML = checkPasswordMatch(password, confirmPassword);
        });

        // Form validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const changePassword = document.getElementById('change_password').checked;
            
            if (changePassword) {
                const password = document.getElementById('kata_laluan').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Kata laluan dan pengesahan kata laluan tidak sepadan.');
                    return false;
                }
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Kata laluan mestilah sekurang-kurangnya 6 aksara.');
                    return false;
                }
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sedang memproses...';
        });

        // Reset form function
        function resetForm() {
            // Reset to original values
            document.getElementById('nama_pengguna').value = '<?php echo addslashes($user_data['nama_pengguna']); ?>';
            document.getElementById('jenis_pengguna').value = '<?php echo $user_data['jenis_pengguna']; ?>';
            document.getElementById('change_password').checked = false;
            document.getElementById('kata_laluan').value = '';
            document.getElementById('confirm_password').value = '';
            
            // Reset password section
            const passwordSection = document.getElementById('passwordSection');
            const passwordFields = passwordSection.querySelectorAll('input');
            passwordSection.classList.remove('active');
            passwordSection.classList.add('inactive');
            passwordFields.forEach(field => {
                field.disabled = true;
                field.value = '';
            });
            
            // Clear password feedback
            document.getElementById('passwordStrength').innerHTML = '';
            document.getElementById('passwordMatch').innerHTML = '';
            
            // Re-enable submit button if it was disabled
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Kemaskini Pengguna';
        }

        // User type change warning
        document.getElementById('jenis_pengguna').addEventListener('change', function() {
            const currentUserId = '<?php echo $edit_user_id; ?>';
            const loggedInUserId = '<?php echo $user_info['id']; ?>';
            const loggedInUserType = '<?php echo $user_info['type']; ?>';
            
            if (currentUserId === loggedInUserId && loggedInUserType === 'admin' && this.value !== 'admin') {
                if (!confirm('Amaran: Anda akan menukar jenis pengguna anda sendiri daripada Admin. Ini mungkin menyebabkan anda kehilangan akses kepada halaman ini. Adakah anda pasti?')) {
                    this.value = 'admin'; // Reset to admin
                }
            }
        });

        // Auto-save draft functionality (optional enhancement)
        let autoSaveTimeout;
        function autoSaveDraft() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                // This could be enhanced to save form data to localStorage
                // for recovery in case of accidental page refresh
                console.log('Form data auto-saved (placeholder for future enhancement)');
            }, 2000);
        }

        // Add auto-save listeners to form fields
        document.getElementById('nama_pengguna').addEventListener('input', autoSaveDraft);
        document.getElementById('jenis_pengguna').addEventListener('change', autoSaveDraft);

        // Prevent accidental page leave with unsaved changes
        let formChanged = false;
        const formElements = document.querySelectorAll('#editUserForm input, #editUserForm select');
        formElements.forEach(element => {
            element.addEventListener('change', function() {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
                return 'Anda mempunyai perubahan yang belum disimpan. Adakah anda pasti mahu meninggalkan halaman ini?';
            }
        });

        // Clear the form changed flag when form is submitted
        document.getElementById('editUserForm').addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
</body>
</html>