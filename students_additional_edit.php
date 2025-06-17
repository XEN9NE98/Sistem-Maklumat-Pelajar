<?php
// students_additional_edit.php
// Edit Additional Information - Academic, Financial Aid, and Co-curricular Activities

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

// Get student IC from URL parameter
$student_ic = $_GET['ic'] ?? '';
if (empty($student_ic)) {
    header("Location: students_edit.php");
    exit();
}

// Initialize variables
$errors = [];
$success_message = '';
$student_info = null;
$akademik_records = [];
$bantuan_records = [];
$kokurikulum_records = [];

// Get current year for default
$current_year = date('Y');

// Get available years for dropdown
$years = [];
for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
    $years[] = $i;
}

// Helper function to ensure pelajar_tahun record exists
function ensurePelajarTahunExists($db, $ic_pelajar, $tahun_penggal) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM pelajar_tahun WHERE ic_pelajar = ? AND tahun_penggal = ?");
        $stmt->execute([$ic_pelajar, $tahun_penggal]);
        
        if ($stmt->fetchColumn() == 0) {
            // Record doesn't exist, create it
            $stmt = $db->prepare("INSERT INTO pelajar_tahun (ic_pelajar, tahun_penggal) VALUES (?, ?)");
            $stmt->execute([$ic_pelajar, $tahun_penggal]);
        }
        return true;
    } catch(Exception $e) {
        error_log("Error in ensurePelajarTahunExists: " . $e->getMessage());
        throw $e;
    }
}

