<?php
    //Το ίδιο και με το index.php
    require_once'create_db.php';

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
    echo '<body class="bg-light">';

    echo'<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">';
    echo'<div class="container">';
    echo'<a class="navbar-brand" href="index.php">CityReport</a>';
    echo'<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>';
    echo '<div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Αναφορά Προβλήματος</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="browse.php">Προβολή Προβλημάτων</a>
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

    echo '<div class = "container mb-5">';
    echo '<h2 class= "mb-4 border- bottom pb-2">Αναφερόμενα Προβλήματα</h2>';
    echo '<div class = "row g-4">';

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        //Έλεγχος COOKIE για upvote
        $cookie_name = 'upvoted_' . $row['ticket_id'];
        $has_voted = isset($_COOKIE[$cookie_name]);
    
        if ($has_voted) {
        $btn_class = 'btn-secondary'; 
        $disabled_attribute = 'disabled'; 
        } 
        else {
        $btn_class = 'btn-outline-primary'; 
        $disabled_attribute = ''; 
        }

        echo '<div class = "col-md-6 col-lg-4">';
        echo '<div class = "card h-100 shadow-sm border-0">';

        echo'<div class = "card-header bg-white d-flex justify-content-between align-items-center">';
        echo'<span class="badge-bg-primary">'.htmlspecialchars($row['category_name']).'</span>';

        $date_format = date('d/m/Y', strtotime($row['created_at']));
        echo'<small class = "text-muted">'.$date_format.'</small>';
        echo'</div>';

        echo'<div class = "card-body">';
        echo'<h4 class="card_title">'.htmlspecialchars($row['title']).'</h4>';
        echo'<p class="card-text text-secondary">' . htmlspecialchars($row['description']) . '</p>';
        echo '<p class="card-text"><small class="text-muted"><strong>Τοποθεσία:</strong> ' . htmlspecialchars($row['address']) . '</small></p>';
        echo'</div>';

        echo'<div class = "card-footer bg-white d-flex justify-content-between align-items-center">';
        echo '<div class="d-flex flex-column">';
        echo '<span class="badge mb-1">' . htmlspecialchars($row['status']) . '</span>';
        echo '<small class="text-muted fw-bold">#' . htmlspecialchars($row['ticket_id']) . '</small>';
        echo '</div>';
        echo '<button class="btn btn-sm ' . $btn_class . ' upvote_button" data-ticket="' . $row['ticket_id'] . '" ' . $disabled_attribute . '>';
        echo '<span class="vote-icon">👍</span> ';
        echo '<span class="vote-count fw-bold">' . $row['upvotes'] . '</span>';
        echo '</button>';
        echo'</div>';
        echo'</div>';
        echo'</div>';
    }

    echo'</div>'; //κλείνει το row g-4
    echo'</div>'; //κλείνει το container mb-5

    //Εισαγωγή του footer από το αρχείο index.php
    echo ' <footer class="bg-dark text-white pt-5 pb-5 mt-5">

      <div class = "container">
        <div class = "row align-items-center">

            <div class= "col-md-6 mb-4 mb-md-0 pe-md-4">
                <h4 class= "text-uppercase fw-bold mb-4" style = "letter-spacing: 1px;">Δημαρχείο</h4>

                <ul>
                    <li class = "mb-3">
                        <strong>Διεύθυνση:</strong><br>
                        <div class = "text-light">Καραολή & Δημητρίου 80 Πειραιάς 18450</div>
                    </li>
                    
                    <li class = "mb-3">
                        <strong>Τηλέφωνο:</strong><br>
                        <a href= "tel:+302106021219" class = "text-light"> 210 6021219</a>
                    </li>

                    <li class = "mb-3">
                        <strong>Email:</strong><br>
                        <a href= "mailto:info@unipi.gr" class = "text-light"> info@unipi.gr</a>
                    </li>

                </ul>

                <p class = "mt-4 text-secondary small">
                        Πάντα δίπλα σας για όλες σας τις ανάγκες. (Φορέστε Ζώνες).
                </p>
            </div>
                 

                <div class = "col-md-6">
                    <div id = "footer-map" style = "height: 300px; width: 100%"></div>   
                </div>
        </div>
      </div>       
    </footer>';

    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>'; 
    echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>'; //προσοχή πρώτα φορτώνεται το leaflet
    echo '<script src="mapFooter.js"></script>';  //Mετά το script που χρησιμοποιεί το leaflet
    echo '<script src="upvotes.js"></script>'; 

    echo'</body>';
    echo'</html>';
?>