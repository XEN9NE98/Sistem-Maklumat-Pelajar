<?php
// users.php
// User Management - Main listing page

require_once 'config/database.php';

// Require login
requireLogin();

// Get user information
$user_info = getUserInfo();
$is_admin = isAdmin();

// Only admin can access this page
if (!$is_admin) {
    header("Location: dashboard.php");
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_jenis = isset($_GET['filter_jenis']) ? $_GET['filter_jenis'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause for search and filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nama_pengguna LIKE ? OR p.id_pengguna LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_jenis)) {
    $where_conditions[] = "p.jenis_pengguna = ?";
    $params[] = $filter_jenis;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // Get total records for pagination
    $count_sql = "SELECT COUNT(*) as total FROM pengguna p $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Get users with pagination
    $sql = "
        SELECT p.*
        FROM pengguna p 
        $where_clause
        ORDER BY p.jenis_pengguna DESC, p.nama_pengguna ASC
        LIMIT $records_per_page OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

} catch(PDOException $e) {
    $error_message = "Ralat mengambil data: " . $e->getMessage();
}

// Handle delete action
if (isset($_POST['delete_user'])) {
    $id_pengguna = $_POST['id_pengguna'];
    
    try {
        // Don't allow deleting self
        if ($id_pengguna == $user_info['id']) {
            throw new Exception("Anda tidak boleh memadamkan akaun anda sendiri.");
        }
        
        // Delete user (now allows deleting other admins)
        $stmt = $db->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
        $stmt->execute([$id_pengguna]);
        
        $success_message = "Pengguna berjaya dipadamkan.";
        
        // Refresh page to show updated data
        header("Location: users.php");
        exit();
        
    } catch(Exception $e) {
        $error_message = "Ralat memadamkan pengguna: " . $e->getMessage();
    } catch(PDOException $e) {
        $error_message = "Ralat memadamkan pengguna: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Pengguna - Sistem Maklumat Pelajar</title>
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

        /* User type badges */
        .user-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .user-admin {
            background: #fff3cd;
            color: #856404;
        }

        .user-guru {
            background: #d1ecf1;
            color: #0c5460;
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

        /* Empty state */
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #999;
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
                min-width: 800px;
            }
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

        /* Admin delete warning */
        .admin-warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
            font-size: 13px;
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
                <li><a href="school.php">Sekolah</a></li>
                <li><a href="reports.php">Laporan</a></li>
                <li><a href="users.php" class="active">Pengguna</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Pengurusan Pengguna</h1>
            <a href="users_add.php" class="add-btn">+ Tambah Pengguna Baru</a>
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
                        <label for="search">Cari Pengguna:</label>
                        <input type="text" id="search" name="search" 
                               placeholder="Nama pengguna atau ID pengguna..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="filter_jenis">Jenis Pengguna:</label>
                        <select id="filter_jenis" name="filter_jenis">
                            <option value="">Semua Jenis</option>
                            <option value="admin" <?php echo $filter_jenis == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="guru" <?php echo $filter_jenis == 'guru' ? 'selected' : ''; ?>>Guru</option>
                        </select>
                    </div>
                    <button type="submit" class="search-btn">Cari</button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <?php if (!empty($users)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Pengguna</th>
                        <th>Nama Pengguna</th>
                        <th>Jenis Pengguna</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['id_pengguna']); ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($user['nama_pengguna']); ?></strong>
                            <?php if ($user['id_pengguna'] == $user_info['id']): ?>
                                <span style="color: #667eea; font-size: 11px;">(Anda)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="user-type <?php echo $user['jenis_pengguna'] == 'admin' ? 'user-admin' : 'user-guru'; ?>">
                                <?php echo ucfirst($user['jenis_pengguna']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="users_edit.php?id=<?php echo urlencode($user['id_pengguna']); ?>" 
                                   class="btn-sm btn-edit">Edit</a>
                                
                                <?php if ($user['id_pengguna'] != $user_info['id']): ?>
                                    <button onclick="confirmDelete('<?php echo htmlspecialchars($user['id_pengguna']); ?>', '<?php echo htmlspecialchars($user['nama_pengguna']); ?>', '<?php echo $user['jenis_pengguna']; ?>')" 
                                            class="btn-sm btn-delete">Padam</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <h3>Tiada pengguna dijumpai</h3>
                <p>Cuba ubah kriteria carian atau tambah pengguna baru.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter_jenis=<?php echo urlencode($filter_jenis); ?>">« Sebelum</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_jenis=<?php echo urlencode($filter_jenis); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter_jenis=<?php echo urlencode($filter_jenis); ?>">Seterus »</a>
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
            <p>Adakah anda pasti mahu memadamkan pengguna <strong id="deleteUserName"></strong>?</p>
            <div id="adminWarning" class="admin-warning" style="display: none;">
                <strong>Amaran:</strong> Anda sedang memadamkan akaun pentadbir. Pastikan masih ada pentadbir lain dalam sistem sebelum meneruskan.
            </div>
            <p style="color: #dc3545; font-size: 14px; margin-top: 10px;">
                Tindakan ini tidak boleh dibatalkan.
            </p>
            <div class="modal-buttons">
                <form method="POST" style="display: inline;" id="deleteForm">
                    <input type="hidden" name="id_pengguna" id="deleteUserId">
                    <button type="submit" name="delete_user" class="modal-btn confirm">Ya, Padam</button>
                </form>
                <button onclick="closeDeleteModal()" class="modal-btn cancel">Batal</button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id, name, userType) {
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteUserId').value = id;
            
            // Show admin warning if deleting an admin
            const adminWarning = document.getElementById('adminWarning');
            if (userType === 'admin') {
                adminWarning.style.display = 'block';
            } else {
                adminWarning.style.display = 'none';
            }
            
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeDeleteModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>