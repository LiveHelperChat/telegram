<?php if (erLhcoreClassUser::instance()->hasAccessTo('lhtelegram','use_admin')) : ?>
<li class="nav-item"><a class="nav-link" href="<?php echo erLhcoreClassDesign::baseurl('telegram/index')?>"><i class="material-icons">textsms</i><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('pagelayout/pagelayout','Telegram settings');?></a></li>
<?php endif; ?>