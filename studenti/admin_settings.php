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
    $address = trim($_POST['address'] ?? '');
    $fiscal_address = trim($_POST['fiscal_address'] ?? '');
    $vat_default = isset($_POST['vat_default']) ? (float)$_POST['vat_default'] : null;

    if(empty($service_name) || empty($email)) {
        $msg = "Numele service-ului și Email-ul sunt obligatorii.";
        $msg_type = "error";
    } else {
        // Procesare Upload Logo
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = 'assets/uploads/logos/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $new_filename = 'logo_' . $admin_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                    $logo_path = $target_path;
                }
            } else {
                $msg = "Format fișier invalid. Doar JPG/PNG.";
                $msg_type = "error";
            }
        }

        // Construire Query Dinamic
        $sql = "UPDATE admins SET service_name = ?, email = ?, cui_cif = ?";
        $params = [$service_name, $email, $cui];
        $types = "sss";

        if ($logo_path) {
            $sql .= ", logo_path = ?";
            $params[] = $logo_path;
            $types .= "s";
        }
        // fiscal fields
        $sql .= ", address = ?, fiscal_address = ?";
        $params[] = $address;
        $params[] = $fiscal_address;
        $types .= "ss";
        if ($vat_default !== null) {
            $sql .= ", vat_rate_default = ?";
            $params[] = $vat_default;
            $types .= "d";
        }
        $sql .= " WHERE id = ?";
        $params[] = $admin_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if($stmt->execute()) {
            $msg = "Profilul a fost actualizat cu succes!";
            $msg_type = "success";
            $_SESSION['service_name'] = $service_name;
            if ($logo_path) $_SESSION['logo_path'] = $logo_path;
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
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Setări Cont Service</h1>
            </header>

            <?php if($msg): ?>
                    <div class="form-alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <section class="admin-form animate-on-scroll mb-2">
                
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
                            <label for="address">Adresă Sediu</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="fiscal_address">Adresă Fiscală (dacă diferă)</label>
                            <input type="text" id="fiscal_address" name="fiscal_address" value="<?php echo htmlspecialchars($user['fiscal_address'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="vat_default">TVA Implicit (%)</label>
                            <input type="number" step="0.01" id="vat_default" name="vat_default" value="<?php echo htmlspecialchars($user['vat_rate_default'] ?? '19.00'); ?>">
                        </div>

                    <div class="form-group">
                        <label for="logo">Logo Companie (Opțional)</label>
                        <?php if(!empty($user['logo_path'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars($user['logo_path']); ?>" alt="Logo Curent" class="logo-preview">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="logo" name="logo" accept="image/png, image/jpeg, image/jpg">
                        <small>Formate acceptate: JPG, PNG. Max 2MB.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvează Profilul</button>
                </form>
            </section>

            <section class="admin-form animate-on-scroll mb-2">
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

            <section class="admin-form animate-on-scroll mb-2">
                <h2>Abonament și Facturare</h2>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 5px solid #007bff;">
                <div class="box-info">
                    <p>Tip Abonament: <strong class="text-primary uppercase">Standard (ServiceFlow PRO)</strong></p>
                    <p>Status: <span class="status-active">Activ</span></p>
                    <p><small>Acesta este planul unic pentru toate service-urile partenere.</small></p>
                </div>
            </section>

        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>