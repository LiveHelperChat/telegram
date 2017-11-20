<?php

class erLhcoreClassModelTelegramChat
{
	use erLhcoreClassDBTrait;

	public static $dbTable = 'lhc_telegram_chat';

	public static $dbTableId = 'id';

	public static $dbSessionHandler = 'erLhcoreClassExtensionLhctelegram::getSession';

	public static $dbSortOrder = 'DESC';

    public function getState()
    {
        return array(
            'id' => $this->id,
            'bot_id' => $this->bot_id,
            'tchat_id' => $this->tchat_id,
            'chat_id' => $this->chat_id,
            'ctime' => $this->ctime,
            'utime' => $this->utime
        );
    }

    public function __toString()
    {
        return $this->bot;
    }

    public function __get($var)
    {
        switch ($var) {
            
            case 'bot':
                $this->bot = erLhcoreClassModelTelegramBot::fetch($this->bot_id);
                return $this->bot;
                break;
                
            case 'chat':
                $this->chat = erLhcoreClassModelChat::fetch($this->chat_id);
                return $this->chat;
                break;
                
            default:
                ;
                break;
        }
    }

    public $id = null;
    
    public $bot_id = null;

    public $tchat_id = null;
    
    public $chat_id = null;
    
    public $ctime = null;
    
    public $utime = null;

}

?>