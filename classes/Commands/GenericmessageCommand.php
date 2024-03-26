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
use GuzzleHttp\Client;
use Longman\TelegramBot\Entities\ServerResponse;

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
    public function executeNoDb(): ServerResponse
    {
        // Do nothing
        return Request::emptyResponse();
    }

    private function processObject($fileId, $chat, $tBot, $params = array())
    {
        //Download the photo after send message response to speedup response
        $response2 = Request::getFile(['file_id' => $fileId]);

        if ($response2->isOk()) {
            $photo_file = $response2->getResult();

            try {

                $path = 'var/storage/' . date('Y') . 'y/' . date('m') . '/' . date('d') . '/' . $chat->id . '/';
                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('file.uploadfile.file_path', array('path' => & $path, 'storage_id' => $chat->id));
                \erLhcoreClassFileUpload::mkdirRecursive($path);

                $filePath = $photo_file->getFilePath();

                if (!isset($params['ext'])) {
                    $parts = explode('.', $filePath);
                    $ext = array_pop($parts);
                } else {
                    $ext = $params['ext'];
                }

                $mimeTypes = array(
                    'mp3' => 'audio/mpeg',
                    'ogg' => 'audio/ogg',
                    'wav' => 'audio/wav',
                    'mp4' => 'video/mp4',
                    'webm'=> 'audio/webm',
                    'gif' => 'image/gif',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'pdf' => 'application/pdf',
                    'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'dotx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx'=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                );

                $partsNames = explode('/',$filePath);
                $uploadName = array_pop($partsNames);

                $fileUpload = new \erLhcoreClassModelChatFile();
                $fileUpload->size = $photo_file->getFileSize();
                $fileUpload->type = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
                $fileUpload->name = md5($filePath . time() . rand(0,100));
                $fileUpload->date = time();
                $fileUpload->user_id = 0;
                $fileUpload->upload_name = $uploadName;
                $fileUpload->file_path = $path;
                $fileUpload->extension = $ext;
                $fileUpload->chat_id = $chat->id;
                $fileUpload->saveThis();

                if (isset($params['convert'])) {

                    $client = new Client(['base_uri' => 'https://api.telegram.org']);
                    $client->get(
                        '/file/bot' . $tBot->bot_api . '/' . $photo_file->getFilePath(),
                        ['sink' => $path .  'convert_' . $fileUpload->name . '.ogg' ]
                    );

                    $cmd = str_replace(array('{file_orig}','{file_dest}'),array(escapeshellarg($path .  'convert_' . $fileUpload->name . '.ogg'), escapeshellarg($path .  $fileUpload->name . '.' . $ext)), $params['cmd']);
                    @exec($cmd, $output, $error);
                    rename($path .  $fileUpload->name . '.' . $ext, $path .  $fileUpload->name);
                    unlink($path .  'convert_' . $fileUpload->name . '.ogg');

                } else {
                    $client = new Client(['base_uri' => 'https://api.telegram.org']);
                    $client->get(
                        '/file/bot' . $tBot->bot_api . '/' . $photo_file->getFilePath(),
                        ['sink' => $path .  $fileUpload->name ]
                    );
                }

                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('file.uploadfile.file_store', array('chat_file' => $fileUpload));

                return '[file='.$fileUpload->id.'_'.$fileUpload->security_hash.']';

            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
    }

    /*
     * Process photo
     */
    private function processPhoto($chat, $message, $tBot) {
        $photos = $message->getPhoto();
        $photo = array_pop($photos);
        return $this->processObject($photo->getFileId(), $chat, $tBot);
    }

    /**
     * @param $fileId
     * @param $chat
     * @param $tBot
     * @return string
     */
    private function processVoice($fileId, $chat, $tBot) {

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $settings = $telegramExt->settings;

        if (isset($settings['convert_ogg']) && $settings['convert_ogg'] == true) {
            return $this->processObject($fileId, $chat, $tBot, array('cmd' => $settings['convert_command'], 'ext' => $settings['convert_to'], 'convert' => true));
        } else {
            return $this->processObject($fileId, $chat, $tBot, array('ext' => 'ogg'));
        }
    }

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
        $type = $message->getType();

        if ($type === 'message' || $type === 'text') {
            $text = trim($message->getText(true));
        } elseif ($type === 'photo' || $type === 'video' || $type === 'voice' || $type === 'sticker' || $type === 'document' || $type === 'audio') {
            $text = '';
        } elseif ($type === 'location') {
            $text = 'https://www.google.com/maps/@'.$message->getLocation()->getLatitude().','.$message->getLocation()->getLongitude().',15z';
        } else {
            $text = 'Unsupported type - '.$type;
        }

        $telegramExt = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram');
        $tBot = $telegramExt->getBot();

        if ($tBot->bot_client == 1) {

            $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tuser_id' => $message->getFrom()->getId(), 'confirmed' => 1, 'bot_id' => $tBot->id)));

            if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

                foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['bot_id' => $tBot->id, 'tchat_id' => $message->getMessageThreadId(), 'type' => 1]]) as $tchat) {

                    $chat = $tchat->chat;

                    if ($chat instanceof \erLhcoreClassModelChat) {

                        if ($type === 'photo') {
                            $text = $this->processPhoto($chat, $message, $tBot);
                        } elseif ($type === 'document') {
                            $text = $this->processObject($message->getDocument()->getFileId(), $chat, $tBot);
                        } elseif ($type === 'video') {
                            $text = $this->processObject($message->getVideo()->getFileId(), $chat, $tBot, array('ext' => 'mp4'));
                        } elseif ($message->getVideoNote()) {
                            $text = $this->processObject($message->getVideoNote()->getFileId(), $chat, $tBot, array('ext' => 'mp4'));
                        } elseif ($type === 'voice') {
                            $text = $this->processVoice($message->getVoice()->getFileId(), $chat, $tBot);
                        } elseif ($type === 'sticker') {
                            $text = $this->processObject($message->getSticker()->getFileId(), $chat, $tBot, array('ext' => 'webp'));
                        } elseif ($type === 'audio') {
                            $text = $this->processObject($message->getAudio()->getFileId(), $chat, $tBot);
                        }

                        $ignoreMessage = false;
                        $messageUserId = $operator->user_id;
                        $messagesProcessed = [];
                        $alwaysProcess = false;

                        if (strpos(trim($text), '!') === 0) {

                            $lastMessageId = $chat->last_msg_id;

                            $statusCommand = \erLhcoreClassChatCommand::processCommand(array(
                                'no_ui_update' => true,
                                'msg' => $text,
                                'chat' => & $chat,
                                'user' => $operator->user
                            ));

                            if ($statusCommand['processed'] === true) {
                                $messageUserId = -1; // Message was processed set as internal message

                                // Find a new possible bot messages and trigger message added events for third party integrations
                                $botMessages = \erLhcoreClassModelmsg::getList(array('filterin' => ['user_id' => [($chat->user_id > 0 ? $chat->user_id : -2), -2]],'filter' => array( 'chat_id' => $chat->id), 'filtergt' => array('id' => $lastMessageId)));

                                foreach ($botMessages as $botMessage) {
                                    $messagesProcessed[] = $botMessage->id;
                                    $chat->last_message = $botMessage;
                                    \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.web_add_msg_admin', array(
                                        'chat' => & $chat,
                                        'msg' => $botMessage,
                                        'no_afterwards_messages' => true,
                                        'always_process' => true,
                                        'no_auto_events' => true    // Some triggers updates last message and webhooks them self sends this event, we want to avoid that
                                    ));
                                }

                                $rawMessage = !isset($statusCommand['raw_message']) ? $text : $statusCommand['raw_message'];

                                $text = '[' . $operator->user->name_support . ']: ' . $rawMessage . ' ' . ($statusCommand['process_status'] != '' ? '|| ' . $statusCommand['process_status'] : '');

                                $alwaysProcess = true;
                            }

                            if (isset($statusCommand['ignore']) && $statusCommand['ignore'] == true) {
                                $ignoreMessage = true;
                                if (isset($statusCommand['last_message'])) {
                                    $msg = $statusCommand['last_message'];
                                    $chat->last_message = $msg;
                                    if (is_object($msg)) {
                                        $chat->last_msg_id = $msg->id;
                                        $chat->updateThis(['update' => ['last_msg_id']]);
                                    }
                                }
                            }

                            if (isset($statusCommand['info'])) {
                                $data = [
                                    'chat_id' => $chat_id,
                                    'message_thread_id' => $message->getMessageThreadId(),
                                    'text'    => '[[System Assistant]] ' . $statusCommand['info'],
                                ];

                                return Request::sendMessage($data);
                            }
                        }

                        if ($ignoreMessage == false) {
                            $msg = new \erLhcoreClassModelmsg();
                            $msg->msg = $text;
                            $msg->chat_id = $chat->id;
                            $msg->user_id = $messageUserId;
                            $msg->time = time();
                            $msg->name_support = $operator->user->name_support;

                            \erLhcoreClassChat::getSession()->save($msg);

                            $db = \ezcDbInstance::get();
                            $stmt = $db->prepare('UPDATE lh_chat SET status = :status, user_status = :user_status, last_msg_id = :last_msg_id, last_op_msg_time = :last_op_msg_time, has_unread_op_messages = :has_unread_op_messages, unread_op_messages_informed = :unread_op_messages_informed WHERE id = :id');
                            $stmt->bindValue(':id',$chat->id,\PDO::PARAM_INT);
                            $stmt->bindValue(':last_msg_id',$msg->id,\PDO::PARAM_INT);
                            $stmt->bindValue(':last_op_msg_time',time(),\PDO::PARAM_INT);
                            $stmt->bindValue(':has_unread_op_messages',1,\PDO::PARAM_INT);
                            $stmt->bindValue(':unread_op_messages_informed',0,\PDO::PARAM_INT);

                            $chat->last_msg_id = $msg->id;
                            $chat->last_op_msg_time = time();
                            $chat->has_unread_op_messages = 1;
                            $chat->unread_op_messages_informed = 0;

                            $ownerChanged = false;
                            if ($operator->user->invisible_mode == 0) { // Change status only if it's not internal command
                                if ($chat->status == \erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
                                    $chat->status = \erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;
                                    $chat->wait_time = time() - ($chat->pnd_time > 0 ? $chat->pnd_time : $chat->time);
                                    $chat->user_id = $operator->user_id;
                                    $ownerChanged = true;
                                }
                            }

                            $stmt->bindValue(':user_status',$chat->user_status,\PDO::PARAM_INT);
                            $stmt->bindValue(':status',$chat->status,\PDO::PARAM_INT);
                            $stmt->execute();

                            if ($chat->status == \erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
                                $data = [
                                    'chat_id' => $chat_id,
                                    'message_thread_id' => $message->getMessageThreadId(),
                                    'text'    => "You sent a message to a closed chat!",
                                ];
                                Request::sendMessage($data);
                            }

                            if ($ownerChanged === true || $chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT || $chat->status == \erLhcoreClassModelChat::STATUS_PENDING_CHAT) {

                                $userData = $operator->user;

                                if ($userData->invisible_mode == 0) {

                                    $db = \ezcDbInstance::get();
                                    $db->beginTransaction();
                                    
                                    $chat->syncAndLock('status');

                                    $chat->status = \erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;

                                    $chat->pnd_time = time() - 2;
                                    $chat->wait_time = 1;

                                    $chat->user_id = $operator->user_id;

                                    // User status in event of chat acceptance
                                    $chat->usaccept = $userData->hide_online;
                                    $chat->operation_admin = "lhinst.updateVoteStatus(".$chat->id.");";
                                    $chat->saveThis();

                                    $db->commit();
                                    

                                    // If chat is transferred to pending state we don't want to process any old events
                                    $eventPending = \erLhcoreClassModelGenericBotChatEvent::findOne(array('filter' => array('chat_id' => $chat->id)));

                                    if ($eventPending instanceof \erLhcoreClassModelGenericBotChatEvent) {
                                        $eventPending->removeThis();
                                    }

                                    \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.data_changed',array('chat' => & $chat, 'user_data' => $userData ));

                                    \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.accept',array('chat' => & $chat, 'user_data' => $userData ));

                                    \erLhcoreClassChat::updateActiveChats($chat->user_id);

                                    if ($chat->department !== false) {
                                        \erLhcoreClassChat::updateDepartmentStats($chat->department);
                                    }

                                    $options = $chat->department->inform_options_array;

                                    \erLhcoreClassChatWorkflow::chatAcceptedWorkflow(array('department' => $chat->department,'options' => $options),$chat);




                                    $data = [
                                        'chat_id' => $chat_id,
                                        'message_thread_id' => $message->getMessageThreadId(),
                                        'parse_mode' => 'HTML',
                                        'text'    => "<b>[System assistant]</b> <i>" . htmlspecialchars($userData->name_official) . "</i> " . \erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram','was assigned as a chat operator! Type /chat for more information.'),
                                    ];
                                    Request::sendMessage($data);
                                }
                            }

                            // We dispatch same event as we were using desktop client, because it force admins and users to resync chat for new messages
                            // This allows NodeJS users to know about new message. In this particular case it's admin users
                            // If operator has opened chat instantly sync
                            if (isset($msg) && !in_array($msg->id, $messagesProcessed)) {
                                $chat->last_message = $msg;
                                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.web_add_msg_admin', array(
                                    'chat' => & $chat,
                                    'msg' => & $msg,
                                    'always_process' => $alwaysProcess
                                ));
                            }

                            // If operator has closed a chat we need force back office sync
                            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.nodjshelper_notify_delay', array(
                                'chat' => & $chat
                            ));

                        } else {

                            if (isset($msg)) {
                                $chat->last_message = $msg;
                            }

                            if (isset($msg) && !in_array($msg->id, $messagesProcessed)) {
                                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.web_add_msg_admin', array('msg' => & $msg,'chat' => & $chat));
                            }

                        }

                        return Request::emptyResponse();

                    } else {
                        $data = [
                            'chat_id' => $chat_id,
                            'message_thread_id' => $message->getMessageThreadId(),
                            'text'    => \erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram','Chat could not be found!'),
                        ];

                        return Request::sendMessage($data);
                    }
                }

            } elseif ($type != 'forum_topic_created') {
                $data = [
                    'chat_id' => $chat_id,
                    'message_thread_id' => $message->getMessageThreadId(),
                    'text'    => "Operator could not be found! Have you registered yourself within Live Helper Chat",
                ];
                return Request::sendMessage($data);
            }
        }

        return Request::emptyResponse();
    }
}
