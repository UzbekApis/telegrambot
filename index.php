<?php
// Telegram bot tokenini kiriting
$TOKEN = "5184312151:AAGtV3odZSBVqbsMTwFSC7bhJ_aP8_l9PiY";

// Webhook orqali kelgan ma'lumotlarni oling
$update = json_decode(file_get_contents("php://input"), TRUE);

// Foydalanuvchidan xabarni oling
$chat_id = $update["message"]["chat"]["id"];
$text = $update["message"]["text"];

// Javob qaytarish
if ($text == "/start") {
    $response = "Welcome to my Telegram bot hosted on Railway!";
} else {
    $response = "You said: $text";
}

// Foydalanuvchiga xabar yuboring
file_get_contents("https://api.telegram.org/bot$TOKEN/sendMessage?chat_id=$chat_id&text=" . urlencode($response));
?>
