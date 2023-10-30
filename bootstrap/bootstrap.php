<?php

#[\AllowDynamicProperties]
class erLhcoreClassExtensionLhctelegram
{

    public function __construct()
    {

    }

    public function run()
    {
        $this->registerAutoload();

        include_once 'extension/lhctelegram/vendor/autoload.php';

        $dispatcher = erLhcoreClassChatEventDispatcher::getInstance();

        $dispatcher->listen('chat.delete', array(
            $this,
            'deleteChat'
        ));

        $dispatcher->listen('instance.extensions_structure', array(
            $this,
            'checkStructure'
        ));

        $dispatcher->listen('instance.registered.created', array(
            $this,
            'instanceCreated'
        ));

        $dispatcher->listen('telegram.get_signature', array(
            $this,
            'getSignature'
        ));

        $dispatcher->listen('chat.chat_started', array(
            $this,
            'chatStarted'
        ));

        $dispatcher->listen('chat.web_add_msg_admin', array(
            $this,
            'messageAddedAdmin'
        ));

        $dispatcher->listen('chat.before_auto_responder_msg_saved', array(
            $this,
            'messageAddedResponder'
        ));

        $dispatcher->listen('chat.addmsguser', array(
            $this,
            'messageAdded'
        ));

        $dispatcher->listen('chat.messages_added_passive', array(
            $this,
            'messageAdded'
        ));

        $dispatcher->listen('chat.genericbot_get_trigger_click_processed', array(
            $this,
            'triggerClicked'
        ));

        // Handle canned messages custom workflow
        $dispatcher->listen('chat.canned_msg_before_save', array(
                $this, 'cannedMessageValidate')
        );

        $dispatcher->listen('chat.before_newcannedmsg', array(
                $this, 'cannedMessageValidate')
        );

        $dispatcher->listen('chat.workflow.canned_message_replace', array(
                $this, 'cannedMessageReplace')
        );

        $dispatcher->listen('chat.incoming_dynamic_array', array(
            $this,'incomingChatDynamicArray')
        );

        $dispatcher->listen('chat.webhook_incoming_chat_started', array(
            $this,'incommingChatStarted')
        );
    }

    /*
     * erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.webhook_incoming_chat_started', array(
            'webhook' => & $incomingWebhook,
            'data' => & $payloadAll,
            'chat' => & $chat
        ));*/
    public static function incommingChatStarted($params)
    {
        if ($params['webhook']->scope == 'telegram') {

            $telegramBot = null;

            if (isset($_GET['telegram_bot_id'])) {
                $telegramBot = erLhcoreClassModelTelegramBot::fetch((int)$_GET['telegram_bot_id']);
            }

            if (!is_object($telegramBot) && isset($params['chat']->chat_variables_array['iwh_field_2']) && $params['chat']->chat_variables_array['iwh_field_2'] != '') {
                $telegramBot = erLhcoreClassModelTelegramBot::fetch($params['chat']->chat_variables_array['iwh_field_2']);
            }

            if (is_object($telegramBot)) {
                $params['chat']->dep_id = $telegramBot->dep_id;
                $params['chat']->updateThis(['update' => ['dep_id']]);

                $chatId = null;
                $messageData = [];
                if (isset($params['data']['message']['chat']['id'])) {
                    $chatId = $params['data']['message']['chat']['id'];
                    $messageData = $params['data']['message']['from'];
                } elseif (isset($params['data']['message']['chat']['id'])) {
                    $chatId = $params['data']['callback_query']['message']['chat']['id'];
                    $messageData = $params['data']['callback_query']['from'];
                }

                if (is_numeric($chatId)){
                    $lead = \erLhcoreClassModelTelegramLead::findOne(array('filter' => array('tchat_id' => $chatId)));
                    if (!($lead instanceof \erLhcoreClassModelTelegramLead)) {
                        $lead = new \erLhcoreClassModelTelegramLead();
                        $lead->language_code = isset($messageData['language_code']) ? $messageData['language_code'] : '';
                        $lead->first_name = isset($messageData['first_name']) ? $messageData['first_name'] : '';
                        $lead->last_name = isset($messageData['last_name']) ? $messageData['last_name'] : '';
                        $lead->utime = time();
                        $lead->ctime = time();
                        $lead->tchat_id = $chatId;
                        $lead->tbot_id = $telegramBot->id;
                        $lead->dep_id = $telegramBot->dep_id;
                        $lead->username = isset($messageData['username']) ? $messageData['username'] : '';
                        $lead->saveThis();
                    }
                }
            }
        }
    }

    
    /*
     * erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.incoming_dynamic_array', array('incoming_chat' => $this, 'dynamic_array' => & $chat_dynamic_array));
    */
    public function incomingChatDynamicArray($params)
    {
        /*
             {{args.chat.incoming_chat.incoming.attributes.bot_username}}
             {{args.chat.incoming_chat.incoming_dynamic_array.bot_username}}
             {{args.chat.incoming_chat.incoming.attributes.access_token}}
             {{args.chat.incoming_chat.incoming_dynamic_array.access_token}}
        */
        if ($params['incoming_chat']->incoming->scope == 'telegram')
        {
            $telegramBot = null;

            if (isset($_GET['telegram_bot_id'])) {
                $telegramBot = erLhcoreClassModelTelegramBot::fetch((int)$_GET['telegram_bot_id']);
            }

            if (!is_object($telegramBot) && isset($params['incoming_chat']->chat->chat_variables_array['iwh_field_2']) && $params['incoming_chat']->chat->chat_variables_array['iwh_field_2'] != '') {
                $telegramBot = erLhcoreClassModelTelegramBot::fetch($params['incoming_chat']->chat->chat_variables_array['iwh_field_2']);
            }

            if (is_object($telegramBot)) {
                $params['dynamic_array']['access_token'] = $telegramBot->bot_api;
                $params['dynamic_array']['bot_username'] = $telegramBot->bot_username;
            }

            if (!isset($params['dynamic_array']['access_token'])) {
                $params['dynamic_array']['access_token'] = $params['incoming_chat']->incoming->attributes['access_token'];
                $params['dynamic_array']['bot_username'] = $params['incoming_chat']->incoming->attributes['bot_username'];
            }
        }
    }

