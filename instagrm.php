<?php
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$reelsUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/'; // REEL_ID o‘rniga haqiqiy Reels ID kiriting.

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

// Reels sahifasini olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reelsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt'); // Cookie faylni ishlatish
$html = curl_exec($ch);
curl_close($ch);

// Sahifadan video URL'ini chiqarib olish
preg_match('/"video_url":"([^"]+)"/', $html, $matches);
if (isset($matches[1])) {
    $videoUrl = stripslashes($matches[1]); // Video URL'ini olish
    echo "Reels video URL: $videoUrl\n";

    // Videoni yuklab olish
    $ch = curl_init($videoUrl);
    $fp = fopen('downloaded_reel.mp4', 'wb'); // Yuklangan fayl
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);
    curl_close($ch);

    echo "Reels video yuklandi!";
} else {
    echo "Reels video topilmadi yoki yuklab bo‘lmadi.";
}
?>
