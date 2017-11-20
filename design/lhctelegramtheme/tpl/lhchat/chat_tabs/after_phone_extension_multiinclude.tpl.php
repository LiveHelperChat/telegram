<?php
$chatVariables = $chat->chat_variables_array;
if (isset($chatVariables['tchat'])) : ?>
	<tr>
		<td><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/adminchat','Telegram')?></td>
		<td><strong><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('chat/adminchat','YES')?></strong></td>
	</tr>
<?php endif;?>