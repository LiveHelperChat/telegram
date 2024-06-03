<?php
#[\AllowDynamicProperties]
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
            'bot_disabled' => $this->bot_disabled,
            'group_chat_id' => $this->group_chat_id
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
                if ($this->bot_client == 1) {
                    $this->callback_url = erLhcoreClassXMP::getBaseHost() . $_SERVER['HTTP_HOST'] . erLhcoreClassDesign::baseurldirect(($this->site_access != '' ? '/' . $this->site_access : '') . '/telegram/callback'). '/' . $this->id;
                } else {
                    $this->callback_url = null;
                    $callbackData = erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'TelegramIntegration']]);
                    if (is_object($callbackData)) {
                        $this->callback_url = erLhcoreClassSystem::getHost() . erLhcoreClassDesign::baseurldirect( ($this->site_access != '' ? '/' . $this->site_access : '') . '/webhooks/incoming') . '/' . $callbackData->identifier . '?telegram_bot_id=' . $this->id;
                    }
                }
                return $this->callback_url;

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

    public $site_access = null;

    public $bot_username = null;

    public $bot_api = null;
    
    public $bot_client = 0;

    public $dep_id = null;

    public $webhook_set = 0;

    public $bot_disabled = 0;
    public $group_chat_id = 0;

    public $chat_timeout = 259200;
}

?>
