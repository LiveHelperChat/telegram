<div role="tabpanel" class="tab-pane" id="main-extension-lang-tel-{{$index}}">
    <div class="form-group">
        <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/cannedmsg','Message');?></label>
        <textarea class="form-control" name="message_lang_tel[{{$index}}]" ng-model="lang.message_lang_tel"></textarea>
    </div>
    <div class="form-group">
        <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/cannedmsg','Fallback message');?></label>
        <textarea class="form-control" name="fallback_message_lang_tel[{{$index}}]" ng-model="lang.fallback_message_lang_tel"></textarea>
    </div>
</div>