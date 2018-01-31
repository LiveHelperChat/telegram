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
                    $statusSignature = erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.get_signature',array('user_id' => erLhcoreClassUser::instance()->getUserID(), 'bot_id' => $tChat->bot_id));

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

	            // General module signal that it has send an sms
	            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('telegram.msg_send_to_user',array('chat' => & $params['chat']));
	            
	            // If operator has closed a chat we need force back office sync
	            erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.nodjshelper_notify_delay', array(
	                'chat' => & $params['chat']
	            ));
	            
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


