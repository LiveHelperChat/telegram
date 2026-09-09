<?php
$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/index.tpl.php');

$settings = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram')->settings;
$tpl->set('disable_leads', isset($settings['disable_leads']) && $settings['disable_leads'] === true);

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array(
        'url' => erLhcoreClassDesign::baseurl('telegram/index'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Telegram')
    )
);

?>