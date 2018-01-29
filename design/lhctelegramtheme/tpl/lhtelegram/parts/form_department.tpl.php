<label><input type="checkbox" name="bot_client" value="1" <?php if ($item->bot_client == 1) : ?>checked="checked"<?php endif; ?> > This bot acts as a client</label>
<p>If checked. Bot will acts as client. So you will receive new chat requests and messages from visitors and will be able to chat with them.</p>
<hr>
<?php $departmentsSelected = $item->getDepartments(); foreach (erLhcoreClassModelDepartament::getList() as $dep) : ?>
    <label><input type="checkbox" name="dep[]" value="<?php echo $dep->id?>" <?php if (key_exists($dep->id,$departmentsSelected)) : ?>checked="checked"<?php endif;?> ><?php echo htmlspecialchars($dep)?></label><br/>
<?php endforeach; ?>
