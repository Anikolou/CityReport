<?php
//κώδικας εισαγωγής του issue από τον χρήστη στην db 
    if(isset($_POST['title'], $_POST['category'],$_POST['description'],$_POST['address'],$_POST['latitude'],$_POST['longitude'])){
        include 'db_connect.php';

        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $address = trim($_POST['address']);
        $latitude = trim($_POST['latitude']);
        $longitude = trim($_POST['longitude']);

        //έλεγχος για κάποιο κενό πεδίο
        if(empty($title) || empty($category) || empty($description) || empty($address) ||empty($latitude) || empty($longitude)){
            echo "Please complete all form fields!";
        }else{
            try{
                //SQL code για εισαγωγή δεδομένων στην db
                $sql = "INSERT INTO issues (title, category_id, description, address, latitude, longitude, created_at)VALUES (:title, :category, :description, :address, :latitude, :longitude, NOW())"; 
                //Η συνάρτηση NOW() της MYSQL επιστρέφει το ακριβές timestamp της εισαγωγής του issue στην db
                //Φτιάχουμε το SQL code και το στέλνουμε στην db (δένουμε τις παραμέτρους του query με τις μεταβλητές της php)      
                $stmt = $conn-> prepare($sql);
                $stmt -> bindparam(':title',$title);
                $stmt -> bindparam(':category',$category);
                $stmt -> bindparam(':description',$description);
                $stmt -> bindparam(':address',$address);
                $stmt -> bindparam(':latitude',$latitude);
                $stmt -> bindparam(':longitude',$longitude);
                $result = $stmt -> execute();

                //Πάρε το τελευταίο Id που μόλις σημιούργησε η MySql(π.χ. 100)
                $last_id = $conn -> lastInsertId();

                //Δημιούργησε το ticketId 
                $ticket_id = "KOROPI-" . str_pad($last_id, 5, "0", STR_PAD_LEFT);


                $photo_path = NULL; //NULL αν ο χρήστης δεν ανεβάσει φωτο

                if(isset($_FILES['photo'])  && $_FILES['photo']['error'] == UPLOAD_ERR_OK){
                    $uploadDir = 'uploads/'; //Ο φάκελος στο project μας (πρέπει να βρίσκεται στον ίδιο φάκελο με το index file)

                    //Πάρε την κατάληξη του αρχείου
                    $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);

                    $file_name = $ticket_id . '.' . $file_ext; //e.g. KOROPI-00015.JPEG
                    $photo_path = $uploadDir . $file_name;

                    //Αν όλα πάνε καλά φτιάξε το SQL statement και εκτέλεσε το 
                    if(move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)){
                        //Προετοιμασία του query
                        $sql_photo = "UPDATE issues SET photo_path = :photo_path WHERE id = :id";
                        $stmt_photo = $conn->prepare($sql_photo);
                        $stmt_photo->bindParam(':photo_path', $photo_path);
                        $stmt_photo->bindParam(':id', $last_id);
                        $stmt_photo->execute();
                    }

                }
                //Εισάγουμε στην βάση το τελικό ticket id 
                $sql_2 = "UPDATE issues SET ticket_id = :ticket_id WHERE id = :id";
                //Φτιάχουμε το SQL code και το στέλνουμε στην db
                $stmt_2 = $conn -> prepare($sql_2);
                //Φτιάχουμε το SQL code και το στέλνουμε στην db (δένουμε τις παραμέτρους του query με τις μεταβλητές της php)
                $stmt_2 -> bindParam(':ticket_id' , $ticket_id);
                $stmt_2 -> bindParam(':id', $last_id);
                $stmt_2 -> execute();

                $cookie_name = "user_tickets";
                $tickets_array = []; //Αρχικά ο πίνακς των cookies των ticket_ids του χρήστη είναι άδειος

                //Έλεγχος για ήdη υπάρχον ιστορικό cookies του χρήστη
                if(isset($_COOKIE[$cookie_name])){
                    //Παίρνουμε σαν array το περιεχόμενο του json file (κανονικά η πληροφορία είναι σαν ένα απλό κείμενο)
                    $tickets_array = json_decode($_COOKIE[$cookie_name], true);
                }

                //Προσθήκη του νέου ticket_id στο τέλος του array of ticket_id cookies
                $tickets_array [] = $ticket_id;
                
                //Μετατροπή του array σε JSON text
                $cookie_value = json_encode($tickets_array);

                //Αποθήκευση του cookie για 1 μήνα (30 μέρες)
                setcookie($cookie_name, $cookie_value, time()+ (86400 * 30), "/"); // "/" αυτό σημαίνει ότι θα είναι το cookie accessible από όλες τις σελίδες του website

                //Debugging code εμφανίζει το cookie
                // echo "<strong>Αποθηκεύτηκε στο Cookie:</strong> " . $cookie_value "; 
                echo "New issue inserted successfully! Your ticket id is: <strong>". $ticket_id ."</strong>";


            }catch(PDOException $e){
                echo "Error: Form information could not be inserted!";
            }
        }

    }else{
        echo "Please complete all form fields!";
    }
    echo "<br><a href = 'index.php' class = 'btn btn-primary'>Επιστροφή</a>";
?>