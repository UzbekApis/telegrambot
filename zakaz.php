<?php
// Bot tokeni va Admin ID
$token = "BOT_TOKENINGIZ";
$admin_id = "ADMIN_CHAT_ID"; // Adminning Telegram ID'si
$apiURL = "https://api.telegram.org/bot$token";

// Railway DATABASE_URL dan MySQL ulanishini o'qish
/*$host = 'mysql.railway.internal'; // Railway ichki xost
$db   = 'railway'; // Ma'lumotlar bazasi nomi
$user = 'root'; // Railway foydalanuvchi nomi
$pass = 'GgOPWyUqoTVdhtSMbaJWiCvvEwUXESpD'; // Railway paroli
$port = 3306; // Port raqami
$mysqli = new mysqli($host, $user, $pass, $db, $port);

if ($mysqli->connect_error) {
    die("Ma'lumotlar bazasi xatosi: " . $mysqli->connect_error);
}*/
<?php
// Ma'lumotlar bazasi konfiguratsiyasi
$host = 'mysql.railway.internal'; // Railway ichki xosti
$db   = 'railway'; // Ma'lumotlar bazasi nomi
$user = 'root'; // Railway foydalanuvchi nomi
$pass = 'GgOPWyUqoTVdhtSMbaJWiCvvEwUXESpD'; // Railway paroli
$port = 3306; // Port raqami
$mysqli = new mysqli($host, $user, $pass, $db, $port);

// Xatolikni tekshirish
if ($mysqli->connect_error) {
    die("Bazaga ulanishda xatolik: " . $mysqli->connect_error);
}

// Jadvalni yaratish SQL kodi
$table_sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NOT NULL
)";

// SQLni bajarish
if ($mysqli->query($table_sql) === TRUE) {
    echo "✅ 'products' jadvali muvaffaqiyatli yaratildi yoki allaqachon mavjud.";
} else {
    echo "❌ Jadval yaratishda xatolik: " . $mysqli->error;
}

// Ulani uzish
$mysqli->close();
?>


// Telegramdan kelayotgan ma'lumotlar
$content = file_get_contents("php://input");
$update = json_decode($content, true);
$message = $update['message'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = trim($message['text'] ?? '');

// Inline tugmalar va admin panel
if ($text === "/start") {
    sendKeyboard($chat_id, "Salom! Bizning botga xush kelibsiz!", [['Buyurtma Berish']]);
} elseif ($text === "Buyurtma Berish") {
    showProducts($chat_id, $mysqli);
} elseif ($text === "/admin" && $chat_id == $admin_id) {
    sendKeyboard($chat_id, "🔧 Admin paneliga xush kelibsiz!", [['Maxsulot Qo\'shish']]);
} elseif ($text === "Maxsulot Qo'shish" && $chat_id == $admin_id) {
    sendMessage($chat_id, "✏️ Mahsulot nomini, narxini va rasm URL'sini quyidagicha yuboring:\n\n`Mahsulot nomi | 12345 | https://rasm_url`");
} elseif ($chat_id == $admin_id && strpos($text, '|') !== false) {
    addProduct($chat_id, $text, $mysqli);
} elseif ($text === "Sotib olish") {
    sendMessage($chat_id, "✅ Buyurtmangiz qabul qilindi. Tez orada siz bilan bog'lanamiz!");
} else {
    sendMessage($chat_id, "Mavjud buyruqlar: /start yoki /admin (faqat admin uchun)");
}

// Funksiya: Tugmalar yuborish
function sendKeyboard($chat_id, $text, $buttons)
{
    global $apiURL;
    $keyboard = ['keyboard' => $buttons, 'resize_keyboard' => true, 'one_time_keyboard' => true];
    $data = ['chat_id' => $chat_id, 'text' => $text, 'reply_markup' => json_encode($keyboard)];
    sendRequest($data);
}

// Funksiya: Mahsulotlarni ko'rsatish
function showProducts($chat_id, $mysqli)
{
    $result = $mysqli->query("SELECT * FROM products");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product_text = "📦 *Mahsulot*: {$row['name']}\n💰 *Narxi*: {$row['price']} so'm";
            sendPhoto($chat_id, $row['image'], $product_text, [['Sotib olish']]);
        }
    } else {
        sendMessage($chat_id, "📭 Hozircha mahsulotlar mavjud emas.");
    }
}

// Funksiya: Mahsulot qo'shish
function addProduct($chat_id, $text, $mysqli)
{
    list($name, $price, $image) = array_map('trim', explode('|', $text));
    if ($name && is_numeric($price) && filter_var($image, FILTER_VALIDATE_URL)) {
        $stmt = $mysqli->prepare("INSERT INTO products (name, price, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $price, $image);
        $stmt->execute();
        sendMessage($chat_id, "✅ Mahsulot qo'shildi: $name");
    } else {
        sendMessage($chat_id, "⚠️ Xatolik: Ma'lumotlarni to'g'ri yuboring! `Nom | Narx | Rasm URL`");
    }
}

// Funksiya: Xabar yuborish
function sendMessage($chat_id, $text)
{
    sendRequest(['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'Markdown']);
}

// Funksiya: Rasm yuborish
function sendPhoto($chat_id, $photo_url, $caption, $buttons)
{
    global $apiURL;
    $inline_keyboard = json_encode(['keyboard' => $buttons, 'resize_keyboard' => true]);
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'Markdown',
        'reply_markup' => $inline_keyboard
    ];
    sendRequest($data, 'sendPhoto');
}

// Asosiy so'rov funksiyasi
function sendRequest($data, $method = 'sendMessage')
{
    global $apiURL;
    $url = "$apiURL/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
?>
