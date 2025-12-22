<?php
session_start();
require_once 'db_connect.php';

// Verificare autentificare
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$msg = '';
$msg_type = '';

// --- 1. PROCESARE FORMULAR DATE GENERALE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $service_name = trim($_POST['service_name']);
    $email = trim($_POST['email']);
    $cui = trim($_POST['cui']);

    if(empty($service_name) || empty($email)) {
        $msg = "Numele service-ului și Email-ul sunt obligatorii.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE admins SET service_name = ?, email = ?, cui_cif = ? WHERE id = ?");
        $stmt->bind_param("sssi", $service_name, $email, $cui, $admin_id);
        
        if($stmt->execute()) {
            $msg = "Profilul a fost actualizat cu succes!";
            $msg_type = "success";
            // Actualizăm și sesiunea pentru a reflecta noul nume imediat
            $_SESSION['service_name'] = $service_name;
        } else {
            $msg = "Eroare la actualizare: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// --- 2. PROCESARE SCHIMBARE PAROLĂ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if(empty($new_pass) || strlen($new_pass) < 6) {
        $msg = "Parola nouă trebuie să aibă minim 6 caractere.";
        $msg_type = "error";
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "Parolele nu coincid.";
        $msg_type = "error";
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $admin_id);
        
        if($stmt->execute()) {
            $msg = "Parola a fost schimbată cu succes!";
            $msg_type = "success";
        }
    }
}

// --- 3. PRELUARE DATE CURENTE ---
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setări Cont - Admin ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <h1>Setări Cont Service</h1>
            </header>

            <?php if($msg): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: white; background-color: <?php echo ($msg_type == 'success') ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <section class="admin-form animate-on-scroll" style="margin-bottom: 2rem;">
                <h2>Profilul Companiei</h2>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="nume_service">Nume Service</label>
                        <input type="text" id="nume_service" name="service_name" value="<?php echo htmlspecialchars($user['service_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Administrator</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cui">CUI / CIF</label>
                        <input type="text" id="cui" name="cui" value="<?php echo htmlspecialchars($user['cui_cif']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo Companie (Opțional)</label>
                        <input type="file" id="logo" name="logo" disabled>
                        <small>Funcția de upload logo va fi disponibilă în curând.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvează Profilul</button>
                </form>
            </section>

            <section class="admin-form animate-on-scroll" style="margin-bottom: 2rem;">
                <h2>Schimbare Parolă</h2>
                <form action="" method="POST">
                    <input type="hidden" name="update_password" value="1">
                    <div class="form-group">
                        <label>Parola Nouă</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirmă Parola</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Schimbă Parola</button>
                </form>
            </section>
            
            <section class="admin-form animate-on-scroll" style="margin-bottom: 2rem;">
                <h2>Abonament și Facturare</h2>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 5px solid #007bff;">
                    <p>Plan actual: <strong style="text-transform: uppercase; color: #007bff;"><?php echo $user['subscription_plan']; ?></strong></p>
                    <p>Status: <span style="color: green; font-weight: bold;">Activ</span></p>
                </div>
            </section>

        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>