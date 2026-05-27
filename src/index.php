<?php
//Κατά την εκκίνηση της αρχικής σελίδας ελέγχεται αν υπάρχει η βάση δεδομένων και αν δεν υπάρχει δημιουργείται
    require_once 'create_db.php';
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CityReport - Αναφορά Προβλήματος</title>
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
                        <a class="nav-link" href="login.php">Σύνδεση Διαχειριστή</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Υποβολή Νέας Αναφοράς</h4>
                    </div>
                    <div class="card-body">
                        
                        <form id="reportForm"  action = "insertIssue.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-3" style="position: relative;">
                                <label for="title" class="form-label">Τίτλος Προβλήματος <span class="text-danger">*</span></label>
                                <input type="text" class="form-control check-length" id="title" name="title" data-min= "5" data-max = "100" data-feedback="feedback-title">
                                <div class="form-text">Σύντομη περιγραφή (5 έως 100 χαρακτήρες).</div>
                                
                                <div id="feedback-title" class="custom-tooltip"></div> <!-- Θα γεμίσει με ενδεχόμενα μήνυμα σφάλματος από την charCounter.js -->
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Κατηγορία <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" >
                                    <option value="" selected>Επιλέξτε κατηγορία...</option>
                                    <option value="1">Οδοποιία – Λακκούβες</option>
                                    <option value="2">Ηλεκτροφωτισμός</option>
                                    <option value="3">Καθαριότητα</option>
                                    <option value="4">Πράσινο</option>
                                    <option value="5">Εγκαταλελειμμένα Οχήματα</option>
                                    <option value="6">Παιδικές Χαρές</option>
                                    <option value="7">Σήμανση</option>
                                    <option value="8">Άλλο</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Διεύθυνση / Τοποθεσία <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="π.χ. Καραολή και Δημητρίου 80, Πειραιάς">

                                <div id="addressFeedback" class="invalid-feedback">
                                    Η διεύθυνση δεν είναι έγκυρη. Παρακαλώ προσπαθήστε ξανά.
                                </div>
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Περιγραφή <span class="text-danger">*</span></label>
                                <textarea class="form-control check-length" id="description" name="description" rows="5" data-min="10" data-max="1000" data-feedback = "feedback-description"></textarea>
                                <div class="form-text">Αναλυτική περιγραφή του προβλήματος (10 έως 1000 χαρακτήρες).</div>

                                <div id="feedback-description" class="custom-tooltip"></div> <!-- Θα γεμίσει με ενδεχόμενα μήνυμα σφάλματος από την charCounter.js -->
                            </div>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Ανέβασμα Φωτογραφίας (Προαιρετικό)</label>
                                <input class="form-control" type="file" id="photo" name="photo" accept=".jpg, .png, .gif">
                                <div class="form-text">Αποδεκτοί τύποι: .jpg, .png, .gif (Μέγιστο μέγεθος 5ΜΒ).</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="insert" class="btn btn-primary btn-lg">Υποβολή Αναφοράς</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <footer class="bg-dark text-white pt-5 pb-5 mt-5">
      <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0 pe-md-4">
                <h4 class="text-uppercase fw-bold mb-4" style="letter-spacing: 1px;">Δημαρχείο</h4>
                <ul>
                    <li class="mb-3">
                        <strong>Διεύθυνση:</strong><br>
                        <div class="text-light">Καραολή & Δημητρίου 80 Πειραιάς 18450</div>
                    </li>
                    <li class="mb-3">
                        <strong>Τηλέφωνο:</strong><br>
                        <a href="tel:+302106021219" class="text-light"> 210 6021219</a>
                    </li>
                    <li class="mb-3">
                        <strong>Email:</strong><br>
                        <a href="mailto:info@unipi.gr" class="text-light"> info@unipi.gr</a>
                    </li>
                </ul>
                <p class="mt-4 text-secondary small">
                        Πάντα δίπλα σας για όλες σας τις ανάγκες. (Φορέστε Ζώνες).
                </p>
            </div>
            <div class="col-md-6">
                <div id="footer-map" style="height: 300px; width: 100%"></div>   
            </div>
        </div>
      </div>       
    </footer>




    <?php 
    // Ελέγχουμε αν υπάρχει κάποιο status στο URL
    if(isset($_GET['status'])): 
        $status = $_GET['status'];
        $show_popup = false;
        
        // Ορίζουμε μεταβλητές για δυναμικό περιεχόμενο
        $popup_color = "";
        $popup_title = "";
        $popup_body = "";

        if ($status == 'success' && isset($_GET['ticket'])) {
            $show_popup = true;
            $t_id = htmlspecialchars($_GET['ticket']);
            $popup_color = "#198754"; // Πράσινο Bootstrap
            $popup_title = "Επιτυχία!";
            $popup_body = "Η καταχώρηση ολοκληρώθηκε επιτυχώς!<br><br>Αριθμός αναφοράς:<br><strong>{$t_id}</strong>";
        } elseif ($status == 'empty') {
            $show_popup = true;
            $popup_color = "#dc3545"; // Κόκκινο Bootstrap
            $popup_title = "Προσοχή";
            $popup_body = "Παρακαλώ συμπληρώστε όλα τα υποχρεωτικά πεδία!";
        } elseif ($status == 'db_error' || $status == 'error') {
            $show_popup = true;
            $popup_color = "#dc3545"; // Κόκκινο Bootstrap
            $popup_title = "Σφάλμα";
            $popup_body = "Δεν ήταν δυνατή η αποθήκευση της αναφοράς. Παρακαλώ προσπαθήστε αργότερα.";
        }

        // Αν το status ταιριάζει σε κάποιο από τα παραπάνω, εμφανίζουμε το Modal
        if ($show_popup):
    ?>
        <style>
            .custom-window-background {
                position: fixed; display: flex; width: 100%; height: 100%;
                background-color: rgba(0, 0, 0, 0.85); justify-content: center;
                align-items: center; z-index: 9999; top: 0; left: 0;
            }
            #custom-window-close {
                top: 20px; right: 35px; color: white; font-size: 40px;
                font-weight: bold; position: absolute; cursor: pointer;
            }
            #custom-window-close:hover { color: #ccc; }
            .custom-modal-box {
                background-color: white; padding: 40px; border-radius: 12px; 
                text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.5); min-width: 350px;
            }
            .btn-back {
                margin-top: 25px; padding: 10px 20px; color: white; border: none; 
                border-radius: 6px; cursor: pointer; font-size: 16px; 
                font-weight: bold; 
                /* Το χρώμα παίρνει τη δυναμική μεταβλητή της PHP */
                background-color: <?php echo $popup_color; ?>;
            }
            .btn-back:hover { opacity: 0.85; }
        </style>

        <div id="custom-alert-window" class="custom-window-background">
            <span id="custom-window-close">&times;</span>
            <div class="custom-modal-box">
                <h2 style="margin-top: 0; color: <?php echo $popup_color; ?>;"><?php echo $popup_title; ?></h2>
                <p style="font-size: 16px; line-height: 1.6; color: #333;">
                    <?php echo $popup_body; ?>
                </p>
                <button id="btn-back" class="btn-back">Επιστροφή</button>
            </div>
        </div>

        <script>
            // Κλείσιμο παραθύρου και καθαρισμός URL
            function closeAlertWindow() {
                document.getElementById('custom-alert-window').style.display = 'none';
                // Σβήνει το ?status=... από το URL
                window.history.replaceState(null, null, window.location.pathname);
            }
            document.getElementById('custom-window-close').addEventListener('click', closeAlertWindow);
            document.getElementById('btn-back').addEventListener('click', closeAlertWindow);
        </script>
    <?php 
        endif; 
    endif; 
    ?>

















    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="geolocation.js"></script>
    <script src= "charCounter.js"></script>
    <script src = "mapFooter.js"></script>
</body>
</html>