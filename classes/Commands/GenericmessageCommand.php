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
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;

/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $need_mysql = false;

    /**
     * Command execute method if MySQL is required but not available
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function executeNoDb()
    {
        // Do nothing
        return Request::emptyResponse();
    }

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
        $text    = trim($message->getText(true));
        
        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();
        
        $tChat = \erLhcoreClassModelTelegramChat::findOne(array(
            'filtergt' => array(
                'utime' => (time() - $tBot->chat_timeout)
            ),
            'filter' => array(
                'tchat_id' => $chat_id
            )
        ));
        
        if ($tChat !== false && ($chat = $tChat->chat) !== false ) {
            
            $msg = new \erLhcoreClassModelmsg();
            $msg->msg = $text;
            $msg->chat_id = $chat->id;
            $msg->user_id = 0;
            $msg->time = time();
            
            \erLhcoreClassChat::getSession()->save($msg);
            
            // Update related chat attributes
            $db = \ezcDbInstance::get();
            $db->beginTransaction();
            
            $stmt = $db->prepare('UPDATE lh_chat SET last_user_msg_time = :last_user_msg_time, last_msg_id = :last_msg_id, has_unread_messages = 1 WHERE id = :id');
            $stmt->bindValue(':id', $chat->id, \PDO::PARAM_INT);
            $stmt->bindValue(':last_user_msg_time', $msg->time, \PDO::PARAM_INT);
            
            // Set last message ID
            if ($chat->last_msg_id < $msg->id) {
                $stmt->bindValue(':last_msg_id', $msg->id, \PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':last_msg_id', $chat->last_msg_id, \PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            $tChat->utime = time();
            $tChat->saveThis();
            
            $db->commit();
            
            // Standard event on unread chat messages
            if ($chat->has_unread_messages == 1 && $chat->last_user_msg_time < (time() - 5)) {
                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.unread_chat', array(
                    'chat' => & $chat
                ));
            }
            
            // We dispatch same event as we were using desktop client, because it force admins and users to resync chat for new messages
            // This allows NodeJS users to know about new message. In this particular case it's admin users
            // If operator has opened chat instantly sync
            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.messages_added_passive', array(
                'chat' => & $chat
            ));
            
            // If operator has closed a chat we need force back office sync
            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.nodjshelper_notify_delay', array(
                'chat' => & $chat
            ));
            
            // General module signal that it has received an sms
            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_received',array('chat' => & $chat));
            
        } else {
            $chat = new \erLhcoreClassModelChat();
            
            $depId = $tBot->dep_id;
            $department = \erLhcoreClassModelDepartament::fetch($depId);
            
            if ($department instanceof \erLhcoreClassModelDepartament) {
                $chat->dep_id = $department->id;
                $chat->priority = $department->priority;
            } else {
                throw new Exception('Could not find department by bot - ' . $tBot->id);
            }
            
            $from = $message->getProperty('from');

            $chat->nick = trim($from['first_name'] . ' ' . $from['last_name']);
            $chat->time = time();
            $chat->status = 0;
            $chat->hash = \erLhcoreClassChat::generateHash();
            $chat->referrer = '';
            $chat->session_referrer = '';
            $chat->saveThis();
            
            /**
             * Store new message
             */
            $msg = new \erLhcoreClassModelmsg();
            $msg->msg = $text;
            $msg->chat_id = $chat->id;
            $msg->user_id = 0;
            $msg->time = time();
            
            \erLhcoreClassChat::getSession()->save($msg);
            
            /**
             * Set appropriate chat attributes
             */
            $chat->last_msg_id = $msg->id;
            $chat->last_user_msg_time = $msg->time;
            
            /**
             * Save telegram chat
             */
            $tChat = new \erLhcoreClassModelTelegramChat();
            $tChat->bot_id = $tBot->id;
            $tChat->tchat_id = $chat_id;
            $tChat->chat_id = $chat->id;
            $tChat->utime = time();
            $tChat->ctime = time();
            $tChat->saveThis();
            
            $chat->chat_variables = json_encode(array(
                'tchat' => true,
                'tchat_id' => $tChat->id
            ));
            
            $chat->saveThis();

            /**
             * Execute standard callback as chat was started
             */
            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.chat_started', array(
                'chat' => & $chat
            ));
            
            // General module signal that it has received an sms
            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_received',array('chat' => & $chat));
        }
        
        $data = [
            'chat_id' => $chat_id,
            'text'    => 'it works i have received message' . $text,
        ];

        return Request::sendMessage($data);

        //If a conversation is busy, execute the conversation command after handling the message
        /*$conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) {
            return $this->telegram->executeCommand($command);
        }

        return Request::emptyResponse();*/
    }
}
