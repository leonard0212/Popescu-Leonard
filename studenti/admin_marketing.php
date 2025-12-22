<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

 $msg = '';
 $msg_type = '';

// --- LOGICĂ TRIMITERE CAMPANIE ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluăm datele din formular
    $nume_intern = trim($_POST['name']); 
    $subiect = trim($_POST['subject']);
    $mesaj = trim($_POST['message_content']);

    // target_audience poate fi array (multi-select) sau string
    $raw_audience = isset($_POST['target_audience']) ? $_POST['target_audience'] : [];
    if (!is_array($raw_audience)) $raw_audience = [$raw_audience];

    // Dacă s-a ales 'manual', preluăm lista de id-uri selectate
    $manual_ids = [];
    if (in_array('manual', $raw_audience, true)) {
        $manual_ids = isset($_POST['manual_clients']) && is_array($_POST['manual_clients']) ? array_map('intval', $_POST['manual_clients']) : [];
    }

    // Salvăm audiența ca JSON (poate conține taguri și/sau array de id-uri)
    $audienta = json_encode(['selected' => $raw_audience, 'manual_ids' => $manual_ids]);

    if(empty($subiect) || empty($mesaj) || empty($nume_intern)) {
        $msg = "Toate câmpurile sunt obligatorii.";
        $msg_type = "error";
    } else {
        // Salvăm în baza de date cu noile nume de coloane
        // Setăm sent_at = NOW() pentru că o trimitem acum
        $admin_id = $_SESSION['admin_id'];
        $insert_sql = "INSERT INTO marketing_campaigns (admin_id, name, subject, target_audience, message_content, sent_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issss", $admin_id, $nume_intern, $subiect, $audienta, $mesaj);

        try {
            if ($stmt->execute()) {
                $msg = "Campania a fost salvată și trimisă cu succes!";
                $msg_type = "success";
            } else {
                $msg = "Eroare SQL: " . $stmt->error;
                $msg_type = "error";
            }
        } catch (mysqli_sql_exception $e) {
            $err = $e->getMessage();
            // Dacă eroarea este legată de trunchierea datelor, încercăm să convertim coloana la TEXT și retry
            if (stripos($err, 'Data truncated') !== false || stripos($err, 'Data too long') !== false || $conn->errno == 1406) {
                $altered = $conn->query("ALTER TABLE marketing_campaigns MODIFY target_audience TEXT");
                if ($altered) {
                    // Retry insert
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param("issss", $admin_id, $nume_intern, $subiect, $audienta, $mesaj);
                    if ($stmt->execute()) {
                        $msg = "Campania a fost salvată și trimisă cu succes!";
                        $msg_type = "success";
                    } else {
                        $msg = "Eroare SQL după migrare: " . $stmt->error;
                        $msg_type = "error";
                    }
                } else {
                    $msg = "Eroare migrare coloana: " . $conn->error;
                    $msg_type = "error";
                }
            } else {
                $msg = "Eroare SQL: " . $err;
                $msg_type = "error";
            }
        }
    }
}

// Preluare Istoric (Ordonat descrescător după dată) - doar campaniile adminului curent
$admin_id = $_SESSION['admin_id'];
$hist_stmt = $conn->prepare("SELECT * FROM marketing_campaigns WHERE admin_id = ? ORDER BY created_at DESC");
$hist_stmt->bind_param("i", $admin_id);
$hist_stmt->execute();
$history = $hist_stmt->get_result();

// Preluăm lista de clienți ai adminului pentru selecția manuală
$clients_stmt = $conn->prepare("SELECT id, full_name FROM clients WHERE admin_id = ? ORDER BY full_name ASC");
$clients_stmt->bind_param("i", $admin_id);
$clients_stmt->execute();
$clients_result = $clients_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Marketing - Admin ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <h1>Comunicare & Marketing</h1>
            </header>

            <?php if($msg): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: white; background-color: <?php echo ($msg_type == 'success') ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <section class="admin-form animate-on-scroll" style="margin-bottom: 2rem;">
                <h2>Creează Campanie Nouă</h2>
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Nume Intern Campanie</label>
                        <input type="text" name="name" placeholder="ex: Promoție Crăciun" required>
                    </div>
                    <div class="form-group">
                        <label>Subiect Email</label>
                        <input type="text" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>Audiență (selectează una sau mai multe)</label>
                        <select name="target_audience[]" multiple size="5">
                            <option value="all">Toți Clienții</option>
                            <option value="active_clients">Clienți Activi</option>
                            <option value="inactive_clients">Clienți Inactivi</option>
                            <option value="itp_under_30">ITP - sub 30 zile</option>
                            <option value="manual">Selectare Manuală (alege clienți mai jos)</option>
                        </select>
                        <small>Țineți apăsată tasta Ctrl (sau Cmd) pentru selecție multiplă.</small>
                    </div>

                    <div class="form-group">
                        <label>Selectare Manuală Clienți</label>
                        <select name="manual_clients[]" multiple size="6">
                            <?php if ($clients_result && $clients_result->num_rows > 0): ?>
                                <?php while($c = $clients_result->fetch_assoc()): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option disabled>Nicio client disponibil</option>
                            <?php endif; ?>
                        </select>
                        <small>Folosește această listă doar dacă ai selectat 'Selectare Manuală' în audiență.</small>
                    </div>
                    <div class="form-group">
                        <label>Conținut Mesaj</label>
                        <textarea name="message_content" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Trimite Campania</button>
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
                            <th>Audiență</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history && $history->num_rows > 0): ?>
                            <?php while($row = $history->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php 
                                            // Folosim sent_at dacă există, altfel data creării
                                            $date_source = $row['sent_at'] ? $row['sent_at'] : $row['created_at'];
                                            echo date('d.m.Y H:i', strtotime($date_source)); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <?php 
                                            // target_audience poate fi JSON (de la modificările recente)
                                            $ta = $row['target_audience'];
                                            $decoded = json_decode($ta, true);
                                            if (is_array($decoded) && isset($decoded['selected'])) {
                                                $labels = [];
                                                foreach ($decoded['selected'] as $s) {
                                                    if ($s === 'all') $labels[] = 'Toți Clienții';
                                                    elseif ($s === 'active_clients') $labels[] = 'Clienți Activi';
                                                    elseif ($s === 'inactive_clients') $labels[] = 'Clienți Inactivi';
                                                    elseif ($s === 'itp_under_30') $labels[] = 'ITP - sub 30 zile';
                                                    elseif ($s === 'manual') {
                                                        $cnt = isset($decoded['manual_ids']) ? count($decoded['manual_ids']) : 0;
                                                        $labels[] = 'Selectare manuală (' . $cnt . ' clienți)';
                                                    } else {
                                                        $labels[] = htmlspecialchars($s);
                                                    }
                                                }
                                                echo implode(', ', $labels);
                                            } else {
                                                // fallback la vechea logică
                                                if($ta == 'all') echo 'Toți Clienții';
                                                elseif($ta == 'active_clients') echo 'Clienți Activi';
                                                elseif($ta == 'inactive_clients') echo 'Clienți Inactivi';
                                                else echo htmlspecialchars($ta);
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($row['sent_at']): ?>
                                            <span style="color:#28a745; font-weight:bold;">Trimisă</span>
                                        <?php else: ?>
                                            <span style="color:#ffc107; font-weight:bold;">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5">Nu există campanii înregistrate.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <script src="js/animations.js"></script>
</body>
</html>