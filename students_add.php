<?php
// students_add.php
// Add New Student - Form for adding student personal and guardian information

require_once 'config/database.php';
// Start session to store form data
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
$jumlah_pendapatan = 0;
$pendapatan_perkapita = 0;
$kaum = '';

// Get dropdown data
try {
    // Get schools and classes
    $schools_stmt = $db->prepare("SELECT kod_sekolah, nama_sekolah FROM SEKOLAH ORDER BY nama_sekolah");
    $schools_stmt->execute();
    $schools = $schools_stmt->fetchAll();

    $classes_stmt = $db->prepare("SELECT k.id_kelas, k.nama_kelas, k.darjah_kelas, s.nama_sekolah, s.kod_sekolah FROM KELAS k JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah ORDER BY s.nama_sekolah, k.darjah_kelas, k.nama_kelas");
    $classes_stmt->execute();
    $classes = $classes_stmt->fetchAll();

} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Student data
    $ic_pelajar = trim($_POST['ic_pelajar']);
    $nama = trim($_POST['nama']);
    $jantina = $_POST['jantina'];
    $kaum = trim($_POST['kaum']);
    $warganegara = trim($_POST['warganegara']);
    $status_pelajar = $_POST['status_pelajar'];
    $status_penjaga = $_POST['status_penjaga'];
    $sijil_lahir = trim($_POST['sijil_lahir']);
    $id_kelas = $_POST['id_kelas'];

    // Guardian data
    $nama_waris = trim($_POST['nama_waris']);
    $status_waris = $_POST['status_waris'];
    $ic_waris = trim($_POST['ic_waris']);
    $nombor_telefon_waris = trim($_POST['nombor_telefon_waris']);
    $alamat = trim($_POST['alamat']);
    $poskod = trim($_POST['poskod']);
    $negeri = trim($_POST['negeri']);
    $bilangan_tanggungan = $_POST['bilangan_tanggungan'];
    $pekerjaan_bapa = trim($_POST['pekerjaan_bapa']);
    $pendapatan_bapa = $_POST['pendapatan_bapa'];
    $pekerjaan_ibu = trim($_POST['pekerjaan_ibu']);
    $pendapatan_ibu = $_POST['pendapatan_ibu'];
    $pekerjaan_penjaga = trim($_POST['pekerjaan_penjaga']);
    $pendapatan_penjaga = $_POST['pendapatan_penjaga'];

    // Validation
    if (empty($ic_pelajar)) $errors[] = "No. IC pelajar diperlukan.";
    if (empty($nama)) $errors[] = "Nama pelajar diperlukan.";
    if (empty($jantina)) $errors[] = "Jantina diperlukan.";
    if (empty($kaum)) $errors[] = "Kaum diperlukan.";
    if (empty($warganegara)) $errors[] = "Warganegara diperlukan.";
    if (empty($id_kelas)) $errors[] = "Kelas diperlukan.";
    if (empty($ic_waris)) $errors[] = "No. IC waris diperlukan.";
    if (empty($nama_waris)) $errors[] = "Nama waris diperlukan.";
    if (empty($alamat)) $errors[] = "Alamat diperlukan.";
    if (empty($poskod)) $errors[] = "Poskod diperlukan.";
    if (empty($negeri)) $errors[] = "Negeri diperlukan.";

    // Validate IC format (basic validation)
    if (!empty($ic_pelajar) && !preg_match('/^\d{12}$/', $ic_pelajar)) {
        $errors[] = "Format No. IC pelajar tidak sah (12 digit).";
    }
    if (!empty($ic_waris) && !preg_match('/^\d{12}$/', $ic_waris)) {
        $errors[] = "Format No. IC waris tidak sah (12 digit).";
    }

    // Validate poskod
    if (!empty($poskod) && !preg_match('/^\d{5}$/', $poskod)) {
        $errors[] = "Format poskod tidak sah (5 digit).";
    }

    // Check if student already exists
    if (!empty($ic_pelajar)) {
        try {
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM PELAJAR WHERE ic_pelajar = ?");
            $check_stmt->execute([$ic_pelajar]);
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = "Pelajar dengan No. IC ini sudah wujud dalam sistem.";
            }
        } catch(PDOException $e) {
            $errors[] = "Ralat memeriksa data: " . $e->getMessage();
        }
    }

    // Calculate total income and per capita income
    $jumlah_pendapatan = 0;
    if (!empty($pendapatan_bapa)) $jumlah_pendapatan += floatval($pendapatan_bapa);
    if (!empty($pendapatan_ibu)) $jumlah_pendapatan += floatval($pendapatan_ibu);
    if (!empty($pendapatan_penjaga)) $jumlah_pendapatan += floatval($pendapatan_penjaga);

    $pendapatan_perkapita = 0;
    if ($jumlah_pendapatan > 0 && $bilangan_tanggungan > 0) {
        $pendapatan_perkapita = $jumlah_pendapatan / $bilangan_tanggungan;
    }

    // If no errors, proceed to next step (don't save to database yet)
    if (empty($errors)) {
        // Store all form data in session to pass to next page
        $_SESSION['student_form_data'] = [
            // Student data
            'ic_pelajar' => $ic_pelajar,
            'nama' => $nama,
            'jantina' => $jantina,
            'kaum' => $kaum,
            'warganegara' => $warganegara,
            'status_pelajar' => $status_pelajar,
            'status_penjaga' => $status_penjaga,
            'sijil_lahir' => $sijil_lahir,
            'id_kelas' => $id_kelas,
            // Guardian data
            'nama_waris' => $nama_waris,
            'status_waris' => $status_waris,
            'ic_waris' => $ic_waris,
            'nombor_telefon_waris' => $nombor_telefon_waris,
            'alamat' => $alamat,
            'poskod' => $poskod,
            'negeri' => $negeri,
            'bilangan_tanggungan' => $bilangan_tanggungan,
            'pekerjaan_bapa' => $pekerjaan_bapa,
            'pendapatan_bapa' => $pendapatan_bapa,
            'pekerjaan_ibu' => $pekerjaan_ibu,
            'pendapatan_ibu' => $pendapatan_ibu,
            'pekerjaan_penjaga' => $pekerjaan_penjaga,
            'pendapatan_penjaga' => $pendapatan_penjaga,
            'jumlah_pendapatan' => $jumlah_pendapatan,
            'pendapatan_perkapita' => $pendapatan_perkapita
        ];

        // Redirect to academic information form
        header("Location: student_additional.php");
        exit();
    }
}

