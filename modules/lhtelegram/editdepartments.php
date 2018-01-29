<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/editdepartments.tpl.php');

$item =  erLhcoreClassModelTelegramBot::fetch($Params['user_parameters']['id']);

if (ezcInputForm::hasPostData()) {

    $Errors = erLhcoreClassTelegramValidator::validateDepartments($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();
            $tpl->set('status', 'updated');
        } catch (Exception $e) {
            $tpl->set('errors',array($e->getMessage()));
        }
    } else {
        $tpl->set('errors',$Errors);
    }
}

$tpl->setArray(array(
    'item' => $item,
));

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array('url' =>erLhcoreClassDesign::baseurl('telegram/index'), 'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram')),
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/list'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots')
    ),
    array (
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Edit bot departments')
    )
);

?>