<?php
// reports.php
// Student Management - Reports page

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get current year and generate year options
$current_year = date('Y'); 
$years = [];
for ($i = $current_year - 5; $i <= $current_year + 1; $i++) {
    $years[] = $i;
}

// Handle AJAX requests for report data
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_report_data') {
    header('Content-Type: application/json');
    
    $report_type = $_GET['report_type'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $school_filter = $_GET['school_filter'] ?? '';
    $class_filter = $_GET['class_filter'] ?? '';
    $gender_filter = $_GET['gender_filter'] ?? '';
    
    try {
        $data = [];
        
        switch ($report_type) {
            case 'students':
                $data = getStudentReportData($db, $date_from, $date_to, $school_filter, $class_filter, $gender_filter);
                break;
                
            case 'classes':
                $data = getClassReportData($db, $school_filter);
                break;
                
            case 'users':
                $data = getUserReportData($db);
                break;
                
            case 'academic':
                $data = getAcademicReportData($db, $date_from, $date_to, $school_filter, $class_filter);
                break;
                
            case 'assistance':
                $data = getAssistanceReportData($db, $date_from, $date_to, $school_filter, $class_filter);
                break;
                
            default:
                throw new Exception('Invalid report type');
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX requests for schools and classes data
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_schools') {
    header('Content-Type: application/json');
    
    try {
        $query = "SELECT DISTINCT kod_sekolah, nama_sekolah FROM sekolah ORDER BY nama_sekolah";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $schools]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_classes') {
    header('Content-Type: application/json');
    
    try {
        $query = "SELECT id_kelas, kod_sekolah, darjah_kelas, nama_kelas, guru_kelas FROM kelas ORDER BY darjah_kelas, nama_kelas";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $classes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Report data functions
function getStudentReportData($db, $date_from, $date_to, $school_filter, $class_filter, $gender_filter) {
    $query = "SELECT p.ic_pelajar, p.nama, p.jantina, p.kaum, k.darjah_kelas,  k.nama_kelas,
                     k.guru_kelas, p.status_pelajar, p.status_penjaga, p.warganegara, s.nama_sekolah
              FROM pelajar p 
              LEFT JOIN kelas k ON p.id_kelas = k.id_kelas 
              LEFT JOIN sekolah s ON k.kod_sekolah = s.kod_sekolah
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($school_filter)) {
        $query .= " AND k.kod_sekolah = ?";
        $params[] = $school_filter;
    }
    
    if (!empty($class_filter)) {
        $query .= " AND p.id_kelas = ?";
        $params[] = $class_filter;
    }
    
    if (!empty($gender_filter)) {
        $query .= " AND p.jantina = ?";
        $params[] = $gender_filter;
    }
    
    $query .= " ORDER BY p.nama";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_NUM);
    foreach ($data as &$row) {
        $row[0] = (string)$row[0];
    }
    return $data;
}

function getClassReportData($db, $school_filter) {
    $query = "SELECT k.id_kelas, k.kod_sekolah, k.darjah_kelas, k.nama_kelas, k.guru_kelas, 
                     COUNT(p.ic_pelajar) as bil_pelajar, s.nama_sekolah
              FROM kelas k 
              LEFT JOIN pelajar p ON k.id_kelas = p.id_kelas 
              LEFT JOIN sekolah s ON k.kod_sekolah = s.kod_sekolah
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($school_filter)) {
        $query .= " AND k.kod_sekolah = ?";
        $params[] = $school_filter;
    }
    
    $query .= " GROUP BY k.id_kelas, k.kod_sekolah, k.darjah_kelas, k.nama_kelas, k.guru_kelas, s.nama_sekolah
                ORDER BY k.darjah_kelas, k.nama_kelas";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_NUM);
}

function getUserReportData($db) {
    $query = "SELECT id_pengguna, nama_pengguna, jenis_pengguna FROM pengguna ORDER BY nama_pengguna";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_NUM);
}

