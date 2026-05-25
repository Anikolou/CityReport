<?php
        //Το ίδιο και με το index.php
        require_once 'create_db.php';
        require_once 'get_badge_class.php';


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
                    </ul>';
        echo        '<ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Σύνδεση Διαχειριστή</a>
                        </li>
                    </ul>';            
        echo '</div>'; //κλείνει το navbarNav
        echo '</div>'; //κλείνει το container
        echo '</nav>'; //κλείνει το nav

        $sql = "SELECT issues.*, categories.name AS category_name 
                FROM issues JOIN categories ON issues.category_id = categories.category_id
                ORDER BY issues.upvotes DESC, issues.created_at DESC";
        
        $stmt = $pdo->query($sql);

        echo '<div class="container mb-5 flex-grow-1">';
        echo '<h2 class="mb-4 border-bottom pb-2">Αναφερόμενα Προβλήματα</h2>';
?>

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
            echo '<div class="card h-100 shadow-sm border-0">';

            if ($has_image) {
                // Layout Α: Υπάρχει φωτογραφία (Οριζόντιο)
                echo '<div class="row g-0 flex-grow-1">'; // Χρησιμοποιούμε Row g-0 για να ενώσουμε στοιχεία
                
                // 1. Δεξί κομμάτι: Η Φωτογραφία (Order-md-2 την πάει δεξιά)
                echo '<div class="col-md-4 order-md-2 p-3 d-flex align-items-center justify-content-center">';
                //Είναι καλή πρακτική να χρησιμοποιούμε την μέθοδο htmlspecialchars() για αποφυγή αππρόσμενων errors με τα photo paths
                echo '  <img src="' . htmlspecialchars($image_path) . '" class="img-fluid rounded shadow-sm" style="object-fit: cover; height: 100%; width: 100%;" alt="Φωτογραφία προβλήματος">';
                echo '</div>';

                // 2. Αριστερό κομμάτι: Τα Στοιχεία 
                echo '<div class="col-md-8 order-md-1 d-flex flex-column border-end">';
                    
                    // Header (Κατηγορία & Ημερομηνία)
                    echo '<div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom-0">';
                    echo '  <span class="badge bg-primary">'.htmlspecialchars($row['category_name']).'</span>';
                    $date_format = date('d/m/Y', strtotime($row['created_at']));
                    echo '  <small class="text-muted">'.$date_format.'</small>';
                    echo '</div>';

                    // Body (Τίτλος, Περιγραφή, Τοποθεσία)
                    echo '<div class="card-body flex-grow-1">';
                    echo '  <h5 class="card-title">'.htmlspecialchars($row['title']).'</h5>';
                    echo '  <p class="card-text text-secondary small">' . htmlspecialchars($row['description']) . '</p>';
                    echo '  <p class="card-text"><small class="text-muted"><strong>Τοποθεσία:</strong> ' . htmlspecialchars($row['address']) . '</small></p>';
                    echo '</div>';

                    // Footer 
                    echo '<div class="card-footer bg-white d-flex justify-content-between align-items-center border-top-0 mt-auto">';
                    echo '  <div class="d-flex flex-column align-items-center">'; 
                    //Χρησιμοποιείται η get_badge_class για να αποφασίσουμε με τι style θα εμφανιστεί το badge που υποδεικνύει την κατάσταση της αναφοράς του issue
                    $badge_css_class = get_badge_class($current_status);
                    echo '<span class="badge ' . $badge_css_class . ' mb-1">' . htmlspecialchars($current_status) . '</span>';
                    echo '    <small class="text-muted fw-bold">#' . $ticket_id . '</small>';
                    echo '  </div>';
                    echo '  <div class="d-flex align-items-center gap-2">';
                    echo '    <button class="btn btn-sm ' . $btn_class . ' upvote_button" data-ticket="' . $ticket_id . '" ' . $disabled_attribute . '>';
                    echo '      <span class="vote-icon">👍</span> ';
                    echo '      <span class="vote-count fw-bold">' . $row['upvotes'] . '</span>';
                    echo '    </button>';
        
                    // ΠΡΟΣΘΗΚΗ ΚΟΥΜΠΙΟΥ ΓΙΑ ΤΟ COLLAPSE 
                    if ($has_image) {
                        echo '<button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#photoCollapse_' . $ticket_id . '" aria-expanded="false" aria-controls="photoCollapse_' . $ticket_id . '">Προβολή Φωτογραφίας</button>';
                    }

                    //Χρησιμοποιώ την default κλάση από τα badges στο style.css (ανακυκλώνω κώδικα!)
                    echo '<button class="btn btn-sm default" type="button" onclick="window.location.href=\'more.php?ticket_id=' . $ticket_id . '\'">Προβολή Λεπτομερειών!</button>';
                    echo '  </div>'; // κλείνει το d-flex των κουμπιών
                    echo '</div>'; // κλείνει το footer
                echo '</div>'; // κλείνει το col-md-8
                echo '</div>'; // κλείνει το row g-0

            } else {
                // Layout Β: ΔΕΝ υπάρχει φωτογραφία 
                echo '<div class="card-header">';
                echo '<span class="badge bg-primary">'.htmlspecialchars($row['category_name']).'</span>';
                $date_format = date('d/m/Y', strtotime($row['created_at']));
                echo '<small class="text-muted">'.$date_format.'</small>';
                echo '</div>';

                echo '<div class="card-body">';
                echo '<h4 class="card-title">'.htmlspecialchars($row['title']).'</h4>';
                echo '<p class="card-text text-secondary">' . htmlspecialchars($row['description']) . '</p>';
                echo '<p class="card-text"><small class="text-muted"><strong>Τοποθεσία:</strong> ' . htmlspecialchars($row['address']) . '</small></p>';
                echo '</div>';

                echo '<div class="card-footer bg-white d-flex justify-content-between align-items-center border-top-0">';
                echo '<div class="d-flex flex-column">';
                $current_status = !empty($row['status']) ? $row['status'] : 'Υποβλήθηκε';
                //Χρησιμοποιείται η get_badge_class για να αποφασίσουμε με τι style θα εμφανιστεί το badge που υποδεικνύει την κατάσταση της αναφοράς του issue
                $badge_css_class = get_badge_class($current_status);
                echo '<span class="badge ' . $badge_css_class . ' mb-1">' . htmlspecialchars($current_status) . '</span>';
                echo '<small class="text-muted fw-bold">#' . $ticket_id . '</small>';
                echo '</div>';

                echo '<button class="btn btn-sm ' . $btn_class . ' upvote_button" data-ticket="' . $ticket_id . '" ' . $disabled_attribute . '>';
                echo '<span class="vote-icon">👍</span> ';
                echo '<span class="vote-count fw-bold">' . $row['upvotes'] . '</span>';
                echo '</button>';
                //Χρησιμοποιώ την default κλάση από τα badges στο style.css (ανακυκλώνω κώδικα!)
                echo '<button class="btn btn-sm default" type="button" onclick="window.location.href=\'more.php?ticket_id=' . $ticket_id . '\'">Προβολή Λεπτομερειών!</button>';
                echo '</div>'; // κλείνει το card-footer
            }
            
            if ($has_image) {
                // Το βάζουμε στο τέλος της κάρτας, μετά το footer.
                echo '<div class="collapse p-3" id="photoCollapse_' . $ticket_id . '">';
                echo '  <img src="' . htmlspecialchars($image_path) . '" class="img-fluid rounded shadow-sm" alt="Φωτογραφία προβλήματος">';
                echo '</div>';
            }

            echo '</div>'; // Κλείνει το card
            echo '</div>'; // Κλείνει το col-12 col-lg-6
        }

        echo '</div>'; // κλείνει το row g-4
        echo '</div>'; // κλείνει το container mb-5

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

        echo '</body>';
        echo '</html>';
    ?>
