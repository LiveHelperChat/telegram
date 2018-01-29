<?php
$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/newoperator.tpl.php');

$item = new erLhcoreClassModelTelegramOperator();

$tpl->set('item',$item);

if (ezcInputForm::hasPostData()) {

    $Errors = erLhcoreClassTelegramValidator::validateOperator($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();
            erLhcoreClassModule::redirect('telegram/operators');
            exit ;

        } catch (Exception $e) {
            $tpl->set('errors',array($e->getMessage()));
        }

    } else {
        $tpl->set('errors',$Errors);
    }
}

$Result['content'] = $tpl->fetch();
$Result['path'] = array(
    array('url' =>erLhcoreClassDesign::baseurl('telegram/index'), 'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram')),
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/operators'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram operators')
    ),
    array(
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'New')
    )
);

?>