    public function cannedMessageReplace($params)
    {
        if (is_object($params['chat']->incoming_chat) && is_object($params['chat']->incoming_chat->incoming) && $params['chat']->incoming_chat->incoming->scope == 'telegram') {
            foreach ($params['items'] as & $item) {

                if ($params['chat']->locale != '' && $item->languages != '') {
                    // Override language by chat locale
                    $languages = json_decode($item->languages, true);

                    if (is_array($languages)) {
                        foreach ($languages as & $lang) {

                            if (isset($lang['message_lang_tel']) && !empty($lang['message_lang_tel'])) {
                                $lang['message'] = $lang['message_lang_tel'];
                            }

                            if (isset($lang['fallback_message_lang_tel']) && !empty($lang['fallback_message_lang_tel'])) {
                                $lang['fallback_msg'] = $lang['fallback_message_lang_tel'];
                            }
                        }
                    }

                    $item->languages = json_encode($languages);
                }

                $additionalData = $item->additional_data_array;

                if (isset($additionalData['message_tel']) && !empty($additionalData['message_tel'])) {
                    $item->msg = $additionalData['message_tel'];
                }

                if (isset($additionalData['fallback_tel']) && !empty($additionalData['fallback_tel'])) {
                    $item->fallback_msg = $additionalData['fallback_tel'];
                }
            }
        }
    }

    public function cannedMessageValidate($params)
    {
        $definition = array(
            'MessageExtTel' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'),
            'FallbackMessageExtTel' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'),

            'message_lang_tel' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw', null, FILTER_REQUIRE_ARRAY),
            'fallback_message_lang_tel' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw', null, FILTER_REQUIRE_ARRAY)
        );

        $form = new ezcInputForm(INPUT_POST, $definition);

        $langArray = array();
        foreach ($params['msg']->languages_array as $index => $langData) {
            $langData['message_lang_tel'] = $form->message_lang_tel[$index];
            $langData['fallback_message_lang_tel'] = $form->fallback_message_lang_tel[$index];
            $langArray[] = $langData;
        }

        $params['msg']->languages = json_encode($langArray);
        $params['msg']->languages_array = $langArray;

        // Store additional data
        $additionalArray = $params['msg']->additional_data_array;

        if ($form->hasValidData('MessageExtTel')) {
            $additionalArray['message_tel'] = $form->MessageExtTel;
        }

        if ($form->hasValidData('FallbackMessageExtTel')) {
            $additionalArray['fallback_tel'] = $form->FallbackMessageExtTel;
        }

        $params['msg']->additional_data = json_encode($additionalArray);
        $params['msg']->additional_data_array = $additionalArray;
    }

    /**
     * Checks automated hosting structure
     *
     * This part is executed once in manager is run this cronjob.
     * php cron.php -s site_admin -e instance -c cron/extensions_update
     *
     * */
    public function checkStructure()
    {
        erLhcoreClassUpdate::doTablesUpdate(json_decode(file_get_contents('extension/lhctelegram/doc/structure.json'), true));
    }

    /**
     * Used only in automated hosting enviroment
     */
    public function instanceCreated($params)
    {
        try {
            // Just do table updates
            erLhcoreClassUpdate::doTablesUpdate(json_decode(file_get_contents('extension/lhctelegram/doc/structure.json'), true));
        } catch (Exception $e) {
            erLhcoreClassLog::write(print_r($e, true));
        }
    }

