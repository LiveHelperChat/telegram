<?php

namespace LiveHelperChatExtension\lhctelegram\providers;

/**
 * Command menu ("/" button) of the operators group. Which LHC bot commands it shows, and in what order, is the bot
 * option "Commands in the "/" menu of the group" (lhc_telegram_bot.topic_commands, comma separated).
 * Descriptions come from the commands themselves (Bot → Commands). "/name" in a topic works as "!name"
 * (classes/Commands/GenericCommand.php).
 *
 * The menu follows the bot option and the LHC commands by itself: on a message in the operators group it is compared
 * with the one sent last time and re-sent to Telegram only when it changed.
 */
class TelegramTopicCommands
{
    // "Command1, /command2 ,!command3" → "command1, command2, command3"
    public static function normalizeList($value)
    {
        $names = array();
        foreach (preg_split('/[\s,;]+/', strtolower((string)$value)) as $name) {
            $name = ltrim($name, '/!');
            if (preg_match('/^[a-z0-9_]{1,32}$/', $name) && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        return implode(', ', $names);
    }

    public static function buildMenu($bot)
    {
        $menu = array();

        foreach (explode(', ', self::normalizeList($bot->topic_commands)) as $name) {
            if ($name === '' || $name === 'ban' || $name === 'endchat') {
                continue;
            }
            $command = \erLhcoreClassModelGenericBotCommand::findOne(array('filter' => array('command' => $name)));
            if (!($command instanceof \erLhcoreClassModelGenericBotCommand)) {
                continue;
            }
            $description = trim((string)($command->info_msg != '' ? $command->info_msg : $command->name));
            $menu[] = array('command' => $name, 'description' => mb_substr($description !== '' ? $description : '!' . $name, 0, 256));
        }

        $menu[] = array('command' => 'ban', 'description' => 'Ban the visitor for 30 days and close the chat');
        $menu[] = array('command' => 'endchat', 'description' => 'Close the chat');

        return $menu;
    }

    private static function stateFile($bot)
    {
        return 'cache/telegram_topic_commands_' . (int)$bot->id . '.md5';
    }

    // Sends the menu when it differs from the last one sent (or always with $force). Returns '' or an error.
    // A bot without commands in the option keeps whatever menu the group has, unless a menu was sent before.
    public static function sync($bot, $force = false)
    {
        if ((int)$bot->bot_client !== 1 || (int)$bot->group_chat_id === 0) {
            return '';
        }

        $stateFile = self::stateFile($bot);
        $scope = array('type' => 'chat', 'chat_id' => $bot->group_chat_id);

        if (self::normalizeList($bot->topic_commands) === '') {
            if (!is_file($stateFile)) {
                return '';
            }
            new \Longman\TelegramBot\Telegram($bot->bot_api, $bot->bot_username);
            $response = \Longman\TelegramBot\Request::deleteMyCommands(array('scope' => $scope));
            if (!$response->isOk()) {
                return (string)$response->getDescription();
            }
            unlink($stateFile);
            return '';
        }

        $menu = self::buildMenu($bot);
        $hash = md5(json_encode(array($menu, (string)$bot->group_chat_id)));

        if ($force === false && is_file($stateFile) && trim((string)file_get_contents($stateFile)) === $hash) {
            return '';
        }

        new \Longman\TelegramBot\Telegram($bot->bot_api, $bot->bot_username);
        $response = \Longman\TelegramBot\Request::setMyCommands(array('commands' => $menu, 'scope' => $scope));

        if (!$response->isOk()) {
            return (string)$response->getDescription();
        }

        file_put_contents($stateFile, $hash);
        return '';
    }
}
