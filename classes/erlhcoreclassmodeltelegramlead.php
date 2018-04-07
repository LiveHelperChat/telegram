<?php

class erLhcoreClassModelTelegramLead
{
    use erLhcoreClassDBTrait;

    public static $dbTable = 'lhc_telegram_lead';

    public static $dbTableId = 'id';

    public static $dbSessionHandler = 'erLhcoreClassExtensionLhctelegram::getSession';

    public static $dbSortOrder = 'DESC';

    public function getState()
    {
        return array(
            'id' => $this->id,
            'language_code' => $this->language_code,
            'username' => $this->username,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'utime' => $this->utime,
            'ctime' => $this->ctime,
            'tbot_id' => $this->tbot_id,
            'dep_id' => $this->dep_id,
            'tchat_id' => $this->tchat_id
        );
    }

    public function __toString()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function __get($var)
    {
        switch ($var) {

            case 'tbot':
                    $this->tbot = erLhcoreClassModelTelegramBot::fetch($this->tbot_id);
                    return $this->tbot;
                break;

            case 'dep':
                    $this->dep = erLhcoreClassModelDepartament::fetch($this->dep_id);
                    return $this->dep;
                break;

            default:
                ;
                break;
        }
    }

    public $id = null;

    public $language_code = null;

    public $username = null;

    public $last_name = null;

    public $first_name = null;

    public $utime = null;

    public $ctime = null;

    public $tbot_id = null;

    public $dep_id = null;

    public $tchat_id = null;
}

?>