<?php
// users_add.php
// Add New User Page

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

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$id_pengguna = '';
$nama_pengguna = '';
$jenis_pengguna = '';
$kata_laluan = '';
$confirm_password = '';
$kod_sekolah = 'SAKNJ/LDG/10018';

$errors = [];
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $id_pengguna = trim($_POST['id_pengguna']);
    $nama_pengguna = trim($_POST['nama_pengguna']);
    $jenis_pengguna = $_POST['jenis_pengguna'];
    $kata_laluan = $_POST['kata_laluan'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($id_pengguna)) {
        $errors[] = "ID Pengguna adalah wajib.";
    } elseif (strlen($id_pengguna) < 3) {
        $errors[] = "ID Pengguna mestilah sekurang-kurangnya 3 aksara.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $id_pengguna)) {
        $errors[] = "ID Pengguna hanya boleh mengandungi huruf, nombor dan underscore.";
    }
    
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
    
    if (empty($kata_laluan)) {
        $errors[] = "Kata Laluan adalah wajib.";
    } elseif (strlen($kata_laluan) < 6) {
        $errors[] = "Kata Laluan mestilah sekurang-kurangnya 6 aksara.";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Pengesahan Kata Laluan adalah wajib.";
    } elseif ($kata_laluan !== $confirm_password) {
        $errors[] = "Kata Laluan dan Pengesahan Kata Laluan tidak sepadan.";
    }
    
    // Check if user ID already exists
    if (empty($errors)) {
        try {
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM pengguna WHERE id_pengguna = ?");
            $check_stmt->execute([$id_pengguna]);
            $count = $check_stmt->fetchColumn();
            
            if ($count > 0) {
                $errors[] = "ID Pengguna sudah wujud. Sila pilih ID yang lain.";
            }
        } catch(PDOException $e) {
            $errors[] = "Ralat memeriksa ID Pengguna: " . $e->getMessage();
        }
    }
    
    // If no errors, insert the new user
    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($kata_laluan, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $db->prepare("
                INSERT INTO pengguna (id_pengguna, nama_pengguna, kata_laluan, jenis_pengguna, kod_sekolah) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $id_pengguna,
                $nama_pengguna,
                $hashed_password,
                $jenis_pengguna,
                $kod_sekolah
            ]);
            
            $success_message = "Pengguna baru berjaya ditambah.";
            
            // Reset form
            $id_pengguna = '';
            $nama_pengguna = '';
            $jenis_pengguna = '';
            $kata_laluan = '';
            $confirm_password = '';
            
        } catch(PDOException $e) {
            $errors[] = "Ralat menambah pengguna: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna Baru - Sistem Maklumat Pelajar</title>
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

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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

        /* Focus states */
        .form-group input:invalid {
            border-color: #dc3545;
        }

        .form-group input:valid {
            border-color: #28a745;
        }

        /* Loading state */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
            <h1 class="page-title">Tambah Pengguna Baru</h1>
            <a href="users.php" class="back-btn">← Kembali ke Senarai</a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="success">
                <?php echo htmlspecialchars($success_message); ?>
                <br><br>
                <a href="users.php">Kembali ke Senarai Pengguna</a> | 
                <a href="users_add.php">Tambah Pengguna Lain</a>
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

        <div class="form-container">
            <form method="POST" action="" id="addUserForm">
                <div class="form-section">
                    <h3 class="section-title">Maklumat Pengguna</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_pengguna">ID Pengguna <span class="required">*</span></label>
                            <input type="text" id="id_pengguna" name="id_pengguna" 
                                   value="<?php echo htmlspecialchars($id_pengguna); ?>" 
                                   required maxlength="50" pattern="[a-zA-Z0-9_]+"
                                   placeholder="Contoh: guru001, admin">
                            <div class="help-text">Hanya huruf, nombor dan underscore dibenarkan. Minimum 3 aksara.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="jenis_pengguna">Jenis Pengguna <span class="required">*</span></label>
                            <select id="jenis_pengguna" name="jenis_pengguna" required>
                                <option value="">Pilih Jenis Pengguna</option>
                                <option value="guru" <?php echo $jenis_pengguna == 'guru' ? 'selected' : ''; ?>>Guru</option>
                                <option value="admin" <?php echo $jenis_pengguna == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
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
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="kata_laluan">Kata Laluan <span class="required">*</span></label>
                            <input type="password" id="kata_laluan" name="kata_laluan" 
                                   required minlength="6" maxlength="255"
                                   placeholder="Minimum 6 aksara">
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Pengesahan Kata Laluan <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   required minlength="6" maxlength="255"
                                   placeholder="Masukkan semula kata laluan">
                            <div class="help-text" id="passwordMatch"></div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <a href="users.php" class="btn btn-secondary">Batal</a>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Tambah Pengguna</button>
                </div>
            </form>
        </div>
    </main>

    <script>
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

        // Event listeners
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
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const password = document.getElementById('kata_laluan').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Kata laluan dan pengesahan kata laluan tidak sepadan.');
                return false;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sedang memproses...';
        });

        // Auto-generate user ID suggestion
        document.getElementById('nama_pengguna').addEventListener('blur', function() {
            const nama = this.value.trim();
            const jenisSelect = document.getElementById('jenis_pengguna');
            const idInput = document.getElementById('id_pengguna');
            
            if (nama && !idInput.value) {
                let suggestion = '';
                if (jenisSelect.value === 'admin') {
                    suggestion = 'admin';
                } else if (jenisSelect.value === 'guru') {
                    // Extract first name and create ID
                    const firstName = nama.split(' ')[0].toLowerCase();
                    suggestion = 'guru_' + firstName;
                }
                idInput.placeholder = 'Cadangan: ' + suggestion;
            }
        });

        // Prevent form submission with Enter key in input fields (except submit button)
        document.querySelectorAll('input').forEach(function(input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.type !== 'submit') {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>