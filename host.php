<?php

// Bot tokenini o'rnating
$botToken = "BOT_TOKEN_HERE";
$apiUrl = "https://api.telegram.org/bot$botToken";
$baseUrl = "https://telegrambot-production-7458.up.railway.app"; // O'zingizning domeningizni kiriting

// Foydalanuvchidan kelgan ma'lumotlarni o'qish
$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

// O'zgaruvchilar
$chatId = "1150081918"; ///$update["message"]["chat"]["id"];
$messageText = $update["message"]["text"] ?? "";
$fileId = $update["message"]["document"]["file_id"] ?? null;

// Fayllar saqlanadigan asosiy papka
$baseDir = __DIR__ . "/uploads";

// Agar papka mavjud bo'lmasa, yarating
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

// Funksiyalar

// 1. Faylni yuklash
function uploadFile($fileId, $baseDir, $botToken, $apiUrl, $chatId) {
    // Telegram serveridan fayl ma'lumotlarini olish
    $filePathResponse = file_get_contents("$apiUrl/getFile?file_id=$fileId");
    $filePathResponse = json_decode($filePathResponse, TRUE);
    
    // Faylni olishda xatolikni tekshirish
    if (!isset($filePathResponse["result"]["file_path"])) {
        sendMessage($chatId, "Faylni olishda xatolik yuz berdi.");
        return;
    }
    
    $filePath = $filePathResponse["result"]["file_path"];
    $fileUrl = "https://api.telegram.org/file/bot$botToken/$filePath";
    
    // Faylni saqlash
    $savePath = $baseDir . "/" . basename($filePath);
    $fileContent = file_get_contents($fileUrl);
    
    // Faylni yuklab olishda xatolikni tekshirish
    if ($fileContent === false) {
        sendMessage($chatId, "Faylni yuklab olishda xatolik yuz berdi.");
        return;
    }

    file_put_contents($savePath, $fileContent);

    // Javob yuborish
    sendMessage($chatId, "Fayl muvaffaqiyatli yuklandi: " . basename($filePath));
}

// 2. Fayllar ro'yxatini ko'rsatish
function listFiles($baseDir, $chatId) {
    $files = scandir($baseDir);
    $files = array_diff($files, ['.', '..']); // "." va ".." ni olib tashlash

    if (empty($files)) {
        sendMessage($chatId, "Hozircha hech qanday fayl yo'q.");
        return;
    }

    $message = "Fayllar ro'yxati:\n";
    foreach ($files as $file) {
        $message .= "- " . $file . "\n";
    }
    sendMessage($chatId, $message);
}

// 3. Papka yaratish
function createFolder($folderName, $baseDir, $chatId) {
    $folderPath = $baseDir . "/" . $folderName;
    if (!is_dir($folderPath)) {
        mkdir($folderPath, 0777, true);
        sendMessage($chatId, "Papka muvaffaqiyatli yaratildi: " . $folderName);
    } else {
        sendMessage($chatId, "Bunday papka allaqachon mavjud.");
    }
}

// 4. Faylni o'chirish
function deleteFile($fileName, $baseDir, $chatId) {
    $filePath = $baseDir . "/" . $fileName;
    if (file_exists($filePath)) {
        unlink($filePath);
        sendMessage($chatId, "Fayl muvaffaqiyatli o'chirildi: " . $fileName);
    } else {
        sendMessage($chatId, "Bunday fayl topilmadi.");
    }
}

// 5. Faylni qayta nomlash
function renameFile($oldName, $newName, $baseDir, $chatId) {
    $oldPath = $baseDir . "/" . $oldName;
    $newPath = $baseDir . "/" . $newName;
    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
        sendMessage($chatId, "Fayl muvaffaqiyatli qayta nomlandi: " . $newName);
    } else {
        sendMessage($chatId, "Bunday fayl topilmadi.");
    }
}

// 6. Foydalanuvchiga xabar yuborish
function sendMessage($chatId, $message) {
    global $apiUrl;
    file_get_contents("$apiUrl/sendMessage?chat_id=$chatId&text=" . urlencode($message));
}

// 7. Fayl URL'ini ko'rsatish
function getFileUrl($fileName, $baseDir, $baseUrl, $chatId) {
    $filePath = $baseDir . "/" . $fileName;
    if (file_exists($filePath)) {
        $fileUrl = $baseUrl . "/uploads/" . urlencode($fileName);
        sendMessage($chatId, "Fayl URL'si: $fileUrl");
    } else {
        sendMessage($chatId, "Bunday fayl topilmadi.");
    }
}


// Buyruqlarni qayta ishlash
if ($fileId) {
    uploadFile($fileId, $baseDir, $botToken, $apiUrl, $chatId);
} elseif (strpos($messageText, "/list") === 0) {
    listFiles($baseDir, $chatId);
} elseif (strpos($messageText, "/mkdir") === 0) {
    $folderName = trim(str_replace("/mkdir", "", $messageText));
    createFolder($folderName, $baseDir, $chatId);
} elseif (strpos($messageText, "/delete") === 0) {
    $fileName = trim(str_replace("/delete", "", $messageText));
    deleteFile($fileName, $baseDir, $chatId);
} elseif (strpos($messageText, "/rename") === 0) {
    $parts = explode(" ", trim(str_replace("/rename", "", $messageText)));
    if (count($parts) == 2) {
        renameFile($parts[0], $parts[1], $baseDir, $chatId);
    } else {
        sendMessage($chatId, "Buyruq noto'g'ri. Format: /rename eski_nomi yangi_nomi");
    }
} elseif (strpos($messageText, "/url") === 0) {
    $fileName = trim(str_replace("/url", "", $messageText));
    getFileUrl($fileName, $baseDir, $baseUrl, $chatId);
} else {
    sendMessage($chatId, "Buyruq noto'g'ri yoki tanib bo'lmadi.");
}

?>
