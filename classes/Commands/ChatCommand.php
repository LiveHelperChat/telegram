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
use Longman\TelegramBot\Entities\ServerResponse;


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
    public function execute(): ServerResponse
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

        $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tuser_id' => $message->getFrom()->getId(), 'confirmed' => 1, 'bot_id' => $tBot->id)));

        if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

            foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['bot_id' => $tBot->id, 'tchat_id' => $message->getMessageThreadId(), 'type' => 1]]) as $tchat) {

               $chat = $tchat->chat;

                if (!($chat instanceof \erLhcoreClassModelChat)) {
                    $data = [
                        'chat_id' => $chat_id,
                        'text'    => "Chat could not be found!",
                    ];
                    Request::sendMessage($data);
                    continue;
                }

                $commands = [];
                if ($chat->user_id == 0){
                    $commands[] = ['text' => 'Accept Chat', 'callback_data' => 'accept_chat||' . $chat->id];
                } else {
                    $commands[] = ['text' => 'Change owner to me', 'callback_data' => 'take_over_chat||' . $chat->id];
                }

                $inline_keyboard = new InlineKeyboard($commands);

                $data = [
                    'chat_id' => $chat_id,
                    'message_thread_id' => $tchat->tchat_id,
                    'text'    => "Operator: ". (string)$chat->n_off_full . " [{$chat->user_id}] " . $chat->id,
                    'reply_markup' => $inline_keyboard,
                ];

                Request::sendMessage($data);
            }

            return Request::emptyResponse();

        } else {
            $data = [
            'chat_id' => $chat_id,
            'text'    => "Associated operator could not be found",
            ];
            return Request::sendMessage($data);
        }

    }
}
