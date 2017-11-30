<?php

class erLhcoreClassModelTelegramSignature
{
    use erLhcoreClassDBTrait;

    public static $dbTable = 'lhc_telegram_signature';

    public static $dbTableId = 'id';

    public static $dbSessionHandler = 'erLhcoreClassExtensionLhctelegram::getSession';

    public static $dbSortOrder = 'DESC';

    public function getState()
    {
        return array(
            'id' => $this->id,
            'bot_id' => $this->bot_id,
            'user_id' => $this->user_id,
            'signature' => $this->signature
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

            case 'user':
                $this->user = erLhcoreClassModelUser::fetch($this->user_id);
                return $this->user;
                break;

            default:
                ;
                break;
        }
    }

    public $id = null;

    public $bot_id = null;

    public $user_id = null;

    public $signature = null;
}

?>