<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

/**
 * User "/register" command
 *
 * Registers sender as operator in Live Helper Chat back office
 */
class ChatsCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'chats';

    /**
     * @var string
     */
    protected $description = 'Get list of my active chats.';

    /**
     * @var string
     */
    protected $usage = 'Type /chats';

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();

        if ($tBot->bot_client == 0) {
            $data = [
                'chat_id' => $chat_id,
                'text'    => "This command is available only if bot is set to act as a client!",
            ];
            return Request::sendMessage($data);
        }

        $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tchat_id' => $chat_id, 'confirmed' => 1, 'bot_id' => $tBot->id)));

        if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

            $chats = \erLhcoreClassModelChat::getList(array('limit' => 10, 'filter' => array('status' => 1, 'user_id' => $operator->user_id)));

            $inlineKeyboards = array();

            foreach ($chats as $chat) {
                $activeText = $chat->id == $operator->chat_id ? '*Current* | ' : '';
                $inlineKeyboards[] = ['text' => $activeText . "Chat [{$chat->id}] $chat->nick", 'callback_data' => 'switch_chat||' . $chat->id];
            }

            if (!empty($inlineKeyboards))
            {
                $inlineKeyboard = new InlineKeyboard([]);
                foreach ($inlineKeyboards as $key => $value) {
                    $inlineKeyboard->addRow(new InlineKeyboardButton($value));
                }

                $data = [
                    'chat_id' => $operator->tchat_id,
                    'text'    => "You have ". count($inlineKeyboards) .' active chats. Click to activate.',
                    'reply_markup' => $inlineKeyboard
                ];

                return Request::sendMessage($data);

            } else {
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => "You do not have any active chats!",
                ];
                return Request::sendMessage($data);
            }

        } else {
            $data = [
                'chat_id' => $chat_id,
                'text'    => "Associated opeartor could not be found",
            ];
            return Request::sendMessage($data);
        }
    }
}
