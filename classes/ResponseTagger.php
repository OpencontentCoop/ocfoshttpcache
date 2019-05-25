<?php

namespace Opencontent\FosHttpCache;

use FOS\HttpCache\ResponseTagger as FosResponseTagger;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use FOS\HttpCache\TagHeaderFormatter\MaxHeaderValueLengthFormatter;

class ResponseTagger
{
    private static $instance;

    private $responseTagger;

    private function __construct()
    {
        $inner = new CommaSeparatedTagHeaderFormatter('X-Cache-Tags', ',');
        $formatter = new MaxHeaderValueLengthFormatter($inner, 4096);
        $this->responseTagger = new FosResponseTagger(['header_formatter' => $formatter]);
    }

    /**
     * @return FosResponseTagger
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new ResponseTagger();
        }

        return self::$instance->responseTagger;
    }

    public static function getCurrentInstanceIdentifier()
    {
        $currentSiteAccess = \eZSiteAccess::current();
        $siteAccessName = $currentSiteAccess['name'];

        $parts = explode('_', $siteAccessName);

        $identifier = array_shift($parts);

        $identifier = \ezpEvent::getInstance()->filter('ocfoshttpcache/instance_identifier', $identifier);

        return $identifier;
    }
}