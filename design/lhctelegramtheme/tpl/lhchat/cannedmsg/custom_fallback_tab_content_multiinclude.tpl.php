<div role="tabpanel" class="tab-pane" id="main-extension-tel">
    <div class="form-group">
        <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/cannedmsg','Message');?></label>
        <textarea class="form-control" name="MessageExtTel"><?php echo isset($canned_message->additional_data_array['message_tel']) ? $canned_message->additional_data_array['message_tel'] : '';?></textarea>
    </div>
    <div class="form-group">
        <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/cannedmsg','Fallback message');?></label>
        <textarea class="form-control" name="FallbackMessageExtTel"><?php echo isset($canned_message->additional_data_array['fallback_tel']) ? $canned_message->additional_data_array['fallback_tel'] : '';?></textarea>
    </div>
</div>