<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_admin();

// Verificare autentificare
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$success_msg = '';
$error_msg = '';

// --- 0. LOGICA PENTRU SALVARE SETĂRI EMAIL (SMTP) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_smtp') {
    $host = trim($_POST['smtp_host']);
    $user = trim($_POST['smtp_user']);
    $pass = trim($_POST['smtp_pass']); 
    $port = intval($_POST['smtp_port']);

    if(empty($user) || empty($pass)) {
        $error_msg = "Adresa de email și parola sunt obligatorii!";
    } else {
        $stmt = $conn->prepare("UPDATE admins SET smtp_host=?, smtp_user=?, smtp_pass=?, smtp_port=? WHERE id=?");
        $stmt->bind_param("sssii", $host, $user, $pass, $port, $admin_id);
        
        if ($stmt->execute()) {
            $success_msg = "Setările de email au fost salvate cu succes! Acum automatizările pot trimite mesaje.";
        } else {
            $error_msg = "Eroare la salvare: " . $conn->error;
        }
    }
}

// --- 1. PROCESARE FORMULAR ADĂUGARE REGULĂ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rule'])) {
    $rule_name = trim($_POST['rule_name']);
    $trigger_type = $_POST['trigger_type'];
    $timing_type = $_POST['timing_type'];
    $days_offset = (int)$_POST['days_offset'];
    $subject = trim($_POST['subject']);
    $message_body = trim($_POST['message_body']);

    if (empty($rule_name) || empty($subject) || empty($message_body)) {
        $error_msg = "Toate câmpurile pentru regulă sunt obligatorii.";
    } else {
        $stmt = $conn->prepare("INSERT INTO notification_rules (admin_id, rule_name, trigger_type, timing_type, days_offset, subject, message_body) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssiss", $admin_id, $rule_name, $trigger_type, $timing_type, $days_offset, $subject, $message_body);

        if ($stmt->execute()) {
            $success_msg = "Regula a fost adăugată cu succes.";
        } else {
            $error_msg = "Eroare la adăugarea regulii: " . $stmt->error;
        }
    }
}

// --- 2. PROCESARE TOGGLE STATUS (ON/OFF) ---
if (isset($_GET['toggle_id'])) {
    $rule_id = (int)$_GET['toggle_id'];
    $current_status = (int)$_GET['current_status'];
    $new_status = $current_status ? 0 : 1; // Flip status

    $stmt = $conn->prepare("UPDATE notification_rules SET is_active = ? WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("iii", $new_status, $rule_id, $admin_id);
    $stmt->execute();
    header("Location: admin_automations.php");
    exit();
}

// --- 3. ȘTERGERE REGULĂ ---
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM notification_rules WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $del_id, $admin_id);
    if($stmt->execute()) {
        header("Location: admin_automations.php?msg=deleted");
        exit();
    }
}

// --- PRELUARE DATE PENTRU AFIȘARE ---
$stmt_rules = $conn->prepare("SELECT * FROM notification_rules WHERE admin_id = ? ORDER BY created_at DESC");
$stmt_rules->bind_param("i", $admin_id);
$stmt_rules->execute();
$result_rules = $stmt_rules->get_result();

