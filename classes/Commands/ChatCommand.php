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
class ChatCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'chat';

    /**
     * @var string
     */
    protected $description = 'Get information about currently assigned chat or any chat by id.';

    /**
     * @var string
     */
    protected $usage = 'Type /chat <chat_number> or /chat';

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

        $text = (int)trim($message->getText(true));

        $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tchat_id' => $chat_id, 'confirmed' => 1, 'bot_id' => $tBot->id)));

        if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

            if ($text > 0) {
                $chat = \erLhcoreClassModelChat::fetch($text);
            } else if ($operator->chat_id > 0) {
                $chat = \erLhcoreClassModelChat::fetch($operator->chat_id);
            }

            if (!($chat instanceof \erLhcoreClassModelChat)) {
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => "Chat could not be found!",
                ];
                return Request::sendMessage($data);
            }

            $messagesContent = '';

            if ($text > 0){
                $messages = array_reverse(\erLhcoreClassModelmsg::getList(array('limit' => 10,'sort' => 'id DESC','filter' => array('chat_id' => $chat->id))));
                foreach ($messages as $msg ) {
                    if ($msg->user_id == -1) {
                        $messagesContent .= date(\erLhcoreClassModule::$dateHourFormat,$msg->time).' '. \erTranslationClassLhTranslation::getInstance()->getTranslation('chat/syncadmin','System assistant').': '.htmlspecialchars($msg->msg)."\n";
                    } else {
                        $messagesContent .= date(\erLhcoreClassModule::$dateHourFormat,$msg->time).' '. ($msg->user_id == 0 ? htmlspecialchars($chat->nick) : htmlspecialchars($msg->name_support)).': '.htmlspecialchars($msg->msg)."\n";
                    }
                }
                $messagesContent = PHP_EOL . "Chat messages. " . PHP_EOL . $messagesContent;
            }


            $inline_keyboard = new InlineKeyboard([
                ['text' => 'Change owner', 'callback_data' => 'replycommand||changeowner||' . $chat->id]
            ]);

            $data = [
                'chat_id' => $chat_id,
                'text'    => "Operator: ". (string)$chat->n_off_full . " \[{$chat->user_id}] *" . $chat->id . '*' .$messagesContent,
                'parse_mode' => 'MARKDOWN',
                'reply_markup' => $inline_keyboard,
            ];

            return Request::sendMessage($data);

        } else {
            $data = [
            'chat_id' => $chat_id,
            'text'    => "Associated operator could not be found",
            ];
            return Request::sendMessage($data);
        }

    }
}
