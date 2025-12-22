<?php
session_start();
require_once 'db_connect.php';

// 1. Verificare securitate
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Setări Calendar (Lună și An)
$dateComponents = getdate();
if(isset($_GET['month']) && isset($_GET['year'])){
    $month = $_GET['month'];
    $year = $_GET['year'];
} else {
    $month = $dateComponents['mon'];
    $year = $dateComponents['year'];
}

// Obținem intervențiile pentru luna și anul selectat
// Folosim un JOIN pentru a afișa și numele clientului/mașina, nu doar ID-uri
$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$sql = "SELECT i.*, c.full_name, e.model
    FROM interventions i
    JOIN clients c ON i.client_id = c.id
    JOIN equipment e ON i.equipment_id = e.id
    WHERE MONTH(i.scheduled_date) = ? AND YEAR(i.scheduled_date) = ? AND c.admin_id = ?
    ORDER BY i.scheduled_date ASC";

$stmt = $conn->prepare($sql);
$m = (int)$month; $y = (int)$year;
$stmt->bind_param("iii", $m, $y, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Grupăm evenimentele pe zile (ex: $events[15] = [lista interventii pe data de 15])
$events = [];
while($row = $result->fetch_assoc()) {
    $day = date('j', strtotime($row['scheduled_date'])); // Ziua fără zero în față (1-31)
    $events[$day][] = $row;
}

// 3. Funcții ajutătoare pentru calendar
function build_calendar($month, $year, $events) {
    // Zilele săptămânii în Română
    $daysOfWeek = array('Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică');
    
    // Prima zi a lunii
    $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
    $numberDays = date('t', $firstDayOfMonth); // Câte zile are luna
    $dateComponents = getdate($firstDayOfMonth);
    
    // Numele lunii în Română
    $monthNames = array(
        1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
        5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
        9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
    );

    // Ce zi a săptămânii este prima zi (1 = Luni, 7 = Duminică)
    // Funcția 'N' returnează 1 pentru Luni, 7 Duminică.
    $dayOfWeek = date('N', $firstDayOfMonth);

    // Linkuri Navigare
    $prev_month = date('m', mktime(0, 0, 0, $month-1, 1, $year));
    $prev_year = date('Y', mktime(0, 0, 0, $month-1, 1, $year));
    $next_month = date('m', mktime(0, 0, 0, $month+1, 1, $year));
    $next_year = date('Y', mktime(0, 0, 0, $month+1, 1, $year));

    $calendar = "<div class='calendar-header'>";
    $calendar .= "<a href='?month=$prev_month&year=$prev_year' class='btn btn-sm'>&laquo; Luna Trecută</a>";
    $calendar .= "<h2>" . $monthNames[(int)$month] . " " . $year . "</h2>";
    $calendar .= "<a href='?month=$next_month&year=$next_year' class='btn btn-sm'>Luna Următoare &raquo;</a>";
    $calendar .= "</div>";

    $calendar .= "<table class='calendar-table'>";
    $calendar .= "<thead><tr>";

    // Header zile
    foreach ($daysOfWeek as $day) {
        $calendar .= "<th class='header'>$day</th>";
    }
    $calendar .= "</tr></thead><tbody><tr>";

    // Celule goale până la prima zi a lunii
    // Dacă luna începe Miercuri (ziua 3), avem nevoie de 2 celule goale (Luni, Marți)
    if ($dayOfWeek > 1) { 
        $calendar .= str_repeat("<td class='empty'></td>", $dayOfWeek - 1); 
    }

    $currentDay = 1;

    // Generarea zilelor
    while ($currentDay <= $numberDays) {
        // Dacă am ajuns la capătul săptămânii (7 zile), începem rând nou
        if ($dayOfWeek == 8) {
            $dayOfWeek = 1;
            $calendar .= "</tr><tr>";
        }

        $date_class = ($currentDay == date('j') && $month == date('m') && $year == date('Y')) ? 'today' : '';
        
        $calendar .= "<td class='day $date_class' valign='top'>";
        $calendar .= "<div class='day-number'>$currentDay</div>";
        
        // --- AFIȘARE EVENIMENTE ---
        if (isset($events[$currentDay])) {
            foreach ($events[$currentDay] as $event) {
                // Culoare în funcție de status
                $statusColor = '#007bff'; // Default albastru (programat)
                if($event['status'] == 'finalizata') $statusColor = '#28a745';
                if($event['status'] == 'in_desfasurare') $statusColor = '#ffc107';

                $ora = date('H:i', strtotime($event['scheduled_date']));
                
                $calendar .= "<div class='event-badge' style='background-color: $statusColor;'>";
                $calendar .= "<span>$ora</span> <small>" . htmlspecialchars($event['model']) . "</small>";
                $calendar .= "</div>";
            }
        }
        
        $calendar .= "</td>";

        $currentDay++;
        $dayOfWeek++;
    }

    // Completăm rândul final cu celule goale dacă e necesar
    if ($dayOfWeek != 1) {
        $remainingDays = 8 - $dayOfWeek;
        $calendar .= str_repeat("<td class='empty'></td>", $remainingDays);
    }

    $calendar .= "</tr></tbody></table>";
    return $calendar;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Programări - Admin ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
    
        <link rel="stylesheet" href="style/pages.css">
</head>
<body id="top">
    
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <h1>Calendar Programări</h1>
                <a href="admin_intervention_new.php" class="btn btn-primary">Adaugă Programare</a>
            </header>

            <section class="admin-form animate-on-scroll">
                <?php echo build_calendar($month, $year, $events); ?>
            </section>

        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>