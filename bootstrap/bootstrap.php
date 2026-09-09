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

        $dispatcher = erLhcoreClassChatEventDispatcher::getInstance();

        $dispatcher->listen('instance.extensions_structure', array(
            $this,
            'checkStructure'
        ));

        $dispatcher->listen('instance.registered.created', array(
            $this,
            'instanceCreated'
        ));

        $dispatcher->listen('chat.incoming_dynamic_array', array(
            $this,'incomingChatDynamicArray')
        );

        $dispatcher->listen('chat.webhook_incoming_chat_started', array(
            $this,'incommingChatStarted')
        );

        // Operators handling chats from their telegram account
        $settings = $this->settings;
        if (!isset($settings['disable_op_flow']) || $settings['disable_op_flow'] !== true) {
            \LiveHelperChatExtension\lhctelegram\providers\TelegramLiveHelperChatOperator::registerListeners($dispatcher);
        }
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
                    $settings = \erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram')->settings;
                    if (!isset($settings['disable_leads']) || $settings['disable_leads'] !== true) {
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
