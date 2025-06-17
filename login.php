<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'sistem_maklumat_pelajar';
$username = 'root';
$password = '';    

$error_message = '';
$success_message = '';

// Check for logout message
if (isset($_SESSION['logout_message'])) {
    $success_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

// Handle login form submission
if ($_POST) {
    $user_id = trim($_POST['user_id']);
    $password_input = trim($_POST['password']);
    
    if (empty($user_id) || empty($password_input)) {
        $error_message = 'Sila masukkan ID Pengguna dan Kata Laluan.';
    } else {
        try {
            // Connect to database
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Prepare and execute query
            $stmt = $pdo->prepare("SELECT id_pengguna, nama_pengguna, kata_laluan, jenis_pengguna FROM PENGGUNA WHERE id_pengguna = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify user and password
            if ($user && password_verify($password_input, $user['kata_laluan'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id_pengguna'];
                $_SESSION['user_name'] = $user['nama_pengguna'];
                $_SESSION['user_type'] = $user['jenis_pengguna'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'ID Pengguna atau Kata Laluan tidak sah.';
            }
        } catch (PDOException $e) {
            $error_message = 'Ralat pangkalan data: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - Sistem Maklumat Pelajar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            color: #363;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Sistem Maklumat Pelajar</h1>
            <p>Sekolah Agama Bukit Banjar</p>
        </div>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="user_id">ID Pengguna:</label>
                <input type="text" id="user_id" name="user_id" required 
                       value="<?php echo isset($_POST['user_id']) ? htmlspecialchars($_POST['user_id']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Kata Laluan:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Log Masuk</button>
        </form>

        <div class="footer">
            <p>&copy; 2025 SMP Sekolah Agama Bukit Banjar</p>
        </div>
    </div>
</body>
</html>