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

// GitHub tokeni va repozitoriya ma'lumotlari
$githubToken = "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"; // GitHub tokenini shu yerga kiriting
$githubRepo = "UzbekApis/telegrambot"; // GitHub repozitoriya nomini shu yerga kiriting
$githubBranch = "main"; // Branch nomi

// Funksiyalar

// 1. Faylni yuklash
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

    // GitHub-ga yuklash
    uploadToGitHub($savePath, basename($filePath), $chatId);

    // Faylni muvaffaqiyatli saqlaganini bildiruvchi xabar
    sendMessage($chatId, "Fayl muvaffaqiyatli yuklandi: " . basename($filePath));
}

// GitHub-ga faylni yuklash
function uploadToGitHub($filePath, $fileName, $chatId) {
    global $githubToken, $githubRepo, $githubBranch;

    $fileContent = base64_encode(file_get_contents($filePath));
    
    $url = "https://api.github.com/repos/$githubRepo/contents/$fileName";

    $data = [
        "message" => "Fayl yuklandi: $fileName",
        "branch" => $githubBranch,
        "content" => $fileContent
    ];

    $options = [
        "http" => [
            "header" => "Authorization: token $githubToken\r\n" .
                        "Content-Type: application/json\r\n",
            "method" => "PUT",
            "content" => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        sendMessage($chatId, "GitHub-ga faylni yuklashda xatolik yuz berdi.");
        return;
    }

    sendMessage($chatId, "Fayl GitHub-ga muvaffaqiyatli yuklandi: " . $fileName);
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
