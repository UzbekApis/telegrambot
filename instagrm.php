<?php
// URL, form maydoni uchun
$instagramUrl = "https://www.instagram.com/reel/DDZVe3gNDI4/"; // Instagram post URL kiriting

// Snapinsta URL manzili
$snapinstaUrl = "https://snapinsta.app/";

// cURL so'rovini yaratish
$ch = curl_init($snapinstaUrl);

// POST so'rov parametrlari
$postData = [
    'url' => $instagramUrl, // Formada kerakli qiymat
];

// cURL sozlamalarini o'rnatish
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

// So'rovni yuborish
$response = curl_exec($ch);

// Xatoliklarni tekshirish
if (curl_errno($ch)) {
    echo "So'rovda xatolik yuz berdi: " . curl_error($ch);
    curl_close($ch);
    exit;
}

// So'rovni yopish
curl_close($ch);

// Qaytarilgan javobni chiqarish
echo "<pre>";
echo "SnapInsta dan qaytgan ma'lumotlar:\n\n";
echo htmlspecialchars($response); // Server javobini HTML xavfsiz chiqish uchun
echo "</pre>";
