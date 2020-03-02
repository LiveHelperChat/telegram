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
class ChangeownerCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'changeowner';

    /**
     * @var string
     */
    protected $description = 'You can use this command to change owner of the chat';

    /**
     * @var string
     */
    protected $usage = 'Type /changeowner - shows logged operators which can be used to transfer current chat. /changeowner <chat_id> issue transfer initialization for specific chat_id /changeowner <chat_id> <operator_id> transfer chat instantly';

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

        if ($message !== null) {
            $params = explode(' ',trim($message->getText(true)));
            $chat_id = $message->getChat()->getId();
            $chatID = isset($params[0]) ? (int)$params[0] : 0;
            $chatUserID = isset($params[1]) ? (int)$params[1] : 0;
        } else {
            $callback_query = $this->getUpdate()->getCallbackQuery();
            $text = $callback_query->getData();
            $chat_id = $callback_query->getMessage()->getChat()->getId();
            $params = explode('||',$text);
            $chatID = isset($params[2]) ? (int)$params[2] : 0;
            $chatUserID = isset($params[3]) ? (int)$params[3] : 0;
        }

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

            if ($chatID === 0) {
                $chat = \erLhcoreClassModelChat::fetch($operator->chat_id);
            } else {
                $chat = \erLhcoreClassModelChat::fetch($chatID);
            }

            if (!is_object($chat)) {
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => "Associated operator could not be found! [{$chatID}]",
                ];
                return Request::sendMessage($data);
            }

            if ($chatUserID !== 0) {

                $operatorDestination = \erLhcoreClassModelUser::fetch($chatUserID);

                if (!is_object($operatorDestination)) {
                    $data = [
                        'chat_id' => $chat_id,
                        'text'    => "Selected destination operator could not be found! [{$chatUserID}]",
                    ];
                    return Request::sendMessage($data);
                }

                $chat->user_id = $operatorDestination->id;

                $msg = new \erLhcoreClassModelmsg();
                $msg->msg = (string)$operatorDestination->name_support.' '.\erTranslationClassLhTranslation::getInstance()->getTranslation('chat/adminchat',' was set as chat owner!');
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

                if (!is_array($variablesArray)) {
                    $variablesArray = array();
                }

                // Try to send notification to destination operator
                $operatorTelegramDestination = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('user_id' => $operatorDestination->id, 'confirmed' => 1, 'bot_id' => $tBot->id)));

                $telUser = 0;
                if ($operatorTelegramDestination instanceof \erLhcoreClassModelTelegramOperator) {
                    $telUser = $operatorTelegramDestination->id;
                }

                $variablesArray['telegram_chat_op'] = $telUser;
                $chat->chat_variables = json_encode($variablesArray);
                $chat->chat_variables_array = $variablesArray;
                $chat->status_sub = \erLhcoreClassModelChat::STATUS_SUB_OWNER_CHANGED;
                $chat->saveThis();

                $operator->chat_id = 0;
                $operator->saveThis();

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
                    'chat_id' => $chat_id,
                    'text'    => 'Chat was transferred to:' . PHP_EOL . (string)$operatorDestination->name_support,
                ];

                Request::sendMessage($data);

                if ($operatorTelegramDestination instanceof \erLhcoreClassModelTelegramOperator) {
                    $cfgSite = \erConfigClassLhConfig::getInstance();
                    $secretHash = $cfgSite->getSetting( 'site', 'secrethash' );

                    $receiver = $operatorDestination->email;
                    $verifyEmail = 	sha1(sha1($receiver.$secretHash).$secretHash);
                    $url = (\erLhcoreClassSystem::$httpsMode ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . \erLhcoreClassDesign::baseurl('chat/accept').'/'.\erLhcoreClassModelChatAccept::generateAcceptLink($chat).'/'.$verifyEmail.'/'.$receiver;

                   $inline_keyboard = new InlineKeyboard([
                        ['text' => 'Accept transferred', 'callback_data' => 'accept_chat||' . $chat->id],
                        ['text' => 'Open url', 'url' => $url],
                    ]);

                    $data = [
                        'chat_id' => $operatorTelegramDestination->tchat_id,
                        'parse_mode' => 'MARKDOWN',
                        'text'    => trim('Chat was transferred to you. Messages: '. PHP_EOL . $messagesContent),
                        'reply_markup' => $inline_keyboard,
                    ];

                    Request::sendMessage($data);
                }

                return ;
            }

            \erLhcoreClassUser::instance()->setLoggedUser($operator->user_id);

            $currentUser = \erLhcoreClassUser::instance();

            $canListOnlineUsersAll = false;

            if (\erLhcoreClassModelChatConfig::fetchCache('list_online_operators')->current_value == 1) {
                $canListOnlineUsers = $currentUser->hasAccessTo('lhuser','userlistonline');
                $canListOnlineUsersAll = $currentUser->hasAccessTo('lhuser','userlistonlineall');
            }

            $onlineOperators = \erLhcoreClassModelUserDep::getOnlineOperators($currentUser, $canListOnlineUsersAll, array('sort' => 'last_activity DESC'), 20, 7*24*3600);

            \erLhcoreClassChat::prefillGetAttributes($onlineOperators,array('lastactivity_ago','offline_since','user_id','id','name_official','pending_chats','inactive_chats','active_chats','departments_names','hide_online'),array(),array('filter_function' => true, 'remove_all' => true));

            $inlineKeyboards = array();

            foreach ($onlineOperators as $operator) {
                $inlineKeyboards[] = ['text' => $operator->name_official . " UID [{$operator->user_id}], AC [{$operator->active_chats}]", 'callback_data' => 'replycommand||changeowner||' . $chat->id . '||' . $operator->user_id];
            }

            $inlineKeyboard = new InlineKeyboard([]);
            foreach ($inlineKeyboards as $key => $value) {
                $inlineKeyboard->addRow(new InlineKeyboardButton($value));
            }

            $data = [
                'chat_id' => $chat_id,
                'text'    => "Choose to whom you want to transfer the chat",
                'reply_markup' => $inlineKeyboard
            ];

            return Request::sendMessage($data);
        } else {
            $data = [
                'chat_id' => $chat_id,
                'text'    => "Associated operator could not be foundddd",
            ];
            return Request::sendMessage($data);
        }

    }
}
