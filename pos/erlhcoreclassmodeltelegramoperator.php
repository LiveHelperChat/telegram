<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_operator";
$def->class = "erLhcoreClassModelTelegramOperator";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

foreach ([
	'bot_id',
	'tchat_id',
	'tuser_id',
	'user_id',
	'chat_id',
	'confirmed']
 as $property) {
	$def->properties[$property] = new ezcPersistentObjectProperty();
	$def->properties[$property]->columnName   = $property;
	$def->properties[$property]->propertyName = $property;
	$def->properties[$property]->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;
}

return $def;

?>