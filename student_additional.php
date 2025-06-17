<?php
// student_additional.php
// Additional Information Form - Academic, Financial Aid, and Co-curricular Activities

require_once 'config/database.php';
// Start session to retrieve form data
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Require login
requireLogin();

// Check if we have student form data from previous step
if (!isset($_SESSION['student_form_data'])) {
    header("Location: students_add.php");
    exit();
}

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$errors = [];
$success_message = '';
$student_data = $_SESSION['student_form_data'];

// Get current year for default
$current_year = date('Y');

// Get dropdown data
try {
    // Get available years for tahun_penggal
    $years = [];
    for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
        $years[] = $i;
    }

    // Get class information for the selected student
    $class_stmt = $db->prepare("SELECT k.nama_kelas, k.darjah_kelas, s.nama_sekolah FROM kelas k JOIN sekolah s ON k.kod_sekolah = s.kod_sekolah WHERE k.id_kelas = ?");
    $class_stmt->execute([$student_data['id_kelas']]);
    $class_info = $class_stmt->fetch();

} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Academic information
    $tahun_penggal = $_POST['tahun_penggal'];
    $kehadiran_penggal_satu = $_POST['kehadiran_penggal_satu'] ?? '';
    $keputusan_penggal_satu = $_POST['keputusan_penggal_satu'] ?? '';
    $kehadiran_penggal_dua = $_POST['kehadiran_penggal_dua'] ?? '';
    $keputusan_penggal_dua = $_POST['keputusan_penggal_dua'] ?? '';
    $upkk = $_POST['upkk'] ?? '';
    $sdea = $_POST['sdea'] ?? '';

    // Financial aid information
    $anak_orang_asli_islam = isset($_POST['anak_orang_asli_islam']) ? 1 : 0;
    $anak_yatim = isset($_POST['anak_yatim']) ? 1 : 0;
    $skim_pinjaman_kitab = isset($_POST['skim_pinjaman_kitab']) ? 1 : 0;
    $skim_pinjaman_spbt = isset($_POST['skim_pinjaman_spbt']) ? 1 : 0;
    $makanan_sihat = isset($_POST['makanan_sihat']) ? 1 : 0;
    $pakaian = isset($_POST['pakaian']) ? 1 : 0;

    // Co-curricular activities
    $persatuan_kelab = trim($_POST['persatuan_kelab'] ?? '');
    $jawatan_persatuan_kelab = trim($_POST['jawatan_persatuan_kelab'] ?? '');

    // Basic validation
    if (empty($tahun_penggal)) {
        $errors[] = "Tahun/Penggal diperlukan.";
    }

    // Validate numeric fields
    if (!empty($kehadiran_penggal_satu) && (!is_numeric($kehadiran_penggal_satu) || $kehadiran_penggal_satu < 0 || $kehadiran_penggal_satu > 100)) {
        $errors[] = "Kehadiran Penggal Satu mesti dalam julat 0-100%.";
    }
    
    if (!empty($keputusan_penggal_satu) && (!is_numeric($keputusan_penggal_satu) || $keputusan_penggal_satu < 0 || $keputusan_penggal_satu > 100)) {
        $errors[] = "Keputusan Penggal Satu mesti dalam julat 0-100%.";
    }
    
    if (!empty($kehadiran_penggal_dua) && (!is_numeric($kehadiran_penggal_dua) || $kehadiran_penggal_dua < 0 || $kehadiran_penggal_dua > 100)) {
        $errors[] = "Kehadiran Penggal Dua mesti dalam julat 0-100%.";
    }
    
    if (!empty($keputusan_penggal_dua) && (!is_numeric($keputusan_penggal_dua) || $keputusan_penggal_dua < 0 || $keputusan_penggal_dua > 100)) {
        $errors[] = "Keputusan Penggal Dua mesti dalam julat 0-100%.";
    }

    // If no errors, save all data to database
    if (empty($errors)) {
        try {
            // Begin transaction
            $db->beginTransaction();

            // Insert into penjaga table first
            $penjaga_stmt = $db->prepare("
                INSERT INTO penjaga (
                    ic_waris, nama_waris, status_waris, nombor_telefon_waris, alamat, 
                    poskod, negeri, bilangan_tanggungan, pekerjaan_bapa, pendapatan_bapa,
                    pekerjaan_ibu, pendapatan_ibu, pekerjaan_penjaga, pendapatan_penjaga,
                    jumlah_pendapatan, pendapatan_perkapita
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $penjaga_stmt->execute([
                $student_data['ic_waris'],
                $student_data['nama_waris'],
                $student_data['status_waris'],
                $student_data['nombor_telefon_waris'],
                $student_data['alamat'],
                $student_data['poskod'],
                $student_data['negeri'],
                $student_data['bilangan_tanggungan'],
                $student_data['pekerjaan_bapa'],
                $student_data['pendapatan_bapa'],
                $student_data['pekerjaan_ibu'],
                $student_data['pendapatan_ibu'],
                $student_data['pekerjaan_penjaga'],
                $student_data['pendapatan_penjaga'],
                $student_data['jumlah_pendapatan'],
                $student_data['pendapatan_perkapita']
            ]);

            // Insert into pelajar table
            $pelajar_stmt = $db->prepare("
                INSERT INTO pelajar (
                    ic_pelajar, nama, jantina, kaum, warganegara, status_pelajar, 
                    status_penjaga, sijil_lahir, id_kelas, ic_waris
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $pelajar_stmt->execute([
                $student_data['ic_pelajar'],
                $student_data['nama'],
                $student_data['jantina'],
                $student_data['kaum'],
                $student_data['warganegara'],
                $student_data['status_pelajar'],
                $student_data['status_penjaga'],
                $student_data['sijil_lahir'],
                $student_data['id_kelas'],
                $student_data['ic_waris']
            ]);

            // Insert into pelajar_tahun table
            $pelajar_tahun_stmt = $db->prepare("
                INSERT INTO pelajar_tahun (ic_pelajar, tahun_penggal) 
                VALUES (?, ?)
            ");
            $pelajar_tahun_stmt->execute([$student_data['ic_pelajar'], $tahun_penggal]);

            // Insert into akademik table if there are academic results
            if (!empty($kehadiran_penggal_satu) || !empty($keputusan_penggal_satu) || 
                !empty($kehadiran_penggal_dua) || !empty($keputusan_penggal_dua) || 
                !empty($upkk) || !empty($sdea)) {
                
                $akademik_stmt = $db->prepare("
                    INSERT INTO akademik (
                        ic_pelajar, tahun_penggal, kehadiran_penggal_satu, keputusan_penggal_satu, 
                        kehadiran_penggal_dua, keputusan_penggal_dua, upkk, sdea
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $akademik_stmt->execute([
                    $student_data['ic_pelajar'],
                    $tahun_penggal,
                    $kehadiran_penggal_satu ?: null,
                    $keputusan_penggal_satu ?: null,
                    $kehadiran_penggal_dua ?: null,
                    $keputusan_penggal_dua ?: null,
                    $upkk ?: null,
                    $sdea ?: null
                ]);
            }

            // Insert into bantuan table if any financial aid is selected
            if ($anak_orang_asli_islam || $anak_yatim || $skim_pinjaman_kitab || 
                $skim_pinjaman_spbt || $makanan_sihat || $pakaian) {
                
                $bantuan_stmt = $db->prepare("
                    INSERT INTO bantuan (
                        ic_pelajar, tahun_penggal, anak_orang_asli_islam, anak_yatim, 
                        skim_pinjaman_kitab, skim_pinjaman_spbt, makanan_sihat, pakaian
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $bantuan_stmt->execute([
                    $student_data['ic_pelajar'],
                    $tahun_penggal,
                    $anak_orang_asli_islam,
                    $anak_yatim,
                    $skim_pinjaman_kitab,
                    $skim_pinjaman_spbt,
                    $makanan_sihat,
                    $pakaian
                ]);
            }

            // Insert into kokurikulum table if there are co-curricular activities
            if (!empty($persatuan_kelab)) {
                $kokurikulum_stmt = $db->prepare("
                    INSERT INTO kokurikulum (
                        ic_pelajar, tahun_penggal, persatuan_kelab, jawatan_persatuan_kelab
                    ) VALUES (?, ?, ?, ?)
                ");
                
                $kokurikulum_stmt->execute([
                    $student_data['ic_pelajar'],
                    $tahun_penggal,
                    $persatuan_kelab,
                    $jawatan_persatuan_kelab ?: null
                ]);
            }

            // Commit transaction
            $db->commit();

            // Clear session data
            unset($_SESSION['student_form_data']);

            // Set success message and redirect
            $_SESSION['success_message'] = "Pelajar berjaya ditambah ke dalam sistem.";
            header("Location: students.php");
            exit();

        } catch(PDOException $e) {
            // Rollback transaction on error
            $db->rollback();
            $errors[] = "Ralat menyimpan data: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maklumat Tambahan Pelajar - Sistem Maklumat Pelajar</title>
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

        .progress-step.completed {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }

        .progress-step.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }

        .step-label {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Student summary */
        .student-summary {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .student-summary h3 {
            color: #1565c0;
            margin-bottom: 15px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
        }

        .summary-item strong {
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-item span {
            color: #555;
            font-size: 14px;
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

        /* Checkbox group */
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .checkbox-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .checkbox-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
        }

        .checkbox-option input[type="checkbox"]:checked ~ label {
            color: #667eea;
            font-weight: 600;
        }

        .checkbox-option label {
            cursor: pointer;
            margin: 0;
            flex: 1;
        }

        /* Form Actions */
        .form-actions {
            padding: 30px;
            background: #f8f9fa;
            display: flex;
            gap: 15px;
            justify-content: space-between;
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

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            font-size: 16px;
            padding: 15px 30px;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
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

        /* Helper text */
        .helper-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Info box */
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
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

            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .progress-steps {
                flex-direction: column;
                gap: 20px;
            }

            .progress-steps::before {
                display: none;
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
            <h1 class="page-title">Maklumat Tambahan Pelajar</h1>
            <a href="students_add.php" class="back-btn">← Kembali</a>
        </div>

        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="progress-steps">
                <div class="progress-step completed">1</div>
                <div class="progress-step active">2</div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                <div class="step-label">Maklumat Peribadi</div>
                <div class="step-label">Maklumat Tambahan</div>
            </div>
        </div>

        <!-- Student Summary -->
        <div class="student-summary">
            <h3>Ringkasan Maklumat Pelajar</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <strong>Nama Pelajar</strong>
                    <span><?php echo htmlspecialchars($student_data['nama']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>No. IC</strong>
                    <span><?php echo htmlspecialchars($student_data['ic_pelajar']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>Kelas</strong>
                    <span><?php echo htmlspecialchars('D'.$class_info['darjah_kelas'].' - '.$class_info['nama_kelas'] ?? 'Tidak diketahui'); ?></span>
                </div>
                <div class="summary-item">
                    <strong>Nama Waris</strong>
                    <span><?php echo htmlspecialchars($student_data['nama_waris']); ?></span>
                </div>
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
            <!-- Academic Information -->
            <div class="form-section">
                <h2 class="section-title">1. Maklumat Akademik</h2>

                <div class="info-box">
                    <strong>Nota:</strong> Maklumat akademik adalah pilihan. Anda boleh mengisi kemudian melalui fungsi kemaskini.
                </div>

                <div class="form-grid" style="margin-bottom: 10px;">
                    <div class="form-group">
                        <label for="tahun_penggal">Tahun/Penggal <span class="required">*</span></label>
                        <select id="tahun_penggal" name="tahun_penggal" required>
                            <option value="">Pilih Tahun/Penggal</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"
                                        <?php echo (($_POST['tahun_penggal'] ?? $current_year) == $year) ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="kehadiran_penggal_satu">Kehadiran Penggal Satu (%)</label>
                        <input type="number" id="kehadiran_penggal_satu" name="kehadiran_penggal_satu" 
                               min="0" max="100" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['kehadiran_penggal_satu'] ?? ''); ?>"
                               placeholder="Contoh: 95.5">
                        <div class="helper-text">Masukkan peratus kehadiran (0-100)</div>
                    </div>

                    <div class="form-group">
                        <label for="keputusan_penggal_satu">Keputusan Penggal Satu (%)</label>
                        <input type="number" id="keputusan_penggal_satu" name="keputusan_penggal_satu" 
                               min="0" max="100" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['keputusan_penggal_satu'] ?? ''); ?>"
                               placeholder="Contoh: 85.0">
                        <div class="helper-text">Masukkan jumlah peratus keputusan (0-100)</div>
                    </div>

                    <div class="form-group">
                        <label for="kehadiran_penggal_dua">Kehadiran Penggal Dua (%)</label>
                        <input type="number" id="kehadiran_penggal_dua" name="kehadiran_penggal_dua" 
                               min="0" max="100" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['kehadiran_penggal_dua'] ?? ''); ?>"
                               placeholder="Contoh: 92.0">
                        <div class="helper-text">Masukkan peratus kehadiran (0-100)</div>
                    </div>

                    <div class="form-group">
                        <label for="keputusan_penggal_dua">Keputusan Penggal Dua (%)</label>
                        <input type="number" id="keputusan_penggal_dua" name="keputusan_penggal_dua" 
                               min="0" max="100" step="0.01" 
                               value="<?php echo htmlspecialchars($_POST['keputusan_penggal_dua'] ?? ''); ?>"
                               placeholder="Contoh: 88.5">
                        <div class="helper-text">Masukkan jumlah peratus keputusan (0-100)</div>
                    </div>

                    <div class="form-group">
                        <label for="upkk">Ujian Penilaian Kelas KAFA (UPKK)</label>
                        <input type="text" id="upkk" name="upkk" 
                               value="<?php echo htmlspecialchars($_POST['upkk'] ?? ''); ?>"
                               placeholder="Masukkan gred UPKK">
                        <div class="helper-text">Pelajar darjah 5 sahaja</div>
                    </div>

                    <div class="form-group">
                        <label for="sdea">Sijil Darjah Enam Agama (SDEA)</label>
                        <select id="sdea" name="sdea">
                            <option value="">Pilih gred SDEA</option>
                            <option value="Mumtaz" <?php echo (($_POST['sdea'] ?? '') === 'Mumtaz') ? 'selected' : ''; ?>>Mumtaz</option>
                            <option value="Jayyid Jiddan" <?php echo (($_POST['sdea'] ?? '') === 'Jayyid Jiddan') ? 'selected' : ''; ?>>Jayyid Jiddan</option>
                            <option value="Jayyid" <?php echo (($_POST['sdea'] ?? '') === 'Jayyid') ? 'selected' : ''; ?>>Jayyid</option>
                            <option value="Makbul" <?php echo (($_POST['sdea'] ?? '') === 'Makbul') ? 'selected' : ''; ?>>Makbul</option>
                            <option value="Rashid" <?php echo (($_POST['sdea'] ?? '') === 'Rashid') ? 'selected' : ''; ?>>Rashid</option>
                        </select>
                        <div class="helper-text">Pelajar darjah 6 sahaja</div>
                    </div>
                </div>
            </div>

            <!-- Financial Aid Information -->
            <div class="form-section">
                <h2 class="section-title">2. Maklumat Bantuan Kewangan</h2>
                
                <div class="info-box">
                    <strong>Nota:</strong> Tandakan bantuan yang diterima oleh pelajar. Anda boleh memilih lebih daripada satu bantuan.
                </div>

                <div class="checkbox-group">
                    <div class="checkbox-option">
                        <input type="checkbox" id="anak_orang_asli_islam" name="anak_orang_asli_islam" value="1"
                               <?php echo (isset($_POST['anak_orang_asli_islam']) && $_POST['anak_orang_asli_islam']) ? 'checked' : ''; ?>>
                        <label for="anak_orang_asli_islam">Anak Orang Asli/Islam</label>
                    </div>

                    <div class="checkbox-option">
                        <input type="checkbox" id="anak_yatim" name="anak_yatim" value="1"
                               <?php echo (isset($_POST['anak_yatim']) && $_POST['anak_yatim']) ? 'checked' : ''; ?>>
                        <label for="anak_yatim">Anak Yatim</label>
                    </div>

                    <div class="checkbox-option">
                        <input type="checkbox" id="skim_pinjaman_kitab" name="skim_pinjaman_kitab" value="1"
                               <?php echo (isset($_POST['skim_pinjaman_kitab']) && $_POST['skim_pinjaman_kitab']) ? 'checked' : ''; ?>>
                        <label for="skim_pinjaman_kitab">Skim Pinjaman Kitab</label>
                    </div>

                    <div class="checkbox-option">
                        <input type="checkbox" id="skim_pinjaman_spbt" name="skim_pinjaman_spbt" value="1"
                               <?php echo (isset($_POST['skim_pinjaman_spbt']) && $_POST['skim_pinjaman_spbt']) ? 'checked' : ''; ?>>
                        <label for="skim_pinjaman_spbt">Skim Pinjaman SPBT</label>
                    </div>

                    <div class="checkbox-option">
                        <input type="checkbox" id="makanan_sihat" name="makanan_sihat" value="1"
                               <?php echo (isset($_POST['makanan_sihat']) && $_POST['makanan_sihat']) ? 'checked' : ''; ?>>
                        <label for="makanan_sihat">Program Makanan Sihat</label>
                    </div>

                    <div class="checkbox-option">
                        <input type="checkbox" id="pakaian" name="pakaian" value="1"
                               <?php echo (isset($_POST['pakaian']) && $_POST['pakaian']) ? 'checked' : ''; ?>>
                        <label for="pakaian">Bantuan Pakaian</label>
                    </div>
                </div>
            </div>

            <!-- Co-curricular Activities -->
            <div class="form-section">
                <h2 class="section-title">3. Aktiviti Kokurikulum</h2>
                
                <div class="info-box">
                    <strong>Nota:</strong> Maklumat aktiviti kokurikulum adalah pilihan. Anda boleh mengisi kemudian melalui fungsi kemaskini.
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="persatuan_kelab">Persatuan/Kelab</label>
                        <select id="persatuan_kelab" name="persatuan_kelab">
                            <option value="">Pilih persatuan/kelab</option>
                            <option value="Kelab Nadi Ansar" <?php echo (($_POST['sdea'] ?? '') === 'Kelab Nadi Ansar') ? 'selected' : ''; ?>>Kelab Nadi Ansar</option>
                        </select>
                        <div class="helper-text">Pelajar tahap 2 sahaja (darjah 4 sehingga 6)</div>
                    </div>

                    <div class="form-group">
                        <label for="jawatan_persatuan_kelab">Jawatan dalam Persatuan/Kelab</label>
                        <input type="text" id="jawatan_persatuan_kelab" name="jawatan_persatuan_kelab" 
                               value="<?php echo htmlspecialchars($_POST['jawatan_persatuan_kelab'] ?? ''); ?>"
                               placeholder="Contoh: Pengerusi, Setiausaha, Ahli">
                        <div class="helper-text">Jawatan yang disandang (jika ada)</div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="students_add.php" class="btn btn-secondary">← Kembali ke Langkah 1</a>
                <button type="submit" class="btn btn-success">Simpan Pelajar</button>
            </div>
        </form>
    </main>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('.btn-success');
            
            // Add loading state on form submit
            form.addEventListener('submit', function() {
                submitBtn.textContent = 'Menyimpan...';
                submitBtn.disabled = true;
            });

            // Validate percentage inputs
            const percentageInputs = document.querySelectorAll('input[type="number"]');
            percentageInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    if (value < 0) this.value = 0;
                    if (value > 100) this.value = 100;
                });
            });

            // Auto-enable jawatan field when persatuan field is filled
            const persatuanInput = document.getElementById('persatuan_kelab');
            const jawatanInput = document.getElementById('jawatan_persatuan_kelab');
            
            persatuanInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    jawatanInput.disabled = false;
                    jawatanInput.style.opacity = '1';
                } else {
                    jawatanInput.disabled = true;
                    jawatanInput.style.opacity = '0.6';
                    jawatanInput.value = '';
                }
            });

            // Initial state for jawatan field
            if (!persatuanInput.value.trim()) {
                jawatanInput.disabled = true;
                jawatanInput.style.opacity = '0.6';
            }

            // Checkbox interaction feedback
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const option = this.closest('.checkbox-option');
                    if (this.checked) {
                        option.style.background = '#e3f2fd';
                        option.style.borderColor = '#2196f3';
                    } else {
                        option.style.background = '';
                        option.style.borderColor = '#e9ecef';
                    }
                });

                // Set initial state
                if (checkbox.checked) {
                    const option = checkbox.closest('.checkbox-option');
                    option.style.background = '#e3f2fd';
                    option.style.borderColor = '#2196f3';
                }
            });
        });
    </script>
</body>
</html>