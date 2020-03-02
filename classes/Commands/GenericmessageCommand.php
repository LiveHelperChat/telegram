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
    public function execute()
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

            $operator = \erLhcoreClassModelTelegramOperator::findOne(array('filter' => array('tchat_id' => $chat_id, 'confirmed' => 1, 'bot_id' => $tBot->id)));

            if ($operator instanceof \erLhcoreClassModelTelegramOperator) {

                if ($operator->chat_id > 0) {

                    $chat = $operator->chat;

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

                        if (strpos(trim($text), '!') === 0) {

                            $statusCommand = \erLhcoreClassChatCommand::processCommand(array(
                                'no_ui_update' => true,
                                'msg' => $text,
                                'chat' => & $chat,
                                'user' => $operator->user
                            ));

                            if ($statusCommand['processed'] === true) {

                                $rawMessage = !isset($statusCommand['raw_message']) ? $text : $statusCommand['raw_message'];

                                $text = '[b]' . $operator->user->name_support . '[/b]: ' . $rawMessage . ' ' . ($statusCommand['process_status'] != '' ? '|| ' . $statusCommand['process_status'] : '');
                            }

                            if (isset($statusCommand['ignore']) && $statusCommand['ignore'] == true) {
                                $ignoreMessage = true;
                            }

                            if (isset($statusCommand['info'])) {
                                $data = [
                                    'chat_id' => $chat_id,
                                    'text'    => '[[System Assistant]] ' . $statusCommand['info'],
                                ];
                                return Request::sendMessage($data);
                            }
                        }

                        if ($ignoreMessage == false) {
                            $msg = new \erLhcoreClassModelmsg();
                            $msg->msg = $text;
                            $msg->chat_id = $chat->id;
                            $msg->user_id = $operator->user_id;
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

                            if ($operator->user->invisible_mode == 0) { // Change status only if it's not internal command
                                if ($chat->status == \erLhcoreClassModelChat::STATUS_PENDING_CHAT) {
                                    $chat->status = \erLhcoreClassModelChat::STATUS_ACTIVE_CHAT;
                                    $chat->wait_time = time() - ($chat->pnd_time > 0 ? $chat->pnd_time : $chat->time);
                                    $chat->user_id = $operator->user_id;
                                }
                            }

                            $stmt->bindValue(':user_status',$chat->user_status,\PDO::PARAM_INT);
                            $stmt->bindValue(':status',$chat->status,\PDO::PARAM_INT);
                            $stmt->execute();

                            // We dispatch same event as we were using desktop client, because it force admins and users to resync chat for new messages
                            // This allows NodeJS users to know about new message. In this particular case it's admin users
                            // If operator has opened chat instantly sync
                            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.messages_added_passive', array(
                                'chat' => & $chat,
                                'msg' => & $msg
                            ));

                            // If operator has closed a chat we need force back office sync
                            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.nodjshelper_notify_delay', array(
                                'chat' => & $chat
                            ));

                            // General module signal that it has received an sms
                            \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_received', array(
                                'chat' => & $chat,
                                'msg' => & $msg,
                                'sender' => 'bot_client'
                            ));

                            if ($chat->status == \erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
                                $data = [
                                    'chat_id' => $chat_id,
                                    'text'    => "You send a message to a closed chat!",
                                ];

                                return Request::sendMessage($data);
                            }
                        }

                    } else {
                        $data = [
                            'chat_id' => $chat_id,
                            'text'    => "Chat could not be found!",
                        ];

                        return Request::sendMessage($data);
                    }

                } else {
                    $data = [
                        'chat_id' => $chat_id,
                        'text'    => "You do not have any chat associated with your account!",
                    ];
                    return Request::sendMessage($data);
                }

            } else {
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => "Operator could not be found! Have you registered yourself within Live Helper Chat",
                ];
                return Request::sendMessage($data);
            }

        } else {
            $tChat = \erLhcoreClassModelTelegramChat::findOne(array(
                'filtergt' => array(
                    'utime' => (time() - $tBot->chat_timeout)
                ),
                'filter' => array(
                    'tchat_id' => $chat_id,
                    'bot_id' => $tBot->id
                )
            ));

            if (!($tChat instanceof \erLhcoreClassModelTelegramChat)) {
                $tChat = new \erLhcoreClassModelTelegramChat();
            }

            $chat = $tChat->chat;
            $renotify = false;

            // fix https://github.com/LiveHelperChat/fbmessenger/issues/1
            if ($chat instanceof \erLhcoreClassModelChat && $chat->status == \erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
                $tOptions = \erLhcoreClassModelChatConfig::fetch('telegram_options');
                $data = (array)$tOptions->data;
                if (!isset($data['new_chat']) || $data['new_chat'] == false)
                {
                    if (isset($data['priority']) && $data['priority'] != '' && $data['priority'] != 0) {
                        $chat->priority = isset($data['priority']) ? (int)$data['priority'] : 0;
                    }

                    $chat->status = \erLhcoreClassModelChat::STATUS_PENDING_CHAT;
                    $chat->status_sub_sub = 2; // Will be used to indicate that we have to show notification for this chat if it appears on list
                    $chat->user_id = 0; // fix https://github.com/LiveHelperChat/fbmessenger/issues/6
                    $chat->pnd_time = time();
                    $chat->saveThis();
                    $renotify = true;

                } else {
                    $chat = null;
                }
            }

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

                $msg = new \erLhcoreClassModelmsg();
                $msg->msg = $text;
                $msg->chat_id = $chat->id;
                $msg->user_id = 0;
                $msg->time = time();

                \erLhcoreClassChat::getSession()->save($msg);

                // Update related chat attributes
                $db = \ezcDbInstance::get();
                $db->beginTransaction();

                $chat_user_id = $chat->user_id;
                $chat_status_sub_sub = $chat->status_sub_sub;
                $chat_status = $chat->status;
                $has_unread_messages = 1;
                $pnd_time = $chat->pnd_time;

                if ($chat->status == \erLhcoreClassModelChat::STATUS_CLOSED_CHAT) {
                    $chat_status = \erLhcoreClassModelChat::STATUS_PENDING_CHAT;
                    $chat_status_sub_sub = 2; // Will be used to indicate that we have to show notification for this chat if it appears on list
                    $chat_user_id = 0;
                    $has_unread_messages = 0;
                    $pnd_time = time();
                }

                $stmt = $db->prepare('UPDATE lh_chat SET last_user_msg_time = :last_user_msg_time, last_msg_id = :last_msg_id, has_unread_messages = :has_unread_messages, user_id = :user_id, pnd_time = :pnd_time, status = :status, status_sub_sub = :status_sub_sub WHERE id = :id');
                $stmt->bindValue(':id', $chat->id, \PDO::PARAM_INT);
                $stmt->bindValue(':last_user_msg_time', $msg->time, \PDO::PARAM_INT);
                $stmt->bindValue(':user_id', $chat_user_id, \PDO::PARAM_INT);
                $stmt->bindValue(':status', $chat_status, \PDO::PARAM_INT);
                $stmt->bindValue(':status_sub_sub', $chat_status_sub_sub, \PDO::PARAM_INT);
                $stmt->bindValue(':has_unread_messages', $has_unread_messages, \PDO::PARAM_INT);
                $stmt->bindValue(':pnd_time', $pnd_time, \PDO::PARAM_INT);

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
                    'chat' => & $chat,
                    'msg' => $msg
                ));

                // If operator has closed a chat we need force back office sync
                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.nodjshelper_notify_delay', array(
                    'chat' => & $chat,
                    'msg' => $msg
                ));

                // General module signal that it has received an sms
                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_received',array(
                    'chat' => & $chat,
                    'msg' => $msg,
                    'sender' => 'bot_visitor'
                ));

                if ($renotify == true) {
                    // General module signal that it has received an sms
                    \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.restart_chat',array(
                        'chat' => & $chat,
                        'msg' => $msg,
                     ));
                }

            } else {
                $chat = new \erLhcoreClassModelChat();

                $tOptions = \erLhcoreClassModelChatConfig::fetch('telegram_options');
                $data = (array)$tOptions->data;

                $depId = $tBot->dep_id;
                $department = \erLhcoreClassModelDepartament::fetch($depId);

                if ($department instanceof \erLhcoreClassModelDepartament) {
                    $chat->dep_id = $department->id;
                } else {
                    throw new Exception('Could not find department by bot - ' . $tBot->id);
                }

                if (isset($data['priority']) && $data['priority'] != '' && $data['priority'] != 0) {
                    $chat->priority = isset($data['priority']) ? (int)$data['priority'] : $department->priority;
                } else {
                    $chat->priority = $department->priority;
                }

                $from = $message->getProperty('from');

                $lead = \erLhcoreClassModelTelegramLead::findOne(array('filter' => array('tchat_id' => $chat_id)));

                if (!($lead instanceof \erLhcoreClassModelTelegramLead)) {
                    $lead = new \erLhcoreClassModelTelegramLead();
                    $lead->language_code = isset($from['language_code']) ? $from['language_code'] : '';
                    $lead->first_name = isset($from['first_name']) ? $from['first_name'] : '';
                    $lead->last_name = isset($from['last_name']) ? $from['last_name'] : '';
                    $lead->utime = time();
                    $lead->ctime = time();
                    $lead->tchat_id = $chat_id;
                    $lead->tbot_id = $tBot->id;
                    $lead->dep_id = $chat->dep_id;
                    $lead->username = isset($from['username']) ? $from['username'] : '';
                    $lead->saveThis();
                }

                if (!isset($data['chat_attr']) || $data['chat_attr'] == 0) {
                    $chat->nick = trim($from['first_name'] . ' ' . $from['last_name']);
                } else {
                    $chat->nick = 'Visitor';

                    $additionalDataArray = array();

                    if ($lead->first_name != '') {
                        $additionalDataArray[] = array(
                            'key' => 'Name',
                            'identifier' => 'firstname',
                            'value' => $lead->first_name,
                        );
                    }

                    if ($lead->last_name != '') {
                        $additionalDataArray[] = array(
                            'key' => 'Last name',
                            'identifier' => 'lastname',
                            'value' => $lead->last_name,
                        );
                    }

                    if ($lead->username != '') {
                        $additionalDataArray[] = array(
                            'key' => 'Telegram username',
                            'identifier' => 'telegram_username',
                            'value' => $lead->username,
                        );
                    }

                    if (!empty($additionalDataArray)) {
                        $chat->additional_data_array = $additionalDataArray;
                        $chat->additional_data = json_encode($additionalDataArray);
                    }
                }

                $chat->pnd_time = $chat->time = time();
                $chat->status = 0;
                $chat->hash = \erLhcoreClassChat::generateHash();
                $chat->referrer = '';
                $chat->session_referrer = '';
                $chat->saveThis();

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
                $tChat->bot_id = $tBot->id;
                $tChat->tchat_id = $chat_id;
                $tChat->chat_id = $chat->id;
                $tChat->utime = time();
                $tChat->ctime = time();
                $tChat->saveThis();

                $chat->chat_variables = json_encode(array(
                    'tchat' => true,
                    'tchat_id' => $tChat->id,
                    'tchat_raw_id' => $chat_id,
                    'tbot_id' => $tChat->bot_id,
                ));

                $chat->saveThis();

                if ($tBot->bot_disabled == 0) {
                    \erLhcoreClassChatValidator::setBot($chat);
                }

                /**
                 * Execute standard callback as chat was started
                 */
                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.chat_started', array(
                    'chat' => & $chat,
                    'msg' => $msg,
                    'bot_disabled' => $tBot->bot_disabled
                ));

                // General module signal that it has received an sms
                \erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_received',array(
                    'chat' => & $chat,
                    'msg' => $msg,
                    'sender' => 'bot_visitor',
                    'bot_disabled' => $tBot->bot_disabled
                ));
            }
        }
        
        return Request::emptyResponse();
    }
}
