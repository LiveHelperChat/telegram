<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot username');?>*</label>
    <input type="text" maxlength="50" class="form-control" name="bot_username" value="<?php echo htmlspecialchars($item->bot_username)?>" />
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot API');?>*</label>
    <input type="text" maxlength="50" class="form-control" name="bot_api" value="<?php echo htmlspecialchars($item->bot_api)?>" />
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Chat timeout');?>*</label>
    <input type="text" maxlength="35" class="form-control" name="chat_timeout" value="<?php echo htmlspecialchars($item->chat_timeout)?>" />
    <p><i><small>How long chat is considered existing before new chat is created</small></i></p>
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Department');?>*</label>
    <?php echo erLhcoreClassRenderHelper::renderCombobox(array(
            'input_name'     => 'dep_id',
    		'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select department'),
            'selected_id'    => $item->dep_id,
            'css_class'      => 'form-control',
            'list_function'  => 'erLhcoreClassModelDepartament::getList',
            'list_function_params'  => array(),
    )); ?>
</div>

<div class="form-group">
    <label><input type="checkbox" value="on" name="bot_disabled" <?php $item->bot_disabled == 1 ? print 'checked="checked"' : ''?> /> Block bot, chat will be never forwarded to bot</label><br/>
</div>