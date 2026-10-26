<?php
#[\AllowDynamicProperties]
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
            'dep_id' => $this->dep_id,
            'topic_icon' => $this->topic_icon,
            'topic_label' => $this->topic_label
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

    // Forum topic icon of the department: custom emoji id or "color:<value>" (providers/TelegramTopicIcons.php)
    public $topic_icon = '';

    // Start of the topic name instead of "[Department]", e.g. "TG" → "TG · Nick #12 …" (the letter of a coloured icon)
    public $topic_label = '';
}

?>