<?php

class erLhcoreClassModelTelegramOperator
{
    use erLhcoreClassDBTrait;

    public static $dbTable = 'lhc_telegram_operator';

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
            'tuser_id' => $this->tuser_id,
            'user_id' => $this->user_id,
            'confirmed' => $this->confirmed
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

    public $tchat_id = 0;

    public $chat_id = 0;

    public $tuser_id = 0;

    public $user_id = null;

    public $confirmed = 0;
}

?>