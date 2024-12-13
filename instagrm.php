<?php

// Fayllar yo‘llari
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie fayli
$downloadedReelPath = __DIR__ . '/downloaded_reel.mp4'; // Yuklangan video

// Instagram API URL'lari
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$reelsUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/'; // REEL_ID joyiga haqiqiy Reels URL

// Foydalanuvchi ma'lumotlari
$username = 'a.l1_07';
$password = '09110620Ali';

// Login uchun so'rov
echo "Instagram hisobiga kirish...\n";
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

// CSRF tokenini olish uchun so'rov
curl_setopt($ch, CURLOPT_HEADER, true);
$initialResponse = curl_exec($ch);
preg_match('/^set-cookie: csrftoken=(.*?);/mi', $initialResponse, $csrfMatches);
$csrfToken = $csrfMatches[1] ?? null;

curl_setopt($ch, CURLOPT_HEADER, false);
$postFields = http_build_query([
    'username' => $username,
    'enc_password' => '#PWD_INSTAGRAM_BROWSER:0:' . time() . ':' . $password,
    'queryParams' => '{}',
    'optIntoOneTap' => 'false'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Login muvaffaqiyatli bo‘lmagan holatlar
if ($httpCode !== 200) {
    echo "Login amalga oshmadi. HTTP kod: $httpCode, Javob: $loginResponse\n";

    // 2FA aniqlash
    $response = json_decode($loginResponse, true);
    if (isset($response['two_factor_required']) && $response['two_factor_required']) {
        $twoFactorIdentifier = $response['two_factor_info']['two_factor_identifier'];
        echo "Ikki faktorli autentifikatsiya talab qilindi.\n";
        echo "Telefon raqamingiz: " . $response['two_factor_info']['obfuscated_phone_number'] . "\n";

        // Kodni foydalanuvchidan so'rash
        echo "Qo'shimcha autentifikatsiya kodini kiriting: ";
        $authCode = trim(fgets(STDIN));

        // 2FA so'rovini yuborish
        $twoFactorLoginUrl = 'https://www.instagram.com/accounts/login/ajax/two_factor/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $twoFactorLoginUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-CSRFToken: $csrfToken",
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
        ]);

        $twoFactorPostData = http_build_query([
            'username' => $username,
            'verification_code' => $authCode,
            'identifier' => $twoFactorIdentifier,
            'trust_this_device' => '1'
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $twoFactorPostData);
        $twoFactorResponse = curl_exec($ch);
        $twoFactorHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($twoFactorHttpCode === 200) {
            echo "Ikki faktorli autentifikatsiya muvaffaqiyatli amalga oshirildi.\n";
        } else {
            die("Ikki faktorli autentifikatsiya amalga oshmadi. Javob: $twoFactorResponse\n");
        }
    } else {
        die("Login muvaffaqiyatsiz tugadi.\n");
    }
}

// Login muvaffaqiyatli bo‘lsa, reels videoni yuklash
echo "Login muvaffaqiyatli amalga oshirildi.\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reelsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
$html = curl_exec($ch);
curl_close($ch);

// Sahifadan video URL'ni chiqarib olish
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

    echo "Reels video yuklandi va '$downloadedReelPath' faylida saqlandi!\n";
} else {
    echo "Reels video topilmadi yoki yuklab bo‘lmadi.\n";
}
?>
