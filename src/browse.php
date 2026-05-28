<?php
        //Το ίδιο και με το index.php
        require_once 'create_db.php';
        require_once 'get_badge_class.php';

        
        //Ελέγχουμε τα cookies του χρήστη για να δουμε αν ο user είναι συνδεδεμένος σαν admin
        //Aν ναι, τραβάμε το όνομά του για να το εμφανίσουμε στο navbar
        $logged_in_admin = null;

        if (isset($_COOKIE['admin_token'])) {
            $stmt = $pdo->prepare("SELECT full_name FROM admins WHERE remember_token = :token");
            $stmt->execute([':token' => $_COOKIE['admin_token']]);
            $logged_in_admin = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        //Γενικά είναι καλύτερο να ελέγχουμε αν ο χρήστης είναι ο admin μέσω των cookies γιατί είναι κρυφά. Θα μπορούσαμε να κάνουμε GET στο URL με status admin αλλά τότε θα μπορούσε να δει ο καθένας ποιος είναι ο admin


        //Τρόπος για να χειριστούμε την αναζήτηση ενός συγκεκριμένου ticket μέσω του ID του. Ελέγχουμε αν υπάρχει το GET parameter 'ticket_search' και αν δεν είναι κενό. 
        //Αν ισχύει, ανακατευθύνουμε τον χρήστη στη σελίδα more.php με το αντίστοιχο ticket_id.
        // Ορίζουμε μια κενή μεταβλητή για το σφάλμα
        $search_error = ''; 

        if (isset($_GET['ticket_search']) && trim($_GET['ticket_search']) !== '') {
            $search_id = trim($_GET['ticket_search']); 

            // Ρωτάμε τη βάση αν υπάρχει αυτό το ticket_id
            $check_stmt = $pdo->prepare("SELECT ticket_id FROM issues WHERE ticket_id = :id");
            $check_stmt->execute([':id' => $search_id]);

            // 2. Ελέγχουμε πόσες γραμμές βρήκε
            if ($check_stmt->rowCount() > 0) {
                // Υπάρχει! Κάνουμε την ανακατεύθυνση κανονικά
                header("Location: more.php?ticket_id=" . urlencode($search_id));
                exit; 
            } else {
                // Δεν υπάρχει! Ορίζουμε το μήνυμα λάθους
                $search_error = "Το Ticket ID <strong>" . htmlspecialchars($search_id) . "</strong> δεν βρέθηκε. Δοκιμάστε ξανά.";
            }
        }

        $cat_stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC");
        $all_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Διαβάζουμε τι επέλεξε ο χρήστης (αν δεν επέλεξε κάτι, η προεπιλογή είναι 'all' ή 'desc')
        $selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';
        $selected_status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $sort_date = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
        //Διαβάζουμε αν επέλεξε ο χρήστης (μόνο για τον admin θα χρησιμοποιείται) επίλεξε κάποια προτεραιότητα προβλημάτων. Στην αρχή προβάλλονται όλα τα προβλήματα ανεξαρτήτου προτεραιότητας.
        $selected_priority = isset($_GET['priority']) ? $_GET['priority'] : 'all';

        // Ξεκινάμε να "χτίζουμε" τη βασική εντολή SQL.
        // Εδώ ενσωματώνουμε τον τύπο υπολογισμού του Priority Score "on the fly"!
        $sql = "SELECT issues.*, categories.name AS category_name,
                (
                    (categories.weight * 2) + 
                    (issues.upvotes * 0.5) + 
                    CASE 
                        WHEN TIMESTAMPDIFF(HOUR, issues.created_at, NOW()) <= 24 THEN 5
                        WHEN TIMESTAMPDIFF(HOUR, issues.created_at, NOW()) <= 72 THEN 3
                        ELSE 1
                    END
                ) AS priority_score
                FROM issues 
                JOIN categories ON issues.category_id = categories.category_id";

        // Εδώ θα μαζέψουμε τα κομμάτια για το "WHERE" (τα φίλτρα)
        $where_clauses = [];
        $params = [];

        // Έλεγχος Κατηγορίας
        if ($selected_category !== 'all') {
            $where_clauses[] = "issues.category_id = :cat_id";
            $params[':cat_id'] = $selected_category;
        }

        // Έλεγχος Κατάστασης
        if ($selected_status !== 'all') {
            if ($selected_status === 'Υποβλήθηκε') {
                $where_clauses[] = "(issues.status = :status OR issues.status IS NULL OR issues.status = '')";
            } else {
                $where_clauses[] = "issues.status = :status";
            }
            $params[':status'] = $selected_status;
        }

        //έλεγχος αν ο χρήστης είναι ο admin και αν υπάρχει το filter
        if ($logged_in_admin && $selected_priority !== 'all') {
            $where_clauses[] = "issues.priority = :priority";
            $params[':priority'] = $selected_priority;
        }

        // Αν ο χρήστης έχει επιλέξει έστω και ένα φίλτρο, τα ενώνουμε με "AND" και τα κολλάμε στο $sql
        if (count($where_clauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        //Τέλος, προσθέτουμε την ταξινόμηση (ΜΟΝΟ για τον admin εμφανίζονται με σειρά priority score) 
        //Αν ο χρήστης δεν είναι ο admin τότε τα issues εμφανίζονται με βάση την ημερομηνία δημιουργίας (τα πιο πρόσφατα πρώτα).
        if ($logged_in_admin) {
            $sql .= " ORDER BY priority_score DESC, ";
        } else {
            $sql .= " ORDER BY ";
        }
        
        if ($sort_date === 'asc') {
            $sql .= "issues.created_at ASC";
        } else {
            $sql .= "issues.created_at DESC";
        }

        //Εκτελούμε το τελικό ερώτημα.
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
?>

<?php

        //εισάγουμε το πάνω navigation menu της αρχικής σελίδας index.php
        echo '<!DOCTYPE html>';
        echo '<html lang="el">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>CityReport - Προβολή Προβλημάτων</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />'; 
        echo '<link rel="stylesheet" href="style.css">'; 
        echo '</head>';
        echo '<body class="bg-light d-flex flex-column min-vh-100">';

        echo '<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">';
        echo '<div class="container">';
        echo '<a class="navbar-brand" href="index.php">CityReport</a>';
        echo '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>';
        echo '<div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Αναφορά Προβλήματος</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="browse.php">Προβολή Προβλημάτων</a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                <li class="nav-item">';
?>                    
                    <!-- Εμφάνιση διαφορετικών επιλογών στο navbar ανάλογα με το αν ο admin είναι συνδεδεμένος ή όχι -->
                    <?php if ($logged_in_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-dashboard.php">Γεια σας, <?= htmlspecialchars($logged_in_admin['full_name']) ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Αποσύνδεση</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Σύνδεση Διαχειριστή</a>
                        </li>
                    <?php endif;?>
                </li>
            </ul> 
                   
        </div> <!-- κλείνει το navbarNav -->
    </div> <!-- κλείνει το container -->
</nav><!-- κλείνει το nav -->

        <!-- Έχω αφαιρέσει το προηγούμενο sql query για να συμβαδίζει με τα νέα φίλτρα που προσθέσαμε. -->
        <!-- Τώρα το $stmt περιέχει το αποτέλεσμα του δυναμικού ερωτήματος που φτιάξαμε παραπάνω, το οποίο λαμβάνει υπόψη τα φίλτρα. -->

<div class="container mb-5 flex-grow-1">
        <h2 class="mb-4 border-bottom pb-2">Αναφερόμενα Προβλήματα</h2>

        <!-- Φόρμα μέσω της οποίας μπορεί ο χρήστης να αναζητήσει ένα συγκεκριμένο ticket μέσω του ID του. Στέλνει το αίτημα στο ίδιο αρχείο (browse.php) με μέθοδο GET. -->

        <form method="GET" action="browse.php" class="bg-white p-4 shadow-sm rounded border mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label for="ticket_search" class="form-label small fw-bold text-muted mb-1">Αναζήτηση με Ticket ID</label>
                    <input type="text" name="ticket_search" id="ticket_search" class="form-control form-control-sm" placeholder="π.χ. KOROPI-00001" required>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                </div>
            </div>

            <?php if (!empty($search_error)): ?>
                <div class="alert alert-danger mt-3 mb-0 py-2 small">
                    <?= $search_error ?>
                </div>
            <?php endif; ?>
        </form>


                <form method="GET" action="browse.php" class="bg-white p-4 shadow-sm rounded border mb-4">
            <h6 class="mb-3 text-secondary fw-bold border-bottom pb-2">Φιλτράρισμα Προβλημάτων</h6>
            <div class="row g-3 align-items-end">
                
                <div class="col-md">
                    <label for="category" class="form-label small fw-bold text-muted mb-1">Κατηγορία</label>
                    <select name="category" id="category" class="form-select form-select-sm">
                        <option value="all">Όλες</option>
                        <!-- Εδώ κάνουμε loop σε όλες τις κατηγορίες που έχουμε τραβήξει από τη βάση και τις εμφανίζουμε ως επιλογές στο dropdown. -->
                        <!-- Αν κάποια κατηγορία είναι αυτή που έχει επιλέξει ο χρήστης, την κάνουμε "selected" για να φαίνεται η επιλογή του. -->
                        <?php foreach($all_categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category_id']) ?>" <?= ($selected_category == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md">
                    <label for="status" class="form-label small fw-bold text-muted mb-1">Κατάσταση</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <!-- Εδώ κάνουμε loop σε όλες τις καταστάσεις που έχουμε τραβήξει από τη βάση και τις εμφανίζουμε ως επιλογές στο dropdown. -->
                        <!-- Αν κάποια κατάσταση είναι αυτή που έχει επιλέξει ο χρήστης, την κάνουμε "selected" για να φαίνεται η επιλογή του. -->
                        <option value="all">Όλες</option>
                        <option value="Υποβλήθηκε" <?= ($selected_status == 'Υποβλήθηκε') ? 'selected' : '' ?>>Υποβλήθηκε</option>
                        <option value="Σε Εξέλιξη" <?= ($selected_status == 'Σε Εξέλιξη') ? 'selected' : '' ?>>Σε Εξέλιξη</option>
                        <option value="Επιλύθηκε" <?= ($selected_status == 'Επιλύθηκε') ? 'selected' : '' ?>>Επιλύθηκε</option>
                    </select>
                </div>
                
                <div class="col-md">
                    <label for="sort" class="form-label small fw-bold text-muted mb-1">Ημερομηνία</label>
                    <select name="sort" id="sort" class="form-select form-select-sm">
                        <option value="desc" <?= ($sort_date == 'desc') ? 'selected' : '' ?>>Πιο πρόσφατα πρώτα</option>
                        <option value="asc" <?= ($sort_date == 'asc') ? 'selected' : '' ?>>Πιο παλιά πρώτα</option>
                    </select>
                </div>

                <?php if ($logged_in_admin): ?>
                    <div class="col-md">
                        <label for="priority" class="form-label small fw-bold text-primary mb-1">
                            <i class="bi bi-shield-lock me-1"></i>Προτεραιότητα (Admin)
                        </label>
                        <select name="priority" id="priority" class="form-select form-select-sm border-primary">
                            <option value="all" <?= ($selected_priority == 'all') ? 'selected' : '' ?>>Όλες</option>
                            <option value="Χαμηλή" <?= ($selected_priority == 'Χαμηλή') ? 'selected' : '' ?>>Χαμηλή</option>
                            <option value="Μεσαία" <?= ($selected_priority == 'Μεσαία') ? 'selected' : '' ?>>Μεσαία</option>
                            <option value="Υψηλή" <?= ($selected_priority == 'Υψηλή') ? 'selected' : '' ?>>Υψηλή</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-auto d-grid">
                    <button type="submit" class="btn btn-sm btn-primary px-4">Εφαρμογή Φίλτρων</button>
                </div>

            </div>
        </form>

<?php
        // Αλλάζουμε το Grid σε col-12 col-lg-6 
        echo '<div class="row g-4">';

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // ΤΡΑΒΑΜΕ ΤΟ TICKET ID από την αρχή για να το έχουμε διαθέσιμο σε όλη την κάρτα
            $ticket_id = $row['ticket_id'];

            //Έλεγχος COOKIE για upvote
            $cookie_name = 'upvoted_' . $ticket_id;
            $has_voted = isset($_COOKIE[$cookie_name]);

            //Παίρνουμε το status για κάθε αναφορά για να το χρησιμοποιήσουμε στην get_badge_class
            if (!empty($row['status'])) {
                $current_status = $row['status'];
            } else {
                $current_status = 'Υποβλήθηκε';
            }

            if ($has_voted) {
                $btn_class = 'btn-secondary'; 
                $disabled_attribute = 'disabled'; 
            } 
            else {
                $btn_class = 'btn-outline-primary'; 
                $disabled_attribute = ''; 
            }

            // Ψάχνουμε να βρούμε την φώτο μέσα στον φάκελο uploads
            $image = glob("uploads/".$ticket_id.".*");
            $has_image = !empty($image);
            $image_path = $has_image ? $image[0] : '';

            // Κεντρικό Grid Column (col-lg-6 για 2 ανά σειρά)
            echo '<div class="col-12 col-lg-6">';
            echo '<div class="card h-100 shadow-sm border-0 d-flex flex-column">';

           //HEADER ΚΑΡΤΑΣ
            echo '<div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom-0 pb-0">';
            echo '<span class="badge bg-primary">'.htmlspecialchars($row['category_name']).'</span>';
            $date_format = date('d/m/Y', strtotime($row['created_at']));
            echo '<small class="text-muted">'.$date_format.'</small>';
            echo '</div>';

            // ΚΥΡΙΩΣ ΣΩΜΑ (Χωρισμένο σε Αριστερή και Δεξιά στήλη)
            echo '<div class="card-body d-flex flex-column">';
            echo '<div class="row flex-grow-1 align-items-center">'; // align-items-center για να κεντράρει κάθετα το grid με το κείμενο

            // ΑΡΙΣΤΕΡΗ ΣΤΗΛΗ: Τίτλος, Περιγραφή, Τοποθεσία
            echo '<div class="col-12 col-xl-7 d-flex flex-column mb-3 mb-xl-0">';
            echo '<h5 class="card-title">'.htmlspecialchars($row['title']).'</h5>';
            echo '<p class="card-text text-secondary small flex-grow-1 mb-2">' . htmlspecialchars($row['description']) . '</p>';
            echo '<p class="card-text mb-0"><small class="text-muted"><strong>Τοποθεσία:</strong> ' . htmlspecialchars($row['address']) . '</small></p>';
            echo '</div>';

            //ΔΕΞΙΑ ΣΤΗΛΗ: Το 2x2 Grid με τα κουμπιά και το Score
            echo '<div class="col-12 col-xl-5">';
            echo '<div class="row g-2">'; // g-2 δημιουργεί ομοιόμορφο κενό ανάμεσα στα 4 στοιχεία

            // 1. ΠΑΝΩ ΑΡΙΣΤΕΡΑ: Upvotes
            echo '<div class="col-6 d-grid">';
            echo '<button class="btn btn-sm ' . $btn_class . ' upvote_button" data-ticket="' . $ticket_id . '" ' . $disabled_attribute . '>';
            echo '<span class="vote-icon">👍</span> <span class="vote-count fw-bold">' . $row['upvotes'] . '</span>';
            echo '</button>';
            echo '</div>';

            // 2. ΠΑΝΩ ΔΕΞΙΑ: Priority Score (Μόνο για Admin)
            echo '<div class="col-6 d-grid">';
            if ($logged_in_admin) {
                $score = number_format($row['priority_score'], 1);
                // Βάζουμε d-flex και align-items-center για να μοιάζει με κουμπί σε μέγεθος
                echo '<span class="badge bg-dark text-warning border border-warning d-flex align-items-center justify-content-center" title="Priority Score" style="font-size: 0.85rem;">';
                echo '<i class="bi bi-graph-up-arrow me-1"></i>Score: ' . $score;
                echo '</span>';
            } else {
                echo '<div></div>'; // Κενό div για να μη χαλάσει το πλέγμα αν δεν είναι admin
            }
            echo '</div>';

            // 3. ΚΑΤΩ ΑΡΙΣΤΕΡΑ: Προβολή Φωτογραφίας
            echo '<div class="col-6 d-grid">';
            if ($has_image) {
                echo '<button class="btn btn-info custom-popup-btn p-1" style="font-size: 0.75rem; font-weight: bold;" type="button" data-image-src="' . htmlspecialchars($image_path) . '">Φωτογραφία</button>';
            } else {
                echo '<div></div>'; // Κενό div αν δεν υπάρχει φωτό
            }
            echo '</div>';

            // 4. ΚΑΤΩ ΔΕΞΙΑ: Προβολή Λεπτομερειών
            echo '<div class="col-6 d-grid">';
            echo '<button class="btn default p-1" style="font-size: 0.75rem; font-weight: bold; background-color: #f8f9fa; border: 1px solid #ddd;" type="button" onclick="window.location.href=\'more.php?ticket_id=' . $ticket_id . '\'">Λεπτομέρειες</button>';
            echo '</div>';

            echo '</div>'; // Κλείνει το row g-2 (Το 2x2 πλέγμα)
            echo '</div>'; // Κλείνει η δεξιά στήλη

            echo '</div>'; // Κλείνει το κεντρικό row του Body
            echo '</div>'; // Κλείνει το card-body

            //FOOTER ΚΑΡΤΑΣ: Status και Ticket ID 
            echo '<div class="card-footer bg-white d-flex flex-column align-items-start border-top-0 pt-0 pb-3">';
            $badge_css_class = get_badge_class($current_status);
            echo '<span class="badge ' . $badge_css_class . ' mb-1">' . htmlspecialchars($current_status) . '</span>';
            echo '<small class="text-muted fw-bold">#' . $ticket_id . '</small>';
            echo '</div>';

            echo '</div>'; // Κλείνει το card
            echo '</div>'; // Κλείνει το col-12 col-lg-6
        }

        echo '</div>'; // κλείνει το row g-4
        echo '</div>'; // κλείνει το container mb-5

        //Η html για το window που θα εμφανίζεται οταν ο χρήστης πατήσει το κουμπί για προεπισκόπηση της φωτογραφίας
        // Τοποθετημένο ΜΙΑ ΦΟΡΑ, έξω από το while loop!
        echo '<div id="custom-image-window" class="custom-window-background">';
        echo '<span id="custom-window-close">&times;</span>';
        echo '<img id="custom-window-img" src="" alt="Προβολή Φωτογραφίας">';
        echo '</div>';

        // Εισαγωγή του footer από το αρχείο index.php
        echo ' <footer class="bg-dark text-white pt-5 pb-5 mt-5">
        <div class="container">
            <div class="row align-items-center">

                <div class="col-md-6 mb-4 mb-md-0 pe-md-4">
                    <h4 class="text-uppercase fw-bold mb-4" style="letter-spacing: 1px;">Δημαρχείο</h4>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <strong>Διεύθυνση:</strong><br>
                            <div class="text-light">Καραολή & Δημητρίου 80 Πειραιάς 18450</div>
                        </li>
                        <li class="mb-3">
                            <strong>Τηλέφωνο:</strong><br>
                            <a href="tel:+302106021219" class="text-light text-decoration-none"> 210 6021219</a>
                        </li>
                        <li class="mb-3">
                            <strong>Email:</strong><br>
                            <a href="mailto:info@unipi.gr" class="text-light text-decoration-none"> info@unipi.gr</a>
                        </li>
                    </ul>
                    <p class="mt-4 text-secondary small">
                            Πάντα δίπλα σας για όλες σας τις ανάγκες. (Φορέστε Ζώνες).
                    </p>
                </div>
                    
                <div class="col-md-6">
                    <div id="footer-map" style="height: 300px; width: 100%; z-index: 1;"></div>   
                </div>
            </div>
        </div>       
        </footer>';

        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>'; 
        echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>'; 
        echo '<script src="mapFooter.js"></script>';  
        echo '<script src="upvotes.js"></script>';
        echo '<script src="photo_window.js"></script>'; 

        echo '</body>';
        echo '</html>';
    ?>
