<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_operator";
$def->class = "erLhcoreClassModelTelegramOperator";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

$def->properties['bot_id'] = new ezcPersistentObjectProperty();
$def->properties['bot_id']->columnName   = 'bot_id';
$def->properties['bot_id']->propertyName = 'bot_id';
$def->properties['bot_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['tchat_id'] = new ezcPersistentObjectProperty();
$def->properties['tchat_id']->columnName   = 'tchat_id';
$def->properties['tchat_id']->propertyName = 'tchat_id';
$def->properties['tchat_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['tuser_id'] = new ezcPersistentObjectProperty();
$def->properties['tuser_id']->columnName   = 'tuser_id';
$def->properties['tuser_id']->propertyName = 'tuser_id';
$def->properties['tuser_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['user_id'] = new ezcPersistentObjectProperty();
$def->properties['user_id']->columnName   = 'user_id';
$def->properties['user_id']->propertyName = 'user_id';
$def->properties['user_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

// Current chat id operator is chatting
$def->properties['chat_id'] = new ezcPersistentObjectProperty();
$def->properties['chat_id']->columnName   = 'chat_id';
$def->properties['chat_id']->propertyName = 'chat_id';
$def->properties['chat_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['confirmed'] = new ezcPersistentObjectProperty();
$def->properties['confirmed']->columnName   = 'confirmed';
$def->properties['confirmed']->propertyName = 'confirmed';
$def->properties['confirmed']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

return $def;

?>