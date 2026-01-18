<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_admin();

// --- INCLUDERE PHPMAILER ---
// Asigură-te că ai folderul 'libs' cu cele 3 fișiere (Exception.php, PHPMailer.php, SMTP.php)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/Exception.php';
require 'libs/PHPMailer.php';
require 'libs/SMTP.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$msg = '';
$msg_type = '';

// =========================================================
// LOGICA 1: SALVARE SETĂRI SMTP
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_smtp') {
    $host = trim($_POST['smtp_host']);
    $user = trim($_POST['smtp_user']);
    $pass = trim($_POST['smtp_pass']); 
    $port = intval($_POST['smtp_port']);

    $stmt = $conn->prepare("UPDATE admins SET smtp_host=?, smtp_user=?, smtp_pass=?, smtp_port=? WHERE id=?");
    $stmt->bind_param("sssii", $host, $user, $pass, $port, $admin_id);
    
    if ($stmt->execute()) {
        $msg = "Setările de email au fost salvate cu succes!";
        $msg_type = "success";
    } else {
        $msg = "Eroare la salvare: " . $conn->error;
        $msg_type = "error";
    }
}

// =========================================================
// LOGICA 2: TRIMITE CAMPANIE (REALĂ)
// =========================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'send_campaign') {
    
    // 1. Preluăm datele campaniei
    $nume_intern = trim($_POST['name']);
    $subiect = trim($_POST['subject']);
    $mesaj_body = trim($_POST['message_content']);
    $raw_audience = isset($_POST['target_audience']) ? $_POST['target_audience'] : [];
    if (!is_array($raw_audience)) $raw_audience = [$raw_audience];

    // Procesare audiență manuală
    $manual_ids = [];
    if (in_array('manual', $raw_audience, true)) {
        $manual_ids = isset($_POST['manual_clients']) && is_array($_POST['manual_clients']) ? array_map('intval', $_POST['manual_clients']) : [];
    }
    $audienta_json = json_encode(['selected' => $raw_audience, 'manual_ids' => $manual_ids]);

    // 2. Preluăm datele SMTP ale adminului
    $stmt_smtp = $conn->prepare("SELECT smtp_host, smtp_user, smtp_pass, smtp_port FROM admins WHERE id = ?");
    $stmt_smtp->bind_param("i", $admin_id);
    $stmt_smtp->execute();
    $smtp_conf = $stmt_smtp->get_result()->fetch_assoc();

    // Verificăm configurarea
    if (empty($smtp_conf['smtp_user']) || empty($smtp_conf['smtp_pass'])) {
        $msg = "Eroare: Nu ai configurat adresa de email! Apasă pe butonul 'Configurează Email' de mai jos.";
        $msg_type = "error";
    } else {
        // 3. Salvăm campania în DB
        $insert_sql = "INSERT INTO marketing_campaigns (admin_id, name, subject, target_audience, message_content, sent_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_ins = $conn->prepare($insert_sql);
        $stmt_ins->bind_param("issss", $admin_id, $nume_intern, $subiect, $audienta_json, $mesaj_body);
        $stmt_ins->execute();

        // 4. Determinam destinatarii (Simplificat pentru demo: ia toți clienții cu email valid)
        $emails_to_send = [];
        $stmt_clients = $conn->prepare("SELECT email, full_name FROM clients WHERE admin_id = ? AND email IS NOT NULL AND email != ''");
        $stmt_clients->bind_param("i", $admin_id);
        $stmt_clients->execute();
        $res_clients = $stmt_clients->get_result();

        // 5. Configurăm PHPMailer și trimitem
        $mail = new PHPMailer(true);
        $errors = [];
        $sent_count = 0;

        try {
            // Configurare Server
            $mail->isSMTP();
            $mail->Host       = $smtp_conf['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_conf['smtp_user'];
            $mail->Password   = $smtp_conf['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
            $mail->Port       = $smtp_conf['smtp_port'];

            // Expeditor
            $mail->setFrom($smtp_conf['smtp_user'], 'Service Auto');
            $mail->isHTML(true);
            $mail->Subject = $subiect;

            while ($client = $res_clients->fetch_assoc()) {
                $mail->clearAddresses();
                $mail->addAddress($client['email'], $client['full_name']);
                $mail->Body = $mesaj_body; 

                try {
                    $mail->send();
                    $sent_count++;
                } catch (Exception $e) {
                    $errors[] = $client['email'] . ": " . $mail->ErrorInfo;
                }
            }
            
            $msg = "Campanie trimisă! ($sent_count emailuri reușite).";
            if(count($errors) > 0) $msg .= " Erori: " . count($errors);
            $msg_type = "success";

        } catch (Exception $e) {
            $msg = "Eroare conectare SMTP: " . $mail->ErrorInfo;
            $msg_type = "error";
        }
    }
}

// --- PRELUARE DATE PENTRU FORMULARE ---
// Istoric
$hist_stmt = $conn->prepare("SELECT * FROM marketing_campaigns WHERE admin_id = ? ORDER BY created_at DESC");
$hist_stmt->bind_param("i", $admin_id);
$hist_stmt->execute();
$history = $hist_stmt->get_result();

// Lista Clienți
$clients_stmt = $conn->prepare("SELECT id, full_name FROM clients WHERE admin_id = ? ORDER BY full_name ASC");
$clients_stmt->bind_param("i", $admin_id);
$clients_stmt->execute();
$clients_result = $clients_stmt->get_result();

// Setări SMTP curente
$stmt_current_smtp = $conn->prepare("SELECT smtp_host, smtp_user, smtp_pass, smtp_port FROM admins WHERE id = ?");
$stmt_current_smtp->bind_param("i", $admin_id);
$stmt_current_smtp->execute();
$current_smtp = $stmt_current_smtp->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Marketing - Admin ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        /* Stiluri pentru zona de setări */
        .smtp-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: none;
        }
        .smtp-box.visible { display: block; }
        
        /* --- FIX PENTRU INPUTURI (Port, Text, Email) --- */
        .smtp-box input[type="text"],
        .smtp-box input[type="email"],
        .smtp-box input[type="password"],
        .smtp-box input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-top: 5px;
            box-sizing: border-box; /* Aliniere corectă */
            font-size: 1rem;
        }

        .toggle-btn {
            background: #6c757d;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-block;
        }

        /* Stiluri pentru Tooltip (?) */
        .tooltip-icon {
            display: inline-block;
            margin-left: 5px;
            cursor: help;
            color: #007bff;
            font-weight: bold;
            font-size: 1.1em;
        }

        .tooltip-wrapper {
            position: relative;
            display: inline-block;
        }

        .tooltip-content {
            visibility: hidden;
            width: 280px;
            background-color: #333;
            color: #fff;
            text-align: left;
            border-radius: 6px;
            padding: 12px;
            position: absolute;
            z-index: 100;
            bottom: 135%; /* Apare deasupra iconiței */
            left: 50%;
            margin-left: -140px; /* Centrare */
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.85rem;
            line-height: 1.4;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            font-weight: normal;
        }

        .tooltip-wrapper:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }

        /* Săgeată mică sub tooltip */
        .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        /* Responsive improvements for marketing page on small screens */
        @media (max-width: 768px) {
            .toggle-btn {
                width: 100%;
                text-align: left;
            }
            .smtp-box {
                padding: 12px;
            }
            .form-row {
                flex-direction: column !important;
            }
            .smtp-box input, .smtp-box textarea, .admin-form .form-group input, .admin-form .form-group select, .admin-form .form-group textarea {
                font-size: 1rem;
            }
            .btn {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
    <script>
        function toggleSmtp() {
            var box = document.getElementById('smtpSettings');
            if (box.style.display === 'none' || box.style.display === '') {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }
    </script>
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Comunicare & Marketing</h1>
            </header>

            <?php if($msg): ?>
                <div class="form-alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <button type="button" class="btn toggle-btn" onclick="toggleSmtp()">
                <i class="fas fa-cog"></i> Configurează Email (<?php echo !empty($current_smtp['smtp_user']) ? 'Conectat: '.$current_smtp['smtp_user'] : 'Neconectat'; ?>)
            </button>

            <div id="smtpSettings" class="smtp-box">
                <h3>Conectare Adresă Email (SMTP)</h3>
                
                <form action="" method="POST">
                    <input type="hidden" name="action" value="save_smtp">
                    
                    <div class="form-group">
                        <label>Server SMTP</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($current_smtp['smtp_host'] ?? 'smtp.gmail.com'); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label>User Email</label>
                            <input type="email" name="smtp_user" value="<?php echo htmlspecialchars($current_smtp['smtp_user'] ?? ''); ?>" placeholder="adresa.ta@gmail.com" required>
                        </div>
                        
                        <div class="form-group flex-1">
                            <label>
                                Parolă (App Password)
                                <div class="tooltip-wrapper">
                                    <span class="tooltip-icon"><i class="fas fa-info-circle"></i></span>
                                    <div class="tooltip-content">
                                        <strong><i class="fas fa-exclamation-triangle"></i> Nu folosi parola ta normală de Google!</strong><br><br>
                                        Pentru Gmail, trebuie să generezi o parolă specială:<br>
                                        1. Intră în <strong>Contul Google</strong> -> <strong>Security</strong>.<br>
                                        2. Activează <strong>2-Step Verification</strong>.<br>
                                        3. Caută opțiunea <strong>"App Passwords"</strong>.<br>
                                        4. Generează una nouă (nume: "ServiceApp") și copiaz-o aici.<br>
                                        <hr class="thin-hr">
                                        <strong>Port recomandat:</strong> 587
                                    </div>
                                </div>
                            </label>
                            <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($current_smtp['smtp_pass'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group w-100">
                            <label>Port</label>
                            <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($current_smtp['smtp_port'] ?? '587'); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Salvează Configurare</button>
                </form>
            </div>

            <section class="admin-form animate-on-scroll mb-2">
                <h2>Creează Campanie Nouă</h2>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="send_campaign">
                    
                    <div class="form-group">
                        <label>Nume Intern Campanie</label>
                        <input type="text" name="name" placeholder="ex: Promoție Crăciun" required>
                    </div>
                    <div class="form-group">
                        <label>Subiect Email</label>
                        <input type="text" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>Audiență</label>
                        <select name="target_audience[]" multiple size="4">
                            <option value="all">Toți Clienții</option>
                            <option value="active_clients">Clienți Activi</option>
                            <option value="manual">Selectare Manuală</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Selectare Manuală (Opțional)</label>
                        <select name="manual_clients[]" multiple size="4">
                            <?php if ($clients_result && $clients_result->num_rows > 0): ?>
                                <?php while($c = $clients_result->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option disabled>Niciun client disponibil</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mesaj</label>
                        <textarea name="message_content" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Trimite Campania (REAL)</button>
                </form>
            </section>

            <section class="admin-form animate-on-scroll">
                <h2>Istoric Campanii</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Nume</th>
                            <th>Subiect</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history && $history->num_rows > 0): ?>
                            <?php while($row = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><span class="status-sent">Trimis</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">Nu există campanii.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>