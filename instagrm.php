<?php
// Fayllar yo'llari
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie faylni saqlash yo‘li
$downloadedReelPath = __DIR__ . '/downloaded_reel.mp4'; // Yuklangan video fayl yo‘li
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$reelsUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/'; // REEL_ID ni haqiqiy ID bilan almashtiring.

$username = 'a.l1_07'; // Instagram foydalanuvchi nomi
$password = '09110620Ali'; // Instagram parolingiz

// 1. CSRF-token olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.instagram.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath); // Cookie faylni saqlash
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
]);
$response = curl_exec($ch);
curl_close($ch);

// CSRF-tokenni chiqarib olish
preg_match('/csrftoken=([^;]+)/', $response, $matches);
$csrfToken = $matches[1] ?? '';

if (!$csrfToken) {
    die("CSRF-token topilmadi. Antibot himoyasi yo‘l qo‘ymayapti.\n");
}

// 2. Login so‘rovi
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath); // Cookie faylni yangilash
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    "X-CSRFToken: $csrfToken",
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
]);

$postFields = http_build_query([
    'username' => $username,
    'enc_password' => "#PWD_INSTAGRAM_BROWSER:0:".time().":$password",
    'queryParams' => '{}',
    'optIntoOneTap' => 'false'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Login javobini tekshirish
if ($httpCode !== 200) {
    die("Login amalga oshmadi. HTTP kod: $httpCode, Javob: $loginResponse\n");
}
echo "Login muvaffaqiyatli.\n";

// 3. Reels sahifasini olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reelsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// HTTP javobni tekshirish
if ($httpCode !== 200) {
    die("Reels sahifasiga ulanishda xatolik. HTTP kod: $httpCode\n");
}

// 4. Reels video URL'ini chiqarib olish
preg_match('/"video_url":"([^"]+)"/', $html, $matches);
if (!isset($matches[1])) {
    die("Reels video topilmadi.\n");
}
$videoUrl = stripslashes($matches[1]);
echo "Reels video URL: $videoUrl\n";

// 5. Videoni yuklab olish
$ch = curl_init($videoUrl);
$fp = fopen($downloadedReelPath, 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_exec($ch);
curl_close($ch);
fclose($fp);

echo "Reels video '$downloadedReelPath' ga yuklandi.\n";
?>
