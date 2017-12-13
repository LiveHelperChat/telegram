<?php

$Module = array( "name" => "Telegram",
				 'variable_params' => true );

$ViewList = array();

$ViewList['callback'] = array(
    'params' => array('id'),
    'uparams' => array()
);

$ViewList['list'] = array(
    'params' => array(),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['index'] = array(
    'params' => array(),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['new'] = array(
    'params' => array(),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['edit'] = array(
    'params' => array('id'),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['editsignature'] = array(
    'params' => array('id'),
    'uparams' => array('action','itemid','status'),
    'functions' => array('use_admin'),
);

$ViewList['editsignatureglobal'] = array(
    'params' => array('id'),
    'uparams' => array('action','itemid','status'),
    'functions' => array('use_admin'),
);

$ViewList['deletesignature'] = array(
    'params' => array('id'),
    'uparams' => array('csfr'),
    'functions' => array('use_admin'),
);

$ViewList['signatures'] = array(
    'params' => array(),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['newsignature'] = array(
    'params' => array(),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['setwebhook'] = array(
    'params' => array('id'),
    'uparams' => array(),
    'functions' => array('use_admin'),
);

$ViewList['delete'] = array(
    'params' => array('id'),
    'uparams' => array('csfr'),
    'functions' => array('use_admin'),
);

$FunctionList['use_admin'] = array('explain' => 'Allow operator to configure telegram bot');