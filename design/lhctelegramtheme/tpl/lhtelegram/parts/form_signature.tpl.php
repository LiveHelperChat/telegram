
<div class="form-group">
    <label>Bot</label>
    <?php echo erLhcoreClassRenderHelper::renderCombobox( array (
        'input_name'     => 'bot_id',
        'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','All bots'),
        'selected_id'    => $item->bot_id,
        'css_class'      => 'form-control input-sm',
        'display_name' => 'bot_username',
        'list_function'  => 'erLhcoreClassModelTelegramBot::getList'
    )); ?>
</div>

<div class="form-group">
    <label>User *</label>
    <?php echo erLhcoreClassRenderHelper::renderCombobox( array (
        'input_name'     => 'user_id',
        'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select operator'),
        'selected_id'    => $item->user_id,
        'css_class'      => 'form-control input-sm',
        'display_name' => 'name_official',
        'list_function'  => 'erLhcoreClassModelUser::getUserList'
    )); ?>
</div>

<div class="form-group">
    <label>Signature *</label>
    <textarea name="signature" rows="5" class="form-control"><?php echo htmlspecialchars(preg_replace("[\n]","\n\r",$item->signature,1))?></textarea>
</div>