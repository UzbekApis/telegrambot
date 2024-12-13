<?php

// Instagram ma'lumotlari
$username = 'a.l1_07';
$password = '09110620Ali';
$backupCode = '63940728'; // Zaxiraviy kod
$reelsUrl = 'https://www.instagram.com/reel/Czp75l9C-mG/';

// Fayl yo'nalishi
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie saqlash yo'nalishi

// Instagram API URL'lari
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';

function showError($message) {
    echo '<div style="color:red; font-weight: bold;">Error: ' . htmlspecialchars($message) . '</div>';
}

function sendCurlRequest($url, $postFields = null, $cookieFilePath = '', $customCookies = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36'
    ]);

    if ($postFields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }

    if (!empty($cookieFilePath)) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath);
    }

    if (!empty($customCookies)) {
        curl_setopt($ch, CURLOPT_COOKIE, $customCookies);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        showError("cURL Error: " . curl_error($ch));
    }

    curl_close($ch);
    return $response;
}

// 1. Login qilish jarayoni
function login($username, $password, $loginUrl, $cookieFilePath) {
    echo "<h3>Login jarayoni boshlanmoqda...</h3>";

    $response = sendCurlRequest($loginUrl, [
        'username' => $username,
        'password' => $password,
    ], $cookieFilePath);

    if (!$response) {
        return false;
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['authenticated']) && $responseData['authenticated']) {
        echo "<h3>Login muvaffaqiyatli yakunlandi!</h3>";
        return true;
    } elseif (isset($responseData['two_factor_required'])) {
        return $responseData['two_factor_info']['two_factor_identifier'];
    } else {
        showError("Login muvaffaqiyatsiz: " . $response);
        return false;
    }
}

// 2. Ikki bosqichli autentifikatsiya
function twoFactorAuth($username, $backupCode, $twoFactorIdentifier, $loginUrl, $cookieFilePath) {
    echo "<h3>Ikki bosqichli autentifikatsiya boshlanmoqda...</h3>";

    $response = sendCurlRequest($loginUrl, [
        'username' => $username,
        'verification_code' => $backupCode,
        'two_factor_identifier' => $twoFactorIdentifier,
    ], $cookieFilePath);

    if (!$response) {
        return false;
    }

    $responseData = json_decode($response, true);

    if (isset($responseData['authenticated']) && $responseData['authenticated']) {
        echo "<h3>Ikki bosqichli autentifikatsiya muvaffaqiyatli yakunlandi!</h3>";
        return true;
    } else {
        showError("Ikki bosqichli autentifikatsiya muvaffaqiyatsiz bo'ldi.");
        return false;
    }
}

// 3. Reels ma'lumotlarini yuklash
function fetchReelsVideo($reelsUrl, $cookieFilePath) {
    echo "<h3>Reels yuklanmoqda...</h3>";

    $response = sendCurlRequest($reelsUrl, null, $cookieFilePath);

    if (!$response) {
        showError("Reels ma'lumotlarini olishda xato yuz berdi.");
        return false;
    }

    preg_match('/"video_url":"([^"]+)"/', $response, $matches);
    if (isset($matches[1])) {
        $videoUrl = stripslashes($matches[1]);
        echo "<h3>Reels video topildi:</h3>";
        echo "<a href='$videoUrl' target='_blank'>$videoUrl</a>";
        return true;
    } else {
        showError("Reels video URL topilmadi yoki yuklab bo'lmaydi.");
        return false;
    }
}

// Bosqichma-bosqich bajariladigan asosiy kod
$twoFactorIdentifier = login($username, $password, $loginUrl, $cookieFilePath);

if ($twoFactorIdentifier && !twoFactorAuth($username, $backupCode, $twoFactorIdentifier, $loginUrl, $cookieFilePath)) {
    exit;
}

if (!fetchReelsVideo($reelsUrl, $cookieFilePath)) {
    exit;
}
?>
