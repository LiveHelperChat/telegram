<?php

if (!$currentUser->validateCSFRToken($Params['user_parameters_unordered']['csfr'])) {
	die('Invalid CSFR Token');
	exit;
}

try {
    $item = erLhcoreClassModelTelegramBot::fetch( $Params['user_parameters']['id']);
    $item->removeThis();
    
    erLhcoreClassModule::redirect('telegram/list');
    exit;
    
} catch (Exception $e) {
    
    print_r( $e);
    
    $tpl = erLhcoreClassTemplate::getInstance('lhkernel/validation_error.tpl.php');
    $tpl->set('errors',array($e->getMessage()));
    $Result['content'] = $tpl->fetch();
}

?>