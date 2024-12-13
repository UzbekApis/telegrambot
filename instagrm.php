<?php
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$mediaUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/?igsh=OGxqcnozcWI0YnIy'; // POST_ID o‘rniga haqiqiy ID qo‘ying.

$username = 'a.l1_07';
$password = '09110620';

// Login uchun cURL so'rovi
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt'); // Cookie faylni saqlash
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
]);

$postFields = http_build_query([
    'username' => $username,
    'password' => $password
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$response = curl_exec($ch);
curl_close($ch);

// Yuklash uchun rasm/video manbasini olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $mediaUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
$html = curl_exec($ch);
curl_close($ch);

// HTML dan video yoki rasmni chiqarish
preg_match('/"display_url":"([^"]+)"/', $html, $matches);
if (isset($matches[1])) {
    $imageUrl = stripslashes($matches[1]);
    echo "Rasm/video URL: $imageUrl\n";

    // Faylni yuklab olish
    $ch = curl_init($imageUrl);
    $fp = fopen('downloaded_media.jpg', 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);
    curl_close($ch);

    echo "Fayl yuklandi!";
} else {
    echo "Rasm yoki video topilmadi.";
}
?>