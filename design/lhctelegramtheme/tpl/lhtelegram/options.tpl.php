<h1 class="attr-header">Telegram Options</h1>

<form action="" method="post">

    <?php include(erLhcoreClassDesign::designtpl('lhkernel/csfr_token.tpl.php'));?>

    <?php if (isset($updated) && $updated == 'done') : $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('chat/onlineusers','Settings updated'); ?>
        <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
    <?php endif; ?>

    <div class="form-group">
        <label><input type="checkbox" value="on" name="new_chat" <?php isset($t_options['new_chat']) && ($t_options['new_chat'] == true) ? print 'checked="checked"' : ''?> /> Create a new chat if chat was closed</label><br/>
    </div>

    <div class="form-group">
        <label><input type="checkbox" value="on" name="block_bot" <?php isset($t_options['block_bot']) && ($t_options['block_bot'] == true) ? print 'checked="checked"' : ''?> /> Block bot, chat will be never forwarded to bot</label><br/>
    </div>

    <div class="form-group">
        <label>Priority</label>
        <input class="form-control" type="text" name="priority" value="<?php (isset($t_options['priority'])) ? print htmlspecialchars($t_options['priority']) : print 0?>" />
        <p><i><small>Set what priority chat's should get. The lower the lower priority. Settings priority to (-1) will make them appear at the bottom of pending chats list.</small></i></p>
    </div>

    <div class="form-group">
        <label><input type="checkbox" value="on" name="exclude_workflow" <?php isset($t_options['exclude_workflow']) && ($t_options['exclude_workflow'] == true) ? print 'checked="checked"' : ''?> /> Exclude chats from auto assign timeout workflow.</label>
        <p><i><small>Chat's won't participate in "Chats waiting in pending queue more than n seconds should be auto-assigned first."</small></i></p>
    </div>

    <div class="form-group">
        <label><input type="checkbox" value="on" name="chat_attr" <?php isset($t_options['chat_attr']) && ($t_options['chat_attr'] == true) ? print 'checked="checked"' : ''?> />Do not store telegram customer name and surname as chat nick</label>
        <p><i><small>Telegram customer name and surname will be shown as chat attributes.</small></i></p>
        <br/>
    </div>

    <input type="submit" class="btn btn-secondary" name="StoreOptions" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Save'); ?>" />

</form>