    public function messageAddedAdmin($params)
    {
        if (isset($params['lhc_caller']['class']) && $params['lhc_caller']['class'] == 'Longman\TelegramBot\Commands\SystemCommands\GenericmessageCommand') {
            return;
        }

        if (isset($params['source']) && $params['source'] == 'webhook') {
            return;
        }

        $this->messageAdded($params);
    }

    public function messageAddedResponder($params)
    {
        if (isset($params['source']) && $params['source'] == 'webhook') {
            return;
        }

        $params['no_afterwards_messages'] = true;

        $this->messageAdded($params);
    }

    public function messageAdded($params)
    {
        $chat = $params['chat'];
        foreach (erLhcoreClassModelTelegramChat::getList(['filter' => ['chat_id_internal' => ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), 'type' => 1]]) as $tchat) {
            if ($tchat->bot->bot_client == 0) {
                continue;
            }
            $telegram = new Longman\TelegramBot\Telegram($tchat->bot->bot_api, $tchat->bot->bot_username);
            $data = [
                'chat_id' => $tchat->bot->group_chat_id,
                'message_thread_id' => $tchat->tchat_id,
                'parse_mode' => 'HTML',
                'text' => trim(($params['msg']->name_support != '' ? '🤖 [' . $params['msg']->name_support . ']: <i>' : '👤: ') . erLhcoreClassBBCodePlain::make_clickable($params['msg']->msg, array('sender' => 0)) . ($params['msg']->name_support != '' ? '</i>' : ''))
            ];

            if ($chat->status == erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                $data['disable_notification'] = true;
            }

            Longman\TelegramBot\Request::sendMessage($data);

            if (isset($params['no_afterwards_messages']) && $params['no_afterwards_messages'] == true) {
                continue;
            }

            // Send bot responses if any
            $botMessages = erLhcoreClassModelmsg::getList(array('filter' => array('user_id' => -2, 'chat_id' => $chat->id), 'filtergt' => array('id' => $params['msg']->id)));
            foreach ($botMessages as $botMessage) {
                $data = [
                    'chat_id' => $tchat->bot->group_chat_id,
                    'message_thread_id' => $tchat->tchat_id,
                    'parse_mode' => 'HTML',
                    'text' => trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤: ') . erLhcoreClassBBCodePlain::make_clickable($botMessage->msg, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''))
                ];
                if ($chat->status == erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                    $data['disable_notification'] = true;
                }
                Longman\TelegramBot\Request::sendMessage($data);
            }
        }
    }

    public function triggerClicked($params)
    {

        // Everything will be processed on main start chat trigger
        if (erLhcoreClassGenericBotWorkflow::$startChat == true) {
            return;
        }

        if (is_object($params['chat']->incoming_chat) && $params['chat']->incoming_chat->incoming->scope == 'telegram') {
            $telegramBot = erLhcoreClassModelTelegramBot::fetch((int)$_GET['telegram_bot_id']);
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

        foreach (erLhcoreClassModelTelegramChat::getList(['filter' => ['chat_id_internal' => ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), 'type' => 1]]) as $tchat) {

            $telegram = new Longman\TelegramBot\Telegram($tchat->bot->bot_api, $tchat->bot->bot_username);

            if ($tchat->bot->bot_client == 0) {
                continue;
            }

            // Send bot responses if any
            $botMessages = erLhcoreClassModelmsg::getList(array('filterin' => ['user_id' => [0, -2]], 'filter' => array('chat_id' => $chat->id), 'filtergt' => array('id' => $params['last_msg_id'])));
            foreach ($botMessages as $botMessage) {
                $data = [
                    'chat_id' => $tchat->bot->group_chat_id,
                    'message_thread_id' => $tchat->tchat_id,
                    'parse_mode' => 'HTML',
                    'text' => trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤: ') . erLhcoreClassBBCodePlain::make_clickable($botMessage->msg, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''))
                ];
                if ($chat->status == erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                    $data['disable_notification'] = true;
                }
                Longman\TelegramBot\Request::sendMessage($data);
            }
        }
    }

    public function chatStarted($params)
    {
        $bots = erLhcoreClassModelTelegramBotDep::getList(array('filter' => array('dep_id' => $params['chat']->dep_id)));

        foreach ($bots as $bot) {
            if ($bot->bot instanceof erLhcoreClassModelTelegramBot && $bot->bot->bot_client == 1) {

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

                $telegram = new Longman\TelegramBot\Telegram($bot->bot->bot_api, $bot->bot->bot_username);

                if ($tChat->tchat_id == null) {
                    $sendData = Longman\TelegramBot\Request::send('createForumTopic', [
                        'chat_id' => $bot->bot->group_chat_id,
                        'name' => '[' . $params['chat']->department . '] ' . $params['chat']->nick . ' #' . $params['chat']->id
                    ]);

                    if ($sendData->isOk()) {
                        $tChat->tchat_id = $sendData->getResult()->getMessageThreadId();
                    }
                }

                $visitor = array();
                $visitor[] = "├──New chat\n├──Department: " . ((string)$params['chat']->department) . "\n├──ID: " . $params['chat']->id . (isset($params['chat']->chat_variables_array['iwh_field']) ? "\n├──Username: @" . $params['chat']->chat_variables_array['iwh_field'] : ''). "\n├──Nick: " . $params['chat']->nick . "\n└──Messages:";

                // Collect all chat messages including bot
                $botMessages = erLhcoreClassModelmsg::getList(array('filterin' => ['user_id' => [0, -2]], 'filter' => array('chat_id' => $params['chat']->id)));
                foreach ($botMessages as $botMessage) {
                    $visitor[] = trim(($botMessage->name_support != '' ? '🤖 [' . $botMessage->name_support . ']: <i>' : '👤: ') . erLhcoreClassBBCodePlain::make_clickable($botMessage->msg, array('sender' => 0)) . ($botMessage->name_support != '' ? '</i>' : ''));
                }

                $data = [
                    'chat_id' => $bot->bot->group_chat_id,
                    'message_thread_id' => $tChat->tchat_id,
                    'text' => implode("\n\n", $visitor),
                    'parse_mode' => 'HTML'
                ];

                if ($params['chat']->status == erLhcoreClassModelChat::STATUS_BOT_CHAT) {
                    $data['disable_notification'] = true;
                }

                Longman\TelegramBot\Request::sendMessage($data);

                $tChat->saveThis();
            }
        }
    }

    public function registerAutoload()
    {
        spl_autoload_register(array(
            $this,
            'autoload'
        ), true, false);
    }

    public function autoload($className)
    {
        $classesArray = array(
            'erLhcoreClassModelTelegramBot' => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegrambot.php',
            'erLhcoreClassModelTelegramBotDep' => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegrambotdep.php',
            'erLhcoreClassModelTelegramOperator' => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramoperator.php',
            'erLhcoreClassModelTelegramChat' => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramchat.php',
            'erLhcoreClassModelTelegramSignature' => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramsignature.php',
            'erLhcoreClassModelTelegramLead' => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramlead.php',
            'erLhcoreClassTelegramValidator' => 'extension/lhctelegram/classes/erlhcoreclasstelegramvalidator.php'
        );

        if (key_exists($className, $classesArray)) {
            include_once $classesArray [$className];
        }
    }

    public static function getSession()
    {
        if (!isset (self::$persistentSession)) {
            self::$persistentSession = new ezcPersistentSession (ezcDbInstance::get(), new ezcPersistentCodeManager ('./extension/lhctelegram/pos'));
        }
        return self::$persistentSession;
    }

    /**
     * @desc delete chat if exists
     *
     * @param $params
     */
    public function deleteChat($params)
    {
        $db = ezcDbInstance::get();
        $stmt = $db->prepare('DELETE FROM lhc_telegram_chat WHERE chat_id_internal = :chat_id_internal');
        $stmt->bindValue(':chat_id_internal', ($params['chat']->online_user_id > 0 ? ($params['chat']->online_user_id * -1) : $params['chat']->id), PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @desc Returns signature. Other extensions can use this callback also. E.g Twilio extension
     *
     * @param $params
     * @return array
     */
    public function getSignature($params)
    {

        if (isset($params['bot_id'])) {
            $signature = erLhcoreClassModelTelegramSignature::findOne(array('filter' => array('bot_id' => $params['bot_id'], 'user_id' => $params['user_id'])));
            if ($signature instanceof erLhcoreClassModelTelegramSignature) {
                return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'signature' => $signature->signature);
            }
        }

        $signature = erLhcoreClassModelTelegramSignature::findOne(array('filter' => array('bot_id' => 0, 'user_id' => $params['user_id'])));
        if ($signature instanceof erLhcoreClassModelTelegramSignature) {
            return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'signature' => $signature->signature);
        }

        return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'signature' => '');
    }

    public function __get($var)
    {
        switch ($var) {
            case 'is_active' :
                return true;;
                break;

            case 'settings' :
                $this->settings = include('extension/lhctelegram/settings/settings.ini.php');
                return $this->settings;
                break;

            default :
                ;
                break;
        }
    }

    public function setBot($tbot)
    {
        $this->tbot = $tbot;
    }

    public function getBot()
    {
        return $this->tbot;
    }

    private static $persistentSession;

    private $tbot = null;

    private $configData = false;
}


