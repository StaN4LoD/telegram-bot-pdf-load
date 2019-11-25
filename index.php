<?php

header('Content-Type: text/html; charset=utf-8');

// подключим API
require_once __DIR__ . '/vendor/autoload.php';


$token = "токен";
$bot = new \TelegramBot\Api\Client($token);
if(!file_exists("registered.trigger")){

// URl текущей страницы
$page_url = "https://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

$result = $bot->setWebhook($page_url);

if($result){
file_put_contents("registered.trigger",time()); // создаем файл дабы прекратить повторные регистрации
    }
}

$bot->command('start', function ($message) use ($bot) {

    $answer = 'Вас приветствует тестовый бот!';

    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
        [
            [
                ['text' => 'Информация о боте', 'callback_data' => 'help'],
                ['text' => 'Скачать переписку', 'callback_data' => 'load']
            ]
        ]
    );

    $bot->sendMessage($message->getChat()->getId(), $answer, null, false, null, $keyboard);
});

$bot->command('help', function ($message) use ($bot) {

    $answer = 'Бот ведет запись переписки в группе. Чтобы скачать переписку в формате pdf воспользуйтесь инлайн клавиатурой, либо напишите "скачать"';

    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
        [
            [
                ['text' => 'Скачать переписку', 'callback_data' => 'load']
            ]
        ]
    );

    $bot->sendMessage($message->getChat()->getId(), $answer, null, false, null, $keyboard);
});


$bot->callbackQuery(function ($callbackQuery) use ($bot) {

    if ($callbackQuery->getData() == "load"){

        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $fileName = './log/' . $chatId . '.txt';
        $fileNamePDF = './log/' . $chatId . '.pdf';

        $data = file_get_contents($fileName);

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($data);
        $mpdf->Output($fileNamePDF, 'F');

        $document = new \CURLFile($fileNamePDF);

        $bot->sendDocument($chatId, $document);

    }

    if ($callbackQuery->getData() == "help"){

        $answer = 'Бот ведет запись переписки в группе. Чтобы скачать переписку в формате pdf воспользуйтесь инлайн клавиатурой, либо напишите "скачать"';

        $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
            [
                [
                    ['text' => 'Скачать переписку', 'callback_data' => 'load']
                ]
            ]
        );
        $bot->sendMessage($callbackQuery->getMessage()->getChat()->getId(), $answer, null, false, null, $keyboard);

    }


});


$bot->on(function($Update) use ($bot){
    $message = $Update->getMessage();
    $mtext = $message->getText();
    $chatId = $message->getChat()->getId();
    $fileName = './log/' . $chatId . '.txt';
    $userName = $message->getFrom()->getFirstName();
    $data = $userName . ' : ' . $mtext . "<br>";  // подготовка данных для записи в txt
    $fileNamePDF = './log/' . $chatId . '.pdf';


    if (file_exists($fileName)) {

        file_put_contents($fileName, $data, FILE_APPEND);

    } else {

        $fp = fopen($fileName, "w+");

        fwrite($fp, $data);
        fclose ($fp);

        $messageText = 'Началась запись переписки!';
        $bot->sendMessage($chatId, $messageText);

    }

    if(mb_stripos($mtext,"скачать") !== false){

        $datatxt = file_get_contents($fileName);

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($datatxt);
        $mpdf->Output($fileNamePDF, 'F');

        $document = new \CURLFile($fileNamePDF);

        $bot->sendDocument($chatId, $document);

    }

}, function($message) use ($name){
    return true;
});



$bot->run();