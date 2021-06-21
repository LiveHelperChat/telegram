<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','New bot');?></h1>

<?php if (isset($errors)) : ?>
	<?php include(erLhcoreClassDesign::designtpl('lhkernel/validation_error.tpl.php'));?>
<?php endif; ?>

<form action="<?php echo erLhcoreClassDesign::baseurl('telegram/new')?>" method="post" ng-non-bindable>

	<?php include(erLhcoreClassDesign::designtpl('lhtelegram/parts/form.tpl.php'));?>
	
    <div class="btn-group" role="group" aria-label="...">
		<input type="submit" class="btn btn-secondary" name="Save_page" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Save');?>"/>
		<input type="submit" class="btn btn-secondary" name="Cancel_page" value="<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/buttons','Cancel');?>"/>
	</div>
	
</form>