<?php
// Fayllar joylashuvi
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie faylni saqlash uchun katalog
$downloadedReelPath = __DIR__ . '/downloaded_reel.mp4'; // Yuklangan video fayli katalogi

// Instagram API URL'lari
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$reelsUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/'; // REEL_ID o‘rniga haqiqiy Reels URL yoki ID-ni kiriting

// Foydalanuvchi ma'lumotlari
$username = 'a.l1_07';
$password = '09110620Ali';

// Login uchun cURL so'rovi
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath); // Cookie'larni saqlash
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36',
]);

$postFields = http_build_query([
    'username' => $username,
    'password' => $password,
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Login javobini tahlil qilish
$responseData = json_decode($response, true);
if ($httpCode === 400 && isset($responseData['two_factor_required'])) {
    echo "Ikki bosqichli autentifikatsiya talab qilinmoqda.\n";
    $twoFactorIdentifier = $responseData['two_factor_info']['two_factor_identifier'];

    // Foydalanuvchidan 2FA kodni kiritish so'raladi
    echo "Qo'shimcha autentifikatsiya kodini kiriting: ";
    $twoFactorCode = trim(fgets(STDIN));

    // 2FA uchun so'rov yuborish
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36',
    ]);

    $twoFactorFields = http_build_query([
        'username' => $username,
        'verificationCode' => $twoFactorCode,
        'two_factor_identifier' => $twoFactorIdentifier,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $twoFactorFields);
    $twoFactorResponse = curl_exec($ch);
    $twoFactorHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($twoFactorHttpCode === 200) {
        echo "Muvaffaqiyatli login amalga oshirildi!\n";
    } else {
        echo "2FA autentifikatsiyasi muvaffaqiyatsiz bo‘ldi. Javob: $twoFactorResponse\n";
        exit;
    }
} elseif ($httpCode !== 200) {
    echo "Login amalga oshmadi. HTTP kod: $httpCode, Javob: $response\n";
    exit;
} else {
    echo "Muvaffaqiyatli login qilindi!\n";
}

// Reels sahifasini olish va video yuklab olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reelsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
$html = curl_exec($ch);
curl_close($ch);

// Video URL'ni chiqarib olish
preg_match('/"video_url":"([^"]+)"/', $html, $matches);
if (isset($matches[1])) {
    $videoUrl = stripslashes($matches[1]);
    echo "Reels video URL: $videoUrl\n";

    // Videoni yuklab olish
    $ch = curl_init($videoUrl);
    $fp = fopen($downloadedReelPath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);
    curl_close($ch);

    echo "Reels video yuklandi va '$downloadedReelPath' saqlandi!\n";
} else {
    echo "Reels video topilmadi yoki yuklab bo‘lmadi.\n";
}
?>
