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

/**
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '1.1.1';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $callback_query    = $this->getCallbackQuery();
        $callback_query_id = $callback_query->getId();
        $callback_data     = $callback_query->getData();

        $paramsCallback = explode('||',$callback_data);

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();

        if ($paramsCallback[0] == 'go_offline' || $paramsCallback[0] == 'go_online') {

            $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('bot_id' => $tBot->id, 'confirmed' => 1, 'tuser_id' => $callback_query->getFrom()->getId())));

            if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

                $userData = $operator->user;

                if ($paramsCallback[0] == 'go_offline') {
                    $userData->hide_online = 1;
                } else {
                    $userData->hide_online = 0;
                }

                \erLhcoreClassUser::getSession()->update($userData);

                \erLhcoreClassUserDep::setHideOnlineStatus($userData);

                \erLhcoreClassChat::updateActiveChats($userData->id);

                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.operator_status_changed',array('user' => & $userData, 'reason' => 'user_action'));

                $data = [
                    'callback_query_id' => $callback_query_id,
                    'text'              => 'Your online status was changed to '. ($paramsCallback[0] == 'go_offline' ? 'Offline' : 'Online') .'!',
                    'show_alert'        => false,
                    'cache_time'        => 5,
                ];

                Request::answerCallbackQuery($data);

            } else {
                $data = [
                    'chat_id' => $callback_query->getFrom()->getId(),
                    'text'    => 'Operator could not be found!',
                ];

                return Request::sendMessage($data);
            }


        } else if ($paramsCallback[0] == 'switch_chat') {

            $chat = \erLhcoreClassModelChat::fetch($paramsCallback[1]);

            $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('bot_id' => $tBot->id, 'confirmed' => 1, 'tuser_id' => $callback_query->getFrom()->getId())));

            if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

                if ($operator->chat_id != $chat->id) {
                    $operator->chat_id = $chat->id;
                    $operator->saveThis();

                    $variablesArray = $chat->chat_variables_array;

                    if (!isset($variablesArray['telegram_chat_op']) || $operator->id != $variablesArray['telegram_chat_op']) {
                        $variablesArray['telegram_chat_op'] = $operator->id;
                        $chat->chat_variables = json_encode($variablesArray);
                        $chat->chat_variables_array = $variablesArray;
                        $chat->saveThis();
                    }

                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text'              => 'Chat was activated!',
                        'show_alert'        => false,
                        'cache_time'        => 5,
                    ];

                    Request::answerCallbackQuery($data);

                    $data = [
                        'chat_id' => $callback_query->getFrom()->getId(),
                        'text'    => "Active chat switched to [{$chat->id}] \"{$chat->nick}\"! You now can just type and message will be send to \"{$chat->nick}\" To get your active chats type /chats",
                    ];

                    return Request::sendMessage($data);

                } else {

                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text'              => 'This chat is already assigned to you!',
                        'show_alert'        => false,
                        'cache_time'        => 5,
                    ];

                    return Request::answerCallbackQuery($data);
                }

            } else {
                $data = [
                    'chat_id' => $callback_query->getFrom()->getId(),
                    'text'    => 'Operator could not be found!',
                ];

                return Request::sendMessage($data);
            }


        } else if ($paramsCallback[0] == 'accept_chat') {

            $chat = \erLhcoreClassModelChat::fetch($paramsCallback[1]);

            if ($chat->status == \erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
                $chat->status = \erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;

                if ($chat->wait_time == 0) {
                    $chat->wait_time = time() - $chat->time;
                }

                $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('bot_id' => $tBot->id, 'confirmed' => 1, 'tuser_id' => $callback_query->getFrom()->getId())));

                if ($operator instanceof \erLhcoreClassModelTelegramOperator)
                {
                    $chat->user_id = $operator->user_id;

                    // User status in event of chat acceptance
                    $chat->usaccept = $operator->user->hide_online;

                    $msg = new \erLhcoreClassModelmsg();
                    $msg->msg = (string)$operator->user->name_support.' '.\erTranslationClassLhTranslation::getInstance()->getTranslation('chat/adminchat','has accepted the chat!');
                    $msg->chat_id = $chat->id;
                    $msg->user_id = -1;
                    $msg->time = time();

                    \erLhcoreClassChat::getSession()->save($msg);

                    $chat->last_msg_id = $msg->id;
                    $chat->support_informed = 1;
                    $chat->has_unread_messages = 0;
                    $chat->unread_messages_informed = 0;

                    if ($chat->unanswered_chat == 1 && $chat->user_status == \erLhcoreClassModelChat::USER_STATUS_JOINED_CHAT)
                    {
                        $chat->unanswered_chat = 0;
                    }

                    $variablesArray = $chat->chat_variables_array;
                    $variablesArray['telegram_chat_op'] = $operator->id;
                    $chat->chat_variables = json_encode($variablesArray);
                    $chat->chat_variables_array = $variablesArray;

                    $chat->saveThis();

                    $operator->chat_id = $chat->id;
                    $operator->saveThis();

                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text'              => 'Chat was accepted!',
                        'show_alert'        => false,
                        'cache_time'        => 5,
                    ];

                    Request::answerCallbackQuery($data);

                    $messages = array_reverse(\erLhcoreClassModelmsg::getList(array('limit' => 10,'sort' => 'id DESC','filter' => array('chat_id' => $chat->id))));
                    $messagesContent = '';

                    foreach ($messages as $msg ) {
                        if ($msg->user_id == -1) {
                            $messagesContent .= date(\erLhcoreClassModule::$dateHourFormat,$msg->time).' '. \erTranslationClassLhTranslation::getInstance()->getTranslation('chat/syncadmin','System assistant').': '.htmlspecialchars($msg->msg)."\n";
                        } else {
                            $messagesContent .= date(\erLhcoreClassModule::$dateHourFormat,$msg->time).' '. ($msg->user_id == 0 ? htmlspecialchars($chat->nick) : htmlspecialchars($msg->name_support)).': '.htmlspecialchars($msg->msg)."\n";
                        }
                    }

                    $data = [
                        'chat_id' => $callback_query->getFrom()->getId(),
                        'text'    => 'Chat was accepted! Chat content:' . PHP_EOL . $messagesContent,
                    ];

                    Request::sendMessage($data);

                } else {
                    $data = [
                        'callback_query_id' => $callback_query_id,
                        'text'              => 'Operator could not be found!',
                        'show_alert'        => false,
                        'cache_time'        => 5,
                    ];

                    Request::answerCallbackQuery($data);

                    $data = [
                        'chat_id' => $callback_query->getFrom()->getId(),
                        'text'    => 'Operator could not be found. Please check back office!',
                    ];

                    Request::sendMessage($data);
                }

            } else {

                $data = [
                    'callback_query_id' => $callback_query_id,
                    'text'              => 'Chat was already accepted.',
                    'show_alert'        => false,
                    'cache_time'        => 5,
                ];

                Request::answerCallbackQuery($data);

                $data = [
                    'chat_id' => $callback_query->getFrom()->getId(),
                    'text'    => 'Chat was already accepted. Please ignore it.',
                ];

                Request::sendMessage($data);
            }

        } else {
            $data = [
                'callback_query_id' => $callback_query_id,
                'text'              => 'Chat with - ' . $callback_data,
                'show_alert'        => $callback_data === 'thumb up',
                'cache_time'        => 5,
            ];

            Request::answerCallbackQuery($data);

            $data = [
                'chat_id' => $callback_query->getFrom()->getId(),
                'text'    => 'Chat accepted',
            ];

            return Request::sendMessage($data);
        }

    }
}
