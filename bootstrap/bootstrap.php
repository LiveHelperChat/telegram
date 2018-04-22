<?php
class erLhcoreClassExtensionLhctelegram {
    
	public function __construct() {
	    
	}
	
	public function run() {
		$this->registerAutoload ();
		
		include_once 'extension/lhctelegram/vendor/autoload.php';
		
		$dispatcher = erLhcoreClassChatEventDispatcher::getInstance();
		
		$dispatcher->listen('chat.web_add_msg_admin', array(
		    $this,
		    'sendMessageToTelegram'
		));

		$dispatcher->listen('chat.auto_preload', array(
		    $this,
		    'autoPreload'
		));
		
		$dispatcher->listen('chat.desktop_client_admin_msg', array(
		    $this,
		    'sendMessageToTelegram'
		));
		
		$dispatcher->listen('chat.workflow.canned_message_before_save', array(
		    $this,
		    'sendMessageToTelegram'
		));
		
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
		    'messageAdded'
		));

		$dispatcher->listen('chat.messages_added_fb', array(
		    $this,
		    'messageAdded'
		));

		$dispatcher->listen('chat.addmsguser', array(
		    $this,
		    'messageAdded'
		));

		$dispatcher->listen('telegram.msg_received', array(
		    $this,
		    'messageAdded'
		));

        $dispatcher->listen('chat.workflow.autoassign', array(
            $this,
            'autoAssignBlock'
        ));

        // Elastic Search
        $dispatcher->listen('system.getelasticstructure', array(
                $this,'getElasticStructure')
        );

        $dispatcher->listen('elasticsearch.indexchat', array(
                $this,'indexChat')
        );

        $dispatcher->listen('elasticsearch.getstate', array(
                $this,'getState')
        );

        $dispatcher->listen('elasticsearch.getpreviouschats', array(
                $this, 'getPreviousChatsFilter')
        );

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
	}

	// Always auto preload telegram chats
	public function autoPreload($params) {

        $chatVariables = $params['chat']->chat_variables_array;

        if (isset($chatVariables['tchat']) && $chatVariables['tchat'] == 1)
        {
            $params['load_previous'] = 1;
        }
    }

    public function cannedMessageReplace($params)
    {
        $chatVariables = $params['chat']->chat_variables_array;

        if (isset($chatVariables['tchat']) && $chatVariables['tchat'] == 1)
        {
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

            'message_lang_tel' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw',null,FILTER_REQUIRE_ARRAY),
            'fallback_message_lang_tel' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw',null,FILTER_REQUIRE_ARRAY)
        );

        $form = new ezcInputForm( INPUT_POST, $definition );

        $langArray = array();
        foreach ($params['msg']->languages_array as $index => $langData) {
            $langData['message_lang_tel'] = $form->message_lang_tel[$index];
            $langData['fallback_message_lang_tel'] = $form->fallback_message_lang_tel[$index];
            $langArray[] = $langData;
        }

        $params['msg']->languages = json_encode($langArray);
        $params['msg']->languages_array = $langArray;

        // Store additional data
        $additionalArray =  $params['msg']->additional_data_array;

        if ( $form->hasValidData( 'MessageExtTel' )) {
            $additionalArray['message_tel'] = $form->MessageExtTel;
        }

        if ( $form->hasValidData( 'FallbackMessageExtTel' ) )
        {
            $additionalArray['fallback_tel'] = $form->FallbackMessageExtTel;
        }

        $params['msg']->additional_data = json_encode($additionalArray);
        $params['msg']->additional_data_array = $additionalArray;
    }

    /**
     *
     * If user disabled auto assign for timed out assign wrofklows
     *
     * @param array $params
     */
    public function autoAssignBlock($params) {
        if (isset($params['chat']) && isset($params['params']['auto_assign_timeout']) && $params['params']['auto_assign_timeout'] == true) {
            $chatVariables = $params['chat']->chat_variables_array;
            if (isset($chatVariables['tchat']) && $chatVariables['tchat'] == 1)
            {
                $tOptions = erLhcoreClassModelChatConfig::fetch('telegram_options');
                $data = (array)$tOptions->data;
                if (isset($data['exclude_workflow']) && $data['exclude_workflow'] == true)
                {
                    return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'user_id' => 0); // Do nothing if it was executed
                }
            }
        }
    }

    public function getPreviousChatsFilter($params)
    {
        $chatVariables = json_decode($params['chat']->chat_variables,true);

        if (isset($chatVariables['tchat']) && $chatVariables['tchat'] == 1 && isset($chatVariables['tchat_raw_id']) && is_numeric($chatVariables['tchat_raw_id']))
        {
            $params['sparams']['body']['query']['bool']['must'][]['term']['tchat_raw_id'] = (int)$chatVariables['tchat_raw_id'];
        }
    }

    // Get elastic structure
    public function getElasticStructure($params)
    {
        $params['structure'][(isset($params['index_new']) ? $params['index_new'] : 'chat')]['types']['lh_chat']['tchat_raw_id'] = array('type' => 'long');
        $params['structure'][(isset($params['index_new']) ? $params['index_new'] : 'chat')]['types']['lh_chat']['tbot_id'] = array('type' => 'long');
    }


    // Index chat
    public function indexChat($params)
    {
        $chatVariables = json_decode($params['chat']->chat_variables,true);

        if (isset($chatVariables['tchat']) && $chatVariables['tchat'] == 1 && isset($chatVariables['tchat_raw_id']) && isset($chatVariables['tchat_raw_id']))
        {
            $params['chat']->tchat_raw_id = $chatVariables['tchat_raw_id'];
            $params['chat']->tbot_id = isset($chatVariables['tbot_id']) ? $chatVariables['tbot_id'] : 0;
        }
    }

    public function getState($params)
    {
        if (isset($params['chat']->tchat_raw_id) && is_numeric($params['chat']->tchat_raw_id)) {
            $params['state']['tchat_raw_id'] = $params['chat']->tchat_raw_id;
        } else {
            $params['state']['tchat_raw_id'] = 0;
        }

        if (isset($params['chat']->tbot_id) && is_numeric($params['chat']->tbot_id)) {
            $params['state']['tbot_id'] = $params['chat']->tbot_id;
        } else {
            $params['state']['tbot_id'] = 0;
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

	public function messageAdded($params)
    {
        $chat = $params['chat'];

        $variablesArray = $chat->chat_variables_array;

        if (isset($variablesArray['telegram_chat_op']) && is_numeric($variablesArray['telegram_chat_op'])) {

            $operator = erLhcoreClassModelTelegramOperator::fetch($variablesArray['telegram_chat_op']);

            if ($operator instanceof erLhcoreClassModelTelegramOperator && $operator->confirmed == 1) {
                if (!isset($params['sender']) || $params['sender'] == 'bot_visitor')
                {
                    $telegram = new Longman\TelegramBot\Telegram($operator->bot->bot_api, $operator->bot->bot_username);

                    $data = [
                        'chat_id' => $operator->tchat_id,
                        'text'    => trim( ($params['msg']->user_id == 0 ? $params['chat']->nick . ': ' : '') . ($params['msg']->name_support . ' ' . $params['msg']->msg))
                    ];

                    if ($operator->chat_id != $chat->id) {
                        $inlineKeyboards = array();
                        $inlineKeyboards[] = ['text' => "Reply to [{$chat->id}] $chat->nick", 'callback_data' => 'switch_chat||' . $chat->id];
                        $inline_keyboard = new Longman\TelegramBot\Entities\InlineKeyboard($inlineKeyboards);
                        $data['reply_markup'] = $inline_keyboard;
                    }

                    Longman\TelegramBot\Request::sendMessage($data);

                } else {
                    $params['ignore_callback'] = true;
                    $params['telegram_user_id'] = $operator->user_id;
                    $this->sendMessageToTelegram($params);
                }
            }
        }
    }

	public function chatStarted($params)
    {
        $bots = erLhcoreClassModelTelegramBotDep::getList(array('filter' => array('dep_id' => $params['chat']->dep_id)));

        foreach ($bots as $bot) {
            if ($bot->bot instanceof erLhcoreClassModelTelegramBot && $bot->bot->bot_client == 1) {
                $operators = erLhcoreClassModelTelegramOperator::getList(array('filter' => array('bot_id' => $bot->bot->id)));
                foreach ($operators as $operator) {
                    if ($operator->user->hide_online == 0) {

                        $cfgSite = erConfigClassLhConfig::getInstance();
                        $secretHash = $cfgSite->getSetting( 'site', 'secrethash' );

                        // Set internal variables
                        $telegram = new Longman\TelegramBot\Telegram($bot->bot->bot_api, $bot->bot->bot_username);

                        $visitor = array();
                        $visitor[] = 'New chat, ID: ' . $params['chat']->id .', Nick: ' . $params['chat']->nick;
                        $visitor[] = 'Message: *' . trim($params['msg']->msg) . '*';

                        $receiver = $operator->user->email;
                        $verifyEmail = 	sha1(sha1($receiver.$secretHash).$secretHash);
                        $url = (erLhcoreClassSystem::$httpsMode ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . erLhcoreClassDesign::baseurl('chat/accept').'/'.erLhcoreClassModelChatAccept::generateAcceptLink($params['chat']).'/'.$verifyEmail.'/'.$receiver;

                        $inline_keyboard = new Longman\TelegramBot\Entities\InlineKeyboard([
                            ['text' => 'Accept Chat', 'callback_data' => 'accept_chat||' . $params['chat']->id],
                            ['text' => 'Open url', 'url' => $url],
                        ]);

                        $data = [
                            'chat_id' => $operator->tchat_id,
                            'parse_mode' => 'MARKDOWN',
                            'text'    => implode("\n", $visitor),
                            'reply_markup' => $inline_keyboard,
                        ];

                        Longman\TelegramBot\Request::sendMessage($data);
                    }
                }
            }
        }
    }

	public function registerAutoload() {
		spl_autoload_register ( array (
				$this,
				'autoload'
		), true, false );
	}
	
	public function autoload($className) {
		$classesArray = array (
				'erLhcoreClassModelTelegramBot'         => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegrambot.php',
				'erLhcoreClassModelTelegramBotDep'      => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegrambotdep.php',
				'erLhcoreClassModelTelegramOperator'    => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramoperator.php',
				'erLhcoreClassModelTelegramChat'        => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramchat.php',
				'erLhcoreClassModelTelegramSignature'   => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramsignature.php',
				'erLhcoreClassModelTelegramLead'        => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramlead.php',
				'erLhcoreClassTelegramValidator'        => 'extension/lhctelegram/classes/erlhcoreclasstelegramvalidator.php'
		);
		
		if (key_exists ( $className, $classesArray )) {
			include_once $classesArray [$className];
		}
	}
	
	public static function getSession() {
		if (! isset ( self::$persistentSession )) {
			self::$persistentSession = new ezcPersistentSession ( ezcDbInstance::get (), new ezcPersistentCodeManager ( './extension/lhctelegram/pos' ) );
		}
		return self::$persistentSession;
	}

    /**
     * @desc delete chat if exists
     *
     * @param $params
     */
	public function deleteChat($params) {
        $db = ezcDbInstance::get();
        $stmt = $db->prepare('DELETE FROM lhc_telegram_chat WHERE chat_id = :chat_id');
        $stmt->bindValue(':chat_id', $params['chat']->id, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @desc Returns signature. Other extensions can use this callback also. E.g Twilio extension
     *
     * @param $params
     * @return array
     */
	public function getSignature($params) {

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

        return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'signature' =>'');
    }

	/**
	 * Sends SMS to user
	 *
	 * */
	public function sendMessageToTelegram($params)
	{
	    $chatVariables = $params['chat']->chat_variables_array;
	    
	    
	    // It's SMS chat we need to send a message
	    if (isset($chatVariables['tchat']) && $chatVariables['tchat'] == true) {
	        
	        try {
	            
	            $response = erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.send_msg_user', $params);
	            
	            // Check is module disabled
	            if ($response !== false && $response['status'] === erLhcoreClassChatEventDispatcher::STOP_WORKFLOW) {
	                throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('twilio/sms', 'Module is disabled for you!'));
	            }
	            
	            if ($params['msg']->msg == '') {
	                throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('twilio/sms', 'Please enter a message!'));
	            }

	            $tChat = erLhcoreClassModelTelegramChat::fetch($chatVariables['tchat_id']);

                $telegram = new Longman\TelegramBot\Telegram($tChat->bot->bot_api, $tChat->bot->bot_username);

                $images =$this->extractImages($params);

                if (trim($params['msg']->msg) !== '') {

                    $signatureText = '';

                    // General module signal that it has send an sms
                    $statusSignature = erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.get_signature',array('user_id' => (isset($params['telegram_user_id']) ? $params['telegram_user_id'] : erLhcoreClassUser::instance()->getUserID()), 'bot_id' => $tChat->bot_id));

                    if ($statusSignature !== false) {
                        $signatureText = $statusSignature['signature'];
                    }

                    $data = [
                        'chat_id' => $tChat->tchat_id,
                        'text'    => trim($params['msg']->msg) . $signatureText,
                    ];

                    Longman\TelegramBot\Request::sendMessage($data);
                }

	            foreach ($images['images'] as $image) {
                    $data = [
                        'chat_id' => $tChat->tchat_id,
                        'photo'   => $image,
                    ];

                    Longman\TelegramBot\Request::sendPhoto($data);
                }

	            foreach ($images['files'] as $doc) {
                    $data = [
                        'chat_id' => $tChat->tchat_id,
                        'document'   => $doc,
                    ];

                    Longman\TelegramBot\Request::sendDocument($data);
                }

                if (!isset($params['ignore_callback'])) {
                    // General module signal that it has send an sms
                    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_send_to_user',array('chat' => & $params['chat']));

                    // If operator has closed a chat we need force back office sync
                    erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.nodjshelper_notify_delay', array(
                        'chat' => & $params['chat']
                    ));
                }
	            
	        } catch (Exception $e) {
	            
	            $msg = new erLhcoreClassModelmsg();
	            $msg->msg = $e->getMessage();
	            $msg->chat_id = $params['chat']->id;
	            $msg->user_id = - 1;
	            $msg->time = time();
	            erLhcoreClassChat::getSession()->save($msg);
	            
	            // Update chat attributes
	            $db = ezcDbInstance::get();
	            $db->beginTransaction();
	            
	            $stmt = $db->prepare('UPDATE lh_chat SET last_user_msg_time = :last_user_msg_time, last_msg_id = :last_msg_id WHERE id = :id');
	            $stmt->bindValue(':id', $params['chat']->id, PDO::PARAM_INT);
	            $stmt->bindValue(':last_user_msg_time', $msg->time, PDO::PARAM_STR);
	            $stmt->bindValue(':last_msg_id', $msg->id, PDO::PARAM_STR);
	            $stmt->execute();
	            
	            $db->commit();
	            
	            if ($this->settings['debug'] == true) {
	                erLhcoreClassLog::write(print_r($e, true));
	            }
	        }
	    }
	}

    private function extractImages(& $params)
    {
        $matches = array();

        preg_match_all('/\[file="?(.*?)"?\]/is', $params['msg']->msg,$matches);

        $files = array();

        if (isset($matches[1])) {
            foreach ($matches[1] as $matchItem) {
                list($fileID,$hash) = explode('_',$matchItem);
                try {
                    $file = erLhcoreClassModelChatFile::fetch($fileID);
                    $files[] = $file;
                } catch (Exception $e) {

                }
            }
        }

        $params['msg']->msg = preg_replace('#\[file="?(.*?)"?\]#is', '', $params['msg']->msg);

        $images = array('images' => array(),'files' => array());

        foreach ($files as $file) {
            if (in_array($file->type,array('image/png','image/jpeg','image/gif'))) {
                $images['images'][] = erLhcoreClassXMP::getBaseHost() . $_SERVER['HTTP_HOST'] . erLhcoreClassDesign::baseurl('file/downloadfile')."/{$file->id}/{$file->security_hash}/";
            } else {
                $images['files'][] = erLhcoreClassXMP::getBaseHost() . $_SERVER['HTTP_HOST'] . erLhcoreClassDesign::baseurl('file/downloadfile')."/{$file->id}/{$file->security_hash}/" . $file->upload_name;
            }
        }

        return $images;
    }

	public function __get($var) {
		switch ($var) {
			case 'is_active' :
				return true;
				;
				break;
			
			case 'settings' :
				$this->settings = include ('extension/lhctelegram/settings/settings.ini.php');
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