// Get current year for default
$current_year = date('Y');

// Malaysian ethnic groups
$ethnic_groups = [
    'Melayu', 'Cina', 'India', 'Iban', 'Bidayuh', 'Kadazan', 'Bajau', 
    'Murut', 'Orang Asli', 'Lain-lain'
];
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pelajar Baru - Sistem Maklumat Pelajar</title>
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
            max-width: 1000px;
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

        /* Progress indicator */
        .progress-indicator {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .progress-step {
            background: white;
            border: 3px solid #e9ecef;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .progress-step.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }

        .progress-step.incomplete {
            background: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .step-label {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
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
            border-bottom: 1px solid #e9ecef;
        }

        .form-section:last-child {
            border-bottom: none;
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

        .form-group input[type="number"] {
            appearance: textfield;
        }

        .form-group input[type="number"]::-webkit-outer-spin-button,
        .form-group input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Radio buttons */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .radio-option input[type="radio"] {
            width: auto;
            margin: 0;
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

            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Helper text */
        .helper-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Income calculation info */
        .income-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
            color: #1565c0;
        }

        /* Auto-calculation styling */
        .auto-calc {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef) !important;
            border: 2px dashed #6c757d !important;
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
                <li><a href="students.php" class="active">Pelajar</a></li>
                <li><a href="classes.php">Kelas</a></li>
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
            <h1 class="page-title">Tambah Pelajar Baru</h1>
            <a href="students.php" class="back-btn">← Kembali</a>
        </div>
        
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-steps">
                <div class="progress-step active">1</div>
                <div class="progress-step incomplete">2</div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                <div class="step-label">Maklumat Peribadi</div>
                <div class="step-label">Maklumat Tambahan</div>
            </div>
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
            <!-- Student Personal Information -->
            <div class="form-section">
                <h2 class="section-title">1. Maklumat Peribadi Pelajar</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ic_pelajar">No. IC Pelajar <span class="required">*</span></label>
                        <input type="text" id="ic_pelajar" name="ic_pelajar" maxlength="12" 
                               value="<?php echo htmlspecialchars($_POST['ic_pelajar'] ?? ''); ?>" required>
                        <div class="helper-text">12 digit tanpa tanda '-'</div>
                    </div>

                    <div class="form-group">
                        <label for="nama">Nama Penuh <span class="required">*</span></label>
                        <input type="text" id="nama" name="nama" 
                               value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Jantina <span class="required">*</span></label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="lelaki" name="jantina" value="lelaki" 
                                       <?php echo (($_POST['jantina'] ?? '') == 'lelaki') ? 'checked' : ''; ?>>
                                <label for="lelaki">Lelaki</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="perempuan" name="jantina" value="perempuan"
                                       <?php echo (($_POST['jantina'] ?? '') == 'perempuan') ? 'checked' : ''; ?>>
                                <label for="perempuan">Perempuan</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="kaum">Kaum <span class="required">*</span></label>
                        <select id="kaum" name="kaum" required>
                            <option value="">Pilih Kaum</option>
                            <?php foreach ($ethnic_groups as $ethnic): ?>
                            <option value="<?php echo $ethnic; ?>" <?php echo $kaum == $ethnic ? 'selected' : ''; ?>><?php echo $ethnic; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="warganegara">Warganegara <span class="required">*</span></label>
                        <input type="text" id="warganegara" name="warganegara" 
                               value="<?php echo htmlspecialchars($_POST['warganegara'] ?? 'Malaysia'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status_pelajar">Status Pelajar <span class="required">*</span></label>
                        <select id="status_pelajar" name="status_pelajar" required>
                            <option value="">Pilih Status</option>
                            <option value="kandung" <?php echo (($_POST['status_pelajar'] ?? '') == 'kandung') ? 'selected' : ''; ?>>Kandung</option>
                            <option value="tiri" <?php echo (($_POST['status_pelajar'] ?? '') == 'tiri') ? 'selected' : ''; ?>>Tiri</option>
                            <option value="angkat" <?php echo (($_POST['status_pelajar'] ?? '') == 'angkat') ? 'selected' : ''; ?>>Angkat</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status_penjaga">Status Penjaga <span class="required">*</span></label>
                        <select id="status_penjaga" name="status_penjaga" required>
                            <option value="">Pilih Status</option>
                            <option value="ibu bapa" <?php echo (($_POST['status_penjaga'] ?? '') == 'ibu bapa') ? 'selected' : ''; ?>>Ibu Bapa</option>
                            <option value="ibu tunggal" <?php echo (($_POST['status_penjaga'] ?? '') == 'ibu tunggal') ? 'selected' : ''; ?>>Ibu Tunggal</option>
                            <option value="bapa tunggal" <?php echo (($_POST['status_penjaga'] ?? '') == 'bapa tunggal') ? 'selected' : ''; ?>>Bapa Tunggal</option>
                            <option value="penjaga" <?php echo (($_POST['status_penjaga'] ?? '') == 'penjaga') ? 'selected' : ''; ?>>Penjaga</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sijil_lahir">No. Sijil Lahir</label>
                        <input type="text" id="sijil_lahir" name="sijil_lahir" 
                               value="<?php echo htmlspecialchars($_POST['sijil_lahir'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="id_kelas">Kelas <span class="required">*</span></label>
                        <select id="id_kelas" name="id_kelas" required>
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id_kelas']; ?>"
                                        <?php echo (($_POST['id_kelas'] ?? '') == $class['id_kelas']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars('D' . $class['darjah_kelas'] . ' - ' . $class['nama_kelas']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Guardian Information -->
            <div class="form-section">
                <h2 class="section-title">2. Maklumat Penjaga/Waris</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ic_waris">No. IC Waris <span class="required">*</span></label>
                        <input type="text" id="ic_waris" name="ic_waris" maxlength="12" 
                               value="<?php echo htmlspecialchars($_POST['ic_waris'] ?? ''); ?>" required>
                        <div class="helper-text">12 digit tanpa tanda '-'</div>
                    </div>

                    <div class="form-group">
                        <label for="nama_waris">Nama Waris <span class="required">*</span></label>
                        <input type="text" id="nama_waris" name="nama_waris" 
                               value="<?php echo htmlspecialchars($_POST['nama_waris'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status_waris">Status Waris <span class="required">*</span></label>
                        <select id="status_waris" name="status_waris" required>
                            <option value="">Pilih Status</option>
                            <option value="bapa" <?php echo (($_POST['status_waris'] ?? '') == 'bapa') ? 'selected' : ''; ?>>Bapa</option>
                            <option value="ibu" <?php echo (($_POST['status_waris'] ?? '') == 'ibu') ? 'selected' : ''; ?>>Ibu</option>
                            <option value="penjaga" <?php echo (($_POST['status_waris'] ?? '') == 'penjaga') ? 'selected' : ''; ?>>Penjaga</option>
                            <option value="datuk" <?php echo (($_POST['status_waris'] ?? '') == 'datuk') ? 'selected' : ''; ?>>Datuk</option>
                            <option value="nenek" <?php echo (($_POST['status_waris'] ?? '') == 'nenek') ? 'selected' : ''; ?>>Nenek</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nombor_telefon_waris">Nombor Telefon Waris</label>
                        <input type="text" id="nombor_telefon_waris" name="nombor_telefon_waris" 
                               value="<?php echo htmlspecialchars($_POST['nombor_telefon_waris'] ?? ''); ?>">
                        <div class="helper-text">Contoh: 013-1234567</div>
                    </div>

                    <div class="form-group full-width">
                        <label for="alamat">Alamat <span class="required">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" required><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="poskod">Poskod <span class="required">*</span></label>
                        <input type="text" id="poskod" name="poskod" maxlength="5" 
                               value="<?php echo htmlspecialchars($_POST['poskod'] ?? ''); ?>" required>
                        <div class="helper-text">5 digit</div>
                    </div>

                    <div class="form-group">
                        <label for="negeri">Negeri <span class="required">*</span></label>
                        <select id="negeri" name="negeri" required>
                            <option value="">Pilih Negeri</option>
                            <option value="Johor" <?php echo (($_POST['negeri'] ?? '') == 'Johor') ? 'selected' : ''; ?>>Johor</option>
                            <option value="Kedah" <?php echo (($_POST['negeri'] ?? '') == 'Kedah') ? 'selected' : ''; ?>>Kedah</option>
                            <option value="Kelantan" <?php echo (($_POST['negeri'] ?? '') == 'Kelantan') ? 'selected' : ''; ?>>Kelantan</option>
                            <option value="Melaka" <?php echo (($_POST['negeri'] ?? '') == 'Melaka') ? 'selected' : ''; ?>>Melaka</option>
                            <option value="Negeri Sembilan" <?php echo (($_POST['negeri'] ?? '') == 'Negeri Sembilan') ? 'selected' : ''; ?>>Negeri Sembilan</option>
                            <option value="Pahang" <?php echo (($_POST['negeri'] ?? '') == 'Pahang') ? 'selected' : ''; ?>>Pahang</option>
                            <option value="Perak" <?php echo (($_POST['negeri'] ?? '') == 'Perak') ? 'selected' : ''; ?>>Perak</option>
                            <option value="Perlis" <?php echo (($_POST['negeri'] ?? '') == 'Perlis') ? 'selected' : ''; ?>>Perlis</option>
                            <option value="Pulau Pinang" <?php echo (($_POST['negeri'] ?? '') == 'Pulau Pinang') ? 'selected' : ''; ?>>Pulau Pinang</option>
                            <option value="Sabah" <?php echo (($_POST['negeri'] ?? '') == 'Sabah') ? 'selected' : ''; ?>>Sabah</option>
                            <option value="Sarawak" <?php echo (($_POST['negeri'] ?? '') == 'Sarawak') ? 'selected' : ''; ?>>Sarawak</option>
                            <option value="Selangor" <?php echo (($_POST['negeri'] ?? '') == 'Selangor') ? 'selected' : ''; ?>>Selangor</option>
                            <option value="Terengganu" <?php echo (($_POST['negeri'] ?? '') == 'Terengganu') ? 'selected' : ''; ?>>Terengganu</option>
                            <option value="Wilayah Persekutuan Kuala Lumpur" <?php echo (($_POST['negeri'] ?? '') == 'Wilayah Persekutuan Kuala Lumpur') ? 'selected' : ''; ?>>Wilayah Persekutuan Kuala Lumpur</option>
                            <option value="Wilayah Persekutuan Labuan" <?php echo (($_POST['negeri'] ?? '') == 'Wilayah Persekutuan Labuan') ? 'selected' : ''; ?>>Wilayah Persekutuan Labuan</option>
                            <option value="Wilayah Persekutuan Putrajaya" <?php echo (($_POST['negeri'] ?? '') == 'Wilayah Persekutuan Putrajaya') ? 'selected' : ''; ?>>Wilayah Persekutuan Putrajaya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="bilangan_tanggungan">Bilangan Tanggungan</label>
                        <input type="number" id="bilangan_tanggungan" name="bilangan_tanggungan" min="1" max="20" 
                               value="<?php echo htmlspecialchars($_POST['bilangan_tanggungan'] ?? ''); ?>">
                        <div class="helper-text">Termasuk pelajar ini</div>
                    </div>
                </div>
            </div>

            <!-- Employment and Income Information -->
            <div class="form-section">
                <h2 class="section-title">3. Maklumat Pekerjaan dan Pendapatan</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="pekerjaan_bapa">Pekerjaan Bapa</label>
                        <input type="text" id="pekerjaan_bapa" name="pekerjaan_bapa" 
                               value="<?php echo htmlspecialchars($_POST['pekerjaan_bapa'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pendapatan_bapa">Pendapatan Bapa (RM)</label>
                        <input type="number" id="pendapatan_bapa" name="pendapatan_bapa" min="0" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['pendapatan_bapa'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
                        <input type="text" id="pekerjaan_ibu" name="pekerjaan_ibu" 
                               value="<?php echo htmlspecialchars($_POST['pekerjaan_ibu'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pendapatan_ibu">Pendapatan Ibu (RM)</label>
                        <input type="number" id="pendapatan_ibu" name="pendapatan_ibu" min="0" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['pendapatan_ibu'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pekerjaan_penjaga">Pekerjaan Penjaga (jika berlainan)</label>
                        <input type="text" id="pekerjaan_penjaga" name="pekerjaan_penjaga" 
                               value="<?php echo htmlspecialchars($_POST['pekerjaan_penjaga'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="pendapatan_penjaga">Pendapatan Penjaga (RM)</label>
                        <input type="number" id="pendapatan_penjaga" name="pendapatan_penjaga" min="0" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['pendapatan_penjaga'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="jumlah_pendapatan">Jumlah Pendapatan (RM)</label>
                        <input type="number" id="jumlah_pendapatan" name="jumlah_pendapatan" value="<?php echo htmlspecialchars($jumlah_pendapatan); ?>" min="0" step="0.01" class="auto-calc" readonly>
                        <div class="helper-text">Dikira secara automatik</div>
                    </div>
                
                    <div class="form-group">
                        <label for="pendapatan_perkapita">Pendapatan Per Kapita (RM)</label>
                        <input type="number" id="pendapatan_perkapita" name="pendapatan_perkapita" value="<?php echo htmlspecialchars($pendapatan_perkapita); ?>" min="0" step="0.01" class="auto-calc" readonly>
                        <div class="helper-text">Dikira secara automatik</div>
                    </div>
                </div>

                <div class="income-info">
                    <strong>Maklumat:</strong> Jumlah pendapatan dan pendapatan per kapita akan dikira secara automatik berdasarkan maklumat yang dimasukkan.
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="students.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Seterusnya</button>
            </div>
        </form>
    </main>

    <script>
        // Auto-format IC numbers
        document.getElementById('ic_pelajar').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 12);
        });

        document.getElementById('ic_waris').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 12);
        });

        // Auto-format poskod
        document.getElementById('poskod').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 5);
        });

        // Auto-format phone number
        document.getElementById('nombor_telefon_waris').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 3) {
                value = value.substring(0, 3) + '-' + value.substring(3, 10);
            }
            this.value = value;
        });

        // Auto-calculate total income and per capita income
        function calculateTotalIncome() {
            const pendapatanBapa = parseFloat(document.getElementById('pendapatan_bapa').value) || 0;
            const pendapatanIbu = parseFloat(document.getElementById('pendapatan_ibu').value) || 0;
            const pendapatanPenjaga = parseFloat(document.getElementById('pendapatan_penjaga').value) || 0;
            const bilanganTanggungan = parseInt(document.getElementById('bilangan_tanggungan').value) || 1;
            
            const jumlahPendapatan = pendapatanBapa + pendapatanIbu + pendapatanPenjaga;
            const pendapatanPerkapita = jumlahPendapatan / Math.max(bilanganTanggungan, 1);
            
            document.getElementById('jumlah_pendapatan').value = jumlahPendapatan.toFixed(2);
            document.getElementById('pendapatan_perkapita').value = pendapatanPerkapita.toFixed(2);
        }

        // Add event listeners for income calculation
        document.getElementById('pendapatan_bapa').addEventListener('input', calculateTotalIncome);
        document.getElementById('pendapatan_ibu').addEventListener('input', calculateTotalIncome);
        document.getElementById('pendapatan_penjaga').addEventListener('input', calculateTotalIncome);
        document.getElementById('bilangan_tanggungan').addEventListener('input', calculateTotalIncome);

        // Show/hide employment fields based on guardian status
        document.getElementById('status_penjaga').addEventListener('change', function() {
            const value = this.value;
            const bapaFields = document.getElementById('pekerjaan_bapa').closest('.form-group');
            const bapaIncomeFields = document.getElementById('pendapatan_bapa').closest('.form-group');
            const ibuFields = document.getElementById('pekerjaan_ibu').closest('.form-group');
            const ibuIncomeFields = document.getElementById('pendapatan_ibu').closest('.form-group');
            const penjagaFields = document.getElementById('pekerjaan_penjaga').closest('.form-group');
            const penjagaIncomeFields = document.getElementById('pendapatan_penjaga').closest('.form-group');

            // Reset all fields visibility
            [bapaFields, bapaIncomeFields, ibuFields, ibuIncomeFields, penjagaFields, penjagaIncomeFields].forEach(field => {
                field.style.display = 'flex';
            });

            // Clear hidden fields and recalculate
            if (value === 'ibu tunggal') {
                bapaFields.style.display = 'none';
                bapaIncomeFields.style.display = 'none';
                penjagaFields.style.display = 'none';
                penjagaIncomeFields.style.display = 'none';
                // Clear hidden field values
                document.getElementById('pendapatan_bapa').value = '';
                document.getElementById('pendapatan_penjaga').value = '';
            } else if (value === 'bapa tunggal') {
                ibuFields.style.display = 'none';
                ibuIncomeFields.style.display = 'none';
                penjagaFields.style.display = 'none';
                penjagaIncomeFields.style.display = 'none';
                // Clear hidden field values
                document.getElementById('pendapatan_ibu').value = '';
                document.getElementById('pendapatan_penjaga').value = '';
            } else if (value === 'penjaga') {
                bapaFields.style.display = 'none';
                bapaIncomeFields.style.display = 'none';
                ibuFields.style.display = 'none';
                ibuIncomeFields.style.display = 'none';
                // Clear hidden field values
                document.getElementById('pendapatan_bapa').value = '';
                document.getElementById('pendapatan_ibu').value = '';
            }
            
            // Recalculate after hiding/showing fields
            calculateTotalIncome();
        });

        // Trigger the change event on page load to set initial state
        document.getElementById('status_penjaga').dispatchEvent(new Event('change'));

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const icPelajar = document.getElementById('ic_pelajar').value;
            const icWaris = document.getElementById('ic_waris').value;
            const poskod = document.getElementById('poskod').value;

            // Validate IC format
            if (icPelajar && !/^\d{12}$/.test(icPelajar)) {
                alert('Format No. IC pelajar tidak sah. Sila masukkan 12 digit.');
                e.preventDefault();
                return false;
            }

            if (icWaris && !/^\d{12}$/.test(icWaris)) {
                alert('Format No. IC waris tidak sah. Sila masukkan 12 digit.');
                e.preventDefault();
                return false;
            }

            // Validate poskod format
            if (poskod && !/^\d{5}$/.test(poskod)) {
                alert('Format poskod tidak sah. Sila masukkan 5 digit.');
                e.preventDefault();
                return false;
            }

            // Check if IC numbers are different
            if (icPelajar === icWaris) {
                alert('No. IC pelajar dan waris tidak boleh sama.');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>