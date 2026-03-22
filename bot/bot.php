<?php 
// JSON_PRETTY_PRINT
date_default_timezone_set("Asia/Tashkent"); 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'Telegram.php';
require_once 'db.php';

const TOKEN = '8578945945:AAHh5FjSb2Mp0xegN4_wk_IbDGOsXUj0gx0';
const BASE_URL = 'https://api.telegram.org/bot'.TOKEN;

$telegram = new Telegram('8578945945:AAHh5FjSb2Mp0xegN4_wk_IbDGOsXUj0gx0');
$data = $telegram->getData();
$chat_id = $telegram->UserID();
// $user_id = $telegram->UserID();
$text = $telegram->Text();

if ($data['message']) {
	$chat_type = $data['message']['chat']['type'];
}
else{
	$chat_type = $data['callback_query']['message']['chat']['type'];
}

$message = ((isset($data['message'])) ? $data['message'] : '');
$callback_query = (($telegram->Callback_Query()) ? $telegram->Callback_Query() : false);
$callback_data = (($telegram->Callback_Data()) ? $telegram->Callback_Data() : false);
$step_id = '';

$group_id = '-5026004534'; // aosiy gruppa
$admins = ['284914591'];
$eskiz_token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3NzY2Njk4NzAsImlhdCI6MTc3NDA3Nzg3MCwicm9sZSI6InVzZXIiLCJzaWduIjoiYjlhMjA0NmUwZmVjZTY4YTc5MzVjMDE5MDhjZWEzYmYzZjllMzhkMTM1ZWQyMTJmN2M2MGMyYjc0YzBmN2U3YiIsInN1YiI6IjEzNDg0In0.WIPvDrdjvqtUi-lNS-ujyxZo6VI9EXYv4rAgAJnK_pk';
$add_shop_text_uz = "BIYRON dasturiga kirish kodingiz:";
$add_shop_text_ru = "Ваш код доступа к программе BIYRON:";
$apiKey = 'f6f40aea-928a-4aab-b6e7-92fad1b3333f';

$no_texts = [
    '/start',
];

function insertTwoDigit($twoDigit, $fourDigit, $position1, $position2)
{
    $position1--;
    $position2--;

    $two  = str_pad($twoDigit, 2, '0', STR_PAD_LEFT);
    $four = str_pad($fourDigit, 4, '0', STR_PAD_LEFT);

    $result = array_fill(0, 6, null);

    $result[$position1] = $two[0];
    $result[$position2] = $two[1];

    $fourIndex = 0;
    for ($i = 0; $i < 6; $i++) {
        if ($result[$i] === null) {
            $result[$i] = $four[$fourIndex];
            $fourIndex++;
        }
    }

    return implode('', $result);
}


