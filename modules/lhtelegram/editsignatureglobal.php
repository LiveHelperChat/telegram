<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/editsignatureglobal.tpl.php');

$item = erLhcoreClassModelTelegramSignature::fetch($Params['user_parameters']['id']);

if (ezcInputForm::hasPostData()) {

    if (isset($_POST['Cancel_action'])) {
        erLhcoreClassModule::redirect('twilio/list');
        exit ;
    }

    $Errors = erLhcoreClassTelegramValidator::validateSignatureGlobal($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();

            erLhcoreClassModule::redirect('telegram/signatures');
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
        'url' =>erLhcoreClassDesign::baseurl('telegram/signatures'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Signatures')
    ),
    array (
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Edit signature')
    )
);

?>