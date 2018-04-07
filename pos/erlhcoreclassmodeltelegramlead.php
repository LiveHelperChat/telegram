<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_telegram_lead";
$def->class = "erLhcoreClassModelTelegramLead";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

$def->properties['dep_id'] = new ezcPersistentObjectProperty();
$def->properties['dep_id']->columnName   = 'dep_id';
$def->properties['dep_id']->propertyName = 'dep_id';
$def->properties['dep_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['tchat_id'] = new ezcPersistentObjectProperty();
$def->properties['tchat_id']->columnName   = 'tchat_id';
$def->properties['tchat_id']->propertyName = 'tchat_id';
$def->properties['tchat_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['tbot_id'] = new ezcPersistentObjectProperty();
$def->properties['tbot_id']->columnName   = 'tbot_id';
$def->properties['tbot_id']->propertyName = 'tbot_id';
$def->properties['tbot_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['ctime'] = new ezcPersistentObjectProperty();
$def->properties['ctime']->columnName   = 'ctime';
$def->properties['ctime']->propertyName = 'ctime';
$def->properties['ctime']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['utime'] = new ezcPersistentObjectProperty();
$def->properties['utime']->columnName   = 'utime';
$def->properties['utime']->propertyName = 'utime';
$def->properties['utime']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['utime'] = new ezcPersistentObjectProperty();
$def->properties['utime']->columnName   = 'utime';
$def->properties['utime']->propertyName = 'utime';
$def->properties['utime']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

$def->properties['first_name'] = new ezcPersistentObjectProperty();
$def->properties['first_name']->columnName   = 'first_name';
$def->properties['first_name']->propertyName = 'first_name';
$def->properties['first_name']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['last_name'] = new ezcPersistentObjectProperty();
$def->properties['last_name']->columnName   = 'last_name';
$def->properties['last_name']->propertyName = 'last_name';
$def->properties['last_name']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['username'] = new ezcPersistentObjectProperty();
$def->properties['username']->columnName   = 'username';
$def->properties['username']->propertyName = 'username';
$def->properties['username']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['language_code'] = new ezcPersistentObjectProperty();
$def->properties['language_code']->columnName   = 'language_code';
$def->properties['language_code']->propertyName = 'language_code';
$def->properties['language_code']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

return $def;

?>