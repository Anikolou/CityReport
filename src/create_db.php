<?php
    $host_name = "db";
    $user_name = "user";
    $user_pass = "password";
    $db_name = "cityreport";

    try{
        // Σύνδεση στον MySQL Server ΧΩΡΙΣ dbname στο DSN string
        $pdo = new PDO("mysql:host=$host_name;charset=utf8mb4", $user_name, $user_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //echo "Σύνδεση στον MySQL Server επιτυχής.<br>";

        // 1. Δημιουργία της Βάσης Δεδομένων
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4");

        // 2. Επιλογή της βάσης δεδομένων cityreport για τις επόμενες εντολές
        $pdo->exec("USE `$db_name`");

        // 3. Δημιουργία Πίνακα Κατηγοριών (categories)
        //Ο πίνακας categories έχει ως primary key του το unique id κάθε κατηγορίες που φτιάχνει από μόνη της η MySQL.

        $createCategoriesTable = "
            CREATE TABLE IF NOT EXISTS `categories` (
                `category_id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `weight` INT NOT NULL,
                `description` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ";
        $pdo->exec($createCategoriesTable);
        //echo "Ο πίνακας 'categories' δημιουργήθηκε επιτυχώς.<br>";

        // 4. Δημιουργία Πίνακα Διαχειριστών (admins)
        $createAdminsTable = "
            CREATE TABLE IF NOT EXISTS `admins` (
                `admin_id` INT  AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(100) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ";
        
        $pdo->exec($createAdminsTable);
        //echo "Ο πίνακας 'admins' δημιουργήθηκε επιτυχώς.<br>";

        // 5. Δημιουργία Πίνακα Αναφορών (issues)
        //Ο πίνακας issues χρησιμοποιεί ως primary key το id κάθε καταχώρησης που γίνεται μόνο του auto increment από την MYSQL
        //Αυτό χρησιμοποιείται για την τελική παραγωγή του unique ticket_id στο αρχείο insertIssue.php
        $createIssuesTable = "
           CREATE TABLE IF NOT EXISTS `issues` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,  
                `ticket_id` VARCHAR(20) UNIQUE,
                `title` VARCHAR(100) NOT NULL,
                `category_id` INT NOT NULL,
                `description` TEXT NOT NULL,
                `address` VARCHAR(255) NOT NULL,
                `latitude` DECIMAL(10, 8) NOT NULL,
                `longitude` DECIMAL(11, 8) NOT NULL,
                `photo_path` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(20) DEFAULT 'Υποβλήθηκε',
                `priority` VARCHAR(20) DEFAULT 'Χαμηλή',
                `upvotes` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE RESTRICT
            );
        ";
        $pdo->exec($createIssuesTable);
        //echo "Ο πίνακας 'issues' δημιουργήθηκε επιτυχώς.<br>";
        


        //Οι κατηγορίες εφόσον είναι στάνταρ και κάτι που θα αλλάζει με τους καιρούς στον δήμο, θα αποθηκευτούν σε ενα json file έτσι ώστε οι αλλαγή τους να είναι προσιτή διαδικασία
        // 1. Ελέγχουμε αν ο πίνακας categories έχει ήδη μέσα δεδομένα
        $checkCategories = $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
        
        // Αν ο πίνακας είναι άδειος (COUNT == 0), τότε μόνο κάνουμε το "φύτεμα"
        if ($checkCategories == 0) {
            
            // Διαβάζουμε το αρχείο JSON ως κείμενο
            $jsonContent = file_get_contents('categories.json');
            
            // Μετατρέπουμε το JSON κείμενο σε πίνακα της PHP
            $categoriesData = json_decode($jsonContent, true);
            
            // Προετοιμάζουμε το SQL query για την εισαγωγή
            $sql = "INSERT INTO `categories` (`name`, `weight`, `description`) 
                    VALUES (:name, :weight, :description)";
            $stmt = $pdo->prepare($sql);
            
            // Τρέχουμε ένα loop για κάθε κατηγορία του JSON αρχείου
            foreach ($categoriesData as $category) {
                $stmt->bindParam(':name', $category['name']);
                $stmt->bindParam(':weight', $category['weight']);
                $stmt->bindParam(':description', $category['description']);
                $stmt->execute();
            }
            
            // echo "Οι κατηγορίες αρχικοποιήθηκαν επιτυχώς από το JSON αρχείο!<br>";
        }

        // Ελέγχουμε αν υπάρχουν ήδη εγγραφές στον πίνακα categories
        $checkEmpty = $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
        
        if ($checkEmpty == 0) {
            $stmt = $pdo->prepare("INSERT INTO `categories` (`name`, `weight`, `description`) VALUES (?, ?, ?)");
            foreach ($categoriesData as $category) {
                $stmt->execute($category);
            }
        }
        //echo "Οι κατηγορίες εισήχθησαν επιτυχώς.<br>";

        //echo "<strong>Η προετοιμασία της βάσης ολοκληρώθηκε επιτυχώς!</strong>";

    } catch (PDOException $e) {
        echo "Σφάλμα κατά την αρχικοποίηση της βάσης: " . $e->getMessage();
        exit();
    }
?>