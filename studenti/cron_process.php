<?php
// cron_process.php
// Acest script trebuie rulat o dată pe zi (Manual sau prin Task Scheduler)

require_once 'db_connect.php';

// --- INCLUDERE PHPMAILER ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/Exception.php';
require 'libs/PHPMailer.php';
require 'libs/SMTP.php';

echo "<h3>--- START PROCES AUTOMATIZARE " . date('Y-m-d H:i:s') . " ---</h3>";

// 1. Selectăm toate regulile ACTIVE
$sql_rules = "SELECT * FROM notification_rules WHERE is_active = 1";
$result_rules = $conn->query($sql_rules);

if ($result_rules->num_rows > 0) {
    while ($rule = $result_rules->fetch_assoc()) {
        $rule_id = $rule['id'];
        $admin_id = $rule['admin_id'];
        $days_offset = $rule['days_offset'];
        
        echo "<hr><strong>Procesare Regulă ID: $rule_id ({$rule['rule_name']})</strong><br>";

        // 2. Extragem datele SMTP ale Adminului care a creat regula
        $stmt_smtp = $conn->prepare("SELECT smtp_host, smtp_user, smtp_pass, smtp_port FROM admins WHERE id = ?");
        $stmt_smtp->bind_param("i", $admin_id);
        $stmt_smtp->execute();
        $smtp_conf = $stmt_smtp->get_result()->fetch_assoc();

        if (empty($smtp_conf['smtp_user']) || empty($smtp_conf['smtp_pass'])) {
            echo "<span style='color:red'>SKIP: Adminul $admin_id nu are email configurat.</span><br>";
            continue;
        }

        // 3. Calculăm Data Țintă
        // Dacă regula e 'before' (înainte), căutăm evenimente în VIITOR (Azi + X zile)
        // Dacă regula e 'after' (după), căutăm evenimente în TRECUT (Azi - X zile)
        
        $target_date = "";
        if ($rule['timing_type'] == 'before') {
            $target_date = date('Y-m-d', strtotime("+$days_offset days"));
        } else {
            $target_date = date('Y-m-d', strtotime("-$days_offset days"));
        }
        echo "Data eveniment căutată: $target_date<br>";

        // 4. Căutăm clienți care se potrivesc
        $matches = [];
        
        if ($rule['trigger_type'] == 'itp_expiry') {
            // Căutăm mașini cărora le expiră ITP-ul la data țintă
            $sql_query = "
                SELECT c.id as client_id, c.full_name, c.email, e.model, e.serial_number
                FROM equipment e
                JOIN clients c ON e.client_id = c.id
                WHERE c.admin_id = ? 
                AND e.itp_expiry_date = ?
                AND c.email IS NOT NULL AND c.email != ''
            ";
        } elseif ($rule['trigger_type'] == 'service_followup') {
            // Căutăm intervenții finalizate la data țintă
            // Folosim completed_date sau scheduled_date (depinde cum folosești aplicația)
            $sql_query = "
                SELECT c.id as client_id, c.full_name, c.email, e.model, e.serial_number
                FROM interventions i
                JOIN clients c ON i.client_id = c.id
                JOIN equipment e ON i.equipment_id = e.id
                WHERE c.admin_id = ?
                AND i.status = 'finalizata'
                AND DATE(i.completed_date) = ?
                AND c.email IS NOT NULL AND c.email != ''
            ";
        } else {
            continue;
        }

        $stmt_check = $conn->prepare($sql_query);
        $stmt_check->bind_param("is", $admin_id, $target_date);
        $stmt_check->execute();
        $result_matches = $stmt_check->get_result();

        // 5. Trimitem Emailurile
        while ($match = $result_matches->fetch_assoc()) {
            $client_id = $match['client_id'];
            
            // Verificăm să nu fi trimis deja azi acest mail (evităm dublurile)
            $check_log = $conn->prepare("SELECT id FROM notification_logs WHERE rule_id = ? AND client_id = ? AND DATE(sent_at) = CURDATE()");
            $check_log->bind_param("ii", $rule_id, $client_id);
            $check_log->execute();
            if ($check_log->get_result()->num_rows > 0) {
                echo " - Client {$match['full_name']}: Deja trimis azi. Skip.<br>";
                continue;
            }

            // Pregătire PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Configurare SMTP (Dinamică per admin)
                $mail->isSMTP();
                $mail->Host       = $smtp_conf['smtp_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $smtp_conf['smtp_user'];
                $mail->Password   = $smtp_conf['smtp_pass'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $smtp_conf['smtp_port'];

                // Expeditor și Destinatar
                $mail->setFrom($smtp_conf['smtp_user'], 'Service Auto Automat');
                $mail->addAddress($match['email'], $match['full_name']);

                // Înlocuire variabile în mesaj
                $final_subject = $rule['subject'];
                $final_body = str_replace(
                    ['{CLIENT_NAME}', '{CAR_MODEL}', '{SERIAL_NUMBER}'],
                    [$match['full_name'], $match['model'], $match['serial_number']],
                    $rule['message_body']
                );

                $mail->isHTML(false); // Trimitem text simplu sau HTML
                $mail->Subject = $final_subject;
                $mail->Body    = $final_body;

                $mail->send();
                echo "<span style='color:green'> - Email trimis către {$match['full_name']} ({$match['email']})</span><br>";

                // Logare în baza de date
                $stmt_log = $conn->prepare("INSERT INTO notification_logs (admin_id, rule_id, client_id, sent_at) VALUES (?, ?, ?, NOW())");
                $stmt_log->bind_param("iii", $admin_id, $rule_id, $client_id);
                $stmt_log->execute();

            } catch (Exception $e) {
                echo "<span style='color:red'> - Eroare trimitere {$match['full_name']}: {$mail->ErrorInfo}</span><br>";
            }
        }
    }
} else {
    echo "Nu există reguli active.<br>";
}

echo "<hr><h3>--- FINAL PROCES ---</h3>";
?>