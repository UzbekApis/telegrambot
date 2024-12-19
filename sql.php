<?php
function createTables($host, $user, $pass, $db, $port) {
    // MySQLga ulanish
    $conn = new mysqli($host, $user, $pass, $db, $port);

    // Ulanish xatosini tekshirish
    if ($conn->connect_error) {
        die("Ulanish xatosi: " . $conn->connect_error);
    }

    // Jadval yaratish uchun SQL so'rovlari
    $usersTable = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_id BIGINT NOT NULL UNIQUE,
        class_name VARCHAR(255) DEFAULT NULL,
        current_step VARCHAR(50) DEFAULT 'ask_class'
    )";

    $studentDataTable = "
    CREATE TABLE IF NOT EXISTS student_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_id BIGINT NOT NULL,
        class_name VARCHAR(255) NOT NULL
    )";

    // `users` jadvalini yaratish
    if ($conn->query($usersTable) === TRUE) {
        echo "Jadval 'users' muvaffaqiyatli yaratildi.\n";
    } else {
        echo "Jadval 'users' yaratishda xatolik: " . $conn->error . "\n";
    }

    // `student_data` jadvalini yaratish
    if ($conn->query($studentDataTable) === TRUE) {
        echo "Jadval 'student_data' muvaffaqiyatli yaratildi.\n";
    } else {
        echo "Jadval 'student_data' yaratishda xatolik: " . $conn->error . "\n";
    }

    // Ulanishni yopish
    $conn->close();
}

// Misol
$host = 'mysql.railway.internal'; 
$db   = 'railway'; 
$user = 'root'; 
$pass = 'GgOPWyUqoTVdhtSMbaJWiCvvEwUXESpD'; 
$port = 3306; 

// Funksiyani chaqirish
createTables($host, $user, $pass, $db, $port);
?>
