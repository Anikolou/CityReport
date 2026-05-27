<?php
require_once 'create_db.php'; // Η σύνδεσή σου με το PDO

// Αποθήκευση cookies για αυτόματη σύνδεση (Remember Me)
if (!isset($_SESSION['admin_logged_in']) && isset($_COOKIE['remember_admin'])) {
    $token = $_COOKIE['remember_admin'];
    
    // Ψάχνουμε τον admin που έχει αυτό το token
    $stmt = $pdo->prepare("SELECT admin_id, username FROM admins WHERE remember_token = :token");
    $stmt->execute([':token' => $token]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Το token βρέθηκε, κάνουμε login τον χρήστη
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        // Ανακατεύθυνση στον πίνακα ελέγχου (άλλαξέ το στο αρχείο που θέλεις)
        header("Location: admin-dashboard.php"); 
        exit;
    }
}

// =========================================================
// 2. ΕΛΕΓΧΟΣ ΑΝ ΕΙΝΑΙ ΗΔΗ ΣΥΝΔΕΔΕΜΕΝΟΣ ΜΕΣΩ SESSION
// =========================================================
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin-dashboard.php");
    exit;
}


// Επεξεργασία φόρμας σύνδεσης
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']); // Επιστρέφει true αν το κουτάκι είναι τσεκαρισμένο

    if (!empty($username) && !empty($password)) {
        // Βρίσκουμε τον χρήστη
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ελέγχουμε αν υπάρχει ο χρήστης ΚΑΙ αν ταιριάζει ο κωδικός
        if ($admin && password_verify($password, $admin['password_hash'])) {
            
            // Επιτυχής Σύνδεση: Αποθήκευση στο Session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];

            // Αν επέλεξε "Να με θυμάσαι"
            if ($remember) {
                // Δημιουργούμε ένα τυχαίο, ασφαλές token
                $token = bin2hex(random_bytes(32)); 
                
                // Το αποθηκεύουμε στη βάση για αυτόν τον χρήστη
                $update_stmt = $pdo->prepare("UPDATE admins SET remember_token = :token WHERE admin_id = :admin_id");
                $update_stmt->execute([':token' => $token, ':admin_id' => $admin['admin_id']]);
                
                // Δημιουργούμε το Cookie (Διάρκεια: 30 ημέρες)
                setcookie('remember_admin', $token, time() + (86400 * 30), "/"); 
            }

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
                        <a href="index.php" class="text-decoration-none small text-muted">← Επιστροφή στην αρχική</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>