function getAcademicReportData($db, $date_from, $date_to, $school_filter, $class_filter) {
    $query = "SELECT a.ic_pelajar, p.nama, k.nama_kelas, a.tahun_penggal, 
                     a.kehadiran_penggal_satu, a.keputusan_penggal_satu, 
                     a.kehadiran_penggal_dua, a.keputusan_penggal_dua, 
                     a.upkk, a.sdea
              FROM akademik a 
              LEFT JOIN pelajar p ON a.ic_pelajar = p.ic_pelajar 
              LEFT JOIN kelas k ON p.id_kelas = k.id_kelas 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($date_from)) {
        $query .= " AND a.tahun_penggal >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND a.tahun_penggal <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($school_filter)) {
        $query .= " AND k.kod_sekolah = ?";
        $params[] = $school_filter;
    }
    
    if (!empty($class_filter)) {
        $query .= " AND p.id_kelas = ?";
        $params[] = $class_filter;
    }
    
    $query .= " ORDER BY a.tahun_penggal, p.nama";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_NUM);
}

function getAssistanceReportData($db, $date_from, $date_to, $school_filter, $class_filter) {
    $query = "SELECT b.ic_pelajar, p.nama, b.tahun_penggal, 
                     b.anak_orang_asli_islam, b.anak_yatim, b.skim_pinjaman_kitab, 
                     b.skim_pinjaman_spbt, b.makanan_sihat, b.pakaian
              FROM bantuan b 
              LEFT JOIN pelajar p ON b.ic_pelajar = p.ic_pelajar 
              LEFT JOIN kelas k ON p.id_kelas = k.id_kelas 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($date_from)) {
        $query .= " AND b.tahun_penggal >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND b.tahun_penggal <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($school_filter)) {
        $query .= " AND k.kod_sekolah = ?";
        $params[] = $school_filter;
    }
    
    if (!empty($class_filter)) {
        $query .= " AND p.id_kelas = ?";
        $params[] = $class_filter;
    }
    
    $query .= " ORDER BY b.tahun_penggal, p.nama";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_NUM);
}

