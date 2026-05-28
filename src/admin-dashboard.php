<?php
    //Κατά την εκκίνηση της αρχικής σελίδας ελέγχεται αν υπάρχει η βάση δεδομένων και αν δεν υπάρχει δημιουργείται
    require_once 'create_db.php';

    //Ελέγχουμε τα cookies του χρήστη για να δοπυμε αν ο user είναι συνδεδεμένος σαν admin
    //Aν ναι, τραβάμε το όνομά του για να το εμφανίσουμε στο navbar
    $logged_in_admin = null;

    if (isset($_COOKIE['admin_token'])) {
        $stmt = $pdo->prepare("SELECT full_name FROM admins WHERE remember_token = :token");
        $stmt->execute([':token' => $_COOKIE['admin_token']]);
        $logged_in_admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }
?>

<!-- Ελεγχος αν ο admin είναι συνδεδεμένος, αν όχι τον στέλνουμε στο login page -->
<?php if (!$logged_in_admin): ?>
    <script>
        // Αν δεν είναι συνδεδεμένος, τον στέλνουμε στο login page
        window.location.href = "login.php";
    </script>
<?php exit; endif; ?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityReport - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="style.css" rel="stylesheet">
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">CityReport</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Αναφορά Προβλήματος</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="browse.php">Προβολή Προβλημάτων</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                            <li class="nav-item">
                                <a class="nav-link" href="admin-dashboard.php">Γεια σας, <?= htmlspecialchars($logged_in_admin['full_name']) ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">Αποσύνδεση</a>
                            </li>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</body>
</html>