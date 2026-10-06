<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots');?></h1>

<?php if (isset($items)) : ?>

<table cellpadding="0" cellspacing="0" class="table" width="100%" ng-non-bindable>
<thead>
    <tr>   
        <th width="1%">ID</th>
        <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot');?></th>
        <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Callback URL');?></th>
        <th width="1%"></th>
    </tr>
</thead>
    <?php foreach ($items as $item) : ?>
    <tr>
        <td><?php echo $item->id?></td>
        <td>
            <a href="<?php echo erLhcoreClassDesign::baseurl('telegram/edit')?>/<?php echo $item->id?>"><i class="material-icons">&#xE254;</i><?php echo htmlspecialchars($item->bot_username)?></a>
            <a href="https://t.me/<?php echo urlencode($item->bot_username)?>" target="_blank" rel="noopener"><i class="material-icons mr-0">&#xE89E;</i></a>
        </td>
        <td>
            <?php if ($item->callback_url !== null) : ?>

            <form method="post" action="<?php echo erLhcoreClassDesign::baseurl('telegram/setwebhook')?>/<?php echo $item->id?>">
                <?php include(erLhcoreClassDesign::designtpl('lhkernel/csfr_token.tpl.php'));?>
                
                <div class="input-group input-group-sm">
                    <select class="form-control form-control-sm w-50" name="site_access">
                            <?php foreach (erConfigClassLhConfig::getInstance()->getSetting( 'site', 'available_site_access' ) as $locale ) : ?>
                                <option value="<?php echo htmlspecialchars($locale)?>"><?php echo htmlspecialchars($locale)?></option>
                            <?php endforeach; ?>
                    </select>
                    <span class="input-group-text">
                        <?php if ($item->webhook_set == 1) : ?>Yes<?php else : ?>No<?php endif;?>&nbsp; <button title="<?php echo $item->callback_url?>" class="btn btn-xs btn-info">Set webhook</button>
                    </span>
                </div>
                <span class="fs11 text-muted">Language, after choosing a language click `Set webhook`. After setting webhook this option will remain the same, but webhook URL will be set to a chosen language.</span>
            </form>

            <?php else : ?>
                <?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram','Please finish');?> <a href="<?php echo erLhcoreClassDesign::baseurl('telegram/options')?>"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/telegram','configuration');?></a>
            <?php endif; ?>
        </td>
        <td nowrap>
          <div class="btn-group" role="group" aria-label="...">
            <a class="btn btn-danger btn-xs csfr-required" onclick="return confirm('<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('kernel/messages','Are you sure?');?>')" href="<?php echo erLhcoreClassDesign::baseurl('telegram/delete')?>/<?php echo $item->id?>" ><i class="material-icons me-0">&#xE872;</i></a>
          </div>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php include(erLhcoreClassDesign::designtpl('lhkernel/secure_links.tpl.php')); ?>

<?php if (isset($pages)) : ?>
    <?php include(erLhcoreClassDesign::designtpl('lhkernel/paginator.tpl.php')); ?>
<?php endif;?>

<?php endif;?>

<a href="<?php echo erLhcoreClassDesign::baseurl('telegram/new')?>" class="btn btn-secondary"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','New');?></a>