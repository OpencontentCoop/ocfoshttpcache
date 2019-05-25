<?php

namespace Opencontent\FosHttpCache;

use FOS\HttpCache\Exception\ExceptionCollection;

class StaticCache implements \ezpStaticCache
{
    public function generateAlwaysUpdatedCache($quiet = false, $cli = false, $delay = true)
    {
    }

    public function generateNodeListCache($nodeList)
    {
        if (!empty($nodeList)) {
            $cleanupValue = \eZContentCache::calculateCleanupValue(count($nodeList));
            $doClearNodeList = \eZContentCache::inCleanupThresholdRange($cleanupValue);
            if ($doClearNodeList) {
                foreach ($nodeList as $nodeId) {
                    CacheInvalidator::instance()
                        ->invalidate(['X-Location-Id' => $nodeId]);
                    (new Logger())->debug('Clear header: X-Location-Id ' . $nodeId);
                }

                try {
                    CacheInvalidator::instance()->flush();
                } catch (ExceptionCollection $exceptions) {
                    /** @var \Exception $exception */
                    foreach ($exceptions as $exception) {
                        (new Logger())->error($exception->getMessage());
                    }
                } catch (\Exception $exception) {
                    (new Logger())->error($exception->getMessage());
                }

            } else {
                $this->generateCache(true);
            }
        }
        return true;
    }

    public function generateCache($force = false, $quiet = false, $cli = false, $delay = true)
    {
        try {
            CacheInvalidator::instance()
                ->invalidate(['X-Instance' => ResponseTagger::getCurrentInstanceIdentifier()])
                ->flush();
        } catch (ExceptionCollection $exceptions) {
            /** @var \Exception $exception */
            foreach ($exceptions as $exception) {
                (new Logger())->error($exception->getMessage());
            }
        } catch (\Exception $exception) {
            (new Logger())->error($exception->getMessage());
        }
    }

    public function cacheURL($url, $nodeID = false, $skipExisting = false, $delay = true)
    {
    }

    public function removeURL($url)
    {
    }

    static function executeActions()
    {
    }

}