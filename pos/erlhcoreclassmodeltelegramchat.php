<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_chat";
$def->class = "erLhcoreClassModelTelegramChat";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

$def->properties['bot_id'] = new ezcPersistentObjectProperty();
$def->properties['bot_id']->columnName   = 'bot_id';
$def->properties['bot_id']->propertyName = 'bot_id';
$def->properties['bot_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['chat_id'] = new ezcPersistentObjectProperty();
$def->properties['chat_id']->columnName   = 'chat_id';
$def->properties['chat_id']->propertyName = 'chat_id';
$def->properties['chat_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['tchat_id'] = new ezcPersistentObjectProperty();
$def->properties['tchat_id']->columnName   = 'tchat_id';
$def->properties['tchat_id']->propertyName = 'tchat_id';
$def->properties['tchat_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['ctime'] = new ezcPersistentObjectProperty();
$def->properties['ctime']->columnName   = 'ctime';
$def->properties['ctime']->propertyName = 'ctime';
$def->properties['ctime']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['utime'] = new ezcPersistentObjectProperty();
$def->properties['utime']->columnName   = 'utime';
$def->properties['utime']->propertyName = 'utime';
$def->properties['utime']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

return $def;

?>