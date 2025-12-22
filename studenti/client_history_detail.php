<?php
session_start();
require_once 'db_connect.php';

// --- SIMULARE LOGIN (Șterge după ce ai login real) ---
// $_SESSION['client_id'] = 1; 
// -----------------------------------------------------

// 1. Verificare Autentificare
if (!isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Verificare ID Intervenție
if (!isset($_GET['id'])) {
    header("Location: client_history.php");
    exit();
}

$intervention_id = $_GET['id'];
$client_id = $_SESSION['client_id'];

// 3. Interogare Bază de Date
// Folosim un JOIN pentru a ne asigura că intervenția aparține echipamentului ACESTUI client
$sql = "SELECT i.*, e.model, e.serial_number 
        FROM interventions i
        JOIN equipment e ON i.equipment_id = e.id
        WHERE i.id = ? AND i.client_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $intervention_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Intervenția nu a fost găsită sau nu aveți acces la ea.");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detaliu Intervenție - Portal Client</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/client.css">
    <link rel="stylesheet" href="style/pages.css">
</head>
<body id="top">
    
    <div class="client-portal">
        <?php include 'client_sidebar.php'; ?>

        <main class="client-content">
            <div class="animate-on-scroll">
                <a href="client_history.php" class="btn btn-secondary mb-20">&larr; Înapoi la Istoric</a>
                
                <div class="detail-card">
                    <div class="detail-header">
                        <div>
                            <h1 class="detail-title">Fișă Service #<?php echo $data['id']; ?></h1>
                            <p class="muted-text">
                                Data: <?php echo date('d.m.Y', strtotime($data['scheduled_date'])); ?>
                            </p>
                        </div>
                        
                        <?php 
                            $status_class = 'status-' . $data['status'];
                            $status_text = ucfirst(str_replace('_', ' ', $data['status']));
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Vehicul</label>
                            <div><?php echo htmlspecialchars($data['model']); ?></div>
                        </div>
                        <div class="detail-item">
                            <label>Serie Șasiu / Nr. Înmatriculare</label>
                            <div><?php echo htmlspecialchars($data['serial_number']); ?></div>
                        </div>

                        <div class="detail-item full-width">
                            <label>Problemă Reclamată</label>
                            <div><?php echo nl2br(htmlspecialchars($data['problem_description'])); ?></div>
                        </div>

                        <?php if(!empty($data['diagnostic_notes'])): ?>
                        <div class="detail-item full-width">
                            <label>Diagnostic & Note Mecanic</label>
                            <div style="color: #0056b3;"><?php echo nl2br(htmlspecialchars($data['diagnostic_notes'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($data['parts_used'])): ?>
                        <div class="detail-item full-width">
                            <label>Piese / Materiale Folosite</label>
                            <div><?php echo nl2br(htmlspecialchars($data['parts_used'])); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if($data['labor_cost'] > 0): ?>
                        <div class="detail-item">
                            <label>Cost Manoperă</label>
                            <div><?php echo number_format($data['labor_cost'], 2); ?> RON</div>
                        </div>
                        <?php endif; ?>
                        
                        </div>
                </div>
            </div>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>