<?php

  $host_name = "db";
  $user_name = "root";
  $user_pass = "password";
  $db_name = "cityreport";        //classic MySQL database connection code
  try{
    $conn = new PDO("mysql:host=$host_name;dbname=$db_name", $user_name, $user_pass);
    $conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  catch(PDOException $e){
    echo "Connection failed: " . $e->getMessage(); 
    exit();
  }
?>