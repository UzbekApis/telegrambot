<?php

// Fayl yo'nalishi
$logFilePath = __DIR__ . '/process_log.txt';

// Log yozish funksiyasi
function logMessage($message, $isError = false) {
    global $logFilePath;

    $timestamp = date('Y-m-d H:i:s');
    $logType = $isError ? 'ERROR' : 'INFO';
    $formattedMessage = "[$timestamp] [$logType] $message\n";

    // Faylga yozish
    file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);

    // Foydalanuvchi interfeysida ko'rsatish
    if ($isError) {
        echo '<div style="color:red; font-weight: bold;">' . htmlspecialchars($formattedMessage) . '</div>';
    } else {
        echo '<div style="color:green;">' . htmlspecialchars($formattedMessage) . '</div>';
    }
}

// Curl so'rov funksiyasi
function sendCurlRequest($url, $postFields = null, $cookieFilePath = '', $customCookies = '') {
    global $logFilePath;

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
        $error = curl_error($ch);
        logMessage("cURL error for URL $url: $error", true);
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    logMessage("Successful cURL request to $url.");
    return $response;
}

// Misol uchun ishlatiladigan funksiyalar
function login($username, $password, $loginUrl, $cookieFilePath) {
    logMessage("Login jarayoni boshlandi.");
    $response = sendCurlRequest($loginUrl, [
        'username' => $username,
        'password' => $password,
    ], $cookieFilePath);

    if (!$response) {
        logMessage("Login muvaffaqiyatsiz tugadi.", true);
        return false;
    }

    $responseData = json_decode($response, true);
    if (isset($responseData['authenticated']) && $responseData['authenticated']) {
        logMessage("Login muvaffaqiyatli yakunlandi.");
        return true;
    } elseif (isset($responseData['two_factor_required'])) {
        logMessage("Ikki bosqichli autentifikatsiya talab qilinmoqda.");
        return $responseData['two_factor_info']['two_factor_identifier'];
    } else {
        logMessage("Login muvaffaqiyatsiz: " . $response, true);
        return false;
    }
}

// Foydalanish
$username = 'a.l1_07';
$password = '09110620Ali';
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$cookieFilePath = __DIR__ . '/cookies.txt';

$twoFactorIdentifier = login($username, $password, $loginUrl, $cookieFilePath);

// Loglar haqida foydalanuvchiga ma'lumot berish
echo "<h3>Barcha loglar faylda saqlanmoqda:</h3>";
echo "<p>Log fayli yo'nalishi: <code>$logFilePath</code></p>";
echo '<p>Loglarni to`liq ko`rish uchun <a href="' . htmlspecialchars($logFilePath) . '" target="_blank">shu yerni bosing</a>.</p>';

?>
