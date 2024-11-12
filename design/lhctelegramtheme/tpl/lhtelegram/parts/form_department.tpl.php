<div>
    <label><input type="checkbox" name="bot_client" value="1" <?php if ($item->bot_client == 1) : ?>checked="checked"<?php endif; ?> > This bot acts as a client</label>
</div>

<div>
    <label><input type="checkbox" name="delete_on_close" value="1" <?php if ($item->delete_on_close == 1) : ?>checked="checked"<?php endif; ?> > Delete topic on chat close/delete</label>
</div>

<div class="form-group">
    <label>Group Chat ID</label>
    <input name="group_chat_id" class="form-control form-control-sm" type="text" value="<?php echo (int)$item->group_chat_id?>" />
</div>

<p>If checked. Bot will acts as client. So you will receive new chat requests and messages from visitors and will be able to chat with them.</p>
<hr>
<?php $departmentsSelected = $item->getDepartments(); foreach (erLhcoreClassModelDepartament::getList() as $dep) : ?>
    <label><input type="checkbox" name="dep[]" value="<?php echo $dep->id?>" <?php if (key_exists($dep->id,$departmentsSelected)) : ?>checked="checked"<?php endif;?> ><?php echo htmlspecialchars($dep)?></label><br/>
<?php endforeach; ?>