// Handle AJAX requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // Clear any output buffer to prevent HTML output before JSON
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $table = $_POST['table'] ?? '';
    $tahun_penggal = $_POST['tahun_penggal'] ?? '';
    
    try {
        // Validate required fields
        if (empty($tahun_penggal)) {
            throw new Exception('Tahun penggal diperlukan');
        }
        
        switch ($action) {
            case 'add_akademik':
                // Check if record already exists
                $check_stmt = $db->prepare("SELECT COUNT(*) FROM akademik WHERE ic_pelajar = ? AND tahun_penggal = ?");
                $check_stmt->execute([$student_ic, $tahun_penggal]);
                if ($check_stmt->fetchColumn() > 0) {
                    throw new Exception('Rekod akademik untuk tahun ini sudah wujud');
                }
                
                // Ensure pelajar_tahun record exists first
                ensurePelajarTahunExists($db, $student_ic, $tahun_penggal);
                
                $stmt = $db->prepare("
                    INSERT INTO akademik (ic_pelajar, tahun_penggal, kehadiran_penggal_satu, keputusan_penggal_satu, 
                                        kehadiran_penggal_dua, keputusan_penggal_dua, upkk, sdea) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $student_ic,
                    $tahun_penggal,
                    !empty($_POST['kehadiran_penggal_satu']) ? $_POST['kehadiran_penggal_satu'] : null,
                    !empty($_POST['keputusan_penggal_satu']) ? $_POST['keputusan_penggal_satu'] : null,
                    !empty($_POST['kehadiran_penggal_dua']) ? $_POST['kehadiran_penggal_dua'] : null,
                    !empty($_POST['keputusan_penggal_dua']) ? $_POST['keputusan_penggal_dua'] : null,
                    !empty($_POST['upkk']) ? $_POST['upkk'] : null,
                    !empty($_POST['sdea']) ? $_POST['sdea'] : null
                ]);
                echo json_encode(['success' => true, 'message' => 'Rekod akademik berjaya ditambah']);
                break;

            case 'edit_akademik':
                $stmt = $db->prepare("
                    UPDATE akademik SET kehadiran_penggal_satu=?, keputusan_penggal_satu=?, 
                           kehadiran_penggal_dua=?, keputusan_penggal_dua=?, upkk=?, sdea=?
                    WHERE ic_pelajar=? AND tahun_penggal=?
                ");
                $result = $stmt->execute([
                    !empty($_POST['kehadiran_penggal_satu']) ? $_POST['kehadiran_penggal_satu'] : null,
                    !empty($_POST['keputusan_penggal_satu']) ? $_POST['keputusan_penggal_satu'] : null,
                    !empty($_POST['kehadiran_penggal_dua']) ? $_POST['kehadiran_penggal_dua'] : null,
                    !empty($_POST['keputusan_penggal_dua']) ? $_POST['keputusan_penggal_dua'] : null,
                    !empty($_POST['upkk']) ? $_POST['upkk'] : null,
                    !empty($_POST['sdea']) ? $_POST['sdea'] : null,
                    $student_ic,
                    $tahun_penggal
                ]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Tiada rekod yang dikemaskini. Sila pastikan rekod wujud.');
                }
                echo json_encode(['success' => true, 'message' => 'Rekod akademik berjaya dikemaskini']);
                break;

            case 'delete_akademik':
                $stmt = $db->prepare("DELETE FROM akademik WHERE ic_pelajar=? AND tahun_penggal=?");
                $stmt->execute([$student_ic, $tahun_penggal]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Tiada rekod yang dipadam. Sila pastikan rekod wujud.');
                }
                echo json_encode(['success' => true, 'message' => 'Rekod akademik berjaya dipadam']);
                break;

            case 'add_bantuan':
                // Check if record already exists
                $check_stmt = $db->prepare("SELECT COUNT(*) FROM bantuan WHERE ic_pelajar = ? AND tahun_penggal = ?");
                $check_stmt->execute([$student_ic, $tahun_penggal]);
                if ($check_stmt->fetchColumn() > 0) {
                    throw new Exception('Rekod bantuan untuk tahun ini sudah wujud');
                }
                
                // Ensure pelajar_tahun record exists first
                ensurePelajarTahunExists($db, $student_ic, $tahun_penggal);
                
                $stmt = $db->prepare("
                    INSERT INTO bantuan (ic_pelajar, tahun_penggal, anak_orang_asli_islam, anak_yatim, 
                                       skim_pinjaman_kitab, skim_pinjaman_spbt, makanan_sihat, pakaian) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $student_ic,
                    $tahun_penggal,
                    isset($_POST['anak_orang_asli_islam']) ? 1 : 0,
                    isset($_POST['anak_yatim']) ? 1 : 0,
                    isset($_POST['skim_pinjaman_kitab']) ? 1 : 0,
                    isset($_POST['skim_pinjaman_spbt']) ? 1 : 0,
                    isset($_POST['makanan_sihat']) ? 1 : 0,
                    isset($_POST['pakaian']) ? 1 : 0
                ]);
                echo json_encode(['success' => true, 'message' => 'Rekod bantuan berjaya ditambah']);
                break;

            case 'edit_bantuan':
                $stmt = $db->prepare("
                    UPDATE bantuan SET anak_orang_asli_islam=?, anak_yatim=?, skim_pinjaman_kitab=?, 
                           skim_pinjaman_spbt=?, makanan_sihat=?, pakaian=?
                    WHERE ic_pelajar=? AND tahun_penggal=?
                ");
                $result = $stmt->execute([
                    isset($_POST['anak_orang_asli_islam']) ? 1 : 0,
                    isset($_POST['anak_yatim']) ? 1 : 0,
                    isset($_POST['skim_pinjaman_kitab']) ? 1 : 0,
                    isset($_POST['skim_pinjaman_spbt']) ? 1 : 0,
                    isset($_POST['makanan_sihat']) ? 1 : 0,
                    isset($_POST['pakaian']) ? 1 : 0,
                    $student_ic,
                    $tahun_penggal
                ]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Tiada rekod yang dikemaskini. Sila pastikan rekod wujud.');
                }
                echo json_encode(['success' => true, 'message' => 'Rekod bantuan berjaya dikemaskini']);
                break;

            case 'delete_bantuan':
                $stmt = $db->prepare("DELETE FROM bantuan WHERE ic_pelajar=? AND tahun_penggal=?");
                $stmt->execute([$student_ic, $tahun_penggal]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Tiada rekod yang dipadam. Sila pastikan rekod wujud.');
                }
                echo json_encode(['success' => true, 'message' => 'Rekod bantuan berjaya dipadam']);
                break;

            case 'add_kokurikulum':
                // Validate required field
                if (empty($_POST['persatuan_kelab'])) {
                    throw new Exception('Persatuan/Kelab diperlukan');
                }
                
                // Check if record already exists
                $check_stmt = $db->prepare("SELECT COUNT(*) FROM kokurikulum WHERE ic_pelajar = ? AND tahun_penggal = ?");
                $check_stmt->execute([$student_ic, $tahun_penggal]);
                if ($check_stmt->fetchColumn() > 0) {
                    throw new Exception('Rekod kokurikulum untuk tahun ini sudah wujud');
                }
                
                // Ensure pelajar_tahun record exists first
                ensurePelajarTahunExists($db, $student_ic, $tahun_penggal);
                
                $stmt = $db->prepare("
                    INSERT INTO kokurikulum (ic_pelajar, tahun_penggal, persatuan_kelab, jawatan_persatuan_kelab) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $student_ic,
                    $tahun_penggal,
                    $_POST['persatuan_kelab'],
                    !empty($_POST['jawatan_persatuan_kelab']) ? $_POST['jawatan_persatuan_kelab'] : null
                ]);
                echo json_encode(['success' => true, 'message' => 'Rekod kokurikulum berjaya ditambah']);
                break;

            case 'edit_kokurikulum':
                // Validate required field
                if (empty($_POST['persatuan_kelab'])) {
                    throw new Exception('Persatuan/Kelab diperlukan');
                }
                
                $stmt = $db->prepare("
                    UPDATE kokurikulum SET persatuan_kelab=?, jawatan_persatuan_kelab=?
                    WHERE ic_pelajar=? AND tahun_penggal=?
                ");
                $result = $stmt->execute([
                    $_POST['persatuan_kelab'],
                    !empty($_POST['jawatan_persatuan_kelab']) ? $_POST['jawatan_persatuan_kelab'] : null,
                    $student_ic,
                    $tahun_penggal
                ]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Tiada rekod yang dikemaskini. Sila pastikan rekod wujud.');
                }
                echo json_encode(['success' => true, 'message' => 'Rekod kokurikulum berjaya dikemaskini']);
                break;

            case 'delete_kokurikulum':
                $stmt = $db->prepare("DELETE FROM kokurikulum WHERE ic_pelajar=? AND tahun_penggal=?");
                $stmt->execute([$student_ic, $tahun_penggal]);
                if ($stmt->rowCount() == 0) {
                    throw new Exception('Tiada rekod yang dipadam. Sila pastikan rekod wujud.');
                }
                echo json_encode(['success' => true, 'message' => 'Rekod kokurikulum berjaya dipadam']);
                break;

            default:
                throw new Exception('Tindakan tidak sah');
        }
    } catch(Exception $e) {
        error_log("AJAX Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Only fetch data if not handling AJAX request
try {
    // Get student information
    $student_stmt = $db->prepare("
        SELECT p.*, pe.nama_waris, k.nama_kelas, k.darjah_kelas, s.nama_sekolah 
        FROM pelajar p
        JOIN penjaga pe ON p.ic_waris = pe.ic_waris
        JOIN kelas k ON p.id_kelas = k.id_kelas
        JOIN sekolah s ON k.kod_sekolah = s.kod_sekolah
        WHERE p.ic_pelajar = ?
    ");
    $student_stmt->execute([$student_ic]);
    $student_info = $student_stmt->fetch();

    if (!$student_info) {
        throw new Exception("Pelajar tidak dijumpai.");
    }

    // Get akademik records
    $akademik_stmt = $db->prepare("SELECT * FROM akademik WHERE ic_pelajar = ? ORDER BY tahun_penggal DESC");
    $akademik_stmt->execute([$student_ic]);
    $akademik_records = $akademik_stmt->fetchAll();

    // Get bantuan records
    $bantuan_stmt = $db->prepare("SELECT * FROM bantuan WHERE ic_pelajar = ? ORDER BY tahun_penggal DESC");
    $bantuan_stmt->execute([$student_ic]);
    $bantuan_records = $bantuan_stmt->fetchAll();

    // Get kokurikulum records
    $kokurikulum_stmt = $db->prepare("SELECT * FROM kokurikulum WHERE ic_pelajar = ? ORDER BY tahun_penggal DESC");
    $kokurikulum_stmt->execute([$student_ic]);
    $kokurikulum_records = $kokurikulum_stmt->fetchAll();

} catch(Exception $e) {
    $errors[] = "Ralat mengambil data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kemaskini Maklumat Tambahan - Sistem Maklumat Pelajar</title>
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
            max-width: 1200px;
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

        /* Student summary */
        .student-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .student-summary h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: white;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            color: white;
        }

        .summary-item strong {
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
            opacity: 0.8;
            color: white;
        }

        .summary-item span {
            font-size: 14px;
            opacity: 0.9;
            color: white;
        }

        /* Table sections */
        .table-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
        }

        .add-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .data-table tr:hover {
            background: #f8f9ff;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-radius: 0 0 10px 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Messages */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Badge for years */
        .year-badge {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
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

            .nav-menu {
                flex-wrap: wrap;
                gap: 10px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .action-btns {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 2% auto;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
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
            <h1 class="page-title">Kemaskini Maklumat Tambahan</h1>
            <a href="students_edit.php?ic=<?php echo urlencode($student_ic); ?>" class="back-btn">← Kembali</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Ralat:</strong>
                <ul style="margin: 5px 0 0 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($student_info): ?>
        <!-- Student Summary -->
        <div class="student-summary">
            <h3>Maklumat Pelajar</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <strong>Nama Pelajar</strong>
                    <span><?php echo htmlspecialchars($student_info['nama']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>No. IC</strong>
                    <span><?php echo htmlspecialchars($student_info['ic_pelajar']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>Kelas</strong>
                    <span><?php echo htmlspecialchars('D'. $student_info['darjah_kelas'] .' - '.$student_info['nama_kelas']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>Nama Waris</strong>
                    <span><?php echo htmlspecialchars($student_info['nama_waris']); ?></span>
                </div>
            </div>
        </div>

        <!-- Academic Records Section -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">Rekod Akademik</h2>
                <button class="add-btn" onclick="openModal('akademik', 'add')">+ Tambah Rekod</button>
            </div>
            <div class="table-container">
                <?php if (empty($akademik_records)): ?>
                    <div class="empty-state">
                        <h3>Tiada Rekod Akademik</h3>
                        <p>Klik butang "Tambah Rekod" untuk menambah maklumat akademik pelajar.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tahun</th>
                                <th>Kehadiran P1 (%)</th>
                                <th>Keputusan P1 (%)</th>
                                <th>Kehadiran P2 (%)</th>
                                <th>Keputusan P2 (%)</th>
                                <th>UPKK</th>
                                <th>SDEA</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($akademik_records as $record): ?>
                                <tr>
                                    <td><span class="year-badge"><?php echo htmlspecialchars($record['tahun_penggal']); ?></span></td>
                                    <td><?php echo $record['kehadiran_penggal_satu'] ? number_format($record['kehadiran_penggal_satu'], 1) : '-'; ?></td>
                                    <td><?php echo $record['keputusan_penggal_satu'] ? number_format($record['keputusan_penggal_satu'], 1) : '-'; ?></td>
                                    <td><?php echo $record['kehadiran_penggal_dua'] ? number_format($record['kehadiran_penggal_dua'], 1) : '-'; ?></td>
                                    <td><?php echo $record['keputusan_penggal_dua'] ? number_format($record['keputusan_penggal_dua'], 1) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($record['upkk'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($record['sdea'] ?: '-'); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-sm btn-edit" onclick="openModal('akademik', 'edit', <?php echo htmlspecialchars(json_encode($record)); ?>)">Edit</button>
                                            <button class="btn-sm btn-delete" onclick="deleteRecord('akademik', '<?php echo htmlspecialchars($record['tahun_penggal']); ?>')">Padam</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Financial Aid Records Section -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">Rekod Bantuan Kewangan</h2>
                <button class="add-btn" onclick="openModal('bantuan', 'add')">+ Tambah Rekod</button>
            </div>
            <div class="table-container">
                <?php if (empty($bantuan_records)): ?>
                    <div class="empty-state">
                        <h3>Tiada Rekod Bantuan</h3>
                        <p>Klik butang "Tambah Rekod" untuk menambah maklumat bantuan kewangan pelajar.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tahun</th>
                                <th>Anak Orang Asli Islam</th>
                                <th>Anak Yatim</th>
                                <th>Skim Pinjaman Kitab</th>
                                <th>Skim Pinjaman SPBT</th>
                                <th>Makanan Sihat</th>
                                <th>Pakaian</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bantuan_records as $record): ?>
                                <tr>
                                    <td><span class="year-badge"><?php echo htmlspecialchars($record['tahun_penggal']); ?></span></td>
                                    <td>
                                        <span class="status-badge <?php echo $record['anak_orang_asli_islam'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $record['anak_orang_asli_islam'] ? 'Ya' : 'Tidak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $record['anak_yatim'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $record['anak_yatim'] ? 'Ya' : 'Tidak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $record['skim_pinjaman_kitab'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $record['skim_pinjaman_kitab'] ? 'Ya' : 'Tidak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $record['skim_pinjaman_spbt'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $record['skim_pinjaman_spbt'] ? 'Ya' : 'Tidak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $record['makanan_sihat'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $record['makanan_sihat'] ? 'Ya' : 'Tidak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $record['pakaian'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $record['pakaian'] ? 'Ya' : 'Tidak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-sm btn-edit" onclick="openModal('bantuan', 'edit', <?php echo htmlspecialchars(json_encode($record)); ?>)">Edit</button>
                                            <button class="btn-sm btn-delete" onclick="deleteRecord('bantuan', '<?php echo htmlspecialchars($record['tahun_penggal']); ?>')">Padam</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Co-curricular Records Section -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">Rekod Kokurikulum</h2>
                <button class="add-btn" onclick="openModal('kokurikulum', 'add')">+ Tambah Rekod</button>
            </div>
            <div class="table-container">
                <?php if (empty($kokurikulum_records)): ?>
                    <div class="empty-state">
                        <h3>Tiada Rekod Kokurikulum</h3>
                        <p>Klik butang "Tambah Rekod" untuk menambah maklumat kokurikulum pelajar.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tahun</th>
                                <th>Persatuan/Kelab</th>
                                <th>Jawatan</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kokurikulum_records as $record): ?>
                                <tr>
                                    <td><span class="year-badge"><?php echo htmlspecialchars($record['tahun_penggal']); ?></span></td>
                                    <td><?php echo htmlspecialchars($record['persatuan_kelab']); ?></td>
                                    <td><?php echo htmlspecialchars($record['jawatan_persatuan_kelab'] ?: '-'); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-sm btn-edit" onclick="openModal('kokurikulum', 'edit', <?php echo htmlspecialchars(json_encode($record)); ?>)">Edit</button>
                                            <button class="btn-sm btn-delete" onclick="deleteRecord('kokurikulum', '<?php echo htmlspecialchars($record['tahun_penggal']); ?>')">Padam</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </main>

    <!-- Academic Modal -->
    <div id="akademikModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="akademikModalTitle">Tambah Rekod Akademik</h2>
                <span class="close" onclick="closeModal('akademikModal')">&times;</span>
            </div>
            <form id="akademikForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="akademik_tahun_penggal">Tahun Penggal *</label>
                        <select id="akademik_tahun_penggal" name="tahun_penggal" required>
                            <option value="">Pilih Tahun</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="kehadiran_penggal_satu">Kehadiran Penggal 1 (%)</label>
                        <input type="number" id="kehadiran_penggal_satu" name="kehadiran_penggal_satu" 
                               min="0" max="100" step="0.1" placeholder="Contoh: 95.5">
                    </div>
                    <div class="form-group">
                        <label for="keputusan_penggal_satu">Keputusan Penggal 1 (%)</label>
                        <input type="number" id="keputusan_penggal_satu" name="keputusan_penggal_satu" 
                               min="0" max="100" step="0.1" placeholder="Contoh: 87.5">
                    </div>
                    <div class="form-group">
                        <label for="kehadiran_penggal_dua">Kehadiran Penggal 2 (%)</label>
                        <input type="number" id="kehadiran_penggal_dua" name="kehadiran_penggal_dua" 
                               min="0" max="100" step="0.1" placeholder="Contoh: 92.0">
                    </div>
                    <div class="form-group">
                        <label for="keputusan_penggal_dua">Keputusan Penggal 2 (%)</label>
                        <input type="number" id="keputusan_penggal_dua" name="keputusan_penggal_dua" 
                               min="0" max="100" step="0.1" placeholder="Contoh: 85.0">
                    </div>
                    <div class="form-group">
                        <label for="upkk">UPKK</label>
                        <input type="text" id="upkk" name="upkk" placeholder="Contoh: A">
                    </div>
                    <div class="form-group">
                        <label for="sdea">SDEA</label>
                        <input type="text" id="sdea" name="sdea" placeholder="Contoh: B">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('akademikModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Aid Modal -->
    <div id="bantuanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="bantuanModalTitle">Tambah Rekod Bantuan</h2>
                <span class="close" onclick="closeModal('bantuanModal')">&times;</span>
            </div>
            <form id="bantuanForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bantuan_tahun_penggal">Tahun Penggal *</label>
                        <select id="bantuan_tahun_penggal" name="tahun_penggal" required>
                            <option value="">Pilih Tahun</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jenis Bantuan</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="anak_orang_asli_islam" name="anak_orang_asli_islam" value="1">
                                <label for="anak_orang_asli_islam">Anak Orang Asli/Islam</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="anak_yatim" name="anak_yatim" value="1">
                                <label for="anak_yatim">Anak Yatim</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="skim_pinjaman_kitab" name="skim_pinjaman_kitab" value="1">
                                <label for="skim_pinjaman_kitab">Skim Pinjaman Kitab</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="skim_pinjaman_spbt" name="skim_pinjaman_spbt" value="1">
                                <label for="skim_pinjaman_spbt">Skim Pinjaman SPBT</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="makanan_sihat" name="makanan_sihat" value="1">
                                <label for="makanan_sihat">Makanan Sihat</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="pakaian" name="pakaian" value="1">
                                <label for="pakaian">Pakaian</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('bantuanModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Co-curricular Modal -->
    <div id="kokurikulumModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="kokurikulumModalTitle">Tambah Rekod Kokurikulum</h2>
                <span class="close" onclick="closeModal('kokurikulumModal')">&times;</span>
            </div>
            <form id="kokurikulumForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="kokurikulum_tahun_penggal">Tahun Penggal *</label>
                        <select id="kokurikulum_tahun_penggal" name="tahun_penggal" required>
                            <option value="">Pilih Tahun</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="persatuan_kelab">Persatuan/Kelab</label>
                        <select id="persatuan_kelab" name="persatuan_kelab">
                            <option value="">Pilih persatuan/kelab</option>
                            <option value="Kelab Nadi Ansar" <?php echo (($_POST['sdea'] ?? '') === 'Kelab Nadi Ansar') ? 'selected' : ''; ?>>Kelab Nadi Ansar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jawatan_persatuan_kelab">Jawatan</label>
                        <input type="text" id="jawatan_persatuan_kelab" name="jawatan_persatuan_kelab" 
                               placeholder="Contoh: Pengerusi, Setiausaha, Ahli">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('kokurikulumModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentEditMode = 'add';
        let currentTable = '';
        let currentRecord = null;

        // Modal functions
        function openModal(table, mode, record = null) {
            currentTable = table;
            currentEditMode = mode;
            currentRecord = record;

            const modal = document.getElementById(table + 'Modal');
            const form = document.getElementById(table + 'Form');
            const title = document.getElementById(table + 'ModalTitle');

            // Reset form
            form.reset();

            if (mode === 'add') {
                title.textContent = 'Tambah Rekod ' + getTableTitle(table);
                // Enable year selection for add mode
                const yearSelect = form.querySelector('[name="tahun_penggal"]');
                if (yearSelect) {
                    yearSelect.disabled = false;
                }
            } else if (mode === 'edit' && record) {
                title.textContent = 'Edit Rekod ' + getTableTitle(table);
                populateForm(table, record);
                const yearSelect = form.querySelector('[name="tahun_penggal"]');
                if (yearSelect) {
                    yearSelect.disabled = false;
                }
            }

            modal.style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function getTableTitle(table) {
            const titles = {
                'akademik': 'Akademik',
                'bantuan': 'Bantuan',
                'kokurikulum': 'Kokurikulum'
            };
            return titles[table] || '';
        }

        function populateForm(table, record) {
            const form = document.getElementById(table + 'Form');
            
            // Populate common fields
            Object.keys(record).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = record[key] == 1;
                    } else {
                        field.value = record[key] || '';
                    }
                }
            });
        }

        // Form submission handlers
        document.getElementById('akademikForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm('akademik');
        });

        document.getElementById('bantuanForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm('bantuan');
        });

        document.getElementById('kokurikulumForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm('kokurikulum');
        });

        function submitForm(table) {
            const form = document.getElementById(table + 'Form');
            const formData = new FormData(form);
            
            // Add action and table
            formData.append('action', currentEditMode + '_' + table);
            formData.append('table', table);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal(table + 'Modal');
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Ralat semasa menyimpan data', 'error');
            });
        }

        function deleteRecord(table, tahun_penggal) {
            if (confirm('Adakah anda pasti ingin memadam rekod ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete_' + table);
                formData.append('table', table);
                formData.append('tahun_penggal', tahun_penggal);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Ralat semasa memadam data', 'error');
                });
            }
        }

        function showMessage(message, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.alert');
            existingMessages.forEach(msg => msg.remove());

            // Create new message
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
            alertDiv.innerHTML = `<strong>${type === 'success' ? 'Berjaya!' : 'Ralat!'}</strong> ${message}`;

            // Insert at the top of main content
            const mainContent = document.querySelector('.main-content');
            const pageHeader = document.querySelector('.page-header');
            mainContent.insertBefore(alertDiv, pageHeader.nextSibling);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>