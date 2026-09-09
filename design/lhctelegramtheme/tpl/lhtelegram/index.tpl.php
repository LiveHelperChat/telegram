<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/twilio','Telegram');?></h1>

<ul>
    <li><a href="<?php echo erLhcoreClassDesign::baseurl('telegram/list')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots');?></a></li>
    <li><a href="<?php echo erLhcoreClassDesign::baseurl('telegram/operators')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram operators');?></a></li>
    <li><a href="<?php echo erLhcoreClassDesign::baseurl('telegram/options')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Options');?></a></li>
    <?php if (!isset($disable_leads) || $disable_leads == false) : ?>
    <li><a href="<?php echo erLhcoreClassDesign::baseurl('telegram/leads')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Leads');?></a></li>
    <?php endif; ?>
</ul>