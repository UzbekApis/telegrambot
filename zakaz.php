<?php
// Bot tokeni va Admin ID
$token = "5452547137:AAGCwUieVadqwaSMMCC-R_G3cQZ26OHrCHc";
$admin_id = "1150081918"; 
$apiURL = "https://api.telegram.org/bot$token";

// MySQL bazaga ulanish konfiguratsiyasi
$host = 'mysql.railway.internal'; 
$db   = 'railway'; 
$user = 'root'; 
$pass = 'GgOPWyUqoTVdhtSMbaJWiCvvEwUXESpD'; 
$port = 3306; 
$mysqli = new mysqli($host, $user, $pass, $db, $port);

// Xatolikni qayd qilish
if ($mysqli->connect_error) {
    error_log("MySQL ulanish xatosi: " . $mysqli->connect_error);
    die("⛔ Bot vaqtincha ishlamayapti. Iltimos, keyinroq urinib ko'ring.");
}

// Telegramdan kelayotgan ma'lumotlarni olish
$content = file_get_contents("php://input");
$update = json_decode($content, true);
$message = $update['message'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = trim($message['text'] ?? '');

// Inline tugmalar va admin panel
if ($text === "/start") {
    sendKeyboard($chat_id, "🌟 Salom! Bizning botga xush kelibsiz!", [['📦 Buyurtma Berish']]);
} elseif ($text === "📦 Buyurtma Berish") {
    showProducts($chat_id, $mysqli);
} elseif ($text === "/admin" && $chat_id == $admin_id) {
    sendKeyboard($chat_id, "🔧 Admin paneli!", [['➕ Mahsulot Qo\'shish', '📝 Mahsulotlar'], ['📊 Statistika', '❌ Mahsulot O‘chirish']]);
} elseif ($text === "➕ Mahsulot Qo'shish" && $chat_id == $admin_id) {
    sendMessage($chat_id, "✏️ Mahsulot qo‘shish uchun quyidagicha yuboring:\n`Mahsulot nomi | 12345 | https://rasm_url`");
} elseif ($chat_id == $admin_id && strpos($text, '|') !== false) {
    addProduct($chat_id, $text, $mysqli);
} elseif ($text === "📝 Mahsulotlar" && $chat_id == $admin_id) {
    showProducts($chat_id, $mysqli);
} elseif ($text === "❌ Mahsulot O‘chirish" && $chat_id == $admin_id) {
    sendMessage($chat_id, "❌ O‘chirish uchun mahsulot nomini yuboring:");
} elseif (strpos($text, "O‘chirish:") === 0 && $chat_id == $admin_id) {
    $product_name = trim(str_replace("O‘chirish:", "", $text));
    deleteProduct($chat_id, $product_name, $mysqli);
} elseif ($text === "📊 Statistika" && $chat_id == $admin_id) {
    showStats($chat_id, $mysqli);
} elseif ($text === "Sotib olish") {
    saveOrder($chat_id, $mysqli);
} else {
    sendMessage($chat_id, "📌 Buyruqlar:\n/start - Boshlash\n/admin - Admin paneli (faqat admin uchun)");
}

// Funksiyalar:
function sendKeyboard($chat_id, $text, $buttons)
{
    global $apiURL;
    $keyboard = ['keyboard' => $buttons, 'resize_keyboard' => true, 'one_time_keyboard' => true];
    $data = ['chat_id' => $chat_id, 'text' => $text, 'reply_markup' => json_encode($keyboard)];
    sendRequest($data);
}

function showProducts($chat_id, $mysqli)
{
    $result = $mysqli->query("SELECT * FROM products");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product_text = "📦 *Mahsulot*: {$row['name']}\n💰 *Narxi*: {$row['price']} so'm";
            sendPhoto($chat_id, $row['image'], $product_text, [['Sotib olish']]);
        }
    } else {
        sendMessage($chat_id, "📭 Mahsulotlar yo'q.");
    }
}

function addProduct($chat_id, $text, $mysqli)
{
    list($name, $price, $image) = array_map('trim', explode('|', $text));
    if ($name && is_numeric($price) && filter_var($image, FILTER_VALIDATE_URL)) {
        $stmt = $mysqli->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $price, $image);
        if ($stmt->execute()) {
            sendMessage($chat_id, "✅ Mahsulot qo‘shildi: $name");
        } else {
            sendMessage($chat_id, "❌ Mahsulot qo‘shishda xatolik: " . $stmt->error);
        }
    } else {
        sendMessage($chat_id, "⚠️ Noto'g'ri format. To'g'ri yuboring: `Nom | Narx | Rasm URL`");
    }
}

function deleteProduct($chat_id, $name, $mysqli)
{
    $stmt = $mysqli->prepare("DELETE FROM products WHERE name = ?");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        sendMessage($chat_id, "✅ Mahsulot o‘chirildi: $name");
    } else {
        sendMessage($chat_id, "❌ Mahsulot o‘chirishda xatolik: " . $stmt->error);
    }
}

function saveOrder($chat_id, $mysqli)
{
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, status) VALUES (?, 'pending')");
    $stmt->bind_param("i", $chat_id);
    if ($stmt->execute()) {
        sendMessage($chat_id, "✅ Buyurtmangiz saqlandi!");
    } else {
        sendMessage($chat_id, "❌ Buyurtmani saqlashda xatolik.");
    }
}

function showStats($chat_id, $mysqli)
{
    $product_count = $mysqli->query("SELECT COUNT(*) AS count FROM products")->fetch_assoc()['count'];
    sendMessage($chat_id, "📊 Statistika:\n🛍 Mahsulotlar soni: $product_count");
}

function sendMessage($chat_id, $text)
{
    sendRequest(['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'Markdown']);
}

function sendPhoto($chat_id, $photo_url, $caption, $buttons)
{
    global $apiURL;
    $keyboard = json_encode(['keyboard' => $buttons, 'resize_keyboard' => true, 'one_time_keyboard' => true]);
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
        'reply_markup' => $keyboard
    ];
    sendRequest($data, 'sendPhoto');
}

function sendRequest($data, $method = 'sendMessage')
{
    global $apiURL;
    $url = "$apiURL/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if (!$result) {
        error_log("Telegram API xatosi: " . curl_error($ch));
    }
    curl_close($ch);
}
?>
