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
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * User "/register" command
 *
 * Registers sender as operator in Live Helper Chat back office
 */
class EndchatCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'endchat';

    /**
     * @var string
     */
    protected $description = 'End and close active chat';

    /**
     * @var string
     */
    protected $usage = 'Type /endchat';

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

        $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tuser_id' => $message->getFrom()->getId(), 'confirmed' => 1, 'bot_id' => $tBot->id)));

        if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

            foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['bot_id' => $tBot->id, 'tchat_id' => $message->getMessageThreadId(), 'type' => 1]]) as $tchat) {
                $chat = $tchat->chat;

                if ($chat instanceof \erLhcoreClassModelChat) {

                    \erLhcoreClassChatHelper::closeChat(array(
                        'user' => $operator->user,
                        'chat' => $chat,
                    ));

                    $operator->chat_id = 0;
                    $operator->saveThis();

                    $data = [
                        'chat_id' => $chat_id,
                        'message_thread_id' => $tchat->tchat_id,
                        'text'    => 'Chat was closed! To list your chats type /chats',
                    ];

                    Request::sendMessage($data);

                } else {
                    $data = [
                        'chat_id' => $chat_id,
                        'message_thread_id' => $tchat->tchat_id,
                        'text'    => 'Active chat could not be found!',
                    ];

                    Request::sendMessage($data);
                }
            }

            return Request::emptyResponse();

        } else {
            $data = [
                'chat_id' => $chat_id,
                'text'    => 'Operator could not be found!',
            ];

            return Request::sendMessage($data);
        }
    }
}
