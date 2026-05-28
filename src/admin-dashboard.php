<?php
    // Κατά την εκκίνηση της αρχικής σελίδας ελέγχεται αν υπάρχει η βάση δεδομένων και αν δεν υπάρχει δημιουργείται
    require_once 'create_db.php';

    // Ελέγχουμε τα cookies του χρήστη για να δούμε αν ο user είναι συνδεδεμένος σαν admin
    $logged_in_admin = null;

    if (isset($_COOKIE['admin_token'])) {
        $stmt = $pdo->prepare("SELECT full_name FROM admins WHERE remember_token = :token");
        $stmt->execute([':token' => $_COOKIE['admin_token']]);
        $logged_in_admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Πιο ασφαλής έλεγχος με PHP: Αν δεν είναι συνδεδεμένος, τον στέλνουμε στο login page αμέσως
    if (!$logged_in_admin) {
        header("Location: login.php");
        exit;
    }

    
        
    // 1. Συνολικός αριθμός αναφορών
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM `issues`");
    $totalReports = $stmtTotal->fetchColumn();

    // 2. Μέσος όρος upvotes
    $stmtAvgUpvotes = $pdo->query("SELECT AVG(upvotes) FROM `issues`");
    $avgUpvotes = round($stmtAvgUpvotes->fetchColumn(), 2);

    // 3. Αναφορές ανά κατηγορία (ΕΜΦΑΝΙΣΗ ΟΛΩΝ ΤΩΝ ΚΑΤΗΓΟΡΙΩΝ ΑΚΟΜΑ ΚΑΙ ΜΕ 0)
    // Χρησιμοποιούμε RIGHT JOIN (ή LEFT JOIN ξεκινώντας από το categories).
    // Έτσι παίρνουμε ΟΛΕΣ τις γραμμές του categories, και μετράμε πόσα issues ταιριάζουν.
    $queryCategory = "
        SELECT c.name AS category_name, COUNT(i.id) AS count 
        FROM `categories` c
        LEFT JOIN `issues` i ON c.category_id = i.category_id
        GROUP BY c.category_id, c.name
        ORDER BY count DESC, c.name ASC
    ";
    $reportsByCategory = $pdo->query($queryCategory)->fetchAll(PDO::FETCH_ASSOC);

    // 4. Αναφορές ανά κατάσταση (ΕΜΦΑΝΙΣΗ ΟΛΩΝ ΤΩΝ ΣΤΑΘΕΡΩΝ ΚΑΤΑΣΤΑΣΕΩΝ)
    // Επειδή οι "καταστάσεις" στο δικό σου schema δεν είναι σε ξεχωριστό πίνακα, αλλά απλά 
    // ένα text πεδίο 'status' στον πίνακα 'issues' (με default 'Υποβλήθηκε'), 
    // πρέπει να φτιάξουμε εμείς έναν "εικονικό" πίνακα με τις γνωστές καταστάσεις 
    // για να κάνουμε το JOIN, αλλιώς η MySQL δεν ξέρει ποιες είναι οι "όλες" καταστάσεις αν δεν υπάρχουν!
        
    $queryStatus = "
        SELECT all_statuses.status_name AS status, COUNT(i.id) AS count
        FROM (
            SELECT 'Υποβλήθηκε' AS status_name UNION ALL
            SELECT 'Σε επεξεργασία' AS status_name UNION ALL
            SELECT 'Ολοκληρώθηκε' AS status_name UNION ALL
            SELECT 'Απορρίφθηκε' AS status_name
        ) AS all_statuses
        LEFT JOIN `issues` i ON all_statuses.status_name = i.status
        GROUP BY all_statuses.status_name
        ORDER BY 
            CASE all_statuses.status_name
                WHEN 'Υποβλήθηκε' THEN 1
                WHEN 'Σε επεξεργασία' THEN 2
                WHEN 'Ολοκληρώθηκε' THEN 3
                WHEN 'Απορρίφθηκε' THEN 4
                ELSE 5
            END
    ";
    $reportsByStatus = $pdo->query($queryStatus)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityReport - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="style.css" rel="stylesheet">
    <link href = "style_admin.css" rel="stylesheet">
    
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
                        <a class="nav-link" href="index.php">Αναφορά Προβλήματος</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="browse.php">Προβολή Προβλημάτων</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="admin-dashboard.php">
                            Γεια σας, <?= htmlspecialchars($logged_in_admin['full_name']) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Αποσύνδεση</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <h2 class="fw-bold text-dark mb-4">Συνοπτικά Στατιστικά</h2>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card dashboard-card h-100 p-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 fw-semibold">Συνολικές Αναφορές</p>
                            <h2 class="mb-0 fw-bold"><?= $totalReports ?: 0 ?></h2>
                        </div>
                        <div class="icon-box bg-light-primary">
                            <i class="bi bi-files"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card dashboard-card h-100 p-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 fw-semibold">Μέσος Όρος Upvotes</p>
                            <h2 class="mb-0 fw-bold"><?= $avgUpvotes ?: '0' ?></h2>
                        </div>
                        <div class="icon-box bg-light-success">
                            <i class="bi bi-hand-thumbs-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card dashboard-card h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-0">Ανά Κατηγορία</h5>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <ul class="list-group list-group-flush">
                            <?php if (empty($reportsByCategory)): ?>
                                <li class="list-group-item text-center text-muted">Δεν υπάρχουν δεδομένα</li>
                            <?php else: ?>
                                <?php foreach ($reportsByCategory as $row): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-secondary"><i class="bi bi-tag"></i></div>
                                            <span class="fw-medium text-dark"><?= htmlspecialchars($row['category_name']) ?></span>
                                        </div>
                                        <span class="badge bg-primary rounded-pill px-3 py-2"><?= $row['count'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card dashboard-card h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-0">Ανά Κατάσταση</h5>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <ul class="list-group list-group-flush">
                            <?php if (empty($reportsByStatus)): ?>
                                <li class="list-group-item text-center text-muted">Δεν υπάρχουν δεδομένα</li>
                            <?php else: ?>
                                <?php foreach ($reportsByStatus as $row): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3 text-secondary"><i class="bi bi-record-circle"></i></div>
                                            <span class="fw-medium text-dark"><?= htmlspecialchars($row['status']) ?></span>
                                        </div>
                                        <span class="badge bg-dark rounded-pill px-3 py-2"><?= $row['count'] ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-5 mb-4">
        <button class="btn btn-sm btn-primary px-4 py-2" onclick="window.location.href='browse.php';">
        Προβολή Λεπτομερειών!
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>