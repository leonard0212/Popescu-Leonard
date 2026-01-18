<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

// Check if coming from signup page
if (isset($_GET['success']) && $_GET['success'] == 'created') {
    $success = "Contul a fost creat cu succes! Te poți autentifica.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // 1. Try to authenticate as ADMIN
    $stmt = $conn->prepare("SELECT id, password_hash, service_name, subscription_plan FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Admin found, check password
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Admin Login Successful
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['service_name'] = $user['service_name'];
            $_SESSION['role'] = 'admin';
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Parolă incorectă (Cont Admin).";
        }
    } else {
        // 2. If not Admin, try as CLIENT
        
        // Check if password_hash column exists in clients table to avoid errors
        $check_col = $conn->query("SHOW COLUMNS FROM clients LIKE 'password_hash'");
        
        if($check_col->num_rows > 0) {
            $stmt_client = $conn->prepare("SELECT c.id, c.password_hash, c.full_name, c.admin_id, a.service_name
                                            FROM clients c
                                            LEFT JOIN admins a ON a.id = c.admin_id
                                            WHERE c.email = ?");
            $stmt_client->bind_param("s", $email);
            $stmt_client->execute();
            $result_client = $stmt_client->get_result();

            if ($result_client->num_rows >= 1) {
                $candidates = [];
                while ($row = $result_client->fetch_assoc()) {
                    if (!empty($row['password_hash']) && password_verify($password, $row['password_hash'])) {
                        $candidates[] = [
                            'client_id' => (int)$row['id'],
                            'admin_id' => (int)$row['admin_id'],
                            'client_name' => $row['full_name'],
                            'service_name' => $row['service_name'] ?? 'Service'
                        ];
                    }
                }

                if (count($candidates) === 1) {
                    $sel = $candidates[0];
                    $_SESSION['client_id'] = $sel['client_id'];
                    $_SESSION['client_name'] = $sel['client_name'];
                    $_SESSION['admin_id'] = $sel['admin_id'];
                    $_SESSION['role'] = 'client';

                    header("Location: client_dashboard.php");
                    exit();
                } elseif (count($candidates) > 1) {
                    // Multiple valid client accounts with same email/password across services
                    // Store candidates in a temporary session key and redirect to selection
                    $_SESSION['login_candidates'] = $candidates;
                    header('Location: select_service.php');
                    exit();
                } else {
                    $error = "Parolă incorectă pentru contul client sau contul nu are parolă setată.";
                }
            } else {
                $error = "Nu există un cont cu acest email.";
            }
        } else {
            // Client login not supported yet
            $error = "Nu există un cont de administrator cu acest email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body id="top">
    <div class="form-container animate-on-scroll">
        <div class="form-logo">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="ServiceFlow Logo" class="login-logo">
            </a>
        </div>
        <h1>Autentificare</h1>
        
        <?php if($success): ?>
                <div class="form-alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
                <div class="form-alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Adresa de Email</label>
                <input type="email" id="email" name="email" required placeholder="admin@service.ro sau client@yahoo.com">
            </div>
            <div class="form-group">
                <label for="password">Parolă</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Intră în cont</button>
            <p>Ești un service nou? <a href="signup.php">Creează cont de Admin</a></p>
        </form>
    </div>

    <script src="js/animations.js"></script>
</body>
</html>