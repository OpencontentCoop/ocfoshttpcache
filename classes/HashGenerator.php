<?php
namespace Opencontent\FosHttpCache;

use FOS\HttpCache\UserContext\DefaultHashGenerator;

class HashGenerator
{
    private static $instance;

    private $hashGenerator;

    private function __construct($includeUserIdProvider = false)
    {
        $providers = [
            new ContextProvider\IsAuthenticatedProvider(),
            new ContextProvider\RoleProvider(),
        ];
        if ($includeUserIdProvider){
            $providers[] = new ContextProvider\UserIdProvider();
        }

        $this->hashGenerator = new DefaultHashGenerator($providers);
    }

    /**
     * @return DefaultHashGenerator
     */
    public static function instance()
    {
        if (self::$instance === null){
            $includeUserIdProvider = false;
            if (\eZINI::instance()->hasVariable('UserContextHash', 'IncludeCurrentUserId')
                && \eZINI::instance()->variable('UserContextHash', 'IncludeCurrentUserId') == 'enabled'){
                $includeUserIdProvider = true;
            }
            self::$instance = new HashGenerator($includeUserIdProvider);
        }

        return self::$instance->hashGenerator;
    }
}