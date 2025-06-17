<?php
// classes_edit.php
// Edit Class Page

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Get class ID from URL
$edit_class_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($edit_class_id)) {
    header("Location: classes.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$kod_sekolah = '';
$darjah_kelas = '';
$nama_kelas = '';
$guru_kelas = '';

$errors = [];
$success_message = '';
$class_data = null;
$schools = [];

// Get dropdown data - schools
try {
    $schools_stmt = $db->prepare("SELECT kod_sekolah, nama_sekolah FROM SEKOLAH ORDER BY nama_sekolah");
    $schools_stmt->execute();
    $schools = $schools_stmt->fetchAll();
} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data sekolah: " . $e->getMessage();
}

// Fetch existing class data
try {
    $stmt = $db->prepare("
        SELECT k.*, s.nama_sekolah 
        FROM KELAS k 
        LEFT JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
        WHERE k.id_kelas = ?
    ");
    $stmt->execute([$edit_class_id]);
    $class_data = $stmt->fetch();
    
    if (!$class_data) {
        header("Location: classes.php");
        exit();
    }
    
    // Set form data
    $kod_sekolah = $class_data['kod_sekolah'];
    $darjah_kelas = $class_data['darjah_kelas'];
    $nama_kelas = $class_data['nama_kelas'];
    $guru_kelas = $class_data['guru_kelas'];
    
} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data kelas: " . $e->getMessage();
}

// Get student count for this class
$student_count = 0;
try {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM PELAJAR WHERE id_kelas = ?");
    $count_stmt->execute([$edit_class_id]);
    $student_count = $count_stmt->fetchColumn();
} catch(PDOException $e) {
    // Non-critical error, continue
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $kod_sekolah = $_POST['kod_sekolah'];
    $darjah_kelas = $_POST['darjah_kelas'];
    $nama_kelas = trim($_POST['nama_kelas']);
    $guru_kelas = trim($_POST['guru_kelas']);
    
    // Validation
    if (empty($kod_sekolah)) {
        $errors[] = "Sekolah adalah wajib.";
    }
    
    if (empty($darjah_kelas)) {
        $errors[] = "Darjah Kelas adalah wajib.";
    } elseif (!in_array($darjah_kelas, ['1', '2', '3', '4', '5', '6'])) {
        $errors[] = "Darjah Kelas tidak sah.";
    }
    
    if (empty($nama_kelas)) {
        $errors[] = "Nama Kelas adalah wajib.";
    } elseif (strlen($nama_kelas) < 2) {
        $errors[] = "Nama Kelas mestilah sekurang-kurangnya 2 aksara.";
    }
    
    if (empty($guru_kelas)) {
        $errors[] = "Nama Guru Kelas adalah wajib.";
    } elseif (strlen($guru_kelas) < 3) {
        $errors[] = "Nama Guru Kelas mestilah sekurang-kurangnya 3 aksara.";
    }
    
    // Check for duplicate class name in the same school and grade (excluding current class)
    if (!empty($kod_sekolah) && !empty($darjah_kelas) && !empty($nama_kelas)) {
        $duplicate_check = $db->prepare("
            SELECT COUNT(*) FROM KELAS 
            WHERE kod_sekolah = ? AND darjah_kelas = ? AND nama_kelas = ? AND id_kelas != ?
        ");
        $duplicate_check->execute([$kod_sekolah, $darjah_kelas, $nama_kelas, $edit_class_id]);
        if ($duplicate_check->fetchColumn() > 0) {
            $errors[] = "Kelas dengan nama yang sama sudah wujud untuk darjah dan sekolah ini.";
        }
    }
    
    // If no errors, update the class
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE KELAS 
                SET kod_sekolah = ?, darjah_kelas = ?, nama_kelas = ?, guru_kelas = ? 
                WHERE id_kelas = ?
            ");
            
            $stmt->execute([
                $kod_sekolah,
                $darjah_kelas,
                $nama_kelas,
                $guru_kelas,
                $edit_class_id
            ]);
            
            $success_message = "Maklumat kelas berjaya dikemas kini.";
            
            // Refresh class data
            $stmt = $db->prepare("
                SELECT k.*, s.nama_sekolah 
                FROM KELAS k 
                LEFT JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
                WHERE k.id_kelas = ?
            ");
            $stmt->execute([$edit_class_id]);
            $class_data = $stmt->fetch();
            
            // Update form data
            $kod_sekolah = $class_data['kod_sekolah'];
            $darjah_kelas = $class_data['darjah_kelas'];
            $nama_kelas = $class_data['nama_kelas'];
            $guru_kelas = $class_data['guru_kelas'];
            
        } catch(PDOException $e) {
            $errors[] = "Ralat mengemaskini kelas: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kelas - Sistem Maklumat Pelajar</title>
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

        /* Class Info Card */
        .class-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .class-info-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .class-info-card p {
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .class-info-card .class-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin-top: 10px;
            margin-right: 10px;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .stat-item {
            background: rgba(255,255,255,0.1);
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-item .stat-number {
            font-size: 20px;
            font-weight: bold;
            display: block;
        }

        .stat-item .stat-label {
            font-size: 12px;
            opacity: 0.8;
        }

        .class-card-content {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            flex-wrap: wrap;
        }

        .left-info {
            flex: 1 1 60%;
        }

        .right-stats {
            flex: 1 1 35%;
        }

        @media (max-width: 600px) {
            .stats-row {
                flex-direction: column;
                gap: 10px;
            }
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
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

        /* Info box */
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
            color: #1565c0;
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

            .stats-row {
                flex-direction: column;
                gap: 10px;
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
                <li><a href="classes.php" class="active">Kelas</a></li>
                <li><a href="school.php">Sekolah</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <?php if ($is_admin): ?>
                <li><a href="users.php">Pengguna</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Edit Kelas</h1>
            <a href="classes.php" class="back-btn">← Kembali ke Senarai</a>
        </div>

        <?php if ($class_data): ?>
        <!-- Class Info Card -->
        <div class="class-info-card">
            <h3>Maklumat Kelas Semasa</h3>
            <div class="class-card-content">
                <div class="left-info">
                    <p><strong>ID Kelas:</strong> <?php echo htmlspecialchars($class_data['id_kelas']); ?></p>
                    <p><strong>Sekolah:</strong> <?php echo htmlspecialchars($class_data['nama_sekolah']); ?></p>
                    <p><strong>Kelas:</strong> Darjah <?php echo htmlspecialchars($class_data['darjah_kelas']); ?> - <?php echo htmlspecialchars($class_data['nama_kelas']); ?></p>
                    <p><strong>Guru Kelas:</strong> <?php echo htmlspecialchars($class_data['guru_kelas']); ?></p>
                </div>
                <div class="right-stats">
                    <div class="stats-row">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $student_count; ?></span>
                            <span class="stat-label">Bilangan Pelajar</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">Darjah <?php echo htmlspecialchars($class_data['darjah_kelas']); ?></span>
                            <span class="stat-label">Tahap Pendidikan</span>
                        </div>
                    </div>
                </div>
            </div>

            <span class="class-badge">Kelas Aktif</span>
            <?php if ($student_count > 0): ?>
                <span class="class-badge"><?php echo $student_count; ?> Pelajar</span>
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

        <?php if ($student_count > 0): ?>
            <div class="warning">
                <strong>Amaran:</strong> Kelas ini mempunyai <?php echo $student_count; ?> pelajar. 
                Perubahan pada maklumat sekolah atau darjah kelas mungkin mempengaruhi rekod pelajar.
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" id="editClassForm">
                <div class="form-section">
                    <h3 class="section-title">Maklumat Kelas</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_kelas_display">ID Kelas</label>
                            <input type="text" id="id_kelas_display" 
                                   value="<?php echo htmlspecialchars($edit_class_id); ?>" 
                                   disabled>
                            <div class="help-text">ID Kelas tidak boleh diubah</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="kod_sekolah">Sekolah <span class="required">*</span></label>
                            <select id="kod_sekolah" name="kod_sekolah" required>
                                <option value="">Pilih Sekolah</option>
                                <?php foreach ($schools as $school): ?>
                                    <option value="<?php echo htmlspecialchars($school['kod_sekolah']); ?>"
                                            <?php echo ($kod_sekolah == $school['kod_sekolah']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($school['nama_sekolah']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($student_count > 0): ?>
                                <div class="help-text" style="color: #dc3545;">Berhati-hati menukar sekolah - ada <?php echo $student_count; ?> pelajar!</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="darjah_kelas">Darjah Kelas <span class="required">*</span></label>
                            <select id="darjah_kelas" name="darjah_kelas" required>
                                <option value="">Pilih Darjah</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($darjah_kelas == $i) ? 'selected' : ''; ?>>
                                        Darjah <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <?php if ($student_count > 0): ?>
                                <div class="help-text" style="color: #dc3545;">Berhati-hati menukar darjah - ada <?php echo $student_count; ?> pelajar!</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="nama_kelas">Nama Kelas <span class="required">*</span></label>
                            <input type="text" id="nama_kelas" name="nama_kelas" 
                                   value="<?php echo htmlspecialchars($nama_kelas); ?>" 
                                   required maxlength="50"
                                   placeholder="Contoh: Kelas A, Kelas B, Kelas Cemerlang">
                            <div class="help-text">Nama kelas yang unik untuk darjah dan sekolah ini. Minimum 2 aksara.</div>
                        </div>
                    </div>

                    <div class="form-row single">
                        <div class="form-group">
                            <label for="guru_kelas">Nama Guru Kelas <span class="required">*</span></label>
                            <input type="text" id="guru_kelas" name="guru_kelas" 
                                   value="<?php echo htmlspecialchars($guru_kelas); ?>" 
                                   required maxlength="100"
                                   placeholder="Nama penuh guru yang bertanggungjawab">
                            <div class="help-text">Nama penuh guru yang bertanggungjawab untuk kelas ini. Minimum 3 aksara.</div>
                        </div>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Nota Penting:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Perubahan pada sekolah atau darjah akan mempengaruhi rekod pelajar dalam kelas ini</li>
                        <li>Pastikan guru kelas yang baru telah bersetuju untuk mengendalikan kelas ini</li>
                        <li>Nama kelas mestilah unik untuk setiap darjah di sekolah yang sama</li>
                        <li>Sistem akan mengekalkan semua rekod pelajar selepas perubahan dibuat</li>
                    </ul>
                </div>

                <div class="form-buttons">
                    <a href="classes.php" class="btn btn-secondary">Batal</a>
                    <button type="reset" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Kemaskini Kelas</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Form validation
        document.getElementById('editClassForm').addEventListener('submit', function(e) {
            const namaKelas = document.getElementById('nama_kelas').value.trim();
            const guruKelas = document.getElementById('guru_kelas').value.trim();
            
            if (namaKelas.length < 2) {
                e.preventDefault();
                alert('Nama kelas mestilah sekurang-kurangnya 2 aksara.');
                return false;
            }
            
            if (guruKelas.length < 3) {
                e.preventDefault();
                alert('Nama guru kelas mestilah sekurang-kurangnya 3 aksara.');
                return false;
            }
            
            // Check for critical changes
            const studentCount = <?php echo $student_count; ?>;
            const originalSchool = '<?php echo addslashes($class_data['kod_sekolah']); ?>';
            const originalGrade = '<?php echo $class_data['darjah_kelas']; ?>';
            const newSchool = document.getElementById('kod_sekolah').value;
            const newGrade = document.getElementById('darjah_kelas').value;
            
            if (studentCount > 0 && (originalSchool !== newSchool || originalGrade !== newGrade)) {
                if (!confirm('Amaran: Kelas ini mempunyai ' + studentCount + ' pelajar. Perubahan sekolah atau darjah akan mempengaruhi rekod mereka. Adakah anda pasti mahu meneruskan?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sedang memproses...';
        });

        // Auto-capitalize names
        document.getElementById('nama_kelas').addEventListener('input', function(e) {
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });

        document.getElementById('guru_kelas').addEventListener('input', function(e) {
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });

        // Live validation feedback
        document.getElementById('nama_kelas').addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && value.length < 2) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e1e1';
            }
        });
        
        document.getElementById('guru_kelas').addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && value.length < 3) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e1e1';
            }
        });

        // Reset form function
        function resetForm() {
            if (confirm('Adakah anda pasti mahu reset borang ini? Semua perubahan akan hilang.')) {
                document.getElementById('editClassForm').reset();
                // Reset any styling changes
                document.getElementById('nama_kelas').style.borderColor = '#e1e1e1';
                document.getElementById('guru_kelas').style.borderColor = '#e1e1e1';
            }
        }

        // Prevent form submission on Enter key in text inputs
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.type === 'text') {
                e.preventDefault();
            }
        });

        // Show loading state when navigating away
        window.addEventListener('beforeunload', function(e) {
            const form = document.getElementById('editClassForm');
            const formData = new FormData(form);
            let hasChanges = false;
            
            // Check if form has been modified
            const originalValues = {
                kod_sekolah: '<?php echo addslashes($class_data['kod_sekolah']); ?>',
                darjah_kelas: '<?php echo $class_data['darjah_kelas']; ?>',
                nama_kelas: '<?php echo addslashes($class_data['nama_kelas']); ?>',
                guru_kelas: '<?php echo addslashes($class_data['guru_kelas']); ?>'
            };
            
            for (let [key, value] of formData.entries()) {
                if (originalValues[key] && originalValues[key] !== value) {
                    hasChanges = true;
                    break;
                }
            }
            
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Real-time duplicate check (optional enhancement)
        let duplicateCheckTimeout;
        function checkDuplicateClass() {
            clearTimeout(duplicateCheckTimeout);
            duplicateCheckTimeout = setTimeout(function() {
                const kodSekolah = document.getElementById('kod_sekolah').value;
                const darjahKelas = document.getElementById('darjah_kelas').value;
                const namaKelas = document.getElementById('nama_kelas').value.trim();
                
                if (kodSekolah && darjahKelas && namaKelas && namaKelas.length >= 2) {
                    // This could be enhanced with AJAX call to check duplicates
                    // For now, just provide visual feedback
                }
            }, 500);
        }

        document.getElementById('kod_sekolah').addEventListener('change', checkDuplicateClass);
        document.getElementById('darjah_kelas').addEventListener('change', checkDuplicateClass);
        document.getElementById('nama_kelas').addEventListener('input', checkDuplicateClass);
    </script>
</body>
</html>