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
		
		$dispatcher->listen('instance.destroyed', array(
		    $this,
		    'instanceDestroyed'
		));		
	}

	public function registerAutoload() {
		spl_autoload_register ( array (
				$this,
				'autoload'
		), true, false );
	}
	
	public function autoload($className) {
		$classesArray = array (
				'erLhcoreClassModelTelegramBot'  => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegrambot.php',
				'erLhcoreClassModelTelegramChat'  => 'extension/lhctelegram/classes/erlhcoreclassmodeltelegramchat.php',
				'erLhcoreClassTelegramValidator'=> 'extension/lhctelegram/classes/erlhcoreclasstelegramvalidator.php'
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
	             
	            $data = [
	                'chat_id' => $tChat->tchat_id,
	                'text'    => $params['msg']->msg,
	            ];
	          
	            $telegram = new Longman\TelegramBot\Telegram($tChat->bot->bot_api, $tChat->bot->bot_username);
	            Longman\TelegramBot\Request::sendMessage($data);
	            
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


