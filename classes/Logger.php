<?php

namespace Opencontent\FosHttpCache;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    private $filename;

    public function __construct()
    {
        $this->filename = 'ocfoshttpcache.log';
    }

    public function log($level, $message, array $context = array())
    {
        if (\eZDebugSetting::isConditionTrue('extension-ocfoshttpcache', $this->mapLogLevel($level))){
            \eZLog::write("[$level] $message", $this->filename);
        }
    }

    private function mapLogLevel($level)
    {
        switch ($level){
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                return \eZDebug::LEVEL_ERROR;

            case LogLevel::WARNING:
                return \eZDebug::LEVEL_WARNING;

            case LogLevel::NOTICE:
            case LogLevel::INFO:
                return \eZDebug::LEVEL_NOTICE;

            case LogLevel::DEBUG:
                return \eZDebug::LEVEL_DEBUG;
        }

        return \eZDebug::LEVEL_DEBUG;
    }
}