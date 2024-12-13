<?php
// Fayl joylashuvi va o'zgaruvchilarni saqlash
$cookieFilePath = __DIR__ . '/cookies.txt'; // Cookie faylni saqlash
$downloadedReelPath = __DIR__ . '/downloaded_reel.mp4'; // Yuklangan video fayli

// Instagram login va Reels URL'lari
$loginUrl = 'https://www.instagram.com/accounts/login/ajax/';
$reelsUrl = 'https://www.instagram.com/reel/DDZVe3gNDI4/'; // REEL_ID bilan almashtiring

// Foydalanuvchi ma'lumotlari
$username = 'a.l1_07';
$password = '09110620Ali';

// Error ko'rsatish funksiyasi
function showError($message) {
    echo '<div style="color:red; font-weight: bold;">Error: ' . htmlspecialchars($message) . '</div>';
}

// Login uchun cURL so'rovi
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFilePath); // Cookie saqlash
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFilePath); 
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0 Safari/537.36',  // Muqobil User-Agent
]);

$postFields = http_build_query([
    'username' => $username,
    'password' => $password,
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

// Bajarilayotgan so'rovning javobi
$response = curl_exec($ch);
if ($response === false) {
    $error_message = curl_error($ch);
    showError("cURL so'rovida xato: $error_message");
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Javobni tekshirish
$responseData = json_decode($response, true);
if ($httpCode === 400 && isset($responseData['two_factor_required'])) {
    echo "<h3>Ikki bosqichli autentifikatsiya talab qilinmoqda.</h3>";
    $twoFactorIdentifier = $responseData['two_factor_info']['two_factor_identifier'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Foydalanuvchi Zaxira kodini yuborishi kerak
        if (isset($_POST['backupCode'])) {
            // Backup Code yuboriladi
            $backupCode = $_POST['backupCode'];  // Zaxira kodini olish

            // POST so'rovi yuborish
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
                'backupCode' => $backupCode,
                'two_factor_identifier' => $twoFactorIdentifier,
            ]);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $twoFactorFields);

            $twoFactorResponse = curl_exec($ch);
            $twoFactorHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($twoFactorHttpCode === 200) {
                echo "<h3>Zaxira kodi yordamida muvaffaqiyatli kirildi!</h3>";
            } else {
                showError("Zaxira kodi bilan autentifikatsiya muvaffaqiyatsiz bo‘ldi.");
            }
        } else {
            echo '<form method="POST" action="">
                    <label for="backupCode">Zaxira kodi:</label>
                    <input type="text" id="backupCode" name="backupCode" required>
                    <button type="submit">Yuborish</button>
                  </form>';
        }
    } else {
        echo "<h3>Ikki bosqichli autentifikatsiya amalga oshirilgan</h3>";
    }
} elseif ($httpCode !== 200) {
    showError("Login amalga oshmadi. HTTP kod: $httpCode, Javob: $response");
    exit;
} else {
    echo "<h3>Muvaffaqiyatli login qilindi!</h3>";
}

// Reels sahifasini olish
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

    // Video yuklab olish
    $ch = curl_init($videoUrl);
    $fp = fopen($downloadedReelPath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    fclose($fp);
    curl_close($ch);

    echo "<h4>Reels video yuklandi va '$downloadedReelPath' saqlandi!</h4>";
} else {
    showError("Reels video topilmadi yoki yuklab bo‘lmadi.");
}
?>
