<?php

// Bot tokenini o'rnating
$botToken = "7295475589:AAFZ20mG7vYEe7D79XOiuta7MPff4AzayUM";
$apiUrl = "https://api.telegram.org/bot$botToken";
$baseUrl = "https://telegrambot-production-7458.up.railway.app"; // O'zingizning domeningizni kiriting

// Foydalanuvchidan kelgan ma'lumotlarni o'qish
$update = file_get_contents("php://input");
$update = json_decode($update, TRUE);

// Agar xabar bo'lmasa, chiqarib tashlash
if (!isset($update["message"])) {
    exit();
}

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
// Faylni yuklash
function uploadFile($fileId, $baseDir, $botToken, $apiUrl, $chatId) {
    // Telegram serveridan fayl ma'lumotlarini olish
    $filePathResponse = file_get_contents("$apiUrl/getFile?file_id=$fileId");
    $filePathResponse = json_decode($filePathResponse, TRUE);
    
    // Faylni olishda xatolikni tekshirish
    if (!isset($filePathResponse["result"]["file_path"])) {
        sendMessage($chatId, "Faylni olishda xatolik yuz berdi. Iltimos, qayta urinib ko'ring.");
        return;
    }
    
    $filePath = $filePathResponse["result"]["file_path"];
    $fileUrl = "https://api.telegram.org/file/bot$botToken/$filePath";
    
    // Faylni saqlash
    $savePath = $baseDir . "/" . basename($filePath);
    
    // GitHub va Railwayda yuklab olish uchun ba'zi cheklovlar bo'lishi mumkin, shuning uchun bu kodni sinab ko'ring:
    $fileContent = file_get_contents($fileUrl);
    
    // Faylni yuklab olishda xatolikni tekshirish
    if ($fileContent === false) {
        sendMessage($chatId, "Faylni yuklab olishda xatolik yuz berdi. Iltimos, qayta urinib ko'ring.");
        return;
    }

    // Faylni serverga saqlash
    file_put_contents($savePath, $fileContent);

    // Faylni muvaffaqiyatli saqlaganini bildiruvchi xabar
    sendMessage($chatId, "Fayl muvaffaqiyatli yuklandi: " . basename($filePath));
}

/*function uploadFile($fileId, $baseDir, $botToken, $apiUrl, $chatId) {
    // Telegram serveridan fayl ma'lumotlarini olish
    $filePathResponse = file_get_contents("$apiUrl/getFile?file_id=$fileId");
    $filePathResponse = json_decode($filePathResponse, TRUE);
    
    // Faylni olishda xatolikni tekshirish
    if (!isset($filePathResponse["result"]["file_path"])) {
        sendMessage($chatId, "Faylni olishda xatolik yuz berdi. Iltimos, qayta urinib ko'ring.");
        return;
    }
    
    $filePath = $filePathResponse["result"]["file_path"];
    $fileUrl = "https://api.telegram.org/file/bot$botToken/$filePath";
    
    // Faylni saqlash
    $savePath = $baseDir . "/" . basename($filePath);
    $fileContent = file_get_contents($fileUrl);
    
    // Faylni yuklab olishda xatolikni tekshirish
    if ($fileContent === false) {
        sendMessage($chatId, "Faylni yuklab olishda xatolik yuz berdi. Iltimos, qayta urinib ko'ring.");
        return;
    }

    file_put_contents($savePath, $fileContent);

    // Faylni muvaffaqiyatli saqlaganini bildiruvchi xabar
    sendMessage($chatId, "Fayl muvaffaqiyatli yuklandi: " . basename($filePath));
}*/

// 2. Faylni o'zgartirish (matn qo'shish)
function editFile($fileName, $baseDir, $chatId, $newText) {
    $filePath = $baseDir . "/" . $fileName;
    if (file_exists($filePath)) {
        file_put_contents($filePath, $newText, FILE_APPEND);
        sendMessage($chatId, "Faylga yangi matn qo'shildi: " . $fileName);
    } else {
        sendMessage($chatId, "Bunday fayl topilmadi.");
    }
}

// 3. Fayllar ro'yxatini ko'rsatish
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

// 4. Papka yaratish
function createFolder($folderName, $baseDir, $chatId) {
    $folderPath = $baseDir . "/" . $folderName;
    if (!is_dir($folderPath)) {
        mkdir($folderPath, 0777, true);
        sendMessage($chatId, "Papka muvaffaqiyatli yaratildi: " . $folderName);
    } else {
        sendMessage($chatId, "Bunday papka allaqachon mavjud.");
    }
}

// 5. Faylni o'chirish
function deleteFile($fileName, $baseDir, $chatId) {
    $filePath = $baseDir . "/" . $fileName;
    if (file_exists($filePath)) {
        unlink($filePath);
        sendMessage($chatId, "Fayl muvaffaqiyatli o'chirildi: " . $fileName);
    } else {
        sendMessage($chatId, "Bunday fayl topilmadi.");
    }
}

// 6. Faylga izoh qo'shish
function addCommentToFile($fileName, $baseDir, $comment, $chatId) {
    $filePath = $baseDir . "/" . $fileName;
    if (file_exists($filePath)) {
        $commentFile = $filePath . ".comment";
        file_put_contents($commentFile, $comment . "\n", FILE_APPEND);
        sendMessage($chatId, "Izoh muvaffaqiyatli qo'shildi: " . $fileName);
    } else {
        sendMessage($chatId, "Bunday fayl topilmadi.");
    }
}

// 7. Foydalanuvchiga xabar yuborish
function sendMessage($chatId, $message) {
    global $apiUrl;
    file_get_contents("$apiUrl/sendMessage?chat_id=$chatId&text=" . urlencode($message));
}

// 8. Foydalanuvchiga yordam ko'rsatish
function showHelp($chatId) {
    $message = "Quyidagi buyruqlarni ishlatishingiz mumkin:\n";
    $message .= "/upload - Fayl yuklash\n";
    $message .= "/list - Fayllar ro'yxatini ko'rsatish\n";
    $message .= "/mkdir <papka nomi> - Papka yaratish\n";
    $message .= "/delete <fayl nomi> - Faylni o'chirish\n";
    $message .= "/edit <fayl nomi> <matn> - Faylga matn qo'shish\n";
    $message .= "/comment <fayl nomi> <izoh> - Faylga izoh qo'shish\n";
    $message .= "/help - Yordam\n";
    sendMessage($chatId, $message);
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
} elseif (strpos($messageText, "/edit") === 0) {
    $parts = explode(" ", trim(str_replace("/edit", "", $messageText)), 2);
    if (count($parts) == 2) {
        editFile($parts[0], $baseDir, $chatId, $parts[1]);
    } else {
        sendMessage($chatId, "Buyruq noto'g'ri. Format: /edit fayl_nomi matn");
    }
} elseif (strpos($messageText, "/comment") === 0) {
    $parts = explode(" ", trim(str_replace("/comment", "", $messageText)), 2);
    if (count($parts) == 2) {
        addCommentToFile($parts[0], $baseDir, $parts[1], $chatId);
    } else {
        sendMessage($chatId, "Buyruq noto'g'ri. Format: /comment fayl_nomi izoh");
    }
} elseif (strpos($messageText, "/help") === 0) {
    showHelp($chatId);
} else {
    sendMessage($chatId, "Buyruq noto'g'ri yoki tanib bo'lmadi.");
}

?>
