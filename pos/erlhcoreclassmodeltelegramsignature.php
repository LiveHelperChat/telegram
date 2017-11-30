<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_signature";
$def->class = "erLhcoreClassModelTelegramSignature";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

$def->properties['bot_id'] = new ezcPersistentObjectProperty();
$def->properties['bot_id']->columnName   = 'bot_id';
$def->properties['bot_id']->propertyName = 'bot_id';
$def->properties['bot_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['user_id'] = new ezcPersistentObjectProperty();
$def->properties['user_id']->columnName   = 'user_id';
$def->properties['user_id']->propertyName = 'user_id';
$def->properties['user_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['signature'] = new ezcPersistentObjectProperty();
$def->properties['signature']->columnName   = 'signature';
$def->properties['signature']->propertyName = 'signature';
$def->properties['signature']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

return $def;

?>