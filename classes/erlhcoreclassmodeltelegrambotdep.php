<?php

class erLhcoreClassModelTelegramBotDep
{
    use erLhcoreClassDBTrait;

    public static $dbTable = 'lhc_telegram_bot_dep';

    public static $dbTableId = 'id';

    public static $dbSessionHandler = 'erLhcoreClassExtensionLhctelegram::getSession';

    public static $dbSortOrder = 'DESC';

    public function getState()
    {
        return array(
            'id' => $this->id,
            'bot_id' => $this->bot_id,
            'dep_id' => $this->dep_id
        );
    }

    public function __get($var)
    {
        switch ($var) {

            case 'bot':
                $this->bot = erLhcoreClassModelTelegramBot::fetch($this->bot_id);
                return $this->bot;
                break;

            default:
                ;
                break;
        }
    }

    public $id = null;

    public $bot_id = null;

    public $dep_id = null;
}

?>