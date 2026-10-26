<div>
    <label><input type="checkbox" name="bot_client" value="1" <?php if ($item->bot_client == 1) : ?>checked="checked"<?php endif; ?> > This bot acts as a client</label>
</div>

<div>
    <label><input type="checkbox" name="delete_on_close" value="1" <?php if ($item->delete_on_close == 1) : ?>checked="checked"<?php endif; ?> > Delete topic on chat close/delete</label>
</div>

<div>
    <label><input type="checkbox" name="notify_page_change" value="1" <?php if ($item->notify_page_change == 1) : ?>checked="checked"<?php endif; ?> > Change topic if a client navigates to another page</label>
</div>

<div class="form-group">
    <label>Group Chat ID</label>
    <input name="group_chat_id" class="form-control form-control-sm" type="text" value="<?php echo (int)$item->group_chat_id?>" />
</div>

<p>If checked. Bot will acts as client. So you will receive new chat requests and messages from visitors and will be able to chat with them.</p>
<hr>
<p class="text-muted"><small>Topic icon — the icon of new topics of the department in the group: one of Telegram's free topic icons or the default icon in a colour (no Telegram Premium needed). Topic label — the start of topic names instead of the department name ("Department · Nick #12 …"), e.g. "TG" gives "TG · Nick #12 …"; a coloured icon shows its first letter. Operator bot only.</small></p>
<?php
$departmentsSelected = $item->getDepartments();
$topicIconSet = $item->bot_client == 1 && $item->id > 0 ? \LiveHelperChatExtension\lhctelegram\providers\TelegramTopicIcons::getIcons($item) : array();
$topicColorLabels = array(7322096 => '🔵', 16766590 => '🟡', 13338331 => '🟣', 9367192 => '🟢', 16749490 => '🩷', 16478047 => '🔴');
foreach (erLhcoreClassModelDepartament::getList() as $dep) :
    $currentIcon = key_exists($dep->id, $departmentsSelected) ? (string)$departmentsSelected[$dep->id]->topic_icon : ''; ?>
    <div class="d-flex align-items-center gap-2 mb-1">
        <label class="mb-0" style="min-width:16rem"><input type="checkbox" name="dep[]" value="<?php echo $dep->id?>" <?php if (key_exists($dep->id,$departmentsSelected)) : ?>checked="checked"<?php endif;?> > <?php echo htmlspecialchars($dep)?></label>
        <?php if ($item->bot_client == 1) : ?>
        <select name="topic_icon[<?php echo $dep->id?>]" class="form-select form-select-sm w-auto" title="Topic icon">
            <option value="">Topic icon: default</option>
            <optgroup label="Default icon in a colour">
                <?php foreach (\LiveHelperChatExtension\lhctelegram\providers\TelegramTopicIcons::COLORS as $colorValue => $colorName) : ?>
                <option value="color:<?php echo $colorValue?>" <?php if ($currentIcon === 'color:' . $colorValue) : ?>selected="selected"<?php endif;?>><?php echo $topicColorLabels[$colorValue] . ' ' . htmlspecialchars($colorName)?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php if (!empty($topicIconSet)) : ?>
            <optgroup label="Icon">
                <?php foreach ($topicIconSet as $iconEmoji => $iconId) : ?>
                <option value="<?php echo htmlspecialchars($iconId)?>" <?php if ($currentIcon === (string)$iconId) : ?>selected="selected"<?php endif;?>><?php echo htmlspecialchars($iconEmoji)?></option>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
        </select>
        <input type="text" name="topic_label[<?php echo $dep->id?>]" class="form-control form-control-sm w-auto" maxlength="50" placeholder="Topic label" title="Topic label" value="<?php echo key_exists($dep->id, $departmentsSelected) ? htmlspecialchars((string)$departmentsSelected[$dep->id]->topic_label) : ''?>" />
        <span class="topic-preview d-inline-flex align-items-center gap-1 text-muted small" data-department="<?php echo htmlspecialchars((string)$dep)?>" title="Preview of a new topic"></span>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if ($item->bot_client == 1) : ?>
<script>
// Preview of a new topic of each department: the icon (a free icon, or the coloured default icon with the first
// letter of the topic name) and the start of the topic name.
(function () {
    var colors = {'7322096': '#6FB9F0', '16766590': '#FFD67E', '13338331': '#CB86DB', '9367192': '#8EEE98', '16749490': '#FF93B2', '16478047': '#FB6F5F'};
    document.querySelectorAll('.topic-preview').forEach(function (preview) {
        var row = preview.parentNode;
        var select = row.querySelector('select');
        var label = row.querySelector('input[type=text]');
        var update = function () {
            var start = (label.value.trim() !== '' ? label.value.trim() : preview.dataset.department) + ' ·';
            var name = start + ' Ivan #12345 | 1.2.3.4';
            var value = select.value;
            var icon = document.createElement('span');
            icon.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:1.6em;height:1.6em;border-radius:50%;font-weight:700;color:#fff;font-size:0.85em;flex-shrink:0';
            if (value !== '' && value.indexOf('color:') !== 0) {
                icon.textContent = select.options[select.selectedIndex].text;
                icon.style.fontSize = '1.2em';
            } else {
                icon.style.background = value.indexOf('color:') === 0 ? colors[value.substring(6)] : '#9AA4B1';
                icon.textContent = Array.from(name)[0].toUpperCase();
            }
            preview.textContent = '';
            preview.appendChild(icon);
            preview.appendChild(document.createTextNode(name));
        };
        select.addEventListener('change', update);
        label.addEventListener('input', update);
        update();
    });
})();
</script>
<?php endif; ?>
