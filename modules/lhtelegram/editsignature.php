<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/editsignature.tpl.php');

$item =  erLhcoreClassModelTelegramBot::fetch($Params['user_parameters']['id']);

if (is_numeric($Params['user_parameters_unordered']['itemid']) && $Params['user_parameters_unordered']['action'] == 'delete') {
    $itemDelete = erLhcoreClassModelTelegramSignature::fetch($Params['user_parameters_unordered']['itemid']);
    $itemDelete->removeThis();

    erLhcoreClassModule::redirect('telegram/editsignature', '/' . $item->id . '/(status)/removed');
    exit;
}

$prependURLEdit = '';

if (is_numeric($Params['user_parameters_unordered']['itemid']) && $Params['user_parameters_unordered']['action'] == 'edit') {
    $newSignature = erLhcoreClassModelTelegramSignature::fetch($Params['user_parameters_unordered']['itemid']);
    $prependURLEdit = '/(action)/edit/(itemid)/' . $newSignature->id;
} else {
    $newSignature = new erLhcoreClassModelTelegramSignature();
}

if (ezcInputForm::hasPostData()) {

    $Errors = erLhcoreClassTelegramValidator::validateSignature($newSignature);

    if (count($Errors) == 0) {
        try {
            $newSignature->bot_id = $item->id;
            $newSignature->saveThis();

            if ($Params['user_parameters_unordered']['action'] == 'edit'){
                erLhcoreClassModule::redirect('telegram/editsignature', '/' . $item->id . '/(status)/updated');
                exit;
            }

            erLhcoreClassModule::redirect('telegram/editsignature', '/' . $item->id . '/(status)/saved');
            exit;

        } catch (Exception $e) {
            $tpl->set('errors',array($e->getMessage()));
        }

    } else {
        $tpl->set('errors',$Errors);
    }
}

$tpl->setArray(array(
    'item' => $item,
    'prependURLEdit' => $prependURLEdit,
    'status' => $Params['user_parameters_unordered']['status'],
    'newSignature' => $newSignature,
    'signatures' => erLhcoreClassModelTelegramSignature::getList(array('filter' => array('bot_id' => $item->id)))
));


$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array('url' =>erLhcoreClassDesign::baseurl('telegram/index'), 'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram')),
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/list'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots')
    ),
    array (
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Edit bot')
    )
);

?>