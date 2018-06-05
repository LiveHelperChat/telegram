<?php

$tpl = erLhcoreClassTemplate::getInstance('lhtelegram/options.tpl.php');

$tOptions = erLhcoreClassModelChatConfig::fetch('telegram_options');
$data = (array)$tOptions->data;

if ( isset($_POST['StoreOptions']) ) {

    $definition = array(
        'new_chat' => new ezcInputFormDefinitionElement(
            ezcInputFormDefinitionElement::OPTIONAL, 'boolean'
        ),
        'block_bot' => new ezcInputFormDefinitionElement(
            ezcInputFormDefinitionElement::OPTIONAL, 'boolean'
        ),
        'exclude_workflow' => new ezcInputFormDefinitionElement(
            ezcInputFormDefinitionElement::OPTIONAL, 'boolean'
        ),
        'priority' => new ezcInputFormDefinitionElement(
            ezcInputFormDefinitionElement::OPTIONAL, 'int'
        ),
        'chat_attr' => new ezcInputFormDefinitionElement(
            ezcInputFormDefinitionElement::OPTIONAL, 'boolean'
        ),
    );

    $form = new ezcInputForm( INPUT_POST, $definition );
    $Errors = array();

    if ( $form->hasValidData( 'new_chat' ) && $form->new_chat == true ) {
        $data['new_chat'] = 1;
    } else {
        $data['new_chat'] = 0;
    }

    if ( $form->hasValidData( 'block_bot' ) && $form->block_bot == true ) {
        $data['block_bot'] = 1;
    } else {
        $data['block_bot'] = 0;
    }

    if ( $form->hasValidData( 'exclude_workflow' ) && $form->exclude_workflow == true ) {
        $data['exclude_workflow'] = 1;
    } else {
        $data['exclude_workflow'] = 0;
    }

    if ( $form->hasValidData( 'chat_attr' ) && $form->chat_attr == true ) {
        $data['chat_attr'] = 1;
    } else {
        $data['chat_attr'] = 0;
    }

    if ( $form->hasValidData( 'priority' )) {
        $data['priority'] = $form->priority;
    } else {
        $data['priority'] = 0;
    }

    $tOptions->explain = '';
    $tOptions->type = 0;
    $tOptions->hidden = 1;
    $tOptions->identifier = 'telegram_options';
    $tOptions->value = serialize($data);
    $tOptions->saveThis();

    $tpl->set('updated','done');
}

$tpl->set('t_options',$data);

$Result['content'] = $tpl->fetch();

$Result['path'] = array(
    array(
        'url' => erLhcoreClassDesign::baseurl('telegram/index'),
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('lhelasticsearch/module', 'Telegram')
    ),
    array(
        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('lhelasticsearch/module', 'Options')
    )
);

?>