<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/editoperator.tpl.php');

$item =  erLhcoreClassModelTelegramOperator::fetch($Params['user_parameters']['id']);

if (ezcInputForm::hasPostData()) {

    if (isset($_POST['Cancel_action'])) {
        erLhcoreClassModule::redirect('telegram/operators');
        exit ;
    }

    $Errors = erLhcoreClassTelegramValidator::validateOperator($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();

            erLhcoreClassModule::redirect('telegram/operators');
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
));

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array('url' =>erLhcoreClassDesign::baseurl('telegram/index'), 'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram')),
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/operators'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram operators')
    ),
    array (
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Edit operator')
    )
);

?>