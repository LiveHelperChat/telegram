<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Signatures');?></h1>

<?php if (isset($status) && $status == 'removed') : ?>
    <?php $msg = erTranslationClassLhTranslation::getInstance()->getTranslation('user/account','Removed!'); ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
<?php endif; ?>

<?php if (!empty($items)) : ?>
    <table class="table">
        <tr>
            <th>User</th>
            <th>Signature</th>
            <th>Bot</th>
            <th width="1%"></th>
            <th width="1%"></th>
        </tr>
        <?php foreach ($items as $signature) : ?>
            <tr>
                <td><?php echo htmlspecialchars($signature->user)?></td>
                <td><?php echo erLhcoreClassDesign::shrt($signature->signature,30,'...',30,ENT_QUOTES);?></td>
                <td><?php echo htmlspecialchars($signature->bot)?></td>
                <td><a class="btn btn-secondary btn-xs" href="<?php echo erLhcoreClassDesign::baseurl('telegram/editsignatureglobal')?>/<?php echo $signature->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Edit');?></a></td>
                <td><a class="btn btn-danger btn-xs csfr-required" onclick="return confirm('<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('kernel/messages','Are you sure?');?>')" href="<?php echo erLhcoreClassDesign::baseurl('telegram/deletesignature')?>/<?php echo $signature->id?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('user/userlist','Delete');?></a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include(erLhcoreClassDesign::designtpl('lhkernel/secure_links.tpl.php')); ?>

<a class="btn btn-secondary" href="<?php echo erLhcoreClassDesign::baseurl('telegram/newsignature')?>">New</a>

<?php if (isset($pages)) : ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/paginator.tpl.php')); ?>
<?php endif;?>