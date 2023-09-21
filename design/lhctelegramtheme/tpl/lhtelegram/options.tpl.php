<h1 class="attr-header">Telegram Options</h1>

<form action="" method="post" ng-non-bindable>

    <?php include(erLhcoreClassDesign::designtpl('lhkernel/csfr_token.tpl.php'));?>

    <?php if (isset($updated) && $updated == 'done') : $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/onlineusers','Settings updated'); ?>
        <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
    <?php endif; ?>

    <p>You will see what information you have to put</p>

    <h6>Incoming webhook</h6>
    <?php if ($incomingWebhook = erLhcoreClassModelChatIncomingWebhook::findOne(['filter' => ['name' => 'TelegramIntegration']])) : ?>
        <p class="text-success">Exists</p>
        <div class="form-group">
            <label>Callback URL for Telegram integration</label>
            <input readonly type="text" class="form-control form-control-sm" value="https://<?php echo $_SERVER['HTTP_HOST']?><?php echo erLhcoreClassDesign::baseurl('webhooks/incoming')?>/<?php echo htmlspecialchars($incomingWebhook->identifier)?>">
        </div>
    <?php else : ?>
        <p class="text-danger">Missing</p>
    <?php endif; ?>

    <h6>Rest API configuration</h6>
    
    <?php if (erLhcoreClassModelGenericBotRestAPI::getCount(['filter' => ['name' => 'TelegramIntegration']]) > 0) : ?>
        <p class="text-success">Exists</p>
    <?php else : ?>
        <p class="text-danger">Missing</p>
    <?php endif; ?>

    <h6>Bot Configuration</h6>
    <?php if ($bot = erLhcoreClassModelGenericBotBot::findOne(['filter' => ['name' => 'TelegramIntegration']])) : ?>
        <p class="text-success">Exists</p>
    <?php else : ?>
        <p class="text-danger">Missing</p>
    <?php endif; ?>

    <h6>Event listeners</h6>
    <?php if ($bot && erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => 'chat.desktop_client_admin_msg', 'bot_id' => $bot->id]])) : ?>
        <p class="text-success">chat.desktop_client_admin_msg</p>
    <?php else : ?>
        <p class="text-danger">chat.desktop_client_admin_msg</p>
    <?php endif; ?>

    <?php if ($bot && erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => 'chat.web_add_msg_admin', 'bot_id' => $bot->id]])) : ?>
        <p class="text-success">chat.web_add_msg_admin</p>
    <?php else : ?>
        <p class="text-danger">chat.web_add_msg_admin</p>
    <?php endif; ?>

    <?php if ($bot && erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => 'chat.workflow.canned_message_before_save', 'bot_id' => $bot->id]])) : ?>
        <p class="text-success">chat.workflow.canned_message_before_save</p>
    <?php else : ?>
        <p class="text-danger">chat.workflow.canned_message_before_save</p>
    <?php endif; ?>

    <?php if ($bot && erLhcoreClassModelChatWebhook::findOne(['filter' => ['event' => 'chat.before_auto_responder_msg_saved', 'bot_id' => $bot->id]])) : ?>
        <p class="text-success">chat.before_auto_responder_msg_saved</p>
    <?php else : ?>
        <p class="text-danger">chat.before_auto_responder_msg_saved</p>
    <?php endif; ?>

    <input type="submit" class="btn btn-secondary" name="StoreOptionsTelegram" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Save and Activate Telegram configuration'); ?>" />&nbsp;
    <input type="submit" class="btn btn-warning" name="StoreOptionsTelegramRemove" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Save And Remove Telegram configuration'); ?>" />

</form>