$stmt_logs = $conn->prepare("
    SELECT l.sent_at, r.rule_name, c.full_name as client_name
    FROM notification_logs l
    JOIN notification_rules r ON l.rule_id = r.id
    JOIN clients c ON l.client_id = c.id
    WHERE l.admin_id = ?
    ORDER BY l.sent_at DESC LIMIT 50
");
$stmt_logs->bind_param("i", $admin_id);
$stmt_logs->execute();
$result_logs = $stmt_logs->get_result();

$stmt_current_smtp = $conn->prepare("SELECT smtp_host, smtp_user, smtp_pass, smtp_port FROM admins WHERE id = ?");
$stmt_current_smtp->bind_param("i", $admin_id);
$stmt_current_smtp->execute();
$current_smtp = $stmt_current_smtp->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatizări Inteligente - ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
    <style>
        .toggle-btn {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .toggle-on { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .toggle-off { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .section-title { margin-top: 40px; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }

        /* Stiluri pentru zona de setări SMTP */
        .smtp-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: none;
        }
        .smtp-box.visible { display: block; }
        
        /* --- AICI ESTE FIX-UL PENTRU STILIZAREA INPUTURILOR (INCLUSIV PORT) --- */
        .smtp-box input[type="text"],
        .smtp-box input[type="email"],
        .smtp-box input[type="password"],
        .smtp-box input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-top: 5px;
            box-sizing: border-box; /* Important pentru aliniere */
            font-size: 1rem;
        }

        .btn-config {
            background: #6c757d;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-block;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
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
        .tooltip-wrapper { position: relative; display: inline-block; }
        .tooltip-content {
            visibility: hidden; width: 280px; background-color: #333; color: #fff;
            text-align: left; border-radius: 6px; padding: 12px; position: absolute;
            z-index: 100; bottom: 135%; left: 50%; margin-left: -140px; opacity: 0;
            transition: opacity 0.3s; font-size: 0.85rem; line-height: 1.4;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); font-weight: normal;
        }
        .tooltip-wrapper:hover .tooltip-content { visibility: visible; opacity: 1; }
        .tooltip-content::after {
            content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px;
            border-width: 5px; border-style: solid; border-color: #333 transparent transparent transparent;
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
                <h1>Automatizări Inteligente</h1>
            </header>

            <div class="table-container animate-on-scroll">

                <?php if($success_msg): ?>
                    <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if($error_msg): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        Regula a fost ștearsă.
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px;">
                    <button type="button" class="btn-config" onclick="toggleSmtp()">
                        Configurează Email (<?php echo !empty($current_smtp['smtp_user']) ? 'Conectat: '.$current_smtp['smtp_user'] : 'NECONECTAT'; ?>)
                    </button>
                    <p style="font-size: 0.9em; color: #666;">
                        * Automatizările nu vor funcționa dacă nu configurezi o adresă de email validă.
                    </p>
                </div>

                <div id="smtpSettings" class="smtp-box">
                    <h3>Conectare Adresă Email (SMTP)</h3>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="save_smtp">
                        
                        <div class="form-group">
                            <label>Server SMTP</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($current_smtp['smtp_host'] ?? 'smtp.gmail.com'); ?>" required>
                        </div>
                        
                        <div style="display:flex; gap:10px; flex-wrap: wrap;">
                            <div class="form-group" style="flex:1; min-width: 200px;">
                                <label>User Email</label>
                                <input type="email" name="smtp_user" value="<?php echo htmlspecialchars($current_smtp['smtp_user'] ?? ''); ?>" placeholder="adresa.ta@gmail.com" required>
                            </div>
                            
                            <div class="form-group" style="flex:1; min-width: 200px;">
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
                                            <hr style="margin:8px 0; border-color:#555;">
                                            <strong>Port recomandat:</strong> 587
                                        </div>
                                    </div>
                                </label>
                                <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($current_smtp['smtp_pass'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group" style="width:100px;">
                                <label>Port</label>
                                <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($current_smtp['smtp_port'] ?? '587'); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Salvează Setările Email</button>
                    </form>
                </div>
                <div class="admin-form" style="margin-bottom: 30px;">
                    <h3>Creează o nouă regulă de notificare</h3>
                    <form action="" method="POST">
                        <input type="hidden" name="add_rule" value="1">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Nume Regulă (ex: Reminder ITP)</label>
                                <input type="text" name="rule_name" required placeholder="Nume intern pentru admin">
                            </div>

                            <div class="form-group">
                                <label>Tip Eveniment</label>
                                <select name="trigger_type">
                                    <option value="itp_expiry">Expirare ITP</option>
                                    <option value="service_followup">Follow-up după Service</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Momentul Trimiterii</label>
                                <div style="display: flex; gap: 10px;">
                                    <select name="timing_type" style="width: auto; flex: 1;">
                                        <option value="before">Înainte cu</option>
                                        <option value="after">După</option>
                                    </select>
                                    <input type="number" name="days_offset" value="3" min="0" style="width: 80px;" required>
                                    <span style="align-self: center;">zile</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Subiect Email</label>
                                <input type="text" name="subject" required placeholder="Subiectul email-ului primit de client">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Mesaj (Conținut Email)</label>
                            <textarea name="message_body" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;" placeholder="Bună ziua, vă reamintim că..."></textarea>
                            <small style="color: #666;">Poți folosi {CLIENT_NAME}, {CAR_MODEL}, {SERIAL_NUMBER} ca variabile.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Salvează Regula</button>
                    </form>
                </div>

                <h2 class="section-title">Regulile Mele</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nume Regulă</th>
                            <th>Condiție</th>
                            <th>Subiect</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_rules->num_rows > 0): ?>
                            <?php while($row = $result_rules->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['rule_name']); ?></strong></td>
                                    <td>
                                        <?php
                                            $event = ($row['trigger_type'] == 'itp_expiry') ? 'Expirare ITP' : 'Service Finalizat';
                                            $timing = ($row['timing_type'] == 'before') ? 'înainte cu' : 'după';
                                            echo "$event ($timing {$row['days_offset']} zile)";
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <a href="admin_automations.php?toggle_id=<?php echo $row['id']; ?>&current_status=<?php echo $row['is_active']; ?>"
                                           class="toggle-btn <?php echo $row['is_active'] ? 'toggle-on' : 'toggle-off'; ?>">
                                            <?php echo $row['is_active'] ? 'ACTIVA (ON)' : 'OPRITĂ (OFF)'; ?>
                                        </a>
                                    </td>
                                    <td class="actions">
                                        <a href="admin_automations.php?delete_id=<?php echo $row['id']; ?>"
                                           class="delete"
                                           onclick="return confirm('Sigur ștergi această regulă?')"
                                           style="color:#dc3545;">
                                           Șterge
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px;">Nu ai definit nicio regulă de automatizare.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h2 class="section-title">Jurnal Trimiteri Recente (Log)</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data Trimiterii</th>
                            <th>Client</th>
                            <th>Regulă Aplicată</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_logs->num_rows > 0): ?>
                            <?php while($log = $result_logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($log['sent_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($log['rule_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center; padding:20px;">Nu s-a trimis niciun mesaj automat momentan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>