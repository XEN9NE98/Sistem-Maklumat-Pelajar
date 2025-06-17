<?php
// students_edit.php
// Edit Student Personal and Guardian Information

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Get student IC from URL
$edit_student_ic = isset($_GET['ic']) ? trim($_GET['ic']) : '';

if (empty($edit_student_ic)) {
    header("Location: students.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables for student
$nama = '';
$jantina = '';
$kaum = '';
$status_pelajar = '';
$status_penjaga = '';
$sijil_lahir = '';
$warganegara = '';
$id_kelas = '';

// Initialize variables for guardian
$ic_waris = '';
$nama_waris = '';
$status_waris = '';
$nombor_telefon_waris = '';
$alamat = '';
$poskod = '';
$negeri = '';
$bilangan_tanggungan = '';
$pekerjaan_bapa = '';
$pendapatan_bapa = '';
$pekerjaan_ibu = '';
$pendapatan_ibu = '';
$pekerjaan_penjaga = '';
$pendapatan_penjaga = '';
$jumlah_pendapatan = '';
$pendapatan_perkapita = '';

$errors = [];
$success_message = '';
$student_data = null;
$guardian_data = null;
$classes = [];

// Get dropdown data - classes
try {
    $classes_stmt = $db->prepare("
        SELECT k.id_kelas, k.nama_kelas, k.darjah_kelas, s.nama_sekolah 
        FROM KELAS k 
        LEFT JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
        ORDER BY s.nama_sekolah, k.darjah_kelas, k.nama_kelas
    ");
    $classes_stmt->execute();
    $classes = $classes_stmt->fetchAll();
} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data kelas: " . $e->getMessage();
}

// Fetch existing student and guardian data
try {
    $stmt = $db->prepare("
        SELECT p.*, pen.*, k.nama_kelas, k.darjah_kelas, s.nama_sekolah 
        FROM PELAJAR p 
        LEFT JOIN PENJAGA pen ON p.ic_waris = pen.ic_waris 
        LEFT JOIN KELAS k ON p.id_kelas = k.id_kelas 
        LEFT JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
        WHERE p.ic_pelajar = ?
    ");
    $stmt->execute([$edit_student_ic]);
    $data = $stmt->fetch();
    
    if (!$data) {
        header("Location: students.php");
        exit();
    }
    
    // Set student form data
    $nama = $data['nama'];
    $jantina = $data['jantina'];
    $kaum = $data['kaum'];
    $status_pelajar = $data['status_pelajar'];
    $status_penjaga = $data['status_penjaga'];
    $sijil_lahir = $data['sijil_lahir'];
    $warganegara = $data['warganegara'];
    $id_kelas = $data['id_kelas'];
    
    // Set guardian form data
    $ic_waris = $data['ic_waris'];
    $nama_waris = $data['nama_waris'];
    $status_waris = $data['status_waris'];
    $nombor_telefon_waris = $data['nombor_telefon_waris'];
    $alamat = $data['alamat'];
    $poskod = $data['poskod'];
    $negeri = $data['negeri'];
    $bilangan_tanggungan = $data['bilangan_tanggungan'];
    $pekerjaan_bapa = $data['pekerjaan_bapa'];
    $pendapatan_bapa = $data['pendapatan_bapa'];
    $pekerjaan_ibu = $data['pekerjaan_ibu'];
    $pendapatan_ibu = $data['pendapatan_ibu'];
    $pekerjaan_penjaga = $data['pekerjaan_penjaga'];
    $pendapatan_penjaga = $data['pendapatan_penjaga'];
    $jumlah_pendapatan = $data['jumlah_pendapatan'];
    $pendapatan_perkapita = $data['pendapatan_perkapita'];
    
    $student_data = $data;
    
} catch(PDOException $e) {
    $errors[] = "Ralat mengambil data pelajar: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get student form data
    $nama = trim($_POST['nama']);
    $jantina = $_POST['jantina'];
    $kaum = trim($_POST['kaum']);
    $status_pelajar = $_POST['status_pelajar'];
    $status_penjaga = $_POST['status_penjaga'];
    $sijil_lahir = trim($_POST['sijil_lahir']);
    $warganegara = trim($_POST['warganegara']);
    $id_kelas = $_POST['id_kelas'];
    
    // Get guardian form data
    $ic_waris = trim($_POST['ic_waris']);
    $nama_waris = trim($_POST['nama_waris']);
    $status_waris = $_POST['status_waris'];
    $nombor_telefon_waris = trim($_POST['nombor_telefon_waris']);
    $alamat = trim($_POST['alamat']);
    $poskod = trim($_POST['poskod']);
    $negeri = trim($_POST['negeri']);
    $bilangan_tanggungan = trim($_POST['bilangan_tanggungan']);
    $pekerjaan_bapa = trim($_POST['pekerjaan_bapa']);
    $pendapatan_bapa = trim($_POST['pendapatan_bapa']);
    $pekerjaan_ibu = trim($_POST['pekerjaan_ibu']);
    $pendapatan_ibu = trim($_POST['pendapatan_ibu']);
    $pekerjaan_penjaga = trim($_POST['pekerjaan_penjaga']);
    $pendapatan_penjaga = trim($_POST['pendapatan_penjaga']);
    $jumlah_pendapatan = trim($_POST['jumlah_pendapatan']);
    $pendapatan_perkapita = trim($_POST['pendapatan_perkapita']);
    
    // Validation for student
    if (empty($nama)) {
        $errors[] = "Nama pelajar adalah wajib.";
    } elseif (strlen($nama) < 3) {
        $errors[] = "Nama pelajar mestilah sekurang-kurangnya 3 aksara.";
    }
    
    if (empty($jantina)) {
        $errors[] = "Jantina adalah wajib.";
    } elseif (!in_array($jantina, ['lelaki', 'perempuan'])) {
        $errors[] = "Jantina tidak sah.";
    }
    
    if (empty($kaum)) {
        $errors[] = "Kaum adalah wajib.";
    }
    
    if (empty($status_pelajar)) {
        $errors[] = "Status pelajar adalah wajib.";
    } elseif (!in_array($status_pelajar, ['kandung', 'tiri', 'angkat'])) {
        $errors[] = "Status pelajar tidak sah.";
    }
    
    if (empty($status_penjaga)) {
        $errors[] = "Status penjaga adalah wajib.";
    } elseif (!in_array($status_penjaga, ['ibu bapa', 'ibu tunggal', 'bapa tunggal', 'penjaga'])) {
        $errors[] = "Status penjaga tidak sah.";
    }
    
    if (empty($warganegara)) {
        $errors[] = "Warganegara adalah wajib.";
    }
    
    if (empty($id_kelas)) {
        $errors[] = "Kelas adalah wajib.";
    }
    
    // Validation for guardian
    if (empty($ic_waris)) {
        $errors[] = "No. IC waris adalah wajib.";
    } elseif (!preg_match('/^\d{12}$/', $ic_waris)) {
        $errors[] = "No. IC waris mestilah 12 digit.";
    }
    
    if (empty($nama_waris)) {
        $errors[] = "Nama waris adalah wajib.";
    } elseif (strlen($nama_waris) < 3) {
        $errors[] = "Nama waris mestilah sekurang-kurangnya 3 aksara.";
    }
    
    if (empty($status_waris)) {
        $errors[] = "Status waris adalah wajib.";
    } elseif (!in_array($status_waris, ['bapa', 'ibu', 'penjaga', 'datuk', 'nenek'])) {
        $errors[] = "Status waris tidak sah.";
    }
    
    if (empty($alamat)) {
        $errors[] = "Alamat adalah wajib.";
    }
    
    if (empty($poskod)) {
        $errors[] = "Poskod adalah wajib.";
    } elseif (!preg_match('/^\d{5}$/', $poskod)) {
        $errors[] = "Poskod mestilah 5 digit.";
    }
    
    if (empty($negeri)) {
        $errors[] = "Negeri adalah wajib.";
    }
    
    // Validate numeric fields
    if (!empty($bilangan_tanggungan) && !is_numeric($bilangan_tanggungan)) {
        $errors[] = "Bilangan tanggungan mestilah nombor.";
    }
    
    if (!empty($pendapatan_bapa) && !is_numeric($pendapatan_bapa)) {
        $errors[] = "Pendapatan bapa mestilah nombor.";
    }
    
    if (!empty($pendapatan_ibu) && !is_numeric($pendapatan_ibu)) {
        $errors[] = "Pendapatan ibu mestilah nombor.";
    }
    
    if (!empty($pendapatan_penjaga) && !is_numeric($pendapatan_penjaga)) {
        $errors[] = "Pendapatan penjaga mestilah nombor.";
    }
    
    if (!empty($jumlah_pendapatan) && !is_numeric($jumlah_pendapatan)) {
        $errors[] = "Jumlah pendapatan mestilah nombor.";
    }
    
    if (!empty($pendapatan_perkapita) && !is_numeric($pendapatan_perkapita)) {
        $errors[] = "Pendapatan perkapita mestilah nombor.";
    }
    
    // Check if guardian IC changed and if new IC already exists
    if ($ic_waris != $student_data['ic_waris']) {
        $guardian_check = $db->prepare("SELECT COUNT(*) FROM PENJAGA WHERE ic_waris = ?");
        $guardian_check->execute([$ic_waris]);
        if ($guardian_check->fetchColumn() > 0) {
            $errors[] = "No. IC waris sudah wujud dalam sistem.";
        }
    }
    
    // If no errors, update the records
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update or insert guardian record
            if ($ic_waris != $student_data['ic_waris']) {
                // New guardian - insert new record
                $guardian_stmt = $db->prepare("
                    INSERT INTO PENJAGA (
                        ic_waris, nama_waris, status_waris, nombor_telefon_waris, alamat, 
                        poskod, negeri, bilangan_tanggungan, pekerjaan_bapa, pendapatan_bapa, 
                        pekerjaan_ibu, pendapatan_ibu, pekerjaan_penjaga, pendapatan_penjaga, 
                        jumlah_pendapatan, pendapatan_perkapita
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $guardian_stmt->execute([
                    $ic_waris, $nama_waris, $status_waris, $nombor_telefon_waris, $alamat,
                    $poskod, $negeri, 
                    !empty($bilangan_tanggungan) ? $bilangan_tanggungan : null,
                    !empty($pekerjaan_bapa) ? $pekerjaan_bapa : null,
                    !empty($pendapatan_bapa) ? $pendapatan_bapa : null,
                    !empty($pekerjaan_ibu) ? $pekerjaan_ibu : null,
                    !empty($pendapatan_ibu) ? $pendapatan_ibu : null,
                    !empty($pekerjaan_penjaga) ? $pekerjaan_penjaga : null,
                    !empty($pendapatan_penjaga) ? $pendapatan_penjaga : null,
                    !empty($jumlah_pendapatan) ? $jumlah_pendapatan : null,
                    !empty($pendapatan_perkapita) ? $pendapatan_perkapita : null
                ]);
            } else {
                // Same guardian - update existing record
                $guardian_stmt = $db->prepare("
                    UPDATE PENJAGA SET 
                        nama_waris = ?, status_waris = ?, nombor_telefon_waris = ?, alamat = ?, 
                        poskod = ?, negeri = ?, bilangan_tanggungan = ?, pekerjaan_bapa = ?, 
                        pendapatan_bapa = ?, pekerjaan_ibu = ?, pendapatan_ibu = ?, 
                        pekerjaan_penjaga = ?, pendapatan_penjaga = ?, jumlah_pendapatan = ?, 
                        pendapatan_perkapita = ?
                    WHERE ic_waris = ?
                ");
                $guardian_stmt->execute([
                    $nama_waris, $status_waris, $nombor_telefon_waris, $alamat,
                    $poskod, $negeri,
                    !empty($bilangan_tanggungan) ? $bilangan_tanggungan : null,
                    !empty($pekerjaan_bapa) ? $pekerjaan_bapa : null,
                    !empty($pendapatan_bapa) ? $pendapatan_bapa : null,
                    !empty($pekerjaan_ibu) ? $pekerjaan_ibu : null,
                    !empty($pendapatan_ibu) ? $pendapatan_ibu : null,
                    !empty($pekerjaan_penjaga) ? $pekerjaan_penjaga : null,
                    !empty($pendapatan_penjaga) ? $pendapatan_penjaga : null,
                    !empty($jumlah_pendapatan) ? $jumlah_pendapatan : null,
                    !empty($pendapatan_perkapita) ? $pendapatan_perkapita : null,
                    $ic_waris
                ]);
            }
            
            // Update student record
            $student_stmt = $db->prepare("
                UPDATE PELAJAR SET 
                    nama = ?, jantina = ?, kaum = ?, status_pelajar = ?, status_penjaga = ?, 
                    sijil_lahir = ?, warganegara = ?, id_kelas = ?, ic_waris = ?
                WHERE ic_pelajar = ?
            ");
            $student_stmt->execute([
                $nama, $jantina, $kaum, $status_pelajar, $status_penjaga,
                !empty($sijil_lahir) ? $sijil_lahir : null, $warganegara, $id_kelas, $ic_waris,
                $edit_student_ic
            ]);
            
            $db->commit();
            $success_message = "Maklumat pelajar dan waris berjaya dikemas kini.";
            
            // Refresh data
            $stmt = $db->prepare("
                SELECT p.*, pen.*, k.nama_kelas, k.darjah_kelas, s.nama_sekolah 
                FROM PELAJAR p 
                LEFT JOIN PENJAGA pen ON p.ic_waris = pen.ic_waris 
                LEFT JOIN KELAS k ON p.id_kelas = k.id_kelas 
                LEFT JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
                WHERE p.ic_pelajar = ?
            ");
            $stmt->execute([$edit_student_ic]);
            $student_data = $stmt->fetch();
            
        } catch(PDOException $e) {
            $db->rollback();
            $errors[] = "Ralat mengemaskini data: " . $e->getMessage();
        }
    }
}

// Malaysian states array
$states = [
    'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang',
    'Pulau Pinang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor',
    'Terengganu', 'Wilayah Persekutuan Kuala Lumpur', 'Wilayah Persekutuan Labuan',
    'Wilayah Persekutuan Putrajaya'
];

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
    <title>Edit Pelajar - Sistem Maklumat Pelajar</title>
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

        /* Student Info Card */
        .student-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .student-info-card h3 {
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .student-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .student-card-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .student-card-item span {
            font-size: 14px;
            opacity: 0.8;
            font-weight: 400;
        }

        .student-card-item strong {
            font-size: 16px;
            font-weight: 600;
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

        /* Section Header with Number - FIXED */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
        }

        .section-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
            padding: 0;
            border: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Grid Column Classes */
        .col-12 { grid-column: span 12; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }
        .col-3 { grid-column: span 3; }

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

        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
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

        /* Income Section Styling */
        .income-grid {
            display: grid;
            grid-template-columns: 1fr 120px;
            gap: 15px;
            align-items: end;
        }

        .calculated-field {
            background: #f8f9fa !important;
            position: relative;
        }

        .calculated-field::after {
            content: "🔄";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
        }

        .income-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }

        /* Messages */
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }

        .error ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        .error li {
            margin-bottom: 5px;
        }

        /* Auto-calculation styling */
        .auto-calc {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef) !important;
            border: 2px dashed #6c757d !important;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .student-card-grid {
                grid-template-columns: 1fr;
            }

            .nav-menu {
                flex-direction: column;
                gap: 5px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .col-12, .col-6, .col-4, .col-3 {
                grid-column: span 1;
            }

            .income-grid {
                grid-template-columns: 1fr;
            }

            .radio-group {
                flex-direction: column;
                gap: 10px;
            }

            .form-actions {
                flex-direction: column;
                gap: 10px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .section-number {
                align-self: center;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }

            .form-section {
                padding: 20px 15px;
            }

            .student-info-card {
                padding: 20px 15px;
            }
        }

        /* Form Actions */
        .form-actions {
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
            justify-content: end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
            padding: 12px 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
            font-size: 14px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 4px;
            margin: 15px 0;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }

        .error ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
        }

        .error li {
            margin-bottom: 3px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 10px;
            }

            .page-header {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .student-card-grid {
                grid-template-columns: 1fr;
            }

            .nav-menu {
                flex-direction: column;
                gap: 5px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .col-12, .col-6, .col-4, .col-3 {
                grid-column: span 1;
            }

            .income-grid {
                grid-template-columns: 1fr;
            }

            .radio-group {
                flex-direction: column;
                gap: 8px;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 0 10px;
            }

            .form-section {
                padding: 15px;
            }
        }

        /* Auto-calculation styling */
        .auto-calc {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border: 2px dashed #6c757d !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
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
    </div>

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
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Edit Maklumat Pelajar</h1>
            <a href="students.php" class="back-btn">← Kembali ke Senarai</a>
        </div>

        <?php if ($student_data): ?>
        <!-- Current Student Info Card -->
        <div class="student-info-card">
            <h3>Maklumat Semasa Pelajar</h3>
            <div class="student-card-grid">
                <div class="student-card-item">
                    <span>No. IC:</span>
                    <strong><?php echo htmlspecialchars($edit_student_ic); ?></strong>
                </div>
                <div class="student-card-item">
                    <span>Nama:</span>
                    <strong><?php echo htmlspecialchars($student_data['nama']); ?></strong>
                </div>
                <div class="student-card-item">
                    <span>Kelas:</span>
                    <strong><?php echo htmlspecialchars($student_data['nama_kelas']); ?> (Darjah <?php echo $student_data['darjah_kelas']; ?>)</strong>
                </div>
                <div class="student-card-item">
                    <span>Sekolah:</span>
                    <strong><?php echo htmlspecialchars($student_data['nama_sekolah']); ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Messages -->
        <?php if (!empty($success_message)): ?>
        <div class="success">
            ✅ <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>⚠️ Terdapat ralat dalam borang:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form method="POST" class="form-container">
            <!-- Section 1: Student Information -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">1</span>
                    <h3 class="section-title">Maklumat Peribadi Pelajar</h3>
                </div>
                
                <div class="form-grid">
                    <div class="col-6">
                        <div class="form-group">
                            <label>No. IC Pelajar</label>
                            <input type="text" value="<?php echo htmlspecialchars($edit_student_ic); ?>" disabled>
                            <div class="help-text">No. IC tidak boleh diubah setelah pelajar didaftarkan.</div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="nama">Nama Penuh <span class="required">*</span></label>
                            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($nama); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label>Jantina <span class="required">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="lelaki" name="jantina" value="lelaki" <?php echo $jantina == 'lelaki' ? 'checked' : ''; ?> required>
                                    <label for="lelaki">Lelaki</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="perempuan" name="jantina" value="perempuan" <?php echo $jantina == 'perempuan' ? 'checked' : ''; ?> required>
                                    <label for="perempuan">Perempuan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label for="kaum">Kaum <span class="required">*</span></label>
                            <select id="kaum" name="kaum" required>
                                <option value="">Pilih Kaum</option>
                                <?php foreach ($ethnic_groups as $ethnic): ?>
                                <option value="<?php echo $ethnic; ?>" <?php echo $kaum == $ethnic ? 'selected' : ''; ?>><?php echo $ethnic; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label for="warganegara">Warganegara <span class="required">*</span></label>
                            <input type="text" id="warganegara" name="warganegara" value="<?php echo htmlspecialchars($warganegara); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label for="sijil_lahir">No. Sijil Lahir</label>
                            <input type="text" id="sijil_lahir" name="sijil_lahir" value="<?php echo htmlspecialchars($sijil_lahir); ?>">
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <div class="form-group">
                            <label for="status_pelajar">Status Pelajar <span class="required">*</span></label>
                            <select id="status_pelajar" name="status_pelajar" required>
                                <option value="">Pilih Status</option>
                                <option value="kandung" <?php echo $status_pelajar == 'kandung' ? 'selected' : ''; ?>>Anak Kandung</option>
                                <option value="tiri" <?php echo $status_pelajar == 'tiri' ? 'selected' : ''; ?>>Anak Tiri</option>
                                <option value="angkat" <?php echo $status_pelajar == 'angkat' ? 'selected' : ''; ?>>Anak Angkat</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <div class="form-group">
                            <label for="status_penjaga">Status Penjaga <span class="required">*</span></label>
                            <select id="status_penjaga" name="status_penjaga" required>
                                <option value="">Pilih Status</option>
                                <option value="ibu bapa" <?php echo $status_penjaga == 'ibu bapa' ? 'selected' : ''; ?>>Ibu Bapa</option>
                                <option value="ibu tunggal" <?php echo $status_penjaga == 'ibu tunggal' ? 'selected' : ''; ?>>Ibu Tunggal</option>
                                <option value="bapa tunggal" <?php echo $status_penjaga == 'bapa tunggal' ? 'selected' : ''; ?>>Bapa Tunggal</option>
                                <option value="penjaga" <?php echo $status_penjaga == 'penjaga' ? 'selected' : ''; ?>>Penjaga</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <div class="form-group">
                            <label for="id_kelas">Kelas <span class="required">*</span></label>
                            <select id="id_kelas" name="id_kelas" required>
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id_kelas']; ?>" <?php echo $id_kelas == $class['id_kelas'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars('D' . $class['darjah_kelas'] . ' - ' . $class['nama_kelas']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Guardian Information -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">2</span>
                    <h3 class="section-title">Maklumat Waris/Penjaga</h3>
                </div>
                
                <div class="form-grid">
                    <div class="col-4">
                        <div class="form-group">
                            <label for="ic_waris">No. IC Waris <span class="required">*</span></label>
                            <input type="text" id="ic_waris" name="ic_waris" value="<?php echo htmlspecialchars($ic_waris); ?>" maxlength="12" pattern="\d{12}" required>
                            <div class="help-text">12 digit tanpa sengkang (-)</div>
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <div class="form-group">
                            <label for="nama_waris">Nama Waris <span class="required">*</span></label>
                            <input type="text" id="nama_waris" name="nama_waris" value="<?php echo htmlspecialchars($nama_waris); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <div class="form-group">
                            <label for="status_waris">Status Waris <span class="required">*</span></label>
                            <select id="status_waris" name="status_waris" required>
                                <option value="">Pilih Status</option>
                                <option value="bapa" <?php echo $status_waris == 'bapa' ? 'selected' : ''; ?>>Bapa</option>
                                <option value="ibu" <?php echo $status_waris == 'ibu' ? 'selected' : ''; ?>>Ibu</option>
                                <option value="penjaga" <?php echo $status_waris == 'penjaga' ? 'selected' : ''; ?>>Penjaga</option>
                                <option value="datuk" <?php echo $status_waris == 'datuk' ? 'selected' : ''; ?>>Datuk</option>
                                <option value="nenek" <?php echo $status_waris == 'nenek' ? 'selected' : ''; ?>>Nenek</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="nombor_telefon_waris">No. Telefon</label>
                            <input type="tel" id="nombor_telefon_waris" name="nombor_telefon_waris" value="<?php echo htmlspecialchars($nombor_telefon_waris); ?>">
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label for="poskod">Poskod <span class="required">*</span></label>
                            <input type="text" id="poskod" name="poskod" value="<?php echo htmlspecialchars($poskod); ?>" maxlength="5" pattern="\d{5}" required>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label for="negeri">Negeri <span class="required">*</span></label>
                            <select id="negeri" name="negeri" required>
                                <option value="">Pilih Negeri</option>
                                <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state; ?>" <?php echo $negeri == $state ? 'selected' : ''; ?>><?php echo $state; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label for="alamat">Alamat <span class="required">*</span></label>
                            <textarea id="alamat" name="alamat" required><?php echo htmlspecialchars($alamat); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-3">
                        <div class="form-group">
                            <label for="bilangan_tanggungan">Bil. Tanggungan</label>
                            <input type="number" id="bilangan_tanggungan" name="bilangan_tanggungan" value="<?php echo htmlspecialchars($bilangan_tanggungan); ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Income Information -->
            <div class="form-section">
                <div class="section-header">
                    <span class="section-number">3</span>
                    <h3 class="section-title">Maklumat Pendapatan</h3>
                </div>
                
                <div class="form-grid">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pekerjaan_bapa">Pekerjaan Bapa</label>
                            <input type="text" id="pekerjaan_bapa" name="pekerjaan_bapa" value="<?php echo htmlspecialchars($pekerjaan_bapa); ?>">
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pendapatan_bapa">Pendapatan Bapa (RM)</label>
                            <input type="number" id="pendapatan_bapa" name="pendapatan_bapa" value="<?php echo htmlspecialchars($pendapatan_bapa); ?>" min="0" step="0.01" onchange="calculateTotalIncome()">
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
                            <input type="text" id="pekerjaan_ibu" name="pekerjaan_ibu" value="<?php echo htmlspecialchars($pekerjaan_ibu); ?>">
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pendapatan_ibu">Pendapatan Ibu (RM)</label>
                            <input type="number" id="pendapatan_ibu" name="pendapatan_ibu" value="<?php echo htmlspecialchars($pendapatan_ibu); ?>" min="0" step="0.01" onchange="calculateTotalIncome()">
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pekerjaan_penjaga">Pekerjaan Penjaga</label>
                            <input type="text" id="pekerjaan_penjaga" name="pekerjaan_penjaga" value="<?php echo htmlspecialchars($pekerjaan_penjaga); ?>">
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pendapatan_penjaga">Pendapatan Penjaga (RM)</label>
                            <input type="number" id="pendapatan_penjaga" name="pendapatan_penjaga" value="<?php echo htmlspecialchars($pendapatan_penjaga); ?>" min="0" step="0.01" onchange="calculateTotalIncome()">
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="jumlah_pendapatan">Jumlah Pendapatan (RM)</label>
                            <input type="number" id="jumlah_pendapatan" name="jumlah_pendapatan" value="<?php echo htmlspecialchars($jumlah_pendapatan); ?>" min="0" step="0.01" class="auto-calc" readonly>
                            <div class="help-text">Dikira secara automatik</div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="form-group">
                            <label for="pendapatan_perkapita">Pendapatan Per Kapita (RM)</label>
                            <input type="number" id="pendapatan_perkapita" name="pendapatan_perkapita" value="<?php echo htmlspecialchars($pendapatan_perkapita); ?>" min="0" step="0.01" class="auto-calc" readonly>
                            <div class="help-text">Dikira secara automatik</div>
                        </div>
                    </div>
                </div>
                
                <div class="income-info">
                    <strong>Nota:</strong> Jumlah pendapatan dan pendapatan per kapita akan dikira secara automatik berdasarkan pendapatan yang dimasukkan dan bilangan tanggungan.
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <a href="students_additional_edit.php?ic=<?php echo urlencode($edit_student_ic); ?>" class="btn btn-primary">Halaman Maklumat Tambahan</a>
                <a href="students.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Kemaskini Maklumat</button>
            </div>
        </form>
    </div>

    <script>
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

        // Recalculate when bilangan_tanggungan changes
        document.getElementById('bilangan_tanggungan').addEventListener('change', calculateTotalIncome);

        // IC validation
        document.getElementById('ic_waris').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 12);
        });

        // Poskod validation
        document.getElementById('poskod').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 5);
        });

        // Initialize calculations on page load
        calculateTotalIncome();
    </script>
</body>
</html>