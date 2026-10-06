<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot username');?>*</label>
    <input type="text" maxlength="50" class="form-control" name="bot_username" value="<?php echo htmlspecialchars($item->bot_username)?>" />
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot API');?>*</label>
    <input type="text" maxlength="50" class="form-control" name="bot_api" value="<?php echo htmlspecialchars($item->bot_api)?>" />
</div>

<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Department');?>*</label>
    <?php /*echo erLhcoreClassRenderHelper::renderCombobox(array(
            'input_name'     => 'dep_id',
    		'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select department'),
            'selected_id'    => $item->dep_id,
            'css_class'      => 'form-control',
            'list_function'  => 'erLhcoreClassModelDepartament::getList',
            'list_function_params'  => array(),
    ));*/ ?>

    <?php echo erLhcoreClassRenderHelper::renderMultiDropdown( array (
        'input_name'     => 'dep_id',
        'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select department'),
        'selected_id'    => [(int)$item->dep_id],
        'type'           => 'radio',
        'data_prop'      => 'data-limit="1"',
        'css_class'      => 'form-control',
        'display_name'   => 'name',
        'no_selector'    => true,
        'ajax'           => 'deps',
        'wrapper_class'  => 'dep-dropdown',
        'list_function_params' => array('limit' => 10, 'sort' => '`name` ASC'),
        'list_function'  => 'erLhcoreClassModelDepartament::getList',
    )); ?>
    <script>
    $(function() {
        $('.dep-dropdown').makeDropdown();
    });
</script>

</div>