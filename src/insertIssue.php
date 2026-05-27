<?php
    // Κώδικας εισαγωγής του issue από τον χρήστη στην db 
    if(isset($_POST['title'], $_POST['category'], $_POST['description'], $_POST['address'], $_POST['latitude'], $_POST['longitude'])){
        include 'db_connect.php';

        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $address = trim($_POST['address']);
        $latitude = trim($_POST['latitude']);
        $longitude = trim($_POST['longitude']);

        if (isset($_POST['category']) && $_POST['category'] !== "") {
            $category = trim($_POST['category']);
        } else {
            $category = ''; // Εδώ το ορίζουμε ρητά ως άδειο
        }

        // Έλεγχος για κενό πεδίο
        if(empty($title) || empty($category) || empty($description) || empty($address) || empty($latitude) || empty($longitude)){
            header("Location: index.php?status=empty");
            exit;
        } else {
            try {
                $sql = "INSERT INTO issues (title, category_id, description, address, latitude, longitude, created_at) 
                        VALUES (:title, :category, :description, :address, :latitude, :longitude, NOW())"; 
                
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':latitude', $latitude);
                $stmt->bindParam(':longitude', $longitude);
                $stmt->execute();

                $last_id = $conn->lastInsertId();
                $ticket_id = "KOROPI-" . str_pad($last_id, 5, "0", STR_PAD_LEFT);
                $photo_path = NULL; 

                if(isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK){
                    $uploadDir = 'uploads/'; 
                    $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $file_name = $ticket_id . '.' . $file_ext; 
                    $photo_path = $uploadDir . $file_name;

                    if(!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)){
                        $photo_path = NULL;
                    }
                }

                $sql_update = "UPDATE issues SET ticket_id = :ticket_id, photo_path = :photo_path WHERE id = :id";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bindParam(':ticket_id', $ticket_id);
                $stmt_update->bindParam(':photo_path', $photo_path);
                $stmt_update->bindParam(':id', $last_id);
                $stmt_update->execute();

                $cookie_name = "user_tickets";
                $tickets_array = []; 
                if(isset($_COOKIE[$cookie_name])){
                    $tickets_array = json_decode($_COOKIE[$cookie_name], true);
                }
                $tickets_array[] = $ticket_id;
                setcookie($cookie_name, json_encode($tickets_array), time() + (86400 * 30), "/");

                header("Location: index.php?status=success&ticket=" . $ticket_id);
                exit;

                

            } catch(PDOException $e) {
                // --- ΕΜΦΑΝΙΣΗ ΠΑΡΑΘΥΡΟΥ ΣΦΑΛΜΑΤΟΣ (false = κόκκινο theme) ---
                header("Location: index.php?status=error");
                exit;
                
            }
        }

    } else {
        header("Location: index.php");
        exit;
    }
?>