<?php
// classes_add.php
// Add New Class - Form for adding class information

require_once 'config/database.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$errors = [];
$success_message = '';

// Get dropdown data
try {
    // Get schools
    $schools_stmt = $db->prepare("SELECT kod_sekolah, nama_sekolah FROM SEKOLAH ORDER BY nama_sekolah");
    $schools_stmt->execute();
    $schools = $schools_stmt->fetchAll();

} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Class data
    $kod_sekolah = $_POST['kod_sekolah'];
    $darjah_kelas = $_POST['darjah_kelas'];
    $nama_kelas = trim($_POST['nama_kelas']);
    $guru_kelas = trim($_POST['guru_kelas']);

    // Validation
    if (empty($kod_sekolah)) $errors[] = "Sekolah diperlukan.";
    if (empty($darjah_kelas)) $errors[] = "Darjah Kelas diperlukan.";
    if (empty($nama_kelas)) $errors[] = "Nama Kelas diperlukan.";
    if (empty($guru_kelas)) $errors[] = "Nama Guru Kelas diperlukan.";

    // Check for duplicate class name in the same school and grade
    if (!empty($kod_sekolah) && !empty($darjah_kelas) && !empty($nama_kelas)) {
        $duplicate_check = $db->prepare("SELECT COUNT(*) FROM KELAS WHERE kod_sekolah = ? AND darjah_kelas = ? AND nama_kelas = ?");
        $duplicate_check->execute([$kod_sekolah, $darjah_kelas, $nama_kelas]);
        if ($duplicate_check->fetchColumn() > 0) {
            $errors[] = "Kelas dengan nama yang sama sudah wujud untuk darjah dan sekolah ini.";
        }
    }

    // If no errors, save to database
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert class (id_kelas will be auto-generated)
            $insert_stmt = $db->prepare("INSERT INTO KELAS (kod_sekolah, darjah_kelas, nama_kelas, guru_kelas) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$kod_sekolah, $darjah_kelas, $nama_kelas, $guru_kelas]);

            $db->commit();
            $success_message = "Kelas baru berjaya ditambah!";
            
            // Clear form data
            $_POST = [];

        } catch(PDOException $e) {
            $db->rollback();
            $errors[] = "Ralat menyimpan data: " . $e->getMessage();
        }
    }
}

// Get current year for default
$current_year = date('Y');
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kelas Baru - Sistem Maklumat Pelajar</title>
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

        /* Form Styles */
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-section {
            padding: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        /* Form Actions */
        .form-actions {
            padding: 30px;
            background: #f8f9fa;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
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
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .error li {
            margin-bottom: 5px;
        }

        .error li:before {
            content: "• ";
            color: #dc3545;
            font-weight: bold;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .nav-menu {
                flex-wrap: wrap;
                gap: 10px;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        /* Helper text */
        .helper-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Sistem Maklumat Pelajar</h1>
                <p>Sekolah Agama Bukit Banjar</p>
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
                <?php if ($is_admin): ?>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="users.php">Pengguna</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Tambah Kelas Baru</h1>
            <a href="classes.php" class="back-btn">← Kembali</a>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
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

        <form method="POST" action="" class="form-container">
            <!-- Class Information -->
            <div class="form-section">
                <h2 class="section-title">Maklumat Kelas</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="kod_sekolah">Sekolah <span class="required">*</span></label>
                        <select id="kod_sekolah" name="kod_sekolah" required>
                            <option value="">Pilih Sekolah</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo htmlspecialchars($school['kod_sekolah']); ?>"
                                        <?php echo (($_POST['kod_sekolah'] ?? '') == $school['kod_sekolah']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($school['nama_sekolah']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="darjah_kelas">Darjah Kelas <span class="required">*</span></label>
                        <select id="darjah_kelas" name="darjah_kelas" required>
                            <option value="">Pilih Darjah</option>
                            <option value="1" <?php echo (($_POST['darjah_kelas'] ?? '') == '1') ? 'selected' : ''; ?>>Darjah 1</option>
                            <option value="2" <?php echo (($_POST['darjah_kelas'] ?? '') == '2') ? 'selected' : ''; ?>>Darjah 2</option>
                            <option value="3" <?php echo (($_POST['darjah_kelas'] ?? '') == '3') ? 'selected' : ''; ?>>Darjah 3</option>
                            <option value="4" <?php echo (($_POST['darjah_kelas'] ?? '') == '4') ? 'selected' : ''; ?>>Darjah 4</option>
                            <option value="5" <?php echo (($_POST['darjah_kelas'] ?? '') == '5') ? 'selected' : ''; ?>>Darjah 5</option>
                            <option value="6" <?php echo (($_POST['darjah_kelas'] ?? '') == '6') ? 'selected' : ''; ?>>Darjah 6</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nama_kelas">Nama Kelas <span class="required">*</span></label>
                        <input type="text" id="nama_kelas" name="nama_kelas" maxlength="50" 
                               value="<?php echo htmlspecialchars($_POST['nama_kelas'] ?? ''); ?>" required>
                        <div class="helper-text">Contoh: Kelas A, Kelas B, Kelas Cemerlang, dsb.</div>
                    </div>

                    <div class="form-group full-width">
                        <label for="guru_kelas">Nama Guru Kelas <span class="required">*</span></label>
                        <input type="text" id="guru_kelas" name="guru_kelas" maxlength="100" 
                               value="<?php echo htmlspecialchars($_POST['guru_kelas'] ?? ''); ?>" required>
                        <div class="helper-text">Nama penuh guru yang bertanggungjawab terhadap kelas ini</div>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Maklumat:</strong> 
                    <ul style="margin: 10px 0 0 20px;">
                        <li>ID Kelas akan dijana secara automatik oleh sistem</li>
                        <li>Pastikan guru kelas yang dipilih telah bersetuju untuk mengendalikan kelas ini</li>
                        <li>Darjah kelas akan mempengaruhi kurikulum dan sistem pemarkahan</li>
                        <li>Nama kelas mestilah unik untuk setiap darjah di sekolah yang sama</li>
                    </ul>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="classes.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Kelas</button>
            </div>
        </form>
    </main>

    <script>
        // Auto-capitalize names
        document.getElementById('nama_kelas').addEventListener('input', function(e) {
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });

        document.getElementById('guru_kelas').addEventListener('input', function(e) {
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });

        // Show school info when selected
        document.getElementById('kod_sekolah').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                console.log('Selected school:', selectedOption.text);
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const namaKelas = document.getElementById('nama_kelas').value.trim();
            const guruKelas = document.getElementById('guru_kelas').value.trim();
            
            // Check for minimum length
            if (namaKelas && namaKelas.length < 2) {
                alert('Nama kelas terlalu pendek. Minimum 2 aksara.');
                e.preventDefault();
                return false;
            }

            if (guruKelas && guruKelas.length < 3) {
                alert('Nama guru terlalu pendek. Minimum 3 aksara.');
                e.preventDefault();
                return false;
            }
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
    </script>
</body>
</html>