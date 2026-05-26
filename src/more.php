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

    if ($has_voted) {
        $btn_class = 'btn-secondary'; 
        $disabled_attribute = 'disabled'; 
    } 
    else {
        $btn_class = 'btn-outline-primary'; 
        $disabled_attribute = ''; 
    }

    // βασικός κώδικας για την λειτουργία του AI

    $groq_api_key = '';

    if (file_exists(__DIR__ . '/.env')) {
        $env_vars = parse_ini_file(__DIR__ . '/.env');
        if (isset($env_vars['GROQ_API_KEY'])) {
            $groq_api_key = $env_vars['GROQ_API_KEY'];
        }
    }

    $ai_response_data = null;
    $ai_error = null;

    if ($issue && !empty($groq_api_key) && strpos($groq_api_key, 'gsk_') === 0) {

        // Προετοιμασία των δεδομένων του προβλήματος για το Prompt
        $prompt_title = $issue['title'];
        $prompt_category = $issue['category_name'];
        $prompt_desc = $issue['description'];

        // Καθορισμός αυστηρών οδηγιών προς το μοντέλο για επιστροφή δομημένου JSON
        $system_prompt = "Είσαι ένας έμπειρος βοηθός διαχείρισης προβλημάτων τοπικής αυτοδιοίκησης. "
                       . "Πρέπει να αναλύσεις το τεχνικό πρόβλημα και να επιστρέψεις ΑΠΟΚΛΕΙΣΤΙΚΑ και ΜΟΝΟ ένα έγκυρο JSON αντικείμενο, χωρίς εισαγωγικά κείμενα ή σχόλια εκτός του JSON. "
                       . "Το JSON αντικείμενο πρέπει να περιλαμβάνει ακριβώς τα εξής 3 κλειδιά:\n"
                       . "1. 'priority': Μία σύντομη πρόταση που καθορίζει την προτεινόμενη προτεραιότητα (Χαμηλή / Μεσαία / Υψηλή / Κρίσιμη) μαζί με σύντομη αιτιολόγηση.\n"
                       . "2. 'category_check': Αξιολόγηση αν η περιγραφή ταιριάζει με την επιλεγμένη κατηγορία και διορθωτική πρόταση αν αποκλίνει.\n"
                       . "3. 'actions': Μία δομημένη λίστα (κείμενο με αλλαγές γραμμών) με τις προτεινόμενες ενέργειες για την οριστική επίλυση του προβλήματος.";

        $user_prompt = "Τίτλος: '$prompt_title'\nΚατηγορία: '$prompt_category'\nΠεριγραφή: '$prompt_desc'";

        $url = "https://api.groq.com/openai/v1/chat/completions";
        $data = [
            "model" => "llama-3.3-70b-versatile",
            "messages" => [
                ["role" => "system", "content" => $system_prompt],
                ["role" => "user", "content" => $user_prompt]
            ],
            // Αναγκάζουμε το Groq API να απαντήσει σε JSON μορφή
            "response_format" => ["type" => "json_object"],
            "temperature" => 0.1
        ];

        // Υλοποίηση HTTP Post Request μέσω cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $groq_api_key,
            "Content-Type: application/json"
        ]);

        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status == 200) {
            $result = json_decode($response, true);
            $ai_json_string = $result['choices'][0]['message']['content'] ?? '{}';
            $ai_response_data = json_decode($ai_json_string, true);
        } else {
            $ai_error = "Αδυναμία λήψης ανάλυσης από το AI σύστημα (HTTP Error Code: $http_status).";
        }
    }

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

            <div style="" class="col-lg-4 text-end d-flex justify-content-between align-items-center">
                <span class="text-muted small fw-bold fs-6">#<?= htmlspecialchars($issue['ticket_id']) ?></span>
                <span class="badge bg-info fs-6  <?= $badge_css_class ?>"><?= htmlspecialchars($current_status) ?></span>
                <button class="fs-6 btn btn-sm <?= htmlspecialchars($btn_class) ?> upvote_button" data-ticket="<?= htmlspecialchars($ticket_id) ?>" <?= htmlspecialchars($disabled_attribute) ?>>
                    <span class="vote-icon">👍</span>
                    <span class="vote-count fw-bold"><?= htmlspecialchars($issue['upvotes']) ?></span>
                </button>
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
                    <div class="card-body align-center">
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
            <div class="col-md-6 align-self-center">
                <?php if ($has_image): ?>
                    <div class="card shadow-sm border-0 mb-4 p-2 bg-white">
                        <div class="card-body p-0 d-flex justify-content-center align-items-center" style="min-height: 200px;">
                            <img src="<?= htmlspecialchars($image_path) ?>" 
                                 class="img-fluid rounded" 
                                 alt="Φωτογραφία προβλήματος" 
                                 style="max-height: 562px; width: auto; object-fit: contain;">
                        </div>
                        <div class="text-muted small text-center mt-2 px-2 pb-2">Φωτογραφία Υποβολής</div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                        <span class="text-muted">Δεν υπάρχει εικόνα</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-5 mb-5">
        <div class="col-12">
            <div class="card shadow-sm border-0 border-top border-primary border-4 rounded-3">
                <div class="card-body p-4">
                    <h4 class="card-title text-primary d-flex align-items-center gap-2 mb-4">
                        <span>🤖</span> Σύστημα Αυτόματης Ανάλυσης (AI Insights)
                    </h4>

                    <?php if ($ai_error): ?>
                        <div class="alert alert-warning border-0 shadow-sm rounded mb-0">
                            <strong>Προειδοποίηση:</strong> <?= htmlspecialchars($ai_error) ?>
                        </div>
                    <?php elseif ($ai_response_data): ?>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <h6 class="fw-bold text-dark mb-2">📊 Προτεινόμενη Προτεραιότητα & Αιτιολόγηση</h6>
                                    <p class="text-secondary small mb-0">
                                        <?= htmlspecialchars($ai_response_data['priority'] ?? 'Δεν επιστράφηκαν δεδομένα.') ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100 border">
                                    <h6 class="fw-bold text-dark mb-2">🔍 Έλεγχος & Επιβεβαίωση Κατηγοριοποίησης</h6>
                                    <p class="text-secondary small mb-0">
                                        <?= htmlspecialchars($ai_response_data['category_check'] ?? 'Δεν επιστράφηκαν δεδομένα.') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 bg-light rounded-3 border-start border-4 border-info border-top border-bottom border-end">
                                    <h6 class="fw-bold text-dark mb-2">🛠️ Προτεινόμενα Βήματα και Ενέργειες Επίλυσης</h6>
                                    <div class="text-secondary small mb-0">
                                        <?= nl2br(htmlspecialchars($ai_response_data['actions'] ?? 'Δεν επιστράφηκαν ενέργειες.')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-muted py-4 mb-0 text-center rounded-3">
                            <span class="d-block mb-2 fs-4">🔑</span>
                            Η αυτόματη ανάλυση AI είναι απενεργοποιημένη. Συμπληρώστε ένα έγκυρο <strong>Groq API Key</strong> στο αρχείο <code>config.php</code> για να ενεργοποιηθεί.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
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
    echo '<script src="upvotes.js"></script>'; 
    echo '</body>';
    echo '</html>';
?>