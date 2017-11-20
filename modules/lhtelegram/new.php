<?php
$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/new.tpl.php');

$item = new erLhcoreClassModelTelegramBot();

$tpl->set('item',$item);

if (ezcInputForm::hasPostData()) {

    $Errors = erLhcoreClassTelegramValidator::validateBot($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();
             
            erLhcoreClassModule::redirect('telegram/list');
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
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/list'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots')
    ),
    array(
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'New')
    )
);

?>