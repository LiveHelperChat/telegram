<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_chat";
$def->class = "erLhcoreClassModelTelegramChat";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

foreach ([
	'bot_id',
	'chat_id',
	'chat_id_internal',
	'tchat_id',
	'ctime',
	'utime',
	'type',
	'last_msg_id']
 as $property) {
	$def->properties[$property] = new ezcPersistentObjectProperty();
	$def->properties[$property]->columnName   = $property;
	$def->properties[$property]->propertyName = $property;
	$def->properties[$property]->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;
}

return $def;

?>