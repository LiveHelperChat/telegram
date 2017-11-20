<?php

// /usr/bin/php cron.php -s site_admin -e lhctelegram -c cron/set

$telegramExt = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');

try {

    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($telegramExt->settings['bot_api'], $telegramExt->settings['bot_username']);

    // Set webhook
    $result = $telegram->setWebhook($telegramExt->settings['bot_hook']);
    if ($result->isOk()) {
        echo $result->getDescription();
    }

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // log telegram errors
    // echo $e->getMessage();
}

?>