<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_bot";
$def->class = "erLhcoreClassModelTelegramBot";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

$def->properties['bot_username'] = new ezcPersistentObjectProperty();
$def->properties['bot_username']->columnName   = 'bot_username';
$def->properties['bot_username']->propertyName = 'bot_username';
$def->properties['bot_username']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['bot_api'] = new ezcPersistentObjectProperty();
$def->properties['bot_api']->columnName   = 'bot_api';
$def->properties['bot_api']->propertyName = 'bot_api';
$def->properties['bot_api']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['webhook_set'] = new ezcPersistentObjectProperty();
$def->properties['webhook_set']->columnName   = 'webhook_set';
$def->properties['webhook_set']->propertyName = 'webhook_set';
$def->properties['webhook_set']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['dep_id'] = new ezcPersistentObjectProperty();
$def->properties['dep_id']->columnName   = 'dep_id';
$def->properties['dep_id']->propertyName = 'dep_id';
$def->properties['dep_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['bot_disabled'] = new ezcPersistentObjectProperty();
$def->properties['bot_disabled']->columnName   = 'bot_disabled';
$def->properties['bot_disabled']->propertyName = 'bot_disabled';
$def->properties['bot_disabled']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['bot_client'] = new ezcPersistentObjectProperty();
$def->properties['bot_client']->columnName   = 'bot_client';
$def->properties['bot_client']->propertyName = 'bot_client';
$def->properties['bot_client']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['chat_timeout'] = new ezcPersistentObjectProperty();
$def->properties['chat_timeout']->columnName   = 'chat_timeout';
$def->properties['chat_timeout']->propertyName = 'chat_timeout';
$def->properties['chat_timeout']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

return $def;

?>