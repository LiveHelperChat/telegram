<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/setwebhook.tpl.php');

$item =  erLhcoreClassModelTelegramBot::fetch($Params['user_parameters']['id']);

try {

    if (!isset($_POST['csfr_token']) || !$currentUser->validateCSFRToken($_POST['csfr_token'])) {
        erLhcoreClassModule::redirect('telegram/list');
        exit;
    }

    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($item->bot_api, $item->bot_username);

    $locates = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'available_site_access' );

    if (isset($_POST['site_access']) && !empty($_POST['site_access']) && in_array($_POST['site_access'], $locates)) {
        $item->site_access = $_POST['site_access'];
    }

    // Set webhook
    $result = $telegram->setWebhook($item->callback_url,['allowed_updates' => ['message','callback_query','message_reaction','edited_message','inline_query']]);
    if ($result->isOk()) {
        $item->webhook_set = 1;
        $item->saveThis();
        $tpl->set('msg', $result->getDescription());
        $tpl->set('updated', true);
    }

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    $tpl->set('errors', array($e->getMessage()));
}
$tpl->set('item', $item);
$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array('url' =>erLhcoreClassDesign::baseurl('telegram/index'), 'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram')),
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/list'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots')
    ),
    array (
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Set webhook')
    )
);

?>