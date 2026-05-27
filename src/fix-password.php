// ένα απλό script για να αλλάζουμε το χαλασμένο hash του admin σε ένα νέο, υγιές hash για τον κωδικό 'admin'

<?php
require_once 'create_db.php';

// Ζητάμε από την PHP να φτιάξει ένα ολοκαίνουργιο, υγιές hash
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);

try {
    // Κάνουμε καθαρό UPDATE στον χρήστη 'admin'
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = :hash WHERE username = 'admin'");
    $stmt->execute([':hash' => $new_hash]);
    
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h2 style='color: green;'>✅ Ο κωδικός διορθώθηκε!</h2>";
    echo "<p>Το χαλασμένο hash αντικαταστάθηκε με επιτυχία.</p>";
    echo "<a href='login.php' style='padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;'>Πήγαινε στο Login</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "Σφάλμα: " . $e->getMessage();
}
?>