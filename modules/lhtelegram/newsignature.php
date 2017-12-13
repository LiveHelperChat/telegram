<?php
$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/newsignature.tpl.php');

$item = new erLhcoreClassModelTelegramSignature();

$tpl->set('item',$item);

if (ezcInputForm::hasPostData()) {

    $Errors = erLhcoreClassTelegramValidator::validateSignatureGlobal($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();

            erLhcoreClassModule::redirect('telegram/signatures');
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
        'url' =>erLhcoreClassDesign::baseurl('telegram/signatures'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Signatures')
    ),
    array(
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'New')
    )
);

?>