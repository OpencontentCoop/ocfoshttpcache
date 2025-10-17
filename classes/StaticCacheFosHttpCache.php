<?php

namespace Opencontent\FosHttpCache;

use eZINI;
use FOS\HttpCache\ProxyClient\Varnish;

class StaticCache implements \ezpStaticCache
{
    /**
     * An array with URLs that is to always be updated.
     *
     * @var array(int=>string)
     */
    private $alwaysUpdate;

    private $enableRefresh;

    private $alwaysUpdatedCacheRegistered = [];

    private $cacheThreshold = 250;

    public function __construct()
    {
        $ini = eZINI::instance('staticcache.ini');
        $this->alwaysUpdate = $ini->variable('CacheSettings', 'AlwaysUpdateArray');
        $this->enableRefresh = $ini->hasVariable('CacheSettings', 'EnableRefresh')
            && $ini->variable('CacheSettings', 'EnableRefresh') == 'enabled';

        $siteIni = eZINI::instance();
        $this->cacheThreshold = min(
            (int)$siteIni->variable('ContentSettings', 'CacheThreshold'),
            (int)$siteIni->variable('ContentSettings', 'StaticCacheThreshold')
        );
    }

    private function inCleanupThresholdRange($value): bool
    {
        return ($value < $this->cacheThreshold);
    }

    public function generateAlwaysUpdatedCache($quiet = false, $cli = false, $delay = true)
    {
        if ($this->enableRefresh) {
            (new Logger())->info('Refresh path ' . implode(', ', $this->alwaysUpdate));
            $cacheInvalidator = CacheInvalidator::instance();
            foreach ($this->alwaysUpdate as $url) {
                if (!isset($this->alwaysUpdatedCacheRegistered[$url])) {
                    $this->alwaysUpdatedCacheRegistered[$url] = true;
                    $cacheInvalidator->refreshPath($url);
                }
            }
        }
    }

    public function generateNodeListCache($nodeList)
    {
        if (!empty($nodeList)) {
            $cleanupValue = \eZContentCache::calculateCleanupValue(count($nodeList));
            $doClearNodeList = $this->inCleanupThresholdRange($cleanupValue);
            if ($doClearNodeList) {

                $tagList = array_map(function ($nodeId) {
                    return "node-{$nodeId}";
                }, $nodeList);

                (new Logger())->info('Clear content tag list: ' . implode(', ', $tagList));
                CacheInvalidator::instance()->invalidateTags($tagList);

            } else {
                (new Logger())->info(
                    "Expiring all view cache since list of nodes({$cleanupValue}) exceeds {$this->cacheThreshold}"
                );
                $this->generateCache(true);
            }
        }
        return true;
    }

    public function generateCache($force = false, $quiet = false, $cli = false, $delay = true)
    {
        (new Logger())->info('Clear all cache');
        CacheInvalidator::instance()->invalidateRegex(Varnish::REGEX_MATCH_ALL);
    }

    public function cacheURL($url, $nodeID = false, $skipExisting = false, $delay = true)
    {
        if ($this->enableRefresh && !isset($this->alwaysUpdatedCacheRegistered[$url])) {
            (new Logger())->info('Refresh path ' . $url);
            CacheInvalidator::instance()->refreshPath($url);
            if (in_array($url, $this->alwaysUpdate)) {
                $this->alwaysUpdatedCacheRegistered[$url] = true;
            }
        }
    }

    public function removeURL($url)
    {
        (new Logger())->info('Remove path ' . $url);
        CacheInvalidator::instance()->invalidatePath($url);
    }

    static function executeActions()
    {
    }

}