// Continue with the HTML content...
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Sistem Maklumat Pelajar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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

        .page-title {
            margin-bottom: 30px;
        }

        .page-title h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #666;
            font-size: 16px;
        }

        /* Report Form */
        .report-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-section {
            margin-bottom: 25px;
        }

        .form-section h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .report-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .report-type {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .report-type:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .report-type input[type="radio"] {
            display: none;
        }

        .report-type.active {
            border-color: #667eea !important;
            background: #f0f4ff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .report-type.active label {
            color: #667eea !important;
            font-weight: bold !important;
        }

        .report-type label {
            cursor: pointer;
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }

        .report-type p {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
        }

        .format-options {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }

        .format-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .format-option:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .format-option.active {
            border-color: #667eea !important;
            background: #f0f4ff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .format-option.active label {
            color: #667eea !important;
            font-weight: bold !important;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            animation: pulse 1.5s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            from { opacity: 0.6; }
            to { opacity: 0.8; }
        }

        /* Error/Success Messages */
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Report Examples */
        .report-examples {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .report-examples h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .examples-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .example-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .example-item h4 {
            color: #333;
            margin-bottom: 8px;
        }

        .example-item p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .nav-menu {
                flex-direction: column;
                gap: 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .report-types {
                grid-template-columns: 1fr;
            }

            .format-options {
                flex-direction: column;
                gap: 10px;
            }

            .examples-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }
            
            .report-form,
            .report-examples {
                padding: 20px;
            }
            
            .btn {
                width: 100%;
                padding: 18px;
                font-size: 16px;
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
                <li><a href="#" onclick="navigateTo('dashboard')">Dashboard</a></li>
                <li><a href="#" onclick="navigateTo('students')">Pelajar</a></li>
                <li><a href="#" onclick="navigateTo('classes')">Kelas</a></li>
                <li><a href="#" onclick="navigateTo('school')">Sekolah</a></li>
                <li><a href="#" class="active">Laporan</a></li>
                <li><a href="#" onclick="navigateTo('users')">Pengguna</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-title">
            <h2>Laporan Sistem</h2>
            <p>Jana laporan terperinci dalam format CSV atau PDF</p>
        </div>

        <div id="messageContainer"></div>

        <!-- Report Generation Form -->
        <form id="reportForm" class="report-form">
            <!-- Report Type Selection -->
            <div class="form-section">
                <h3>1. Pilih Jenis Laporan</h3>
                <div class="report-types">
                    <div class="report-type" onclick="selectReportType('students')">
                        <input type="radio" name="report_type" value="students" id="students">
                        <label for="students">Laporan Pelajar</label>
                        <p>Senarai lengkap maklumat pelajar</p>
                    </div>
                    <div class="report-type" onclick="selectReportType('classes')">
                        <input type="radio" name="report_type" value="classes" id="classes">
                        <label for="classes">Laporan Kelas</label>
                        <p>Maklumat kelas dan bilangan pelajar</p>
                    </div>
                    <div class="report-type" onclick="selectReportType('users')">
                        <input type="radio" name="report_type" value="users" id="users">
                        <label for="users">Laporan Pengguna</label>
                        <p>Senarai pengguna sistem</p>
                    </div>
                    <div class="report-type" onclick="selectReportType('academic')">
                        <input type="radio" name="report_type" value="academic" id="academic">
                        <label for="academic">Laporan Akademik</label>
                        <p>Keputusan dan kehadiran pelajar</p>
                    </div>
                    <div class="report-type" onclick="selectReportType('assistance')">
                        <input type="radio" name="report_type" value="assistance" id="assistance">
                        <label for="assistance">Laporan Bantuan</label>
                        <p>Status bantuan pelajar</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="form-section">
                <h3>2. Penapis Data (Pilihan)</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="date_from">Tahun Dari:</label>
                        <select name="date_from" id="date_from">
                            <option value="">Pilih Tahun</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_to">Tahun Hingga:</label>
                        <select name="date_to" id="date_to">
                            <option value="">Pilih Tahun</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"> 
                        <label for="school_filter">Sekolah:</label>
                        <select name="school_filter" id="school_filter">
                            <option value="">Semua Sekolah</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="class_filter">Kelas:</label>
                        <select name="class_filter" id="class_filter">
                            <option value="">Semua Kelas</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gender_filter">Jantina:</label>
                        <select name="gender_filter" id="gender_filter">
                            <option value="">Semua Jantina</option>
                            <option value="lelaki">Lelaki</option>
                            <option value="perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Format Selection -->
            <div class="form-section">
                <h3>3. Pilih Format Laporan</h3>
                <div class="format-options">
                    <div class="format-option" onclick="selectFormat('csv')">
                        <input type="radio" name="format" value="csv" id="csv">
                        <label for="csv">CSV (Excel)</label>
                    </div>
                    <div class="format-option" onclick="selectFormat('pdf')">
                        <input type="radio" name="format" value="pdf" id="pdf">
                        <label for="pdf">PDF</label>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="form-section">
                <button type="button" id="generateBtn" class="btn" onclick="generateReport()">
                    📥 Jana Laporan
                </button>
            </div>
        </form>

        <!-- Report Examples/Help -->
        <div class="report-examples">
            <h3>📋 Panduan Laporan</h3>
            <div class="examples-grid">
                <div class="example-item">
                    <h4>🎓 Laporan Pelajar</h4>
                    <p>Mengandungi maklumat lengkap pelajar termasuk nama, IC, jantina, kaum, kelas dan sekolah. Berguna untuk analisis demografi pelajar.</p>
                </div>
                <div class="example-item">
                    <h4>🏫 Laporan Kelas</h4>
                    <p>Senarai semua kelas dengan bilangan pelajar, guru kelas dan maklumat sekolah. Membantu dalam perancangan kapasiti kelas.</p>
                </div>
                <div class="example-item">
                    <h4>👥 Laporan Pengguna</h4>
                    <p>Senarai pengguna sistem dan jenis akses mereka. Berguna untuk pengurusan keselamatan sistem.</p>
                </div>
                <div class="example-item">
                    <h4>📚 Laporan Akademik</h4>
                    <p>Rekod kehadiran dan keputusan pelajar untuk setiap penggal. Termasuk data UPKK dan SDEA.</p>
                </div>
                <div class="example-item">
                    <h4>🤝 Laporan Bantuan</h4>
                    <p>Status pelbagai bantuan yang diterima pelajar seperti makanan sihat, pakaian dan skim pinjaman.</p>
                </div>
                <div class="example-item">
                    <h4>💡 Tips Penggunaan</h4>
                    <p>Gunakan penapis untuk mendapatkan data yang lebih spesifik. Format CSV sesuai untuk analisis lanjut, manakala PDF untuk laporan rasmi.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Reports JavaScript Functions
        let schoolsData = [];
        let classesData = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializePage();
            setupEventListeners();
            loadSchoolsAndClasses();
        });

        function initializePage() {
            const currentDateElement = document.getElementById('currentDate');
            if (currentDateElement) {
                const currentDate = new Date().toLocaleDateString('ms-MY', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
                currentDateElement.textContent = currentDate;
            }
        }

        async function loadSchoolsAndClasses() {
            try {
                // Load schools
                const schoolResponse = await fetch('reports.php?ajax=get_schools');
                const schoolResult = await schoolResponse.json();
                if (schoolResult.success) {
                    schoolsData = schoolResult.data;
                    populateSchools();
                } else {
                    console.error('Error loading schools:', schoolResult.error);
                    showMessage('Ralat memuat data sekolah.', 'error');
                }

                // Load classes  
                const classResponse = await fetch('reports.php?ajax=get_classes');
                const classResult = await classResponse.json();
                if (classResult.success) {
                    classesData = classResult.data;
                    populateClasses(); // Initially populate all classes
                } else {
                    console.error('Error loading classes:', classResult.error);
                    showMessage('Ralat memuat data kelas.', 'error');
                }
            } catch (error) {
                console.error('Error loading data:', error);
                showMessage('Ralat memuat data awal. Sila muat semula halaman.', 'error');
            }
        }

        function populateSchools() {
            const schoolSelect = document.getElementById('school_filter');
            schoolSelect.innerHTML = '<option value="">Semua Sekolah</option>';
            
            schoolsData.forEach(school => {
                const option = document.createElement('option');
                option.value = school.kod_sekolah;
                option.textContent = school.nama_sekolah;
                schoolSelect.appendChild(option);
            });
        }

        function populateClasses(selectedSchool = '') {
            const classSelect = document.getElementById('class_filter');
            classSelect.innerHTML = '<option value="">Semua Kelas</option>';
            
            // Filter classes based on selected school
            const filteredClasses = selectedSchool ? 
                classesData.filter(cls => cls.kod_sekolah === selectedSchool) : 
                classesData;
            
            filteredClasses.forEach(cls => {
                const option = document.createElement('option');
                option.value = cls.id_kelas;
                option.textContent = `Darjah ${cls.darjah_kelas} - ${cls.nama_kelas}`;
                classSelect.appendChild(option);
            });
        }

        function setupEventListeners() {
            // Date validation
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const schoolFilter = document.getElementById('school_filter');

            function validateDates() {
                if (dateFrom.value && dateTo.value) {
                    if (parseInt(dateFrom.value) > parseInt(dateTo.value)) {
                        dateTo.setCustomValidity('Tahun hingga mestilah lebih besar atau sama dengan tahun dari');
                        showMessage('Tahun hingga mestilah lebih besar atau sama dengan tahun dari', 'error');
                    } else {
                        dateTo.setCustomValidity('');
                        clearMessages();
                    }
                }
            }

            // School filter change event - update classes dropdown
            schoolFilter.addEventListener('change', function() {
                const selectedSchool = this.value;
                populateClasses(selectedSchool);
                clearMessages(); // Clear any previous error messages
            });

            dateFrom.addEventListener('change', validateDates);
            dateTo.addEventListener('change', validateDates);
        }

        function selectReportType(type) {
            // Remove active class from all report types
            document.querySelectorAll('.report-type').forEach(div => {
                div.classList.remove('active');
            });
            
            // Add active class to selected report type
            event.currentTarget.classList.add('active');
            
            // Check the radio button
            document.getElementById(type).checked = true;
        }

        function selectFormat(format) {
            // Remove active class from all format options
            document.querySelectorAll('.format-option').forEach(div => {
                div.classList.remove('active');
            });
            
            // Add active class to selected format option
            event.currentTarget.classList.add('active');
            
            // Check the radio button
            document.getElementById(format).checked = true;
        }

        async function fetchReportData(reportType, filters) {
            const params = new URLSearchParams({
                ajax: 'get_report_data',
                report_type: reportType,
                date_from: filters.dateFrom || '',
                date_to: filters.dateTo || '',
                school_filter: filters.school || '',
                class_filter: filters.class || '',
                gender_filter: filters.gender || ''
            });

            try {
                const response = await fetch(`reports.php?${params}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Unknown error');
                }
                
                return result.data;
            } catch (error) {
                console.error('Error fetching report data:', error);
                throw error;
            }
        }

        async function generateReport() {
            const reportType = document.querySelector('input[name="report_type"]:checked');
            const format = document.querySelector('input[name="format"]:checked');

            if (!reportType || !format) {
                showMessage('Sila pilih jenis laporan dan format sebelum menjana laporan.', 'error');
                return;
            }

            const filters = {
                dateFrom: document.getElementById('date_from').value,
                dateTo: document.getElementById('date_to').value,
                school: document.getElementById('school_filter').value,
                class: document.getElementById('class_filter').value,
                gender: document.getElementById('gender_filter').value
            };

            const generateBtn = document.getElementById('generateBtn');
            generateBtn.disabled = true;
            generateBtn.innerHTML = '⏳ Menjana Laporan...';

            try {
                // Fetch data from database
                const data = await fetchReportData(reportType.value, filters);
                
                if (format.value === 'csv') {
                    generateCSVFromData(reportType.value, data);
                } else {
                    generatePDFFromData(reportType.value, data);
                }

                showMessage('Laporan berjaya dijana dan dimuat turun!', 'success');
            } catch (error) {
                console.error('Error generating report:', error);
                showMessage('Ralat berlaku semasa menjana laporan. Sila cuba lagi.', 'error');
            } finally {
                setTimeout(() => {
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = '📥 Jana Laporan';
                }, 2000);
            }
        }

        function generateCSVFromData(reportType, data) {
            let headers = [];
            let filename = '';

            switch (reportType) {
                case 'students':
                    headers = ['IC Pelajar', 'Nama Pelajar', 'Jantina', 'Kaum', 'Darjah', 'Kelas', 'Guru Kelas', 'Status Pelajar', 'Status Penjaga', 'Warganegara', 'Sekolah'];
                    filename = 'laporan_pelajar.csv';
                    break;
                case 'classes':
                    headers = ['ID Kelas', 'Kod Sekolah', 'Darjah', 'Nama Kelas', 'Guru Kelas', 'Bilangan Pelajar', 'Nama Sekolah'];
                    filename = 'laporan_kelas.csv';
                    break;
                case 'users':
                    headers = ['ID Pengguna', 'Nama Pengguna', 'Jenis Pengguna'];
                    filename = 'laporan_pengguna.csv';
                    break;
                case 'academic':
                    headers = ['IC Pelajar', 'Nama Pelajar', 'Kelas', 'Tahun', 'Kehadiran P1', 'Keputusan P1', 'Kehadiran P2', 'Keputusan P2', 'UPKK', 'SDEA'];
                    filename = 'laporan_akademik.csv';
                    break;
                case 'assistance':
                    headers = ['IC Pelajar', 'Nama Pelajar', 'Tahun', 'Orang Asli Islam', 'Anak Yatim', 'Pinjaman Kitab', 'Pinjaman SPBT', 'Makanan Sihat', 'Pakaian'];
                    filename = 'laporan_bantuan.csv';
                    break;
            }

            // Add BOM for proper UTF-8 encoding in Excel
            let csvContent = '\uFEFF';
            csvContent += headers.join(',') + '\n';
            
            data.forEach(row => {
                const processedRow = row.map(field => {
                    // Handle null/undefined values
                    if (field === null || field === undefined) {
                        return '';
                    }
                    // Convert boolean values for assistance report
                    if (reportType === 'assistance' && typeof field === 'boolean') {
                        return field ? 'Ya' : 'Tidak';
                    }
                    // Handle numbers
                    if (typeof field === 'number') {
                        return field.toString();
                    }
                    // Escape quotes and wrap in quotes if contains comma or quote
                    const stringValue = field.toString();
                    if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
                        return '"' + stringValue.replace(/"/g, '""') + '"';
                    }
                    return stringValue;
                });
                csvContent += processedRow.join(',') + '\n';
            });

            downloadFile(csvContent, filename, 'text/csv;charset=utf-8;');
        }

        function generatePDFFromData(reportType, data) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Set up PDF document
            doc.setFont('helvetica');
            
            // Add title
            doc.setFontSize(16);
            doc.text('Sistem Maklumat Pelajar', 20, 20);
            doc.setFontSize(12);
            doc.text('Sekolah Agama Bukit Banjar', 20, 30);

            // Add report title
            let reportTitle = '';
            let headers = [];
            
            switch (reportType) {
                case 'students':
                    reportTitle = 'Laporan Pelajar';
                    headers = [['IC', 'Nama', 'Jantina', 'Kaum', 'Darjah', 'Kelas', 'Guru Kelas']];
                    // Select specific columns for PDF (to fit page width)
                    data = data.map(row => [row[0], row[1], row[2], row[3], row[4], row[5], row[6]]);
                    break;
                case 'classes':
                    reportTitle = 'Laporan Kelas';
                    headers = [['ID', 'Darjah', 'Nama Kelas', 'Guru Kelas', 'Bil. Pelajar']];
                    data = data.map(row => [row[0], row[2], row[3], row[4], row[5]]);
                    break;
                case 'users':
                    reportTitle = 'Laporan Pengguna';
                    headers = [['ID Pengguna', 'Nama Pengguna', 'Jenis Pengguna']];
                    break;
                case 'academic':
                    reportTitle = 'Laporan Akademik';
                    headers = [['IC', 'Nama', 'Tahun', 'Kehadiran P1', 'Keputusan P1', 'Kehadiran P2', 'Keputusan P2', 'UPKK', 'SDEA']];
                    data = data.map(row => [row[0], row[1], row[3], row[4], row[5], row[6], row[7], row[8], row[9]]);
                    break;
                case 'assistance':
                    reportTitle = 'Laporan Bantuan';
                    headers = [['IC', 'Nama', 'Tahun', 'Orang Asli', 'Yatim', 'Kitab', 'SPBT', 'Makanan', 'Pakaian']];
                    data = data.map(row => [
                        row[0], 
                        row[1], 
                        row[2],
                        row[3] ? 'Ya' : 'Tidak', 
                        row[4] ? 'Ya' : 'Tidak',
                        row[5] ? 'Ya' : 'Tidak',
                        row[6] ? 'Ya' : 'Tidak', 
                        row[7] ? 'Ya' : 'Tidak', 
                        row[8] ? 'Ya' : 'Tidak'
                    ]);
                    break;
            }

            doc.setFontSize(14);
            doc.text(reportTitle, 20, 45);

            // Add generation date
            doc.setFontSize(10);
            doc.text(`Tarikh Jana: ${new Date().toLocaleDateString('ms-MY')}`, 20, 55);
            
            // Add total records
            doc.text(`Jumlah Rekod: ${data.length}`, 20, 65);

            // Create table
            doc.autoTable({
                head: headers,
                body: data,
                startY: 75,
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: 255
                },
                alternateRowStyles: {
                    fillColor: [245, 247, 250]
                },
                columnStyles: {
                    0: { cellWidth: 25 }, // IC column
                    1: { cellWidth: 35 }  // Name column
                }
            });

            // Add footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text(`Halaman ${i} dari ${pageCount}`, doc.internal.pageSize.width - 40, doc.internal.pageSize.height - 10);
            }

            // Download PDF
            const filename = `${reportTitle.toLowerCase().replace(/\s+/g, '_')}.pdf`;
            doc.save(filename);
        }

        function downloadFile(content, filename, contentType) {
            const blob = new Blob([content], { type: contentType });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }

        function showMessage(message, type) {
            clearMessages();
            const messageContainer = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;
            messageContainer.appendChild(messageDiv);

            // Auto remove after 5 seconds
            setTimeout(() => {
                clearMessages();
            }, 5000);
        }

        function clearMessages() {
            const messageContainer = document.getElementById('messageContainer');
            messageContainer.innerHTML = '';
        }

        function navigateTo(page) {
            // In a real application, this would handle navigation
            window.location.href = `${page}.php`;
        }

        function handleLogout() {
            if (confirm('Adakah anda pasti untuk log keluar?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>