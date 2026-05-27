<?php
require_once 'create_db.php'; 

//Έλεγχος συνδεδεμένου admin μέσω του remember_token της βάσης δεδομένων στο cookie
if (isset($_COOKIE['admin_token'])) {
    $token = $_COOKIE['admin_token'];
    
    // Ψάχνουμε αν το token υπάρχει στη βάση
    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE remember_token = :token");
    $stmt->execute([':token' => $token]);
    
    if ($stmt->fetch()) {
        // Το token ισχύει, άρα τον στέλνουμε κατευθείαν στο dashboard
        header("Location: admin-dashboard.php"); 
        exit;
    }
}

// Φόρμα σύνδεσης και επεξεργασία παρακάτω
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); 

    if (!empty($username) && !empty($password)) {
        
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            
            // Παράγουμε ένα ολοκαίνουργιο τυχαίο token
            $token = bin2hex(random_bytes(32)); 
            
            // Το αποθηκεύουμε στη βάση για αυτόν τον admin_id
            $update_stmt = $pdo->prepare("UPDATE admins SET remember_token = :token WHERE admin_id = :id");
            $update_stmt->execute([':token' => $token, ':id' => $admin['admin_id']]);
            
            // Υπολογισμός διάρκειας Cookie
            // Αν τσέκαρε το κουτάκι τότε το cookie θα έχει διάρκεια 30 μέρες. 
            // Αν όχι τότε θα έχει διάρκεια 0 (λήγει με το κλείσιμο του browser)
            if($remember) {
                $cookie_expiration = time() + (86400 * 30);
            } else {
                $cookie_expiration = 0;
            }
            
            // Θέτουμε το Cookie
            setcookie('admin_token', $token, $cookie_expiration, "/"); 

            // Μεταφορά στο Dashboard
            header("Location: admin-dashboard.php");
            exit;
        } else {
            $error = 'Λάθος όνομα χρήστη ή κωδικός.';
        }
    } else {
        $error = 'Παρακαλώ συμπληρώστε όλα τα πεδία.';
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση Διαχειριστή - CityReport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold">Σύνδεση Διαχειριστή</h4>
                        <p class="text-muted small">Εισάγετε τα στοιχεία σας για να συνεχίσετε</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-bold text-muted">Όνομα Χρήστη</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-bold text-muted">Κωδικός</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small" for="remember">Να με θυμάσαι</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Είσοδος</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-decoration-none small text-muted">Επιστροφή στην αρχική</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>