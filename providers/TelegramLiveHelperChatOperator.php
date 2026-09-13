<?php

namespace LiveHelperChatExtension\lhctelegram\providers;

#[\AllowDynamicProperties]
class TelegramLiveHelperChatOperator {

    public static function registerListeners($dispatcher)
    {
        $dispatcher->listen('chat.delete', self::class . '::deleteChat');
        $dispatcher->listen('chat.close', self::class . '::closeChat');
        $dispatcher->listen('chat.chat_started', self::class . '::chatStarted');
        $dispatcher->listen('chat.web_add_msg_admin', self::class . '::messageAddedAdmin');
        $dispatcher->listen('chat.before_auto_responder_msg_saved', self::class . '::messageAddedResponder');
        $dispatcher->listen('chat.addmsguser', self::class . '::messageAdded');
        $dispatcher->listen('chat.messages_added_passive', self::class . '::messageAdded');
        $dispatcher->listen('chat.genericbot_get_trigger_click_processed', self::class . '::triggerClicked');
        $dispatcher->listen('onlineuser.pageview_logged', self::class . '::pageViewLogged');
    }

    public static function messageAddedAdmin($params)
    {
        if (isset($params['lhc_caller']['class']) && $params['lhc_caller']['class'] == 'Longman\TelegramBot\Commands\SystemCommands\GenericmessageCommand' && (!isset($params['always_process']) || $params['always_process'] === false)) {
            return;
        }

        // We want to by pass resque worker messages from rest_api
        if (isset($params['source']) && $params['source'] == 'webhook' && (!isset($params['sub_source']) || $params['sub_source'] != 'rest_api_worker')) {
            return;
        }

        self::messageAdded($params);
    }

    public static function messageAddedResponder($params)
    {
        if (isset($params['source']) && $params['source'] == 'webhook') {
            return;
        }

        $params['no_afterwards_messages'] = true;

        self::messageAdded($params);
    }

