<?php if ($buttonData['item'] == 'tchat') : ?>

    <?php if (isset($chat->chat_variables_array['tchat']) && $chat->chat_variables_array['tchat'] == true) : ?>
    <tr>
        <td>
            <img width="14" src="<?php echo erLhcoreClassDesign::design('images/Telegram_logo.svg')?>" title="Facebook chat" />&nbsp;<?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/adminchat','Telegram')?>
        </td>
        <td>
            <b>YES</b>
        </td>
    </tr>
    <?php endif; ?>

<?php endif; ?>
