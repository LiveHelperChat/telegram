<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Edit bot');?></h1>

<?php if (isset($errors)) : ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/validation_error.tpl.php'));?>
<?php endif; ?>

<?php if (isset($status) && $status == 'updated') : ?>
    <?php $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Updated!'); ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
<?php endif; ?>

<?php if (isset($status) && $status == 'removed') : ?>
    <?php $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Removed!'); ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
<?php endif; ?>

<?php if (isset($status) && $status == 'saved') : ?>
    <?php $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Saved!'); ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
<?php endif; ?>


<ul class="nav nav-pills" role="tablist">
    <li role="presentation" class="nav-item"><a class="nav-link" href="<?php echo erLhcoreClassDesign::baseurl('telegram/edit')?>/<?php echo $item->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Bot');?></a></li>
    <li role="presentation" class="nav-item"><a class="nav-link active" href="#signature" role="tab" data-toggle="tab" ><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Signature');?></a></li>
    <li role="presentation" class="nav-item"><a class="nav-link" href="<?php echo erLhcoreClassDesign::baseurl('telegram/editdepartments')?>/<?php echo $item->id?>" ><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Departments');?></a></li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="signature">

        <?php if (!empty($signatures)) : ?>
        <h3>Existing signatures</h3>

        <table class="table">
            <tr>
                <th>User</th>
                <th>Signature</th>
                <th width="1%"></th>
                <th width="1%"></th>
            </tr>
            <?php foreach ($signatures as $signature) : ?>
            <tr>
                <td><?php echo htmlspecialchars($signature->user)?></td>
                <td><?php echo erLhcoreClassDesign::shrt($signature->signature,30,'...',30,ENT_QUOTES);?></td>
                <td><a class="btn btn-secondary btn-xs" href="<?php echo erLhcoreClassDesign::baseurl('telegram/editsignature')?>/<?php echo $item->id?>/(action)/edit/(itemid)/<?php echo $signature->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Edit');?></a></td>
                <td><a class="btn btn-danger btn-xs csfr-required" onclick="return confirm('<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('kernel/messages','Are you sure?');?>')" href="<?php echo erLhcoreClassDesign::baseurl('telegram/editsignature')?>/<?php echo $item->id?>/(action)/delete/(itemid)/<?php echo $signature->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Delete');?></a></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php include(erLhcoreClassDesign::designtpl('lhkernel/secure_links.tpl.php')); ?>

        <?php if (isset($pages)) : ?>
            <?php include(erLhcoreClassDesign::designtpl('lhkernel/paginator.tpl.php')); ?>
        <?php endif;?>

        <?php if ($newSignature->id == null) : ?>
            <h3>New signature</h3>
        <?php else : ?>
            <h3>Edit signature</h3>
        <?php endif; ?>
        <form action="<?php echo erLhcoreClassDesign::baseurl('telegram/editsignature')?>/<?php echo $item->id?><?php echo htmlspecialchars($prependURLEdit)?>" method="post">
            <div class="form-group">
                <label>User</label>
                <?php echo erLhcoreClassRenderHelper::renderCombobox( array (
                    'input_name'     => 'user_id',
                    'optional_field' => erTranslationClassLhTranslation::getInstance()->getTranslation('chat/lists/search_panel','Select operator'),
                    'selected_id'    => $newSignature->user_id,
                    'css_class'      => 'form-control input-sm',
                    'display_name' => 'name_official',
                    'list_function'  => 'erLhcoreClassModelUser::getUserList'
                )); ?>
            </div>

            <div class="form-group">
                <label>Signature</label>
                <textarea name="signature" rows="5" class="form-control"><?php echo htmlspecialchars(preg_replace("[\n]","\n\r",$newSignature->signature,1))?></textarea>
            </div>

            <input type="submit" class="btn btn-secondary" name="SaveAction" value="Save">
        </form>

    </div>
</div>