    public static function pageViewLogged($params)
    {
        if (($params['ou']->id > 0 && $params['ou']->chat_id > 0) !== true) {
            return;
        }

        if (!isset($params['url_changed']) || $params['url_changed'] === false) {
            return;
        }

        foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['chat_id_internal' => ($params['ou']->id > 0 ? ($params['ou']->id * -1) : $params['ou']->chat_id), 'type' => 1]]) as $tchat) {
            if ($tchat->bot->bot_client == 0 || $tchat->bot->notify_page_change == 0) {
                continue;
            }

            $chat = $params['ou']->chat;
            if (!is_object($chat)) {
                continue;
            }

            $telegram = new \Longman\TelegramBot\Telegram($tchat->bot->bot_api, $tchat->bot->bot_username);

            $sendData = \Longman\TelegramBot\Request::send('editForumTopic', [
                'chat_id' => $tchat->bot->group_chat_id,
                'message_thread_id' => $tchat->tchat_id,
                'name' => mb_substr('[' . $chat->department . '] ' . $chat->nick . ' #' . $chat->id . ($params['ou']->ip != '' ? ' | ' . $params['ou']->ip : '') . ($params['ou']->user_country_code != '' ? ' | ' . strtoupper($params['ou']->user_country_code) : '') . ($params['ou']->current_page != '' ? ' | '. ltrim($params['ou']->current_page,'/') : '') . ($params['ou']->page_title != '' ? ' | '.$params['ou']->page_title : ''),0,128)
            ]);

            if (!$sendData->isOk()) {
                \erLhcoreClassLog::write('editForumTopic ['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                    \ezcLog::SUCCESS_AUDIT,
                    array(
                        'source' => 'lhc',
                        'category' => 'telegram_exception',
                        'line' => __LINE__,
                        'file' => __FILE__,
                        'object_id' => $params['ou']->id
                    )
                );
            }
        }
    }

    private static function stripTelegramFileEmbeds($text)
    {
        return trim(preg_replace('/\[file=\d+_[a-f0-9]{32}\]/i', '', (string)$text));
    }

    private static function getTelegramMessageFiles($msg)
    {
        $files = array();
        $seen = array();

        if (isset($msg->meta_msg_array['content']['attachements']) && is_array($msg->meta_msg_array['content']['attachements'])) {
            foreach ($msg->meta_msg_array['content']['attachements'] as $messageAttachment) {
                if (isset($messageAttachment['id']) && isset($messageAttachment['security_hash'])) {
                    self::appendTelegramMessageFile($files, $seen, $messageAttachment['id'], $messageAttachment['security_hash']);
                }
            }
        }

        if (preg_match_all('/\[file=(\d+)_([a-f0-9]{32})\]/i', (string)$msg->msg, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                self::appendTelegramMessageFile($files, $seen, $match[1], $match[2]);
            }
        }

        return $files;
    }

    private static function appendTelegramMessageFile(& $files, & $seen, $id, $hash)
    {
        $id = (int)$id;
        $hash = (string)$hash;
        $key = $id . '_' . $hash;

        if (isset($seen[$key])) {
            return;
        }

        try {
            $file = \erLhcoreClassModelChatFile::fetch($id);
        } catch (\Exception $e) {
            return;
        }

        if (!($file instanceof \erLhcoreClassModelChatFile) || strtolower($file->security_hash) !== strtolower($hash)) {
            return;
        }

        $seen[$key] = true;
        $files[] = array(
            'file' => $file,
            'embed' => '[file=' . $file->id . '_' . $file->security_hash . ']'
        );
    }

    private static function getTelegramFileCaption($msg, $chat, $file, $messageText = null)
    {
        $sender = $msg->name_support != '' ? '🤖 [' . $msg->name_support . ']' : '👤 [' . $chat->nick . ']';
        $messageText = $messageText === null ? self::stripTelegramFileEmbeds($msg->msg) : trim((string)$messageText);

        if ($messageText !== '') {
            $caption = $sender . ': ' . $messageText;
        } elseif (strpos(strtolower((string)$file->type), 'image/') === 0) {
            $caption = $sender;
        } elseif (self::isMeaningfulTelegramUploadName($file)) {
            $caption = $sender . ': ' . $file->upload_name;
        } else {
            $caption = $sender;
        }

        return htmlspecialchars(mb_substr($caption, 0, 900), ENT_QUOTES, 'UTF-8');
    }

    private static function isMeaningfulTelegramUploadName($file)
    {
        $uploadName = trim((string)$file->upload_name);

        if ($uploadName === '') {
            return false;
        }

        if (mb_strlen($uploadName) <= 2 && strpos($uploadName, '.') === false) {
            return false;
        }

        return true;
    }

    private static function sendTelegramChatFile($tchat, $fileData, $caption, $disableNotification = false)
    {
        $file = $fileData['file'];

        if (!file_exists($file->file_path_server) || !is_readable($file->file_path_server)) {
            return false;
        }

        $extension = strtolower((string)$file->extension);
        $type = strtolower((string)$file->type);
        $method = 'sendDocument';
        $field = 'document';

        if (in_array($extension, array('jpg', 'jpeg', 'png', 'webp')) || in_array($type, array('image/jpeg', 'image/png', 'image/webp'))) {
            $method = 'sendPhoto';
            $field = 'photo';
        } elseif ($extension === 'ogg' || $type === 'audio/ogg') {
            $method = 'sendVoice';
            $field = 'voice';
        } elseif (in_array($extension, array('mp3', 'm4a')) || in_array($type, array('audio/mpeg', 'audio/mp4'))) {
            $method = 'sendAudio';
            $field = 'audio';
        } elseif ($extension === 'mp4' || $type === 'video/mp4') {
            $method = 'sendVideo';
            $field = 'video';
        }

        $fileSize = is_file($file->file_path_server ?? '') ? filesize($file->file_path_server) : 0;

        $data = array(
            'chat_id' => $tchat->bot->group_chat_id,
            'message_thread_id' => $tchat->tchat_id,
            'parse_mode' => 'HTML'
        );

        // Telegram Bot API supports multipart file uploads up to 50 MB (52428800 bytes).
        // URL-based download limit on Telegram servers is restricted to 20 MB.
        // Uploading directly from server disk allows files between 20MB and 50MB (e.g. videos/recordings) to be delivered natively.
        if ($fileSize > 0 && $fileSize <= 52428800) {
            $fileHandle = \Longman\TelegramBot\Request::encodeFile($file->file_path_server);
            if (is_resource($fileHandle)) {
                $data[$field] = $fileHandle;
            } else {
                $data[$field] = self::getTelegramChatFileUrl($file);
            }
        } else {
            $data[$field] = self::getTelegramChatFileUrl($file);
        }

        if ($caption !== '') {
            $data['caption'] = $caption;
        }

        if ($disableNotification === true) {
            $data['disable_notification'] = true;
        }

        try {
            $sendData = \Longman\TelegramBot\Request::send($method, $data);
        } catch (\Exception $e) {
            \erLhcoreClassLog::write('SendFile exception '.$e->getMessage(),
                \ezcLog::SUCCESS_AUDIT,
                array(
                    'source' => 'lhc',
                    'category' => 'telegram_exception',
                    'line' => __LINE__,
                    'file' => __FILE__,
                    'object_id' => $file->chat_id
                )
            );

            return false;
        }

        if (!$sendData->isOk()) {
            \erLhcoreClassLog::write('SendFile ['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                \ezcLog::SUCCESS_AUDIT,
                array(
                    'source' => 'lhc',
                    'category' => 'telegram_exception',
                    'line' => __LINE__,
                    'file' => __FILE__,
                    'object_id' => $file->chat_id
                )
            );

            return false;
        }

        return true;
    }

    private static function getTelegramChatFileUrl($file)
    {
        $URLHash = '';

        if ($file->chat_id > 0) {
            $tsHash = time();
            $temporaryHash = sha1($file->id . '_' . $file->hash . '_' . $tsHash . '_' . \erConfigClassLhConfig::getInstance()->getSetting('site', 'secrethash'));
            $URLHash = "/(vhash)/{$temporaryHash}/(vts)/{$tsHash}";
        }

        return \erLhcoreClassSystem::getHost() . \erLhcoreClassDesign::baseurldirect('file/downloadfile') . "/{$file->id}/{$file->security_hash}{$URLHash}";
    }

    public static function messageAdded($params)
    {
        $chat = $params['chat'];
        $db = \ezcDbInstance::get();

        foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['chat_id_internal' => ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), 'type' => 1]]) as $tchat) {

            if ((int)$tchat->chat_id !== (int)$params['chat']->id) {
                $tchat->chat_id = (int)$params['chat']->id;
                $tchat->updateThis(['update' => ['chat_id']]);
            }

            $db->beginTransaction();
            $tchat->syncAndLock('`last_msg_id`');
            $db->commit();

            if ($tchat->bot->bot_client == 0) {
                continue;
            }

            $telegram = new \Longman\TelegramBot\Telegram($tchat->bot->bot_api, $tchat->bot->bot_username);

            if ($params['msg']->id > $tchat->last_msg_id) {

                $db->beginTransaction();
                $tchat->syncAndLock('`id`');
                $tchat->last_msg_id = $params['msg']->id;
                $tchat->updateThis(['update' => ['last_msg_id']]);
                $db->commit();


                // remove following if you want enable autoresponder messages for operators chat
                if (isset($params['msg']->meta_msg_array['content']['auto_responder'])) {
                    continue;
                }
                // end here

                $telegramFiles = self::getTelegramMessageFiles($params['msg']);
                $messageText = self::stripTelegramFileEmbeds($params['msg']->msg);

                $sendData = null;

                if ($messageText !== '' && empty($telegramFiles)) {
                    $data = [
                        'chat_id' => $tchat->bot->group_chat_id,
                        'message_thread_id' => $tchat->tchat_id,
                        'parse_mode' => 'HTML',
                        'text' => trim(($params['msg']->name_support != '' ? '🤖 [' . $params['msg']->name_support . ']: <i>' : '👤 [' . \erLhcoreClassBBCodePlain::make_clickable($chat->nick, array('sender' => 0)) . ']: ') . \erLhcoreClassBBCodePlain::make_clickable($messageText, array('sender' => 0)) . ($params['msg']->name_support != '' ? '</i>' : ''))
                    ];

                    if ($chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                        $data['disable_notification'] = true;
                    }

                    $sendData = \Longman\TelegramBot\Request::sendMessage($data);

                    if (!$sendData->isOk() && $sendData->getErrorCode() == 400 && (str_contains($sendData->getDescription(), 'message thread not found') || str_contains($sendData->getDescription(), 'TOPIC_DELETED'))) {
                        // Reset telegram chat
                        $tchat->tchat_id = 0;
                        $tchat->updateThis(['update' => ['tchat_id']]);

                        // Process request as a new chat just
                        self::chatStarted(['chat' => $chat]);
                        return;
                    }

                    if (!$sendData->isOk()) {
                        \erLhcoreClassLog::write('sendMessagesss ['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                            \ezcLog::SUCCESS_AUDIT,
                            array(
                                'source' => 'lhc',
                                'category' => 'telegram_exception',
                                'line' => __LINE__,
                                'file' => __FILE__,
                                'object_id' => $chat->id
                            )
                        );
                    }
                }

                if (!empty($telegramFiles) && ($sendData === null || $sendData->isOk())) {
                    $failedEmbedCodes = array();
                    $fileIndex = 0;

                    foreach ($telegramFiles as $telegramFile) {
                        if (self::sendTelegramChatFile($tchat, $telegramFile, self::getTelegramFileCaption($params['msg'], $chat, $telegramFile['file'], $fileIndex === 0 ? $messageText : ''), $chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) === false) {
                            $failedEmbedCodes[] = $telegramFile['embed'];
                        }
                        $fileIndex++;
                    }

                    if (!empty($failedEmbedCodes)) {
                        \Longman\TelegramBot\Request::sendMessage(array(
                            'chat_id' => $tchat->bot->group_chat_id,
                            'message_thread_id' => $tchat->tchat_id,
                            'parse_mode' => 'HTML',
                            'text' => \erLhcoreClassBBCodePlain::make_clickable(implode("\n", $failedEmbedCodes), array('sender' => 0))
                        ));
                    }
                }
            }

            if (isset($params['no_afterwards_messages']) && $params['no_afterwards_messages'] == true) {
                continue;
            }

            // remove following if you want enable autoresponder messages for operators chat
            if (isset($params['msg']->meta_msg_array['content']['auto_responder'])) {
                continue;
            }


            // Send bot responses if any
            $botMessages = \erLhcoreClassModelmsg::getList(array('filter' => array('user_id' => -2, 'chat_id' => $chat->id), 'filtergt' => array('id' => $params['msg']->id)));

            foreach ($botMessages as $botMessage) {

                $db->beginTransaction();
                $tchat->syncAndLock('`last_msg_id`');

                if ($botMessage->id <= $tchat->last_msg_id) {
                    $db->commit();
                    continue;
                } else {
                    $tchat->last_msg_id = $botMessage->id;
                }

                $tchat->updateThis(['update' => ['last_msg_id']]);
                $db->commit();

                $telegramFiles = self::getTelegramMessageFiles($botMessage);
                $messageText = self::stripTelegramFileEmbeds($botMessage->msg);

                if ($messageText !== '' && empty($telegramFiles)) {
                    $data = [
                        'chat_id' => $tchat->bot->group_chat_id,
                        'message_thread_id' => $tchat->tchat_id,
                        'parse_mode' => 'HTML',
                        'text' => trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤 ['. \erLhcoreClassBBCodePlain::make_clickable($chat->nick, array('sender' => 0)) . ']: ') . \erLhcoreClassBBCodePlain::make_clickable($messageText, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''))
                    ];
                    if ($chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                        $data['disable_notification'] = true;
                    }
                    $sendData = \Longman\TelegramBot\Request::sendMessage($data);

                    if (!$sendData->isOk()) {
                        \erLhcoreClassLog::write('SendMessage BOT ['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                            \ezcLog::SUCCESS_AUDIT,
                            array(
                                'source' => 'lhc',
                                'category' => 'telegram_exception',
                                'line' => __LINE__,
                                'file' => __FILE__,
                                'object_id' => $chat->id
                            )
                        );
                    }
                }

                if (!empty($telegramFiles)) {
                    $failedEmbedCodes = array();
                    $fileIndex = 0;

                    foreach ($telegramFiles as $telegramFile) {
                        if (self::sendTelegramChatFile($tchat, $telegramFile, self::getTelegramFileCaption($botMessage, $chat, $telegramFile['file'], $fileIndex === 0 ? $messageText : ''), $chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) === false) {
                            $failedEmbedCodes[] = $telegramFile['embed'];
                        }
                        $fileIndex++;
                    }

                    if (!empty($failedEmbedCodes)) {
                        \Longman\TelegramBot\Request::sendMessage(array(
                            'chat_id' => $tchat->bot->group_chat_id,
                            'message_thread_id' => $tchat->tchat_id,
                            'parse_mode' => 'HTML',
                            'text' => \erLhcoreClassBBCodePlain::make_clickable(implode("\n", $failedEmbedCodes), array('sender' => 0))
                        ));
                    }
                }
            }
        }
    }

    public static function triggerClicked($params)
    {

        // Everything will be processed on main start chat trigger
        if (\erLhcoreClassGenericBotWorkflow::$startChat == true) {
            return;
        }

        if (is_object($params['chat']->incoming_chat) && $params['chat']->incoming_chat->incoming->scope == 'telegram') {
            $telegramBot = \erLhcoreClassModelTelegramBot::fetch((int)$_GET['telegram_bot_id']);
            if (is_object($telegramBot)) {
                $telegram = new \Longman\TelegramBot\Telegram($telegramBot->bot_api, $telegramBot->bot_username);
                \Longman\TelegramBot\Request::send('editMessageReplyMarkup',[
                    'chat_id' => $params['chat']->incoming_chat->chat_external_id,
                    'message_id' => $params['msg']->meta_msg_array['iwh_msg_id'],
                    'reply_markup' => null
                ]);
            }
        }

        $chat = $params['chat'];

        foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['chat_id_internal' => ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), 'type' => 1]]) as $tchat) {

            $telegram = new \Longman\TelegramBot\Telegram($tchat->bot->bot_api, $tchat->bot->bot_username);

            if ($tchat->bot->bot_client == 0) {
                continue;
            }

            // remove following if you want enable autoresponder messages for operators chat
            if (isset($params['msg']->meta_msg_array['content']['auto_responder'])) {
                continue;
            }
            // end here

            // Send bot responses if any
            $botMessages = \erLhcoreClassModelmsg::getList(array('filterin' => ['user_id' => [0, -2]], 'filter' => array('chat_id' => $chat->id), 'filtergt' => array('id' => $params['last_msg_id'])));
            foreach ($botMessages as $botMessage) {

                $tchat->last_msg_id = $botMessage->id;
                $tchat->updateThis(['update' => ['last_msg_id']]);

                $telegramFiles = self::getTelegramMessageFiles($botMessage);
                $messageText = self::stripTelegramFileEmbeds($botMessage->msg);

                if ($messageText !== '' && empty($telegramFiles)) {
                    $data = [
                        'chat_id' => $tchat->bot->group_chat_id,
                        'message_thread_id' => $tchat->tchat_id,
                        'parse_mode' => 'HTML',
                        'text' => trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤: ') . \erLhcoreClassBBCodePlain::make_clickable($messageText, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''))
                    ];
                    if ($chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                        $data['disable_notification'] = true;
                    }
                    $sendData = \Longman\TelegramBot\Request::sendMessage($data);

                    if (!$sendData->isOk()) {
                        \erLhcoreClassLog::write('['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                            \ezcLog::SUCCESS_AUDIT,
                            array(
                                'source' => 'lhc',
                                'category' => 'telegram_exception',
                                'line' => __LINE__,
                                'file' => __FILE__,
                                'object_id' => $chat->id
                            )
                        );
                    }
                }

                if (!empty($telegramFiles)) {
                    $failedEmbedCodes = array();
                    $fileIndex = 0;

                    foreach ($telegramFiles as $telegramFile) {
                        if (self::sendTelegramChatFile($tchat, $telegramFile, self::getTelegramFileCaption($botMessage, $chat, $telegramFile['file'], $fileIndex === 0 ? $messageText : ''), $chat->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) === false) {
                            $failedEmbedCodes[] = $telegramFile['embed'];
                        }
                        $fileIndex++;
                    }

                    if (!empty($failedEmbedCodes)) {
                        \Longman\TelegramBot\Request::sendMessage(array(
                            'chat_id' => $tchat->bot->group_chat_id,
                            'message_thread_id' => $tchat->tchat_id,
                            'parse_mode' => 'HTML',
                            'text' => \erLhcoreClassBBCodePlain::make_clickable(implode("\n", $failedEmbedCodes), array('sender' => 0))
                        ));
                    }
                }
            }
        }
    }

    public static function chatStarted($params)
    {
        $bots = \erLhcoreClassModelTelegramBotDep::getList(array('filter' => array('dep_id' => $params['chat']->dep_id)));
        $db = \ezcDbInstance::get();

        foreach ($bots as $bot) {
            if ($bot->bot instanceof \erLhcoreClassModelTelegramBot && $bot->bot->bot_client == 1) {

                try {
                    $db->beginTransaction();

                    $chatId = $params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id;

                    $tChat = \erLhcoreClassModelTelegramChat::findOne(array(
                        'filter' => array(
                            'chat_id_internal' => $chatId,
                            'bot_id' => $bot->bot->id,
                            'type' => 1
                        )
                    ));

                    if (!($tChat instanceof \erLhcoreClassModelTelegramChat)) {
                        $tChat = new \erLhcoreClassModelTelegramChat();
                        $tChat->type = 1;
                        $tChat->bot_id = $bot->bot->id;
                        $tChat->chat_id_internal = $chatId;
                        $tChat->chat_id = $params['chat']->id;
                        $tChat->utime = time();
                        $tChat->ctime = time();
                    } else {
                        // Update to a new chat
                        $tChat->chat_id = $params['chat']->id;
                        $tChat->updateThis(['update' => ['chat_id']]);
                    }

                    $telegram = new \Longman\TelegramBot\Telegram($bot->bot->bot_api, $bot->bot->bot_username);

                    if ($tChat->tchat_id == null || $tChat->tchat_id == 0) {
                        $sendData = \Longman\TelegramBot\Request::send('createForumTopic', [
                            'chat_id' => $bot->bot->group_chat_id,
                            'name' => mb_substr('[' . $params['chat']->department . '] ' . $params['chat']->nick . ' #' . $params['chat']->id. ($params['chat']->ip != '' ? ' | ' . $params['chat']->ip : '') . ($params['chat']->country_code != '' ? ' | ' . strtoupper($params['chat']->country_code) : '') . ($params['chat']->referrer != '' ? ' | '. ltrim($params['chat']->referrer,'/') : '') . (is_object($params['chat']->online_user) && $params['chat']->online_user->page_title != '' ? ' | '.$params['chat']->online_user->page_title : ''),0,128)
                        ]);

                        if ($sendData->isOk()) {
                            $tChat->tchat_id = $sendData->getResult()->getMessageThreadId();
                        } else {
                            throw new \Exception('['.$sendData->getErrorCode().']'. $sendData->getDescription());
                        }
                    }

                    $previousChatMessages = '';

                    if ($bot->bot->delete_on_close == 1 && $params['chat']->online_user_id > 0 && is_object($params['chat']->online_user) && is_object($params['chat']->online_user->previous_chat)) {
                        $previousChatMessagesList = [];
                        foreach (array_reverse(\erLhcoreClassModelmsg::getList(array('limit' => 15, 'sort' => 'id DESC', 'filternotin' => ['user_id' => [-1]], 'filter' => array('chat_id' => $params['chat']->online_user->previous_chat->id)))) as $botMessage) {
                            if (empty($botMessage->msg)) {
                                continue;
                            }
                            $previousChatMessagesList[] = trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤 ['. \erLhcoreClassBBCodePlain::make_clickable($params['chat']->nick, array('sender' => 0)) . ']: ') . \erLhcoreClassBBCodePlain::make_clickable($botMessage->msg, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''));
                        }

                        if (!empty($previousChatMessagesList)){
                            $previousChatMessages = "\n├──Previous chat messages: \n" . implode("\n", $previousChatMessagesList);
                        }
                    }

                    $additionalDataFormatted = '';
                    if (isset($params['chat']->additional_data) && !empty($params['chat']->additional_data)) {
                        $additionalData = json_decode($params['chat']->additional_data, true);
                        if (is_array($additionalData) && !empty($additionalData)) {
                            $additionalDataLines = [];
                            foreach ($additionalData as $dataItem) {
                                if (isset($dataItem['key']) && isset($dataItem['value']) && $dataItem['key'] !== '' && $dataItem['value'] !== '') {
                                    $additionalDataLines[] = "├──" . $dataItem['key'] . ": " . $dataItem['value'];
                                }
                            }
                            if (!empty($additionalDataLines)) {
                                $additionalDataFormatted = "\n" . implode("\n", $additionalDataLines);
                            }
                        }
                    }

                    $visitor = array();
                    $visitor[] = "├──New chat\n├──Department: " . ((string)$params['chat']->department) . "\n├──ID: " . $params['chat']->id . (isset($params['chat']->chat_variables_array['iwh_field']) ? "\n├──Username: @" . $params['chat']->chat_variables_array['iwh_field'] : '') . (isset($params['chat']->phone) && !empty($params['chat']->phone) ? "\n├──Phone: +" . $params['chat']->phone : '') .  "\n├──Nick: " . $params['chat']->nick .(isset($params['chat']->referrer) && !empty($params['chat']->referrer) ? "\n├──Referrer: " . ltrim($params['chat']->referrer,'/') : '') . (is_object($params['chat']->online_user) && $params['chat']->online_user->page_title != '' ? "\n├──Page title: " . $params['chat']->online_user->page_title : '') . (isset($params['chat']->ip) && !empty($params['chat']->ip) ? "\n├──IP: " . $params['chat']->ip  : '') . (isset($params['chat']->country_name) && !empty($params['chat']->country_name) ? "\n├──GEO: " . $params['chat']->country_name : '') . $additionalDataFormatted . $previousChatMessages . "\n└──Messages:";

                    // Collect all chat messages including bot
                    $initialTelegramFiles = array();
                    $botMessages = \erLhcoreClassModelmsg::getList(array('filterin' => ['user_id' => [0, -2]], 'filter' => array('chat_id' => $params['chat']->id)));
                    foreach ($botMessages as $botMessage) {
                        $tChat->last_msg_id = $botMessage->id;
                        $telegramFiles = self::getTelegramMessageFiles($botMessage);
                        $messageText = self::stripTelegramFileEmbeds($botMessage->msg);

                        if ($messageText === '' && empty($telegramFiles)) {
                            continue;
                        }

                        if ($messageText !== '' && empty($telegramFiles)) {
                            $visitor[] = trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤 ['. \erLhcoreClassBBCodePlain::make_clickable($params['chat']->nick, array('sender' => 0)) . ']: ') . \erLhcoreClassBBCodePlain::make_clickable($messageText, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''));
                        }

                        $fileIndex = 0;
                        foreach ($telegramFiles as $telegramFile) {
                            $initialTelegramFiles[] = array('msg' => $botMessage, 'file' => $telegramFile, 'text' => $fileIndex === 0 ? $messageText : '');
                            $fileIndex++;
                        }
                    }

                    $data = [
                        'chat_id' => $bot->bot->group_chat_id,
                        'message_thread_id' => $tChat->tchat_id,
                        'text' => implode("\n\n", $visitor),
                        'parse_mode' => 'HTML'
                    ];

                    if ($params['chat']->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                        $data['disable_notification'] = true;
                    }

                    $sendData = \Longman\TelegramBot\Request::sendMessage($data);

                    if (!$sendData->isOk()) {

                        // Try first time to create a topic if old one is gone
                        if ($sendData->getErrorCode() == 400 && (str_contains($sendData->getDescription(), 'message thread not found') || str_contains($sendData->getDescription(), 'TOPIC_DELETED'))) {

                            $sendData = \Longman\TelegramBot\Request::send('createForumTopic', [
                                'chat_id' => $bot->bot->group_chat_id,
                                'name' => mb_substr('[' . $params['chat']->department . '] ' . $params['chat']->nick . ' #' . $params['chat']->id. ($params['chat']->ip != '' ? ' | ' . $params['chat']->ip : '') . ($params['chat']->country_code != '' ? ' | ' . strtoupper($params['chat']->country_code) : '') . ($params['chat']->referrer != '' ? ' | '. ltrim($params['chat']->referrer,'/') : '') . (is_object($params['chat']->online_user) && $params['chat']->online_user->page_title != '' ? ' | '.$params['chat']->online_user->page_title : ''),0,128)
                            ]);

                            if ($sendData->isOk()) {
                                $tChat->tchat_id = $sendData->getResult()->getMessageThreadId();
                            } else {
                                throw new \Exception('['.$sendData->getErrorCode().']'. $sendData->getDescription());
                            }
                        }

                        $data['message_thread_id'] = $tChat->tchat_id;
                        $sendData = \Longman\TelegramBot\Request::sendMessage($data);

                        if (!$sendData->isOk()) {
                            \erLhcoreClassLog::write('['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                                \ezcLog::SUCCESS_AUDIT,
                                array(
                                    'source' => 'lhc',
                                    'category' => 'telegram_exception',
                                    'line' => __LINE__,
                                    'file' => __FILE__,
                                    'object_id' => $tChat->chat_id
                                )
                            );
                        }
                    }

                    if (!empty($initialTelegramFiles)) {
                        $failedEmbedCodes = array();

                        foreach ($initialTelegramFiles as $initialTelegramFile) {
                            if (self::sendTelegramChatFile($tChat, $initialTelegramFile['file'], self::getTelegramFileCaption($initialTelegramFile['msg'], $params['chat'], $initialTelegramFile['file']['file'], $initialTelegramFile['text']), $params['chat']->status == \erLhcoreClassModelChat::STATUS_BOT_CHAT) === false) {
                                $failedEmbedCodes[] = $initialTelegramFile['file']['embed'];
                            }
                        }

                        if (!empty($failedEmbedCodes)) {
                            \Longman\TelegramBot\Request::sendMessage(array(
                                'chat_id' => $tChat->bot->group_chat_id,
                                'message_thread_id' => $tChat->tchat_id,
                                'parse_mode' => 'HTML',
                                'text' => \erLhcoreClassBBCodePlain::make_clickable(implode("\n", $failedEmbedCodes), array('sender' => 0))
                            ));
                        }
                    }

                    $tChat->saveThis();

                    $db->commit();

                } catch (\Exception $e) {

                    $db->rollback();

                    \erLhcoreClassLog::write($e->getMessage() . '-' . $e->getTraceAsString(),
                        \ezcLog::SUCCESS_AUDIT,
                        array(
                            'source' => 'lhc',
                            'category' => 'telegram_exception',
                            'line' => __LINE__,
                            'file' => __FILE__,
                            'object_id' => $params['chat']->id
                        )
                    );
                }
            }
        }
    }

    /**
     * @desc delete chat if exists
     *
     * @param $params
     */
    public static function deleteChat($params)
    {
        self::closeChat($params);

        $db = \ezcDbInstance::get();
        $stmt = $db->prepare('DELETE FROM lhc_telegram_chat WHERE chat_id_internal = :chat_id_internal');
        $stmt->bindValue(':chat_id_internal', ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), \PDO::PARAM_INT);
        $stmt->execute();
    }

    /*
     * Delete forum topic if configured
     * */
    public static function closeChat($params)
    {
        foreach (\erLhcoreClassModelTelegramChat::getList(['filter' => ['chat_id_internal' => ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), 'type' => 1]]) as $tchat) {

            if ($tchat->bot->bot_client == 0 || $tchat->bot->delete_on_close == 0) {
                continue;
            }

            if ($tchat->tchat_id > 0) {

                $telegram = new \Longman\TelegramBot\Telegram($tchat->bot->bot_api, $tchat->bot->bot_username);

                $sendData = \Longman\TelegramBot\Request::send('deleteForumTopic', [
                    'chat_id' => $tchat->bot->group_chat_id,
                    'message_thread_id' => $tchat->tchat_id
                ]);

                $tchat->tchat_id = 0;
                $tchat->updateThis(['update' => ['tchat_id']]);

                if (!$sendData->isOk()) {
                    \erLhcoreClassLog::write('deleteForumTopic ['.$sendData->getErrorCode().']'. $sendData->getDescription(),
                        \ezcLog::SUCCESS_AUDIT,
                        array(
                            'source' => 'lhc',
                            'category' => 'telegram_exception',
                            'line' => __LINE__,
                            'file' => __FILE__,
                            'object_id' => $params['chat']->id
                        )
                    );
                }
            }
        }
    }

}

?>