function bot($method, $data = []) {
    $url = BASE_URL.'/'.$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

$remove_keyboard = array(
    'remove_keyboard' => true,
    'selective' => true
);
$remove_keyboard = json_encode($remove_keyboard);

if ($chat_type == 'private') {

    $stmt = $conn->prepare("SELECT * FROM clients WHERE chat_id = ?");
    $stmt->execute([$chat_id]); 
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        $sql = "INSERT INTO clients (chat_id, step, status, created_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE step = VALUES(step)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([$chat_id, -1, 0, date('Y-m-d H:i:s')]);

        $option = [
            [
                ['text' => "🇺🇿 O'zbek", 'callback_data' => 'uz'],
                ['text' => "🇷🇺 Русский", 'callback_data' => 'ru'],
                ['text' => "🇺🇿 Кирил", 'callback_data' => 'kr']
            ],
        ];

        $keyb = json_encode(['inline_keyboard' => $option]);

        $content = [
            'chat_id' => $chat_id,
            'reply_markup' => $keyb,
            'parse_mode' => 'html',
            'text' => "🇺🇿 Til tanlang".PHP_EOL."🇷🇺 Выберите язык"
        ];
        $response = $telegram->sendMessage($content);

        $message_id = $response['result']['message_id'];
        $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
        $conn->prepare($sql)->execute([$message_id, $chat_id]);
        return;
    }
    else{
        if($text == '/start' && $client['status'] == 1){
            $start_text = "🏠 Bosh sahifa";
            $new_shop = "🏬 Do'kon qo`shish";
            $content_admin = "👨‍💻 Admin bilan bog'lanish";
            
            if ($client['language'] == 2){
                $start_text = "🏠 Главная страница";
                $new_shop = "🏬 Добавить магазин";
                $content_admin = "👨‍💻 Связаться с администратором";
            }
            else if ($client['language'] == 3){
                $start_text = "🏠 Бош саҳифа";
                $new_shop = "🏬 Дўкон қўшиш";
                $content_admin = "👨‍💻 Админ билан боғланиш";
            }

            $option = [ 
                [   $telegram->buildKeyboardButton($new_shop), 
                    $telegram->buildKeyboardButton($content_admin)
                ],  
            ];

            $keyb = $telegram->buildKeyBoard($option, false, true);

            $content = array('chat_id' => $chat_id, 'parse_mode' => 'html', 'reply_markup' => $keyb, 'text' => $start_text);
            $response = $telegram->sendMessage($content);

            $message_id = $response['result']['message_id'];
            $sql = "UPDATE clients SET last_message_id = ?, step = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, 1, $chat_id]);

            $stmt = $conn->prepare("SELECT * FROM shops WHERE client_id = ? AND status = ?");
            $stmt->execute([$client['id'], 1]); 
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($shop) {
                $mini_app_text = "🛍 Buyurtma berish";
                $mini_app_header = "😊 Buyurtma berish uchun quyidagi tugmani bosing";
                if ($client['language'] == 2) {
                    $mini_app_text = "🛍 Заказать";
                    $mini_app_header = "😊 Нажмите на кнопку ниже, чтобы оформить заказ";
                }
                else if ($client['language'] == 3) {
                    $mini_app_text = "🛍 Буюртма бериш";
                    $mini_app_header = "😊 Буюртма бериш учун қуйидаги тугмани босинг";
                }
                $response = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => $mini_app_header,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => $mini_app_text,
                                    'web_app' => [
                                        'url' => 'https://mini.biiyron.uz/index.php?user_id='.$chat_id
                                    ]
                                ]
                            ]
                        ]
                    ])
                ]);
            }
            return;
        }
        else if($client['step'] == -1 && $callback_data){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);

            if ($callback_data == 'uz') {
                $ask_name = 'Ismingizni yozing ✏️';
                $sql = "UPDATE clients SET language = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([1, $chat_id]);
            }
            else if ($callback_data == 'kr') {
                $ask_name = 'Исмингизни ёзинг ✏️';
                $sql = "UPDATE clients SET language = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([3, $chat_id]);
            }
            else{
                $ask_name = 'Напишите свое имя ✏️';
                $sql = "UPDATE clients SET language = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([2, $chat_id]);
            }

            $content = [
                'chat_id' => $chat_id,
                'parse_mode' => 'html',
                'text' => $ask_name
            ];
            $response = $telegram->sendMessage($content);

            $message_id = $response['result']['message_id'];
            $sql = "UPDATE clients SET last_message_id = ?, step = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, -2, $chat_id]);
            return;
        }
        else if($client['step'] == -1 && $text == '/start'){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);

            $option = [
                [
                    ['text' => "🇺🇿 O'zbek", 'callback_data' => 'uz'],
                    ['text' => "🇷🇺 Русский", 'callback_data' => 'ru'],
                    ['text' => "🇺🇿 Кирил", 'callback_data' => 'kr']
                ],
            ];

            $keyb = json_encode(['inline_keyboard' => $option]);

            $content = [
                'chat_id' => $chat_id,
                'reply_markup' => $keyb,
                'parse_mode' => 'html',
                'text' => "🇺🇿 Til tanlang".PHP_EOL."🇷🇺 Выберите язык"
            ];
            $response = $telegram->sendMessage($content);

            $message_id = $response['result']['message_id'];
            $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, $chat_id]);
            return;
        }
        else if($client['step'] == -2 && $text != '/start'){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);

            $contact_message = '☎️ Telefon raqamingizni yuboring';
            $contact_button = '📞 Telefon raqamni yuborish';
            if ($client['language'] == 2) {
                $contact_message = '☎️ Отправьте свой номер телефона';
                $contact_button = '📞 Отправить номер телефона';
            }
            else if ($client['language'] == 3) {
                $contact_message = '☎️ Телефон рақамингизни юборинг';
                $contact_button = '📞 Телефон рақамни юбориш';
            }

            $keyboard = [
                'keyboard' => [
                    [
                        [
                            'text' => $contact_button,
                            'request_contact' => true
                        ]
                    ]
                ],
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ];

            $content = [
                'chat_id' => $chat_id,
                'parse_mode' => 'html',
                'text' => $contact_message,
                'reply_markup' => json_encode($keyboard)
            ];
            $response = $telegram->sendMessage($content);
            $message_id = $response['result']['message_id'];

            $sql = "UPDATE clients SET last_message_id = ?, step = ?, owner_name = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, -3, $text, $chat_id]);
            return;
        }
        else if($client['step'] == -2 && $text == '/start'){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);

            if ($client['language'] == 1) {
                $ask_name = 'Ismingizni yozing ✏️';
                $sql = "UPDATE clients SET language = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([1, $chat_id]);
            }
            else if ($client['language'] == 3) {
                $ask_name = 'Исмингизни ёзинг ✏️';
                $sql = "UPDATE clients SET language = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([3, $chat_id]);
            }
            else if ($client['language'] == 2) {
                $ask_name = 'Напишите свое имя ✏️';
                $sql = "UPDATE clients SET language = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([2, $chat_id]);
            }

            $content = [
                'chat_id' => $chat_id,
                'parse_mode' => 'html',
                'text' => $ask_name
            ];
            $response = $telegram->sendMessage($content);

            $message_id = $response['result']['message_id'];
            $sql = "UPDATE clients SET last_message_id = ?, step = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, -2, $chat_id]);
            return;
        }
        else if($client['step'] == -3){
            if (isset($data['message']['contact'])) {
                $message_id = $client['last_message_id'];
                $telegram->deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => $message_id
                ]);

                $phone = $data['message']['contact']['phone_number'];

                $start_text = "🏠 Bosh sahifa";
                $new_shop = "🏬 Do'kon qo`shish";
                $content_admin = "👨‍💻 Admin bilan bog'lanish";
                if ($client['language'] == 2){
                    $start_text = "🏠 Главная страница";
                    $new_shop = "🏬 Добавить магазин";
                    $content_admin = "👨‍💻 Связаться с администратором";
                }
                else if ($client['language'] == 3){
                    $start_text = "🏠 Бош саҳифа";
                    $new_shop = "🏬 Дўкон қўшиш";
                    $content_admin = "👨‍💻 Админ билан боғланиш";
                }

                $option = [ 
                    [   $telegram->buildKeyboardButton($new_shop), 
                        $telegram->buildKeyboardButton($content_admin)
                    ],  
                ];

                $keyb = $telegram->buildKeyBoard($option, false, true);

                $content = array('chat_id' => $chat_id, 'parse_mode' => 'html', 'reply_markup' => $keyb, 'text' => $start_text);
                $response = $telegram->sendMessage($content);

                $message_id = $response['result']['message_id'];
                $sql = "UPDATE clients SET last_message_id = ?, step = ?, phone_number = ?, status = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([$message_id, 1, $phone, 1, $chat_id]);

            } 
            else {
                $fail_contact_text = "❗️ Iltimos, telefon raqamni yuborish tugmasini bosing!";
                if ($client['language'] == 2)
                    $fail_contact_text = "❗️ Нажмите кнопку «Отправить номер телефона»!";
                else if ($client['language'] == 3)
                    $fail_contact_text = "❗️ Илтимос, телефон рақамни юбориш тугмасини босинг!";
                $content = [
                    'chat_id' => $chat_id,
                    'text' => $fail_contact_text
                ];
                $response = $telegram->sendMessage($content);
            }
            return;
        }
        else if($client['step'] == 1 && !in_array($text, $no_texts) && 
            ($text == "👨‍💻 Admin bilan bog'lanish" || $text == "👨‍💻 Связаться с администратором" || $text == "👨‍💻 Админ билан боғланиш")){

            $admin_message = "👨‍💻 Admin bilan bog'lanish uchun quyidagi raqamlardan biriga qo'ng'iroq qilishingiz mumkin:".PHP_EOL."+998900000000";
            if ($client['language'] == 2) {
                $admin_message = "👨‍💻 Чтобы связаться с администратором, вы можете позвонить по одному из следующих номеров:".PHP_EOL."+998900000000";
            }
            else if ($client['language'] == 3) {
                $admin_message = "👨‍💻 Админ билан боғланиш учун қуйидаги рақамлардан бирига қўнғироқ қилишингиз мумкин:".PHP_EOL."+998900000000";
            }

            $content = [
                'chat_id' => $chat_id,
                'parse_mode' => 'html',
                'text' => $admin_message,
            ];
            $response = $telegram->sendMessage($content);
            $message_id = $response['result']['message_id'];

            $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, $chat_id]);
            return;

        }
        else if($client['step'] == 1 && !in_array($text, $no_texts) && 
            ($text == "🏬 Do'kon qo`shish" || $text == "🏬 Добавить магазин" || $text == "🏬 Дўкон қўшиш")){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);

            $ask_name = "✍️ Do'kon nomini yozing";
            if ($client['language'] == 2) {
                $ask_name = "✍️ Напишите название магазина";
            }
            else if ($client['language'] == 3) {
                $ask_name = "✍️ Дўкон номини ёзинг";
            }

            $content = [
                'chat_id' => $chat_id,
                'parse_mode' => 'html',
                'text' => $ask_name,
            ];
            $response = $telegram->sendMessage($content);
            $message_id = $response['result']['message_id'];

            $sql = "UPDATE clients SET last_message_id = ?, step = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, 2, $chat_id]);
            return;

        }
        else if($client['step'] == 2 && !in_array($text, $no_texts)){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);


            $name = $text;
            
            $stmt = $conn->prepare("SELECT id FROM shops WHERE status = ? AND client_id = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([0, $client['id']]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($shop) {
                // Faqat name ni update qilamiz
                $stmt = $conn->prepare("UPDATE shops SET name = :name, updated_at = :updated_at WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':id'   => $shop['id'],
                    ':updated_at'   => date('Y-m-d H:i:s'),
                ]);
            } else {
                // Yangi yozuv insert qilamiz
                $stmt = $conn->prepare(
                    "INSERT INTO shops (client_id, name, status, created_at) VALUES (:client_id, :name, 0, :date)"
                );
                $stmt->execute([
                    ':client_id' => $client['id'],
                    ':name' => $name,
                    ':date' => date('Y-m-d H:i:s'),
                ]);
            }


            $client_message_button = "📍 Lokatsiyani yuborish";
            $client_message = "Iltimos, yangi do'kon lokatsiyasini yuboring 👇";
            if ($client['language'] == 2){
                $client_message = "Пожалуйста, отправьте новый адрес магазина 👇";
                $client_message_button = "📍 Отправить местоположение";
            }
            else if ($client['language'] == 3){
                $client_message = "Iltimos, yangi do'kon lokatsiyasini yuboring 👇";
                $client_message_button = "📍 Локацияни юбориш";
            }
            
            $keyboard = [
                'keyboard' => [
                    [
                        [
                            'text' => $client_message_button,
                            'request_location' => true
                        ]
                    ]
                ],
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ];

            $content = [
                'chat_id' => $chat_id,
                'parse_mode' => 'html',
                'text' => $client_message,
                'reply_markup' => json_encode($keyboard)
            ];
            $response = $telegram->sendMessage($content);
            $message_id = $response['result']['message_id'];

            $sql = "UPDATE clients SET last_message_id = ?, step = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, 3, $chat_id]);
            return;
        }
        else if($client['step'] == 3 && !in_array($text, $no_texts)){
            if (isset($data['message']['location'])) {

                $message_id = $client['last_message_id'];
                $telegram->deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => $message_id
                ]);

                $warehouse_id = null;

                $lat = $data['message']['location']['latitude'];
                $lon = $data['message']['location']['longitude'];

                $sql = "UPDATE shops SET lat = ?, `long` = ?  WHERE client_id = ? AND status = ?";
                $conn->prepare($sql)->execute([$lat, $lon, $client['id'], 0]);
                
                $url = "https://geocode-maps.yandex.ru/v1/?apikey={$apiKey}&geocode={$lon},{$lat}&format=json&lang=uz_UZ";

                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 10,
                ]);

                $response = curl_exec($ch);
                $error = curl_error($ch);

                curl_close($ch);

                if ($error) {
                    $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => $error
                    ]);
                } 
                else {
                    $responseData = json_decode($response, true);
                    $address = $responseData['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'];

                    $sql = "UPDATE shops SET address = ? WHERE client_id = ? AND status = ?";
                    $conn->prepare($sql)->execute([$address, $client['id'], 0]);

                    $district = null;
                    $street = null;

                    $features = $responseData['response']['GeoObjectCollection']['featureMember'];

                    // Tuman (district) va ko'cha (street) qidirish
                    foreach ($features as $item) {
                        if (!isset($item['GeoObject']['metaDataProperty']['GeocoderMetaData']['Address']['Components'])) {
                            continue;
                        }
                        
                        $components = $item['GeoObject']['metaDataProperty']['GeocoderMetaData']['Address']['Components'];

                        foreach ($components as $comp) {

                            // Tuman
                            if ($district === null && $comp['kind'] === 'district') {
                                $district = $comp['name'];
                            }

                            // Ko'cha
                            if ($street === null && $comp['kind'] === 'street') {
                                $street = $comp['name'];
                            }

                            // Ikkalasi topilganda chiqib ketamiz
                            if ($district !== null && $street !== null) {
                                break 2;
                            }
                        }
                    }

                
                    // Yandex dan kelgan tuman nomini tozalash
                    $cleanDistrict = trim(str_ireplace(
                        [' tumani', ' tuman', ' rayoni', ' rayon'], 
                        '', 
                        mb_strtolower($district)
                    ));

                    // Bosh harf bilan qaytarish (ixtiyoriy)
                    $cleanDistrict = mb_convert_case($cleanDistrict, MB_CASE_TITLE, "UTF-8");

                    // Endi LIKE bo'yicha qidirish
                    $stmt = $conn->prepare("
                        SELECT *
                        FROM districts
                        WHERE LOWER(name_uz) LIKE LOWER(?)
                        LIMIT 1
                    ");

                    $stmt->execute(['%' . $cleanDistrict . '%']);
                    $find_district = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($find_district) {

                        $stmt = $conn->prepare("SELECT position FROM digit_positions WHERE is_active = ?");
                        $stmt->execute([1]); 
                        $digit_positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $stmt = $conn->prepare("SELECT * FROM warehouses WHERE district_id = ? AND deleted_at IS NULL");
                        $stmt->execute([$find_district['id']]); 
                        $warehouse = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$warehouse) {
                            $content = [
                                'chat_id' => $chat_id,
                                'parse_mode' => 'html',
                                'text' => 'Ombor topilmadi!',
                            ];
                            $telegram->sendMessage($content);
                            return;
                        }



                        $position1 = $digit_positions[0]['position'];
                        $position2 = $digit_positions[1]['position'];

                        $random_code = random_int(1000, 9999);
                        $unique_number = $warehouse['unique_number'];

                        $result_code = insertTwoDigit($unique_number, $random_code, $position1, $position2);


                        $sql = "UPDATE clients SET part_code = ?, full_code = ?, step = ?, temp_warehouse_id = ?  WHERE chat_id = ?";
                        $conn->prepare($sql)->execute([$random_code, $result_code, 4, $warehouse['id'], $chat_id]);

                        $arr = [
                            'mobile_phone' => $client['phone_number'],
                            'message' => (($client['language'] == 1) ? $add_shop_text_uz." ".$random_code : $add_shop_text_ru." ".$random_code),
                            'from' => '4546'
                        ];

                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => "https://notify.eskiz.uz/api/message/sms/send",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => $arr,
                            CURLOPT_HTTPHEADER => array(
                                "Authorization:  Bearer ".$eskiz_token,
                                "Cache-Control: no-cache",
                            ),
                        ));
                        $response_eskiz = curl_exec($curl);
                        $err = curl_error($curl);
                        curl_close($curl);

                        if ($err) {
                            $content = [
                                'chat_id' => $chat_id,
                                'parse_mode' => 'html',
                                'text' => $err,
                            ];
                            $response = $telegram->sendMessage($content);
                            $message_id = $response['result']['message_id'];
                            $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                            $conn->prepare($sql)->execute([$message_id, $chat_id]);
                        }
                        else{
                            $response_eskiz = json_decode($response_eskiz);
                            if (isset($response_eskiz->status) && $response_eskiz->status == 'waiting') {
                                $message = "📨 Sizning telefon raqamingizga maxsus kod yuborildi, shu kodni Agentingizga bering";
                                if ($client['language'] == 2)
                                    $message = "📨 На ваш номер телефона отправлен специальный код. Сообщите этот код своему агенту";
                                else if ($client['language'] == 3)
                                    $message = "📨 Сизнинг телефон рақамингизга махсус код юборилди, шу кодни Агентингизга беринг";

                                $content = [
                                    'chat_id' => $chat_id,
                                    'parse_mode' => 'html',
                                    'reply_markup' => $remove_keyboard,
                                    'text' => $message
                                ];
                                $response = $telegram->sendMessage($content);

                                $message_id = $response['result']['message_id'];
                                $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                                $conn->prepare($sql)->execute([$message_id, $chat_id]);
                            }
                            else{
                                $message = "📨 Sms yuborishda xatolik! Adminstrtorga aloqaga chiqing.";
                                if ($client['language'] == 2)
                                    $message = "📨 Ошибка отправки SMS! Свяжитесь с администратором.";
                                else if ($client['language'] == 3)
                                    $message = "📨 Смс юборишда хатолик! Админстрторга алоқага чиқинг.";

                                $content = [
                                    'chat_id' => $chat_id,
                                    'parse_mode' => 'html',
                                    'text' => json_encode($response_eskiz, JSON_PRETTY_PRINT)
                                ];
                                $response = $telegram->sendMessage($content);
                                $message_id = $response['result']['message_id'];
                                $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                                $conn->prepare($sql)->execute([$message_id, $chat_id]);
                            }
                        }
                        return;
                    }
                    else{
                        $not_district = "😔 Afsuski, bu manzil bo'yicha ombor mavjud emas!";
                        if ($client['language'] == 2) {
                            $not_district = "😔 К сожалению, по данному адресу склада нет!";
                        }
                        else if ($client['language'] == 3) {
                            $not_district = "😔 Афсуски, бу манзил бўйича омбор мавжуд эмас!";
                        }

                        $content = [
                            'chat_id' => $chat_id,
                            'parse_mode' => 'html',
                            'text' => $not_district,
                        ];
                        $response = $telegram->sendMessage($content);
                        $message_id = $response['result']['message_id'];

                        $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                        $conn->prepare($sql)->execute([$message_id, $chat_id]);
                        
                        $message_id = $client['last_message_id'];
                        $telegram->deleteMessage([
                            'chat_id' => $chat_id,
                            'message_id' => $message_id
                        ]);

                        $start_text = "🏠 Bosh sahifa";
                        $new_shop = "🏬 Do'kon qo`shish";
                        $content_admin = "👨‍💻 Admin bilan bog'lanish";
                        
                        if ($client['language'] == 2){
                            $start_text = "🏠 Главная страница";
                            $new_shop = "🏬 Добавить магазин";
                            $content_admin = "👨‍💻 Связаться с администратором";
                        }
                        else if ($client['language'] == 3){
                            $start_text = "🏠 Бош саҳифа";
                            $new_shop = "🏬 Дўкон қўшиш";
                            $content_admin = "👨‍💻 Админ билан боғланиш";
                        }

                        $option = [ 
                            [   $telegram->buildKeyboardButton($new_shop), 
                                $telegram->buildKeyboardButton($content_admin)
                            ],  
                        ];

                        $keyb = $telegram->buildKeyBoard($option, false, true);

                        $content = array('chat_id' => $chat_id, 'parse_mode' => 'html', 'reply_markup' => $keyb, 'text' => $start_text);
                        $response = $telegram->sendMessage($content);

                        $message_id = $response['result']['message_id'];
                        $sql = "UPDATE clients SET last_message_id = ?, step = ? WHERE chat_id = ?";
                        $conn->prepare($sql)->execute([$message_id, 1, $chat_id]);

                        $stmt = $conn->prepare("SELECT * FROM shops WHERE client_id = ? AND status = ?");
                        $stmt->execute([$client['id'], 1]); 
                        $shop = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($shop) {
                            $mini_app_text = "🛍 Buyurtma berish";
                            $mini_app_header = "😊 Buyurtma berish uchun quyidagi tugmani bosing";
                            if ($client['language'] == 2) {
                                $mini_app_text = "🛍 Заказать";
                                $mini_app_header = "😊 Нажмите на кнопку ниже, чтобы оформить заказ";
                            }
                            else if ($client['language'] == 3) {
                                $mini_app_text = "🛍 Буюртма бериш";
                                $mini_app_header = "😊 Буюртма бериш учун қуйидаги тугмани босинг";
                            }
                            $response = $telegram->sendMessage([
                                'chat_id' => $chat_id,
                                'text'    => $mini_app_header,
                                'reply_markup' => json_encode([
                                    'inline_keyboard' => [
                                        [
                                            [
                                                'text' => $mini_app_text,
                                                'web_app' => [
                                                    'url' => 'https://mini.biiyron.uz/index.php?user_id='.$chat_id
                                                ]
                                            ]
                                        ]
                                    ]
                                ])
                            ]);
                        }
                        return;
                    }
                }
                return;
            }
            else{
                $location_message = "‼️ Lokatsiya yubormadingiz, yuborib qayta urinib ko'ring.";
                if ($client['language'] == 2)
                    $location_message = "‼️ Вы не отправили местоположение, пожалуйста повторите попытку.";
                else if ($client['language'] == 3)
                    $location_message = "‼️ Локация юбормадингиз, юбориб қайта уриниб кўринг.";
                    
                
                $response = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => $location_message
                ]);
                $message_id = $response['result']['message_id'];

                $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([$message_id, $chat_id]);
                return;
            }
        }
        else if($client['step'] == 4 && !in_array($text, $no_texts)){
            $message_id = $client['last_message_id'];
            $telegram->deleteMessage([
                'chat_id' => $chat_id,
                'message_id' => $message_id
            ]);

            $message_numeric = "❌ Faqat raqam kiriting!";
            $message_six = "❌ 6 xonali raqam kiritilsihi kerak!";
            $message_error = "❌ Xato kod kiritdingiz, qayta urinib ko'ring!";
            $message_success = "🥳 Do'kon muvaffaqiyatli qo'shildi.";
            $mini_app_text = "🛍 Buyurtma berish";
            $mini_app_header = "😊 Buyurtma berish uchun quyidagi tugmani bosing";
            $new_shop = "🏬 Do'kon qo`shish";
            $content_admin = "👨‍💻 Admin bilan bog'lanish";
            if ($client['language'] == 2) {
                $message_numeric = "❌ Только введите число!";
                $message_six = "❌ Необходимо ввести шестизначное число!";
                $message_error = "❌ Вы ввели неверный код, пожалуйста, попробуйте еще раз!";
                $message_success = "🥳 Магазин успешно добавлен.";
                $mini_app_text = "🛍 Заказать";
                $mini_app_header = "😊 Нажмите на кнопку ниже, чтобы оформить заказ";
                $new_shop = "🏬 Добавить магазин";
                $content_admin = "👨‍💻 Связаться с администратором";
            }
            else if ($client['language'] == 3) {
                $message_numeric = "❌ Фақат рақам киритинг!";
                $message_six = "❌ 6 хонали рақам киритилсиҳи керак!";
                $message_error = "❌ Хато код киритдингиз, қайта уриниб кўринг!";
                $message_success = "🥳 Дўкон муваффақиятли қўшилди.";
                $mini_app_text = "🛍 Буюртма бериш";
                $mini_app_header = "😊 Буюртма бериш учун қуйидаги тугмани босинг";
                $new_shop = "🏬 Дўкон қўшиш";
                $content_admin = "👨‍💻 Админ билан боғланиш";
            }

            if (!is_numeric($text)) {
                $message_id = $client['last_message_id'];
                $telegram->deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => $message_id
                ]);

                $response = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => $message_numeric
                ]);
                $message_id = $response['result']['message_id'];
                $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([$message_id, $chat_id]);
            }
            else if(strlen((string)$text) != 6){
                $message_id = $client['last_message_id'];
                $telegram->deleteMessage([
                    'chat_id' => $chat_id,
                    'message_id' => $message_id
                ]);

                $response = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => $message_six
                ]);
                $message_id = $response['result']['message_id'];
                $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
                $conn->prepare($sql)->execute([$message_id, $chat_id]);
            }
            else{
                $stmt = $conn->prepare("SELECT * FROM clients WHERE chat_id = ?");
                $stmt->execute([$chat_id]); 
                $client = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($client['full_code'] == $text) {

                    $sql = "UPDATE shops SET status = ?, warehouse_id = ? WHERE client_id = ? AND status = ?";
                    $conn->prepare($sql)->execute([1, $client['temp_warehouse_id'], $client['id'], 0]);

                    $sql = "UPDATE clients SET part_code = ?, full_code = ?, step = ?, temp_warehouse_id = ? WHERE chat_id = ?";
                    $conn->prepare($sql)->execute([null, null, 1, null, $chat_id]);

                    $option = [ 
                        [   $telegram->buildKeyboardButton($new_shop), 
                            $telegram->buildKeyboardButton($content_admin)
                        ],  
                    ];

                    $keyb = $telegram->buildKeyBoard($option, false, true);

                    $content = array('chat_id' => $chat_id, 'parse_mode' => 'html', 'reply_markup' => $keyb, 'text' => $message_success);
                    $response = $telegram->sendMessage($content);
                    
                    $response = $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => $mini_app_header,
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => $mini_app_text,
                                        'web_app' => [
                                            'url' => 'https://mini.biiyron.uz/index.php?user_id='.$chat_id
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ]);

                    $message_id = $response['result']['message_id'];
                }
                else{
                    $message_id = $client['last_message_id'];
                    $telegram->deleteMessage([
                        'chat_id' => $chat_id,
                        'message_id' => $message_id
                    ]);

                    $response = $telegram->sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => $message_error
                    ]);
                    $message_id = $response['result']['message_id'];
                }
            }
            $sql = "UPDATE clients SET last_message_id = ? WHERE chat_id = ?";
            $conn->prepare($sql)->execute([$message_id, $chat_id]);
            return;
        }
    }
    return;
    
}