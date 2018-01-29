<?php
/**
 * php cron.php -s site_admin -e lhctelegram -c cron/test
 * */

$chat = erLhcoreClassModelChat::fetch(7373);
$msg = erLhcoreClassModelmsg::fetch(23355);

erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.chat_started',array('chat' => & $chat, 'msg' => $msg));

?>