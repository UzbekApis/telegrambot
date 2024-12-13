<?php
// Fayllar yo'llari
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie faylni "UzbekApis/telegrambot" ichida saqlash
$downloadedReelPath = __DIR__ . '/downloaded_reel.mp4'; // Yuklangan video fayli shu katalogda saqlanadi

// Katalogdagi mavjud fayllar
echo "Serverda mavjud fayllar:\n";
$files = scandir(__DIR__);
print_r($files);

$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$reelsUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/'; // REEL_ID o‘rniga haqiqiy Reels ID kiriting.

$username = 'a.l1_07';
$password = '09110620Ali';

// Login uchun cURL so'rovi
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath); // Cookie faylni saqlash
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'X-CSRFToken: random_csrf_token', // Muqobil token
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
]);

$postFields = http_build_query([
    'username' => $username,
    'password' => $password,
    'queryParams' => '{}',
    'optIntoOneTap' => 'false',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$response = curl_exec($ch);

// Login javobi:
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // HTTP kodni olamiz
if ($response === false) {
    echo "Login so‘rovida xato! cURL xatosi: " . curl_error($ch) . "\n";
} else {
    echo "Login javobi ($httpCode):\n";
    echo $response . "\n";
}

curl_close($ch);

// Reels sahifasini olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reelsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath); // Cookie faylni ishlatish
$html = curl_exec($ch);

// HTTP javob kodi va URL muvaffaqiyatli yoki yo'qligini tekshirish
$reelsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($html === false) {
    echo "Reels sahifasini olishda xato! cURL xatosi: " . curl_error($ch) . "\n";
} else {
    echo "Reels sahifasi HTTP kod ($reelsHttpCode):\n";
    echo $html . "\n";
}
curl_close($ch);

// Sahifadan video URL'ini chiqarib olish
preg_match('/"video_url":"([^"]+)"/', $html, $matches);
if (isset($matches[1])) {
    $videoUrl = stripslashes($matches[1]); // Video URL'ini olish
    echo "Reels video URL: $videoUrl\n";

    // Videoni yuklab olish
    $ch = curl_init($videoUrl);
    $fp = fopen($downloadedReelPath, 'wb'); // Yuklangan faylni saqlash uchun belgilangan yo'l
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);
    curl_close($ch);

    echo "Reels video yuklandi va '$downloadedReelPath' ga saqlandi!";
} else {
    echo "Reels video topilmadi yoki yuklab bo‘lmadi.";
}
?>
