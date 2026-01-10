<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar client-nav" id="sidebar">
    <button id="sidebar-close" class="sidebar-close" style="display: none;">&times;</button>
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="Logo Service" class="logo">
    </div>
    <nav>
        <ul class="sidebar-nav client-nav-list">
            <li><a href="client_dashboard.php" <?php if($current_page === 'client_dashboard.php') echo 'class="active"'; ?>>Panoul Meu</a></li>
            <li><a href="client_booking.php" <?php if(in_array($current_page, ['client_booking.php'])) echo 'class="active"'; ?>>Programări</a></li>
            <li><a href="client_equipment.php" <?php if($current_page === 'client_equipment.php') echo 'class="active"'; ?>>Echipamentele Mele</a></li>
            <li><a href="client_history.php" <?php if(in_array($current_page, ['client_history.php','client_history_detail.php'])) echo 'class="active"'; ?>>Istoric Service</a></li>
            <li><a href="client_profile.php" <?php if($current_page === 'client_profile.php') echo 'class="active"'; ?>>Profilul Meu</a></li>
            <li><a href="logout.php">Deconectare</a></li>
        </ul>
    </nav>
</aside>
