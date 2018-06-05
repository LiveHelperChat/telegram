<?php

class erLhcoreClassModelTelegramBot
{
	use erLhcoreClassDBTrait;

	public static $dbTable = 'lhc_telegram_bot';

	public static $dbTableId = 'id';

	public static $dbSessionHandler = 'erLhcoreClassExtensionLhctelegram::getSession';

	public static $dbSortOrder = 'DESC';

    public function getState()
    {
        return array(
            'id' => $this->id,
            'bot_username' => $this->bot_username,
            'webhook_set' => $this->webhook_set,
            'bot_api' => $this->bot_api,
            'dep_id' => $this->dep_id,
            'bot_client' => $this->bot_client,
            'chat_timeout' => $this->chat_timeout,
            'bot_disabled' => $this->bot_disabled
        );
    }  

    public function __toString()
    {
        return $this->bot_username;
    }

    public function __get($var)
    {
        switch ($var) {
                
            case 'callback_url':
                $this->callback_url = erLhcoreClassXMP::getBaseHost() . $_SERVER['HTTP_HOST'] . erLhcoreClassDesign::baseurldirect('telegram/callback'). '/' . $this->id;
                return $this->callback_url;
                break;
                
            default:
                ;
                break;
        }
    }

    public function getDepartments()
    {
        $items = erLhcoreClassModelTelegramBotDep::getList(array('filter' => array('bot_id' => $this->id)));
        $returnItems = array();
        foreach ($items as $item) {
            $returnItems[$item->dep_id] = $item;
        }
        
        return $returnItems;
    }

    /**
     * Delete page chat's
     */
    public function beforeRemove()
    {
        $q = ezcDbInstance::get()->createDeleteQuery();
        $q->deleteFrom('lhc_telegram_chat')->where($q->expr->eq('bot_id', ezcDbInstance::get()->quote($this->id)));
        $stmt = $q->prepare();
        $stmt->execute();
    }

    public $id = null;

    public $bot_username = null;

    public $bot_api = null;
    
    public $bot_client = 0;

    public $dep_id = null;

    public $webhook_set = 0;

    public $bot_disabled = 0;

    public $chat_timeout = 259200;
}

?>
