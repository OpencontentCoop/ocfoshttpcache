<?php

/** @var eZModule $module */
$module = $Params['Module'];

if ('application/vnd.fos.user-context-hash' == strtolower($_SERVER['HTTP_ACCEPT'])) {
    $hash = Opencontent\FosHttpCache\HashGenerator::instance()->generateHash();
    header(sprintf('X-User-Context-Hash: %s', $hash));
    header('Content-Type: application/vnd.fos.user-context-hash');
    //header('Cache-Control: max-age=3600');
    //header('Vary: cookie, authorization');
    eZExecution::cleanExit();
}

$module->redirectTo('/');
return;