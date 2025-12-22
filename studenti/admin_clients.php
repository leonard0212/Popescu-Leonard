<?php
session_start();
require_once 'db_connect.php';

// Verificăm dacă e logat
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- LOGICĂ ȘTERGERE CLIENT (verificăm apartenența la admin) ---
if (isset($_GET['delete_id'])) {
    $id_to_delete = (int)$_GET['delete_id'];
    $admin_id = $_SESSION['admin_id'];
    // Ștergem clientul doar dacă aparține adminului curent
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $id_to_delete, $admin_id);
    if($stmt->execute()){
        header("Location: admin_clients.php?msg=deleted");
        exit();
    }
}

// --- LOGICĂ CĂUTARE + LISTARE ---
$search = isset($_GET['search']) ? $_GET['search'] : '';

$admin_id = $_SESSION['admin_id'];
if (!empty($search)) {
    // Căutăm după nume, email sau telefon
    $search_term = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT * FROM clients WHERE admin_id = ? AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY full_name ASC");
    $stmt->bind_param("isss", $admin_id, $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Listăm toți clienții
    $stmt_all = $conn->prepare("SELECT * FROM clients WHERE admin_id = ? ORDER BY full_name ASC");
    $stmt_all->bind_param("i", $admin_id);
    $stmt_all->execute();
    $result = $stmt_all->get_result();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiune Clienți - Admin ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <div class="table-container animate-on-scroll">
                <div class="table-header">
                    <h2>Clienți Existenți</h2>
                    
                    <form action="" method="GET" style="display:flex; gap:10px;">
                        <input type="text" name="search" placeholder="Caută nume sau telefon..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <button type="submit" class="btn btn-secondary" style="padding: 8px 15px;">Caută</button>
                        <?php if($search): ?>
                            <a href="admin_clients.php" class="btn btn-secondary" style="padding: 8px 15px; background:#6c757d;">Reset</a>
                        <?php endif; ?>
                    </form>

                    <a href="admin_client_new.php" class="btn btn-primary">Adaugă Client</a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        Clientul a fost șters cu succes.
                    </div>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nume</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Puncte</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="admin_client_detail.php?id=<?php echo $row['id']; ?>" style="font-weight: bold; color: #007bff; text-decoration: none;">
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td>
                                        <span class="badge" style="background:#eef2ff; color:#4f46e5; padding:4px 8px; border-radius:10px; font-size:0.85rem;">
                                            <?php echo $row['loyalty_points']; ?> pts
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="admin_client_detail.php?id=<?php echo $row['id']; ?>" class="btn-sm" style="color: #007bff; margin-right: 10px;">Detalii</a>
                                        <a href="admin_clients.php?delete_id=<?php echo $row['id']; ?>" 
                                           class="delete" 
                                           onclick="return confirm('ATENȚIE: Ștergerea clientului va șterge și toate mașinile și istoricul acestuia! Continui?');" 
                                           style="color: #dc3545;">
                                           Șterge
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">Nu au fost găsiți clienți.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>