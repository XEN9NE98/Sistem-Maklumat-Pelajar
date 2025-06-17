<?php
// dashboard.php
// Main dashboard for School Student Information System

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get dashboard statistics
$stats = [];

try {
    // Total students
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM PELAJAR");
    $stmt->execute();
    $stats['total_students'] = $stmt->fetch()['total'];
    
    // Total teachers
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM PENGGUNA WHERE jenis_pengguna = 'guru'");
    $stmt->execute();
    $stats['total_teachers'] = $stmt->fetch()['total'];
    
    // Total classes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM KELAS");
    $stmt->execute();
    $stats['total_classes'] = $stmt->fetch()['total'];
    
    // Total users
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM PENGGUNA");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Students by gender
    $stmt = $db->prepare("SELECT jantina, COUNT(*) as total FROM PELAJAR GROUP BY jantina");
    $stmt->execute();
    $gender_stats = $stmt->fetchAll();
    
    // Recent students (last 5)
    $stmt = $db->prepare("
        SELECT p.nama, p.ic_pelajar, k.nama_kelas, k.darjah_kelas, s.nama_sekolah 
        FROM PELAJAR p 
        JOIN KELAS k ON p.id_kelas = k.id_kelas 
        JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
        ORDER BY p.ic_pelajar DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_students = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Ralat mengambil data: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Maklumat Pelajar</title>
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.students { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.teachers { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.classes { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-icon.users { background: linear-gradient(135deg, #43e97b, #38f9d7); }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Content Sections */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section h3 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Tables */
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            gap: 15px;
        }

        .action-btn {
            display: block;
            padding: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }

        /* Gender Chart */
        .gender-chart {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .gender-item {
            flex: 1;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
        }

        .gender-item.male {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .gender-item.female {
            background: linear-gradient(135deg, #f093fb, #f5576c);
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

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="students.php">Pelajar</a></li>
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
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon students">👨‍🎓</div>
                <div class="stat-number"><?php echo number_format($stats['total_students'] ?? 0); ?></div>
                <div class="stat-label">Jumlah Pelajar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teachers">🧑‍🏫</div>
                <div class="stat-number"><?php echo number_format($stats['total_teachers'] ?? 0); ?></div>
                <div class="stat-label">Jumlah Guru</div>
            </div>            
            <div class="stat-card">
                <div class="stat-icon classes">📚</div>
                <div class="stat-number"><?php echo number_format($stats['total_classes'] ?? 0); ?></div>
                <div class="stat-label">Jumlah Kelas</div>
            </div>
            <?php if ($is_admin): ?>
            <div class="stat-card">
                <div class="stat-icon users">🤵</div>
                <div class="stat-number"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                <div class="stat-label">Jumlah Pengguna</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Students -->
            <div class="section">
                <h3>Pelajar Terkini</h3>
                <?php if (!empty($recent_students)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Pelajar</th>
                            <th>No. IC</th>
                            <th>Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['nama']); ?></td>
                            <td><?php echo htmlspecialchars($student['ic_pelajar']); ?></td>
                            <td><?php echo htmlspecialchars('D' . $student['darjah_kelas'] . ' - ' . $student['nama_kelas']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Tiada data pelajar untuk dipaparkan.</p>
                <?php endif; ?>
            </div>

            <!-- Quick Actions & Gender Stats -->
            <div>
                <!-- Gender Statistics -->
                <?php if (!empty($gender_stats)): ?>
                <div class="section" style="margin-bottom: 30px;">
                    <h3>Statistik Jantina Pelajar</h3>
                    <div class="gender-chart">
                        <?php foreach ($gender_stats as $stat): ?>
                        <div class="gender-item <?php echo $stat['jantina'] == 'lelaki' ? 'male' : 'female'; ?>">
                            <div style="font-size: 24px; font-weight: bold;"><?php echo $stat['total']; ?></div>
                            <div><?php echo ucfirst($stat['jantina']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="section">
                    <h3>Tindakan Pantas</h3>
                    <div class="quick-actions">
                        <a href="students_add.php" class="action-btn">
                            <i>➕</i>
                            Tambah Pelajar Baru
                        </a>
                        <a href="classes_add.php" class="action-btn">
                            <i>📚</i>
                            Tambah Kelas Baru
                        </a>
                        <?php if ($is_admin): ?>
                        <a href="reports.php" class="action-btn">
                            <i>📊</i>
                            Jana Laporan
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>