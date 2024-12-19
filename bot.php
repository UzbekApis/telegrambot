<?php
// Telegram bot token
define('BOT_TOKEN', '5170079634:AAHPDjyE830P9oBrhs9IvgSFmEMs4dYeYxQ');

// MySQL ulanishi
$host = 'mysql.railway.internal'; 
$db   = 'railway'; 
$user = 'root'; 
$pass = 'GgOPWyUqoTVdhtSMbaJWiCvvEwUXESpD'; 
$port = 3306; 
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("MySQL ulanishi muvaffaqiyatsiz: " . $conn->connect_error);
}

// Telegram ma'lumotlarini olish
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

// Kiruvchi xabarlarni aniqlash
$chatId = $update['message']['chat']['id'] ?? ($update['callback_query']['message']['chat']['id'] ?? null);
$text = $update['message']['text'] ?? '';
$callbackData = $update['callback_query']['data'] ?? '';

// /start buyruq
if ($text == '/start') {
    sendMessage($chatId, "Qaysi sinf rahbarisiz?");
    $conn->query("INSERT INTO users (chat_id, current_step) VALUES ('$chatId', 'ask_class') ON DUPLICATE KEY UPDATE current_step='ask_class'");
    exit;
}

// Callback tugmalarni ishlash
if ($callbackData) {
    if ($callbackData == 'change_class') {
        sendMessage($chatId, "Yangi sinfni kiriting:");
        $conn->query("UPDATE users SET current_step='ask_class' WHERE chat_id='$chatId'");
    } elseif ($callbackData == 'start') {
        sendMessage($chatId, "Maʼlumotlarni kiritish uchun:\n1. Sinf nomini kiriting\n2. Oʻquvchi ismi, otasi yoki onasi ismi\n3. Pasport seriyasi\n4. Pasport berilgan sana (YYYY-MM-DD).");
        $conn->query("UPDATE users SET current_step='collect_data' WHERE chat_id='$chatId'");
    }
    exit;
}

// Xabar bosqichlari
$result = $conn->query("SELECT current_step, class_name FROM users WHERE chat_id='$chatId'");
$user = $result->fetch_assoc();
$currentStep = $user['current_step'];

if ($currentStep == 'ask_class') {
		$s = "Allaberganov Alibek, Jabborov Mansur, AD6282936, 21-01-2007";
	$studentName = trim($data[0]);
    $parentName = trim($data[1]);
    $passportSeries = trim($data[2]);
    $passportDate = trim($data[3]);
    $className = $user['class_name'];
    
	$datas = explode(',', $s);
    $conn->query("UPDATE users SET class_name='$text', current_step='show_menu' WHERE chat_id='$chatId'");
    sendInlineKeyboard($chatId, "Sinf o'rnatildi. Quyidagi variantlardan birini tanlang: $datas

$studentName
$parentName
$passportSeries
$passportDate
$className", [
        [['text' => "Sinfni o'zgartirish", 'callback_data' => "change_class"]],
        [['text' => "Boshlash", 'callback_data' => "start"]],
    ]);
    exit;
}

