<div class="col-xs-2">
    <div class="form-group">
        <label>Telegram chat</label>
        <?php $extTel = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionLhctelegram'); ?>
        <input type="checkbox" name="<?php echo $extTel->settings['elastic_search']['search_attr']?>" value="1" <?php if ($input->{$extTel->settings['elastic_search']['search_attr']} == 1) : ?>checked="checked"<?php endif;?> >
    </div>
</div>