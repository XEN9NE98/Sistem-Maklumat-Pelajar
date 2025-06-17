<?php
// school.php
// School information management page

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle form submission (only for admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_admin) {
    try {
        // Check if school data exists
        $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM SEKOLAH");
        $check_stmt->execute();
        $school_exists = $check_stmt->fetch()['count'] > 0;
        
        if ($school_exists) {
            // Update existing school data
            $stmt = $db->prepare("
                UPDATE SEKOLAH SET 
                    nama_sekolah = ?, 
                    nama_guru_besar = ?, 
                    alamat_sekolah = ?, 
                    poskod = ?, 
                    daerah = ?, 
                    negeri = ?, 
                    email = ?, 
                    no_telefon = ?, 
                    tarikh_tubuh_sekolah = ?, 
                    jenis_sekolah = ?, 
                    sesi = ?
                WHERE kod_sekolah = ?
            ");
            
            $stmt->execute([
                $_POST['nama_sekolah'],
                $_POST['nama_guru_besar'],
                $_POST['alamat_sekolah'],
                $_POST['poskod'],
                $_POST['daerah'],
                $_POST['negeri'],
                $_POST['email'] ?: null,
                $_POST['no_telefon'] ?: null,
                $_POST['tarikh_tubuh_sekolah'] ?: null,
                $_POST['jenis_sekolah'],
                $_POST['sesi'],
                $_POST['kod_sekolah']
            ]);
        } else {
            // Insert new school data
            $stmt = $db->prepare("
                INSERT INTO SEKOLAH (
                    kod_sekolah, nama_sekolah, nama_guru_besar, alamat_sekolah, 
                    poskod, daerah, negeri, email, no_telefon, tarikh_tubuh_sekolah, 
                    jenis_sekolah, sesi
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_POST['kod_sekolah'],
                $_POST['nama_sekolah'],
                $_POST['nama_guru_besar'],
                $_POST['alamat_sekolah'],
                $_POST['poskod'],
                $_POST['daerah'],
                $_POST['negeri'],
                $_POST['email'] ?: null,
                $_POST['no_telefon'] ?: null,
                $_POST['tarikh_tubuh_sekolah'] ?: null,
                $_POST['jenis_sekolah'],
                $_POST['sesi']
            ]);
        }
        
        $success_message = "Maklumat sekolah berjaya dikemaskini.";
        
    } catch(PDOException $e) {
        $error_message = "Ralat menyimpan data: " . $e->getMessage();
    }
}

// Get school data
$school_data = null;
try {
    $stmt = $db->prepare("SELECT * FROM SEKOLAH LIMIT 1");
    $stmt->execute();
    $school_data = $stmt->fetch();
} catch(PDOException $e) {
    $error_message = "Ralat mengambil data sekolah: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sekolah - Sistem Maklumat Pelajar</title>
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
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        /* Form Container */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-grid {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:disabled,
        .form-group textarea:disabled,
        .form-group select:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Two column layout */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Buttons */
        .form-actions {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Messages */
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        /* Info Box */
        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #bee5eb;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .status-view {
            background: #17a2b8;
            color: white;
        }

        .status-edit {
            background: #28a745;
            color: white;
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

            .form-row {
                grid-template-columns: 1fr;
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
                <li><a href="classes.php">Kelas</a></li>
                <li><a href="school.php" class="active">Sekolah</a></li>
                <?php if ($is_admin): ?>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="users.php">Pengguna</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h2>Maklumat Sekolah</h2>
            <p>
                <?php if ($is_admin): ?>
                    Kemaskini maklumat am sekolah
                    <span class="status-badge status-edit">Mode Edit</span>
                <?php else: ?>
                    Paparan maklumat am sekolah
                    <span class="status-badge status-view">Mode Lihat</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if ($success_message): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!$school_data && !$is_admin): ?>
            <div class="info-box">
                <strong>Maklumat Sekolah Belum Dikonfigurasi</strong><br>
                Sila hubungi pentadbir sistem untuk mengemaskini maklumat sekolah.
            </div>
        <?php endif; ?>

        <!-- School Form -->
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-grid">
                    <!-- School Code and Name -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="kod_sekolah">Kod Sekolah *</label>
                            <input type="text" 
                                   id="kod_sekolah" 
                                   name="kod_sekolah" 
                                   value="<?php echo htmlspecialchars($school_data['kod_sekolah'] ?? ''); ?>" 
                                   required 
                                   maxlength="20"
                                   <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label for="nama_sekolah">Nama Sekolah *</label>
                            <input type="text" 
                                   id="nama_sekolah" 
                                   name="nama_sekolah" 
                                   value="<?php echo htmlspecialchars($school_data['nama_sekolah'] ?? ''); ?>" 
                                   required 
                                   maxlength="200"
                                   <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        </div>
                    </div>

                    <!-- Principal Name -->
                    <div class="form-group">
                        <label for="nama_guru_besar">Nama Guru Besar *</label>
                        <input type="text" 
                               id="nama_guru_besar" 
                               name="nama_guru_besar" 
                               value="<?php echo htmlspecialchars($school_data['nama_guru_besar'] ?? ''); ?>" 
                               required 
                               maxlength="100"
                               <?php echo !$is_admin ? 'disabled' : ''; ?>>
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label for="alamat_sekolah">Alamat Sekolah *</label>
                        <textarea id="alamat_sekolah" 
                                  name="alamat_sekolah" 
                                  required
                                  <?php echo !$is_admin ? 'disabled' : ''; ?>><?php echo htmlspecialchars($school_data['alamat_sekolah'] ?? ''); ?></textarea>
                    </div>

                    <!-- Location Details -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="poskod">Poskod *</label>
                            <input type="number" 
                                   id="poskod" 
                                   name="poskod" 
                                   value="<?php echo htmlspecialchars($school_data['poskod'] ?? ''); ?>" 
                                   required 
                                   min="10000" 
                                   max="99999"
                                   <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label for="daerah">Daerah *</label>
                            <input type="text" 
                                   id="daerah" 
                                   name="daerah" 
                                   value="<?php echo htmlspecialchars($school_data['daerah'] ?? ''); ?>" 
                                   required 
                                   maxlength="50"
                                   <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="negeri">Negeri *</label>
                        <select id="negeri" name="negeri" required <?php echo !$is_admin ? 'disabled' : ''; ?>>
                            <option value="">Pilih Negeri</option>
                            <?php
                            $negeri_list = [
                                'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan',
                                'Pahang', 'Pulau Pinang', 'Perak', 'Perlis', 'Sabah',
                                'Sarawak', 'Selangor', 'Terengganu', 'Wilayah Persekutuan Kuala Lumpur',
                                'Wilayah Persekutuan Labuan', 'Wilayah Persekutuan Putrajaya'
                            ];
                            foreach ($negeri_list as $negeri) {
                                $selected = (isset($school_data['negeri']) && $school_data['negeri'] == $negeri) ? 'selected' : '';
                                echo "<option value=\"$negeri\" $selected>$negeri</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($school_data['email'] ?? ''); ?>" 
                                   maxlength="100"
                                   <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        </div>
                        <div class="form-group">
                            <label for="no_telefon">No. Telefon</label>
                            <input type="text" 
                                   id="no_telefon" 
                                   name="no_telefon" 
                                   value="<?php echo htmlspecialchars($school_data['no_telefon'] ?? ''); ?>" 
                                   maxlength="20"
                                   <?php echo !$is_admin ? 'disabled' : ''; ?>>
                        </div>
                    </div>

                    <!-- School Details -->
                    <div class="form-group">
                        <label for="tarikh_tubuh_sekolah">Tarikh Tubuh Sekolah</label>
                        <input type="date" 
                               id="tarikh_tubuh_sekolah" 
                               name="tarikh_tubuh_sekolah" 
                               value="<?php echo htmlspecialchars($school_data['tarikh_tubuh_sekolah'] ?? ''); ?>"
                               <?php echo !$is_admin ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="jenis_sekolah">Jenis Sekolah *</label>
                            <select id="jenis_sekolah" name="jenis_sekolah" required <?php echo !$is_admin ? 'disabled' : ''; ?>>
                                <option value="">Pilih Jenis Sekolah</option>
                                <?php
                                $jenis_options = ['Sekolah Agama Petang', 'Sekolah Agama Pagi Petang'];
                                foreach ($jenis_options as $jenis) {
                                    $selected = (isset($school_data['jenis_sekolah']) && $school_data['jenis_sekolah'] == $jenis) ? 'selected' : '';
                                    echo "<option value=\"$jenis\" $selected>$jenis</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sesi">Sesi *</label>
                            <select id="sesi" name="sesi" required <?php echo !$is_admin ? 'disabled' : ''; ?>>
                                <option value="">Pilih Sesi</option>
                                <?php
                                $sesi_options = ['pagi', 'petang', 'penuh'];
                                foreach ($sesi_options as $sesi) {
                                    $selected = (isset($school_data['sesi']) && $school_data['sesi'] == $sesi) ? 'selected' : '';
                                    echo "<option value=\"$sesi\" $selected>" . ucfirst($sesi) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <?php if ($is_admin): ?>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $school_data ? 'Kemaskini Maklumat' : 'Simpan Maklumat'; ?>
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>
</body>
</html>