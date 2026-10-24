<?php
/**
 * Re-sends the command menu of the operators groups right away (normally it follows the bot option
 * "Commands in the "/" menu of the group" by itself, see providers/TelegramTopicCommands.php):
 * php cron.php -s site_admin -e lhctelegram -c cron/sync_commands
 */

use LiveHelperChatExtension\lhctelegram\providers\TelegramTopicCommands;

foreach (erLhcoreClassModelTelegramBot::getList(array('filter' => array('bot_client' => 1))) as $bot) {
    if ((int)$bot->group_chat_id === 0 || TelegramTopicCommands::normalizeList($bot->topic_commands) === '') {
        continue;
    }
    $menu = implode(' ', array_map(function ($item) { return '/' . $item['command']; }, TelegramTopicCommands::buildMenu($bot)));
    $error = TelegramTopicCommands::sync($bot, true);
    echo '@' . $bot->bot_username . ': ' . ($error === '' ? 'menu set — ' . $menu : 'failed: ' . $error) . "\n";
}

?>
