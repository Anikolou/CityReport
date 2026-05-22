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
                        
                        <form id="reportForm" action="insertIssue.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Τίτλος Προβλήματος <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" minlength="5" maxlength="100" required>
                                <div class="form-text">Σύντομη περιγραφή (5 έως 100 χαρακτήρες).</div>
                            </div>

                            <div class="mb-3">
                                <label for="category" class="form-label">Κατηγορία <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" selected disabled>Επιλέξτε κατηγορία...</option>
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
                                <input type="text" class="form-control" id="address" name="address" placeholder="π.χ. Καραολή και Δημητρίου 80, Πειραιάς" required>

                                <div id="addressFeedback" class="invalid-feedback">
                                    Η διεύθυνση δεν είναι έγκυρη. Παρακαλώ προσπαθήστε ξανά.
                                </div>
                                <!-- Κρυφά πεδία στο οποία επιστρέφονται οι τιμές από το geolocation.js-->
                                <input type="hidden" id = "latitude" name = "latitude">
                                <input type = "hidden" id = "longitude" name = "longitude">

                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Περιγραφή <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" minlength="10" maxlength="1000" required></textarea>
                                <div class="form-text">Αναλυτική περιγραφή του προβλήματος (10 έως 1000 χαρακτήρες).</div>
                            </div>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Ανέβασμα Φωτογραφίας (Προαιρετικό)</label>
                                <input class="form-control" type="file" id="photo" name="photo" accept=".jpg, .png, .gif">
                                <div class="form-text">Αποδεκτοί τύποι: .jpg, .png, .gif (Μέγιστο μέγεθος 5ΜΒ).</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name = "insert" class="btn btn-primary btn-lg">Υποβολή Αναφοράς</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white pt-5 pb-5 mt-5">

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
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="geolocation.js"></script>
    <script src = "mapFooter.js"></script>
</body>
</html>