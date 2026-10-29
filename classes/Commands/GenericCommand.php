<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Generic command
 *
 * Gets executed for generic commands, when no other appropriate one is found.
 */
class GenericCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'generic';

    /**
     * @var string
     */
    protected $description = 'Handles generic commands or is executed by default when a command is not found';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        if ($message === null) {
            exit;
        }

        //You can use $command as param
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $command = $message->getCommand();

        // In an operator topic "/name" works as the LHC bot command "!name",
        // so the commands can be picked from the Telegram command menu
        if ((int)$message->getMessageThreadId() > 0 && ($bangText = self::topicCommandToBang((string)$command, (string)$message->getText())) !== null) {
            $raw = $this->getUpdate()->getRawData();
            $raw['message']['text'] = $bangText;
            unset($raw['message']['entities']);

            $genericMessage = $this->telegram->getCommandObject('genericmessage');
            if ($genericMessage !== null) {
                return $genericMessage->setUpdate(new \Longman\TelegramBot\Entities\Update($raw, $this->telegram->getBotUsername()))->preExecute();
            }
        }

        //If the user is an admin and the command is in the format "/whoisXYZ", call the /whois command
        if (stripos($command, 'whois') === 0 && $this->telegram->isAdmin($user_id)) {
            return $this->telegram->executeCommand('whois');
        }

        // Operators group: an unknown or misplaced command (e.g. in General) is ignored, not answered
        if (!$message->getChat()->isPrivateChat()) {
            return Request::emptyResponse();
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'Command /' . $command . ' not found.. :(',
        ];

        return Request::sendMessage($data);
    }

    // "/name args" → "!name args" for LHC bot commands; null when it is not a topic command
    public static function topicCommandToBang($command, $text)
    {
        $command = strtolower(trim($command));
        if ($command === '') {
            return null;
        }

        $args = trim((string)preg_replace('/^\/\S+/u', '', trim($text)));

        if (\erLhcoreClassModelGenericBotCommand::getCount(array('filter' => array('command' => $command))) > 0) {
            return '!' . $command . ($args !== '' ? ' ' . $args : '');
        }

        return null;
    }
}
