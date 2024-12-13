<?php

// Instagram ma'lumotlari
$username = 'a.l1_07';
$password = '09110620Ali';
$backupCode = '63940728'; // Zaxiraviy kod
$reelsUrl = 'https://www.instagram.com/reel/Czp75l9C-mG/'; // Reels ID bilan almashtiring

// Fayl yo'nalishlari
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie saqlash uchun

// Instagram API URL'lari
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';

function showError($message) {
    echo '<div style="color:red; font-weight: bold;">Error: ' . htmlspecialchars($message) . '</div>';
}

// 1. Login qilish uchun cURL so'rovi
echo "<h3>Login jarayoni boshlanmoqda...</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath); // Cookie saqlash uchun
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath); // Cookie qayta foydalanish uchun
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
]);

$postFields = http_build_query([
    'username' => $username,
    'password' => $password,
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$response = curl_exec($ch);

if ($response === false) {
    showError("cURL so'rovida xato: " . curl_error($ch));
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseData = json_decode($response, true);
curl_close($ch);

if ($httpCode !== 200) {
    if (isset($responseData['two_factor_required'])) {
        // Ikki bosqichli autentifikatsiya
        echo "<h3>Ikki bosqichli autentifikatsiya talab qilinmoqda...</h3>";
        $twoFactorIdentifier = $responseData['two_factor_info']['two_factor_identifier'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
        ]);

        $twoFactorFields = http_build_query([
            'username' => $username,
            'verification_code' => $backupCode,
            'two_factor_identifier' => $twoFactorIdentifier,
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $twoFactorFields);
        $twoFactorResponse = curl_exec($ch);
        $twoFactorHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($twoFactorHttpCode !== 200) {
            showError("Zaxiraviy kod orqali autentifikatsiya muvaffaqiyatsiz bo'ldi.");
            exit;
        } else {
            echo "<h3>Ikki bosqichli autentifikatsiya muvaffaqiyatli o'tdi!</h3>";
        }
    } else {
        showError("Login amalga oshmadi: " . $response);
        exit;
    }
} else {
    echo "<h3>Login muvaffaqiyatli yakunlandi!</h3>";
}

// 2. Reels sahifasini yuklab olish
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reelsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
$html = curl_exec($ch);
curl_close($ch);

// 3. Video URL chiqarib olish
preg_match('/"video_url":"([^"]+)"/', $html, $matches);
if (isset($matches[1])) {
    $videoUrl = stripslashes($matches[1]);
    echo "<h3>Reels video topildi:</h3>";
    echo "<a href='$videoUrl' target='_blank'>$videoUrl</a>";
} else {
    showError("Reels video URL topilmadi yoki yuklab bo'lmaydi.");
}

?>
