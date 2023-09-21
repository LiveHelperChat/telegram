<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/options.tpl.php');

if ( isset($_POST['StoreOptions']) || isset($_POST['StoreOptionsTelegram']) || isset($_POST['StoreOptionsTelegramRemove']) ) {

    $tpl->set('updated','done');

    if (isset($_POST['StoreOptionsTelegram']) ) {
        LiveHelperChatExtension\lhctelegram\providers\TelegramLiveHelperChatActivator::installOrUpdate();
    }

    if (isset($_POST['StoreOptionsTelegramRemove']) ) {
        LiveHelperChatExtension\lhctelegram\providers\TelegramLiveHelperChatActivator::remove();
    }
}

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array(
        'url' => erLhcoreClassDesign::baseurl('telegram/index'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('lhelasticsearch/module', 'Telegram')
    ),
    array(
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('lhelasticsearch/module', 'Options')
    )
);

?>