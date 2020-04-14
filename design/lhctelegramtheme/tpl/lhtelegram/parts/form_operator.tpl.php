<div class="form-group">
    <label><input type="checkbox" name="confirmed" <?php echo $item->confirmed == 1 ? print 'checked="checked"' : ''?> > <?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Confirmed');?></label>
    <p><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Do not check this unless you know what you are doing. This value will be automatically checked once you confirm your operator from telegram.')?></p>
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Operator');?>*</label>
    <?php echo erLhcoreClassRenderHelper::renderCombobox(array(
        'input_name'     => 'user_id',
        'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select operator'),
        'selected_id'    => $item->user_id,
        'css_class'      => 'form-control',
        'display_name'   => 'name_official',
        'list_function'  => 'erLhcoreClassModelUser::getUserList',
        'list_function_params'  => array(),
    )); ?>
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot');?>*</label>
    <?php echo erLhcoreClassRenderHelper::renderCombobox(array(
        'input_name'     => 'bot_id',
        'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select bot'),
        'selected_id'    => $item->bot_id,
        'css_class'      => 'form-control',
        'display_name'   => 'bot_username',
        'list_function'  => 'erLhcoreClassModelTelegramBot::getList',
        'list_function_params'  => array('filter' => array('bot_client' => 1)),
    )); ?>
</div>