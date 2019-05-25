<?php

namespace Opencontent\FosHttpCache;

use FOS\HttpCache\CacheInvalidator as FosCacheInvalidator;
use FOS\HttpCache\ProxyClient;
use FOS\HttpCache\ProxyClient\HttpDispatcher;
use FOS\HttpCache\EventListener\LogListener;
use FOS\HttpCache\Exception\ExceptionCollection;


class CacheInvalidator
{
    private static $instance;

    private $cacheInvalidator;

    private function __construct()
    {
        $servers = $this->getVarnishServers();
        $baseUri = \eZINI::instance()->variable('SiteSettings', 'SiteURL');

        (new Logger())->debug('Varnish servers: ' . implode(', ', $servers));

        $httpDispatcher = new HttpDispatcher($servers, $baseUri);
        $client = new ProxyClient\Varnish($httpDispatcher);
        $this->cacheInvalidator = new FosCacheInvalidator($client);

        $logListener = new LogListener(new Logger());
        $this->cacheInvalidator->getEventDispatcher()->addSubscriber($logListener);
    }

    private function getVarnishServers()
    {
        $ini = \eZINI::instance('site.ini');

        $hostname = $ini->variable('VarnishSettings', 'VarnishHostName');
        $port = '8080';

        if ($ini->hasVariable('VarnishSettings', 'VarnishPort')) {
            $port = $ini->variable('VarnishSettings', 'VarnishPort');
        }
        if (!empty($hostname)) {
            $servers = gethostbynamel($hostname);
            if (empty($servers)) {
                \eZDebug::writeError("Function gethostbynamel on $hostname returns empty result", __METHOD__);
            } else {
                foreach ($servers as $index => $server) {
                    $servers[$index] = $server . ':' . $port;
                }
            }
        }

        if (empty($servers)) {
            $servers = $ini->variable('VarnishSettings', 'VarnishServers');
        }

        return $servers;
    }

    /**
     * @return FosCacheInvalidator
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new CacheInvalidator();
            \eZExecution::addCleanupHandler(array('Opencontent\FosHttpCache\CacheInvalidator', 'flush'));
        }

        return self::$instance->cacheInvalidator;
    }

    public static function flush()
    {
        try {
            if (CacheInvalidator::instance()->flush() > 0) {
                (new Logger())->debug('Flush invalidations');
            }
        } catch (ExceptionCollection $exceptions) {
            /** @var \Exception $exception */
            foreach ($exceptions as $exception) {
                (new Logger())->error($exception->getMessage());
            }
        } catch (\Exception $exception) {
            (new Logger())->error($exception->getMessage());
        }
    }
}