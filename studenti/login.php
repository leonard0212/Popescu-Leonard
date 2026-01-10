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
            $stmt_client = $conn->prepare("SELECT id, password_hash, full_name FROM clients WHERE email = ?");
            $stmt_client->bind_param("s", $email);
            $stmt_client->execute();
            $result_client = $stmt_client->get_result();

            if ($result_client->num_rows === 1) {
                $client = $result_client->fetch_assoc();
                // Check if client has a password set
                if (!empty($client['password_hash']) && password_verify($password, $client['password_hash'])) {
                    // Client Login Successful
                    $_SESSION['client_id'] = $client['id'];
                    $_SESSION['client_name'] = $client['full_name'];
                    $_SESSION['role'] = 'client';

                    header("Location: client_dashboard.php");
                    exit();
                } else {
                    $error = "Date de autentificare incorecte sau contul nu are o parolă setată.";
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
    <title>Autentificare - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body id="top">
    <div class="form-container animate-on-scroll">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="ServiceHub Logo" class="login-logo">
            </a>
        </div>
        <h1>Autentificare</h1>
        
        <?php if($success): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
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