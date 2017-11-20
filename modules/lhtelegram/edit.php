<?php 

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/edit.tpl.php');

$item =  erLhcoreClassModelTelegramBot::fetch($Params['user_parameters']['id']);

if (ezcInputForm::hasPostData()) {
        
    if (isset($_POST['Cancel_action'])) {
        erLhcoreClassModule::redirect('twilio/list');
        exit ;
    }
    
    $Errors = erLhcoreClassTelegramValidator::validateBot($item);

    if (count($Errors) == 0) {
        try {
            $item->saveThis();
                       
            erLhcoreClassModule::redirect('telegram/list');
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
    array (
        'url' =>erLhcoreClassDesign::baseurl('telegram/list'), 
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger','Telegram bots')        
    ),
    array (       
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('module/fbmessenger', 'Edit bot')
    )
);

?>