if ($currentStep == 'collect_data') {
    $data = explode(',', $text);
       /// sendMessage($chatId, "Iltimos, to'g'ri formatda maʼlumot kiriting:\nOʻquvchi ismi, Otasi yoki Onasi ismi, Pasport seriyasi, Pasport berilgan sana (YYYY-MM-DD).");
     ///   exit

    // Trim va ajratilgan ma'lumotlar
    $studentName = trim($data[0]);
    $parentName = trim($data[1]);
    $passportSeries = trim($data[2]);
    $passportDate = trim($data[3]);
    $className = $user['class_name'];
$bazatext = 'DARSLIKLAR VA MASHQ DAFTARLARINI BEPUL FOYDALANISHGA BERISH TOʻGʻRISIDAGI SHARTNOMA

Bir tomondan Shovot tuman « Ijtimoiyat» qishlog‘ida joylashgan 9- son umumta’lim maktabi nomidan 9-son maktab direktori Jumanyazov Umarbek Madaminovich Ustav asosida ish yurituvchi, bundan buyon matnda «9-maktab » deb yuritiluvchi maktab direktori shaxsida hamda ikkinchi tomondan ta’lim o‘zbek tilida olib boriladigan $className - sinf o ‘quvchisi $studentName ota-onasi yoki ularning o‘rnini bosuvchi shaxs yoxud homiy bundan buyon matnda bir o‘quv yili davomida «Foydalanuvchi» deb yuritiluvchi shaxs  $parentName Mazkur shartnomani quyidagilar tuzishdi.

SHARTNOMA PREDMETI
1 Maktab 2024-2025 o‘quv yilida vaqtinchalik foydalanish uchun mazkur shartnomaning 1,2 bandida ko‘rsatib o‘tilgan Darsliklar to‘plamini Foydalanuvchiga beradi. 
Foydalanuvchi esa mazkur shartnoma asosida 2025-yil 25-maygacha darsliklar to‘plamini foydalanishga yaroqli holatda qaytarish majburiyatini oladi. 
1,2 Mazkur shartnomaning predmeti bo‘lgan darsliklar va qo‘llanmalar to‘plami foydalanuychigaquydagi nomlarda bir nusxadanbepul beriladi. 
1.2 DARSLIK NOMI
Algebra   Rus tili
Ona tili   Oʻzbekiston tarixi 
Adabiyot   Jahon tarixi
Kimyo  Huquq 
Fizika    Geografiya
Biologiya  Fransuz tili kitobi/daftari
Geometriya  Tarbiya 
Informatika  I.B.A

:II. TOMONLARNING HUQUQLARI
2.1. Maktab Foydalanuvchining aybi bilan darslik yoki oʻquv-metodik qoʻllanma yoʻqolganyoki foydalanishga yaroqsiz holatga kelib qolgan taqdirda, zararni xuddi shunday darslik yoki oʻquvmetodik qoʻllanma bilan qoplashni yoki uning qiymatini mazkur Shartnomaning 4.2.3-bandidakoʻrsatilgan tartibda toʻlashni talab qilish huquqiga ega.2.2. Foydalanuvchi ushbu Shartnoma tuzilgandan keyin uning 1.2-bandida koʻrsatilgandarsliklar yoki mashq daftarlarni foydalanishga yaroqli boʻlgan holatda bepul berilishini talab qilish huquqiga ega.

       3. TOMONLARNING MAJBURIYATLARI
3.1. Maktab quyidagilarga majbur:
3.1.1. Mazkur Shartnoma tuzilgandan keyin uning 1.2-bandida koʻrsatilgan darsliklar yokimashq daftarlarni Foydalanuvchiga foydalanishga yaroqli boʻlgan holatda bepul berish;
3.1.2. Foydalanuvchini darsliklar yoki mashq daftarlardan foydalanish shartlari va muddatlari toʻgʻrisida maʼlumotlar bilan taʼminlash.
3.2. Foydalanuvchi quyidagilarga majbur:
3.2.1. Olingan darsliklar yoki mashq daftarlarning toʻliq va foydalanishga yaroqli holatdasaqlanishini taʼminlash;
3.2.2. Darsliklar yoki mashq daftarlarni foydalanishga yaroqli holatda Maktabga qaytarish; 
3.2.3. Darsliklar yoki mashq daftarlar yoʻqolgan yoki keyinchalikfoydalanishga yaroqsiz holatga kelgan taqdirda, zararni xuddi shunday darsliklar yoki mashqdaftarlar bilan qoplash yoxud uning birlamchi hujjatlarda (hisob-fakturada, yuk xatida) koʻrsatilgan qiymatini quyidagi miqdorlarda toʻlash:
birinchi sinf oʻquvchilarining har bir darslik va mashq daftarlari uchun - uning bir baravarimiqdorida;
2 — 4-sinf oʻquvchilarining har bir darslik va mashq daftarlari uchun - uning ikki baravarimiqdorida;
5 — 9-sinf oʻquvchilarining har bir darslik va mashq daftarlari uchun - uning toʻrt baravarimiqdorida.
4. TOMONLARNING JAVOBGARLIGI
4.1. Mazkur Shartnoma boʻyicha olingan majburiyatlarni bajarmaganligi uchun Tomonlarshartnoma shartlari va qonunchilikhujjatlariga asosan javobgarlikka tortiladi.4.2. Tomonlar bartaraf etib boʻlmaydigan vaziyatlar (fors-major) oqibatida ushbushartnoma boʻyicha majburiyatlarni qisman yoki toʻliq bajarmaganlik uchun javobgarlikdan ozod etiladi. 
5. NIZOLARNI BARTARAF ETISH 
5.1. Mazkur Shartnomaning amal qilishi davomida vujudga kelgan nizolar tomonlar orasidamuzokaralar olib borish yoʻli bilan hal qilinadi.5.2. Nizolarni hal qilishda kelishuvga erishilmagan taqdirda, ular qonunchilik hujjatlaridabelgilangan tartibda sud tomonidan hal etiladi.
6. KORRUPSIYAGA QARSHI KURASHISH SHARTLARI
6.1 Tomonlar amaldagi korrupsiyaga qarshi kurashish borasidagi qonun hujjatlari talablariga rioya qilish va ushbu shartnoma bo‘yicha o‘z huquq va majburiyatlari bilan bogʻliq holda korrupsiyaga qarshi kurashish borasidagi qonun hujjatlarini buzadigan har qanday harakatlarni amalga oshirmaslik, shu jumladan noqonuniy to‘lovlar taklif qilmaslik, bunday to‘lovlarga ruxsat bermaslik, va’da qilmaslik, shuningdek har qanday jismoniy yoki yuridik shaxslarga, shu jumladan (lekin ular bilan cheklanmagan holda) tijorat tashkilotlariga, davlat va o‘zini o‘zi boshqarish organlariga, davlat xizmatchilariga, xususiy kompaniyalarga va ularning vakillariga naqd pul yoki boshqa shaklda (lekin cheklanmagan holda) pora bermaslik va pora talab qilmaslik hamda qonunga xilof boshqa xatti-harakatlar sodir etmaslik majburiyatini oladilar. 
6.2. Tomonlardan biri ushbu bandda ko‘rsatilgan majburiyatlarni buzgan taqdirda, boshqa tomon bir tomonlama va sudgacha bo‘lgan tartibda ushbu shartnomani bajarishni rad etishga haqli. Ushbu bandga muvoﬁq shartnoma bekor qilingan taqdirda tomonlar bir-birlariga kompensatsiya to‘lamaydilar.

7. YAKUNIY QOIDALAR
 Mazkur Shartnoma bilan tartibga solinmagan oʻzaro munosabatlar qonunchilik hujjatlarigaasosan hal qilinadi.
Mazkur Shartnoma 2 ta asl nusxada tuzildi. 
8. TOMONLARNING REKVIZITLARITOMONLARNING I MZOLARI
Maktabning raqami (nomi):9- son maktab                             Foydalanuvchining F.I.O: $parentName                                                                                                               Manzili: Madaniyat mahallasi   
"Madaniyat “ mahallasi Navoiy  ko‘chasi 13-uy                  Pasport seriyasi, $passportSeries kim tomonidan vaqachon berilgan:IIB tomonidan $passportDate. 
telefon /faks: 
Shaxsiy g‘azna hisob varag‘i:       O‘quvchining ism familyasi: $studentName                            100022860332237092100072046 
MFO: 00014STIR: 206892464XXTUT: 92310

Maktab direktori________________ _____               Foydalanuvchi: Ota -onasi ___________( imzo) ( imzo)';
    // Ma'lumotlarni saqlash
  

    sendMessage($chatId, $bazatext);
    $conn->query("UPDATE users SET current_step='show_menu' WHERE chat_id='$chatId'");
    exit;
}

// Funksiyalar
function sendMessage($chatId, $text)
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
    ];
    sendRequest($url, $data);
}

function sendInlineKeyboard($chatId, $text, $keyboard)
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
    ];
    sendRequest($url, $data);
}

function sendRequest($url, $data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    curl_close($ch);
}
?>
