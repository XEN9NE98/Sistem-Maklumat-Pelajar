<?php
// config/database.php
// Database configuration for Religious School Student Information System

class Database {
    private $host = 'localhost';
    private $db_name = 'sistem_maklumat_pelajar';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }
        return $this->conn;
    }
}

// Function to create initial admin user
function createInitialUsers() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if admin user already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM PENGGUNA WHERE id_pengguna = 'admin'");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            // Create admin user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO PENGGUNA (id_pengguna, nama_pengguna, kata_laluan, jenis_pengguna) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', 'Administrator', $admin_password, 'admin']);
            echo "Admin user created successfully!<br>";
            echo "Username: admin<br>";
            echo "Password: admin123<br><br>";
        }
        
        // Create sample teacher user
        $stmt = $db->prepare("SELECT COUNT(*) FROM PENGGUNA WHERE id_pengguna = 'guru001'");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $teacher_password = password_hash('guru123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO PENGGUNA (id_pengguna, nama_pengguna, kata_laluan, jenis_pengguna) VALUES (?, ?, ?, ?)");
            $stmt->execute(['guru001', 'Cikgu Ahmad bin Ali', $teacher_password, 'guru']);
            echo "Teacher user created successfully!<br>";
            echo "Username: guru001<br>";
            echo "Password: guru123<br><br>";
        }
        
        echo "Initial users setup completed!";
        
    } catch(PDOException $e) {
        echo "Error creating users: " . $e->getMessage();
    }
}

// Session management functions
function startSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    startSecureSession();
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function getUserInfo() {
    startSecureSession();
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'type' => $_SESSION['user_type']
        ];
    }
    return null;
}

function logout() {
    startSecureSession();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Uncomment the line below to create initial users (run this once)
// createInitialUsers();
?>