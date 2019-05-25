<?php

$hash = Opencontent\FosHttpCache\HashGenerator::instance()->generateHash();

if ('application/vnd.fos.user-context-hash' == strtolower($_SERVER['HTTP_ACCEPT'])) {
    header(sprintf('X-User-Context-Hash: %s', $hash));
    header('Content-Type: application/vnd.fos.user-context-hash');
    eZExecution::cleanExit();
}

header('HTTP/1.1 406');
eZExecution::cleanExit();