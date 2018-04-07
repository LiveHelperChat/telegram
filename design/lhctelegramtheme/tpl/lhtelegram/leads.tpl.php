<h1><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram leads list');?></h1>

<?php if (isset($items)) : ?>
    <table cellpadding="0" cellspacing="0" class="table" width="100%">
        <thead>
        <tr>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Bot');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Name');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Surname');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Username');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Locale');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Department');?></th>
            <th><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram Chat ID');?></th>
            <th width="1%"></th>
        </tr>
        </thead>
        <?php foreach ($items as $item) : ?>
            <tr>
                <td><?php echo htmlspecialchars($item->tbot)?></td>
                <td><?php echo htmlspecialchars($item->first_name)?></td>
                <td><?php echo htmlspecialchars($item->last_name)?></td>
                <td><?php echo htmlspecialchars($item->username)?></td>
                <td><?php echo htmlspecialchars($item->language_code)?></td>
                <td><?php echo htmlspecialchars($item->dep)?></td>
                <td><?php echo htmlspecialchars($item->tchat_id)?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php include(erLhcoreClassDesign::designtpl('lhkernel/secure_links.tpl.php')); ?>

    <?php if (isset($pages)) : ?>
        <?php include(erLhcoreClassDesign::designtpl('lhkernel/paginator.tpl.php')); ?>
    <?php endif;?>

<?php endif;?>
