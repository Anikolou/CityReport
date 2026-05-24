<?php
//Συνδέσου με την βάση
require_once 'create_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];
    $cookie_name = 'upvoted_' . $ticket_id;
    //Έλεγχος αν έχει ξαναψηφίσει ο χρήστης. Γίνεται έλεγχος μέσα σπό το cookie της αναφοράς του
    if (isset($_COOKIE[$cookie_name])) {
        // Αν υπάρχει, σταματάμε τη διαδικασία και στέλνουμε μήνυμα σφάλματος
        //Το cookie ταξιδεύει σαν JSON file
        echo json_encode([
            'status' => 'error', 
            'message' => 'Έχετε ήδη ψηφίσει αυτή την αναφορά!'
        ]);
        exit(); // Σταματάει η διαδικασία εδώ
    }
    //Ανανέωση του πλήθους upvotes του προβλήματος στην db
    $sql = "UPDATE issues SET upvotes = upvotes + 1 WHERE ticket_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_id]);

    setcookie($cookie_name, 'true', time() + (30 * 24 * 3600), "/");

    //Επιστρέφουμε το νεο πλήθος upvotes της αναφοράς στον πίνακα issues στην db
    $stmt = $pdo->prepare("SELECT upvotes FROM issues WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $new_count = $stmt->fetchColumn();

    //Επιστρέφουμε πίσω σε JSON απάντηση του php κώδικα στην js για λόγους συγχρονισμού και μηνυμάτων debugging
    echo json_encode(['status' => 'success','new_count' => $new_count]);

    exit();

}
?>