<?php
// classes.php
// Class Management - Main listing page

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_darjah = isset($_GET['filter_darjah']) ? $_GET['filter_darjah'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause for search and filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(k.nama_kelas LIKE ? OR k.guru_kelas LIKE ? OR k.id_kelas LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_darjah)) {
    $where_conditions[] = "k.darjah_kelas = ?";
    $params[] = $filter_darjah;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Get total records for pagination
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM KELAS k 
        JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Get classes with pagination and student count
    $sql = "
        SELECT k.*, s.nama_sekolah, s.daerah, s.negeri,
               COUNT(p.ic_pelajar) as jumlah_pelajar
        FROM KELAS k 
        JOIN SEKOLAH s ON k.kod_sekolah = s.kod_sekolah 
        LEFT JOIN PELAJAR p ON k.id_kelas = p.id_kelas
        $where_clause
        GROUP BY k.id_kelas, k.kod_sekolah, k.darjah_kelas, k.nama_kelas, k.guru_kelas, 
                 s.nama_sekolah, s.daerah, s.negeri
        ORDER BY s.nama_sekolah ASC, k.darjah_kelas ASC, k.nama_kelas ASC
        LIMIT $records_per_page OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll();

    // Get filter options
    $darjah_stmt = $db->prepare("SELECT DISTINCT darjah_kelas FROM KELAS ORDER BY darjah_kelas");
    $darjah_stmt->execute();
    $darjah_list = $darjah_stmt->fetchAll();

} catch(PDOException $e) {
    $error_message = "Ralat mengambil data: " . $e->getMessage();
}

// Handle delete action
if (isset($_POST['delete_class'])) {
    $id_kelas = $_POST['id_kelas'];
    
    try {
        $db->beginTransaction();
        
        // Check if class has students
        $check_stmt = $db->prepare("SELECT COUNT(*) as student_count FROM PELAJAR WHERE id_kelas = ?");
        $check_stmt->execute([$id_kelas]);
        $student_count = $check_stmt->fetch()['student_count'];
        
        if ($student_count > 0) {
            throw new Exception("Kelas ini tidak boleh dipadamkan kerana masih mempunyai $student_count orang pelajar.");
        }
        
        // Delete class
        $stmt = $db->prepare("DELETE FROM KELAS WHERE id_kelas = ?");
        $stmt->execute([$id_kelas]);
        
        $db->commit();
        $success_message = "Kelas berjaya dipadamkan.";
        
        // Refresh page to show updated data
        header("Location: classes.php");
        exit();
        
    } catch(Exception $e) {
        $db->rollBack();
        $error_message = "Ralat memadamkan kelas: " . $e->getMessage();
    } catch(PDOException $e) {
        $db->rollBack();
        $error_message = "Ralat memadamkan kelas: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Kelas - Sistem Maklumat Pelajar</title>
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

        .add-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Search and Filter Section */
        .search-filter {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .search-row {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select {
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

        .search-btn {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .search-btn:hover {
            background: #5a6fd8;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e9ecef;
        }

        .table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-sm:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #555;
        }

        .pagination a:hover {
            background: #f0f0f0;
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .student-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
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

            .search-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .nav-menu {
                flex-wrap: wrap;
                gap: 10px;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 700px;
            }
        }

        /* Delete confirmation modal */
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
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        }

        .modal-buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
        }

        .modal-btn.confirm {
            background: #dc3545;
            color: white;
        }

        .modal-btn.cancel {
            background: #6c757d;
            color: white;
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
            <h1 class="page-title">Pengurusan Kelas</h1>
            <a href="classes_add.php" class="add-btn">+ Tambah Kelas Baru</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="search-filter">
            <form method="GET" action="">
                <div class="search-row">
                    <div class="form-group">
                        <label for="search">Cari Kelas:</label>
                        <input type="text" id="search" name="search" 
                               placeholder="Nama kelas, ID kelas atau nama guru..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="filter_darjah">Darjah:</label>
                        <select id="filter_darjah" name="filter_darjah">
                            <option value="">Semua Darjah</option>
                            <?php foreach ($darjah_list as $darjah): ?>
                            <option value="<?php echo $darjah['darjah_kelas']; ?>" 
                                    <?php echo $filter_darjah == $darjah['darjah_kelas'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($darjah['darjah_kelas']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="search-btn">Cari</button>
                </div>
            </form>
        </div>

        <!-- Classes Table -->
        <div class="table-container">
            <?php if (!empty($classes)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Darjah</th>
                        <th>Nama Kelas</th>
                        <th>Guru Kelas</th>
                        <th>Jumlah Pelajar</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['darjah_kelas']); ?></td>
                        <td><?php echo htmlspecialchars($class['nama_kelas']); ?></td>
                        <td><?php echo htmlspecialchars($class['guru_kelas']); ?></td>
                        <td>
                            <span class="student-count">
                                <?php echo $class['jumlah_pelajar']; ?> pelajar
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="classes_edit.php?id=<?php echo urlencode($class['id_kelas']); ?>" 
                                   class="btn-sm btn-edit">Edit</a>
                                <button onclick="confirmDelete('<?php echo htmlspecialchars($class['id_kelas']); ?>', '<?php echo htmlspecialchars($class['nama_kelas']); ?>', <?php echo $class['jumlah_pelajar']; ?>)" 
                                        class="btn-sm btn-delete">Padam</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <h3>Tiada kelas dijumpai</h3>
                <p>Cuba ubah kriteria carian atau tambah kelas baru.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter_school=<?php echo urlencode($filter_school); ?>&filter_darjah=<?php echo urlencode($filter_darjah); ?>">« Sebelum</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_school=<?php echo urlencode($filter_school); ?>&filter_darjah=<?php echo urlencode($filter_darjah); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter_school=<?php echo urlencode($filter_school); ?>&filter_darjah=<?php echo urlencode($filter_darjah); ?>">Seterus »</a>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 10px; color: #666; font-size: 14px;">
            Menunjukkan <?php echo ($offset + 1); ?> hingga <?php echo min($offset + $records_per_page, $total_records); ?> 
            daripada <?php echo $total_records; ?> rekod
        </div>
        <?php endif; ?>
    </main>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Pengesahan Pemadaman</h3>
            <p>Adakah anda pasti mahu memadamkan kelas <strong id="className"></strong>?</p>
            <p id="studentWarning" style="color: #dc3545; font-size: 14px; margin-top: 10px; display: none;">
                Kelas ini mempunyai <span id="studentCount"></span> pelajar dan tidak boleh dipadamkan.
            </p>
            <p id="deleteWarning" style="color: #dc3545; font-size: 14px; margin-top: 10px;">
                Tindakan ini tidak boleh dibatalkan.
            </p>
            <div class="modal-buttons">
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="id_kelas" id="deleteId">
                    <button type="submit" name="delete_class" class="modal-btn confirm" id="confirmDeleteBtn">Ya, Padam</button>
                </form>
                <button onclick="closeModal()" class="modal-btn cancel">Batal</button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id, name, studentCount) {
            document.getElementById('className').textContent = name;
            document.getElementById('deleteId').value = id;
            
            if (studentCount > 0) {
                document.getElementById('studentCount').textContent = studentCount;
                document.getElementById('studentWarning').style.display = 'block';
                document.getElementById('deleteWarning').style.display = 'none';
                document.getElementById('confirmDeleteBtn').style.display = 'none';
            } else {
                document.getElementById('studentWarning').style.display = 'none';
                document.getElementById('deleteWarning').style.display = 'block';
                document.getElementById('confirmDeleteBtn').style.display = 'inline-block';
            }
            
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>