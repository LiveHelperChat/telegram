<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Edit bot');?></h1>

<?php if (isset($errors)) : ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/validation_error.tpl.php'));?>
<?php endif; ?>

<?php if (isset($status) && $status == 'updated') : ?>
    <?php $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Updated!'); ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
<?php endif; ?>

<ul class="nav nav-pills">
    <li role="presentation" class="nav-item"><a class="nav-link"href="<?php echo erLhcoreClassDesign::baseurl('telegram/edit')?>/<?php echo $item->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Bot');?></a></li>
    <li role="presentation" class="nav-item"><a class="nav-link"href="<?php echo erLhcoreClassDesign::baseurl('telegram/editsignature')?>/<?php echo $item->id?>" ><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Signature');?></a></li>
    <li role="presentation"  class="nav-item"><a class="active nav-link" href="#bot" ><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Departments');?></a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="bot">

        <form action="<?php echo erLhcoreClassDesign::baseurl('telegram/editdepartments')?>/<?php echo $item->id?>" method="post">
            <?php include(erLhcoreClassDesign::designtpl('lhtelegram/parts/form_department.tpl.php'));?>
            <div class="btn-group" role="group" aria-label="...">
                <input type="submit" class="btn btn-secondary" name="Save_page" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Save');?>"/>
                <input type="submit" class="btn btn-secondary" name="Cancel_page" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Cancel');?>"/>
            </div>
        </form>

    </div>
</div>