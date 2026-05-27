<?php
require_once 'create_db.php';

// Ελέγχουμε αν ο χρήστης έχει το Cookie σύνδεσης
if (isset($_COOKIE['admin_token'])) {
    $token = $_COOKIE['admin_token'];

    // Ακύρωση του Token στη Βάση Δεδομένων
    // Βάζουμε NULL στο remember_token για να αχρηστευτεί άμεσα το κλειδί
    $stmt = $pdo->prepare("UPDATE admins SET remember_token = NULL WHERE remember_token = :token");
    $stmt->execute([':token' => $token]);

    // Διαγραφή του Cookie από τον Browser
    // Ορίζουμε τον χρόνο λήξης στο παρελθόν για να το καταστρέψει ο browser
    setcookie('admin_token', '', time() - 3600, '/');
}

// Ανακατεύθυνση του χρήστη 
header("Location: index.php");
exit;
?>