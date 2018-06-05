<?php

class erLhcoreClassTelegramValidator
{
    public static function validateBot(erLhcoreClassModelTelegramBot & $item)
    {
        $definition = array(
            'bot_username' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            ),
            'bot_api' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            ),
            'chat_timeout' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            ),
            'bot_disabled' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'boolean'
            ),
            'dep_id' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)
            )
        );

        $form = new ezcInputForm(INPUT_POST, $definition);
        $Errors = array();

        if ($form->hasValidData('bot_username') && $form->bot_username != '') {
            $item->bot_username = $form->bot_username;
        } else {
            $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('xmppservice/operatorvalidator', 'Please enter phone number!');
        }

        if ($form->hasValidData('bot_api') && $form->bot_api != '') {
            $item->bot_api = $form->bot_api;
        } else {
            $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('xmppservice/operatorvalidator', 'Please enter Account SID!');
        }

        if ($form->hasValidData('bot_disabled') && $form->bot_disabled == true) {
            $item->bot_disabled = 1;
        } else {
            $item->bot_disabled = 0;
        }

        if ($form->hasValidData('dep_id')) {
            $item->dep_id = $form->dep_id;
        } else {
            $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('xmppservice/operatorvalidator', 'Please choose a department!');
        }

        if ($form->hasValidData('chat_timeout')) {
            $item->chat_timeout = $form->chat_timeout;
        } else {
            $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('xmppservice/operatorvalidator', 'Please enter chat timeout!');
        }

        return $Errors;
    }

    public static function validateSignature(erLhcoreClassModelTelegramSignature & $item)
    {
        $definition = array(
            'signature' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
            ),
            'user_id' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)
            )
        );

        $form = new ezcInputForm(INPUT_POST, $definition);
        $Errors = array();

        if ($form->hasValidData('signature') && $form->signature != '') {
            $item->signature = $_POST['signature'];
        } else {
            $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('xmppservice/operatorvalidator', 'Please enter signature!');
        }

        if ($form->hasValidData('user_id')) {
            $item->user_id = $form->user_id;
        } else {
            $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('xmppservice/operatorvalidator', 'Please choose a user!');
        }

        return $Errors;
    }

    public static function validateSignatureGlobal(erLhcoreClassModelTelegramSignature & $item)
    {
        $definition = array(
            'bot_id' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)
            )
        );

        $form = new ezcInputForm(INPUT_POST, $definition);

        if ($form->hasValidData('bot_id')) {
            $item->bot_id = $form->bot_id;
        } else {
            $item->bot_id = 0;
        }

        $Errors = self::validateSignature($item);

        return $Errors;
    }

    public static function validateDepartments(erLhcoreClassModelTelegramBot & $item)
    {
        $definition = array(
            'bot_client' => new ezcInputFormDefinitionElement(
                ezcInputFormDefinitionElement::OPTIONAL, 'boolean'
            ),
            'dep' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'int',null,FILTER_REQUIRE_ARRAY),
        );

        $form = new ezcInputForm(INPUT_POST, $definition);

        if ($form->hasValidData('bot_client') && $form->bot_client == true) {
            $item->bot_client = 1;
        } else {
            $item->bot_client = 0;
        }

        $db = ezcDbInstance::get();
        $stmt = $db->prepare('DELETE FROM lhc_telegram_bot_dep WHERE bot_id = :bot_id');
        $stmt->bindValue(':bot_id', $item->id, PDO::PARAM_STR);
        $stmt->execute();

        if ($form->hasValidData('dep') && !empty($form->dep)) {
            foreach ($form->dep as $depId) {
                $botDep = new erLhcoreClassModelTelegramBotDep();
                $botDep->dep_id = $depId;
                $botDep->bot_id = $item->id;
                $botDep->saveThis();
            }
        }
    }

    public static function validateOperator(erLhcoreClassModelTelegramOperator & $item)
    {
        $definition = array(
            'user_id' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)),
            'bot_id' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)),
            'confirmed' => new ezcInputFormDefinitionElement(ezcInputFormDefinitionElement::OPTIONAL, 'boolean'),
        );

        $Errors = array();
        $form = new ezcInputForm(INPUT_POST, $definition);

        if ($form->hasValidData('user_id')) {
            $item->user_id = $form->user_id;
        } else {
            $Errors[] = 'Please choose a user!';
        }

        if ($form->hasValidData('bot_id')) {
            $item->bot_id = $form->bot_id;
        } else {
            $Errors[] = 'Please choose a bot!';
        }

        if ($form->hasValidData('confirmed') && $form->confirmed == 1) {
            $item->confirmed = 1;
        } else {
            $item->confirmed = 0;
        }

        return $Errors;
    }
}