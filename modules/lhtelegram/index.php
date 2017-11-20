<?php
$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/index.tpl.php');

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array(
        'url' => erLhcoreClassDesign::baseurl('telegram/index'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Telegram')
    )
);

?>