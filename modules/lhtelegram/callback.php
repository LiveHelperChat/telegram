<?php

/* erLhcoreClassLog::write(print_r($_SERVER,true));
erLhcoreClassLog::write(print_r($_POST,true));
erLhcoreClassLog::write(print_r($_GET,true));
erLhcoreClassLog::write(file_get_contents("php://input"));
exit; */

$tBot = erLhcoreClassModelTelegramBot::fetch($Params['user_parameters']['id']);

$telegramExt = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
$telegramExt->setBot($tBot);

// Add you bot's API key and name
$bot_api_key  = $tBot->bot_api; // $telegramExt->settings['bot_api'];
$bot_username = $tBot->bot_username; // $telegramExt->settings['bot_username'];

// Define all IDs of admin users in this array (leave as empty array if not used)
$admin_users = [
//    123,
];

// Define all paths for your custom commands in this array (leave as empty array if not used)
$commands_paths = [
   'extension/lhctelegram/classes/Commands/',
];

try {


    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($commands_paths);

    // Enable admin users
    $telegram->enableAdmins($admin_users);

    // Enable MySQL
    //$telegram->enableMySql($mysql_credentials);

    // Logging (Error, Debug and Raw Updates)
    //Longman\TelegramBot\TelegramLog::initErrorLog(__DIR__ . "/{$bot_username}_error.log");
    //Longman\TelegramBot\TelegramLog::initDebugLog(__DIR__ . "/{$bot_username}_debug.log");
    //Longman\TelegramBot\TelegramLog::initUpdateLog(__DIR__ . "/{$bot_username}_update.log");

    // If you are using a custom Monolog instance for logging, use this instead of the above
    //Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);

    // Set custom Upload and Download paths
    //$telegram->setDownloadPath(__DIR__ . '/Download');
    //$telegram->setUploadPath(__DIR__ . '/Upload');

    // Here you can set some command specific parameters
    // e.g. Google geocode/timezone api key for /date command
    //$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);

    // Botan.io integration
    //$telegram->enableBotan('your_botan_token');

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter();

    // Handle telegram webhook request
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    erLhcoreClassLog::write($e->getMessage());
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    erLhcoreClassLog::write($e->getMessage());
} catch (Exception $e) {
    erLhcoreClassLog::write($e->getMessage());
}

exit;
?>