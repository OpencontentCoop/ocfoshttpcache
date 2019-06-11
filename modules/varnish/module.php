<?php
$Module = array( 'name' => 'Varnish' );

$ViewList = array();
$ViewList['dashboard'] = array(
    'script' => 'dashboard.php',
    'ui_content' => 'administration',
    'functions' => array('manage'),
    'default_navigation_part' => 'varnishnavigationpart',
);

$FunctionList = array();
$FunctionList['manage'] = array();
