<?php

require_once 'create_db.php';
require_once 'get_badge_class.php';

if (!isset($_GET['ticket_id']) || trim($_GET['ticket_id']) === '') {
    // Αν κάποιος μπει στο more.php χωρίς ID, τον στέλνουμε πίσω
    header("Location: browse.php");
    exit;
}

$ticket_id = trim($_GET['ticket_id']);

// Αντλούμε τις λεπτομέρειες του συγκεκριμένου issue από τη βάση δεδομένων
$sql = "SELECT issues.*, categories.name AS category_name 
        FROM issues 
        JOIN categories ON issues.category_id = categories.category_id 
        WHERE issues.ticket_id = :ticket_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':ticket_id' => $ticket_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    echo '<div style="text-align:center; margin-top:50px; font-family:sans-serif;"><h3>Το πρόβλημα δεν βρέθηκε.</h3><a href="browse.php">Επιστροφή</a></div>';
}

$image = glob("uploads/".$ticket_id.".*");
$has_image = !empty($image);
$image_path = $has_image ? $image[0] : '';

// 4. Έλεγχος Cookie για το Upvote
$cookie_name = 'upvoted_' . $ticket_id;
$has_voted = isset($_COOKIE[$cookie_name]);
$current_status = !empty($issue['status']) ? $issue['status'] : 'Υποβλήθηκε';
$badge_css_class = get_badge_class($current_status);


echo '<!DOCTYPE html>';
echo '<html lang="el">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Λεπτομέρειες: ' . htmlspecialchars($issue['title']) . '</title>';
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />'; 
echo '<link rel="stylesheet" href="style.css">'; 
echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>'; 
echo '</head>';
echo '<body class="bg-light d-flex flex-column min-vh-100">';

// Το κλασικό Navbar
echo '<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">';
echo '<div class="container">';
echo '<a class="navbar-brand" href="index.php">CityReport</a>';
echo '<div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="index.php">Αναφορά Προβλήματος</a></li>
            <li class="nav-item"><a class="nav-link" href="browse.php">Προβολή Προβλημάτων</a></li>
        </ul>
      </div>';
echo '</div>';
echo '</nav>';

?>



<div class="container mb-5 flex-grow-1">
    <a href="browse.php" class="btn btn-outline-secondary btn-sm mb-4">Επιστροφή στη λίστα</a>
    
    <div class="row g-4">
        <div class="col-lg-7">
            <h2><?= htmlspecialchars($issue['title']) ?></h2>
        </div>

        <div style="" class="col-lg-3 text-end d-flex justify-content-between align-items-center">
            <span class="text-muted small fw-bold fs-6">#<?= htmlspecialchars($issue['ticket_id']) ?></span>
            <span class="badge bg-info fs-6  <?= $badge_css_class ?>"><?= htmlspecialchars($current_status) ?></span>
        </div>
    </div>

    <div class="row mt-5">
        <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <p class="card-text"><strong>Περιγραφή:</strong> <?= htmlspecialchars($issue['description']) ?></p>
                </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <p><strong>Κατηγορία:</strong> <?= htmlspecialchars($issue['category_name']) ?></p>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <p><strong>Υποβλήθηκε στις:</strong> <?= date('d/m/Y H:i', strtotime($issue['created_at'])) ?></p>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Τοποθεσία Αναφοράς</h5>
                    <div id="map-detail" style="height: 320px; width: 100%; rounded: 8px; z-index: 1;"></div>
                    <p class="text-muted small mt-2 mb-0">
                        <strong>Διεύθυνση:</strong> <?= htmlspecialchars($issue['address']) ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <?php if ($has_image): ?>
                <img src="<?= htmlspecialchars($image_path) ?>" alt="Εικόνα προβλήματος" class="img-fluid rounded">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                    <span class="text-muted">Δεν υπάρχει εικόνα</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Παίρνουμε τις συντεταγμένες από την PHP (αντικατάστησε με τα ακριβή ονόματα των στηλών σου)
        const lat = <?= floatval($issue['latitude']) ?>;
        const lng = <?= floatval($issue['longitude']) ?>;

        // Αρχικοποίηση του χάρτη και εστίαση (setView) στις συντεταγμένες με zoom level 16
        const mapDetail = L.map('map-detail').setView([lat, lng], 16);

        // Προσθήκη των OpenStreetMap Tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapDetail);

        // Δημιουργία της πινέζας (Marker) και προσθήκη της στον χάρτη
        const marker = L.marker([lat, lng]).addTo(mapDetail);
                
        // Προαιρετικά: Προσθήκη ενός αναδυόμενου παραθύρου (Popup) όταν ο χρήστης πατάει στην πινέζα
        marker.bindPopup("Σημείο Αναφοράς").openPopup();
    </script>

<?php
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>'; 
echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>'; 
echo '</body>';
echo '</html>';
?>