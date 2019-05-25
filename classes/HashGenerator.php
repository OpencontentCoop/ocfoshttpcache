<?php
namespace Opencontent\FosHttpCache;

use FOS\HttpCache\UserContext\DefaultHashGenerator;

class HashGenerator
{
    private static $instance;

    private $hashGenerator;

    private function __construct()
    {
        $this->hashGenerator = new DefaultHashGenerator([
            new ContextProvider\IsAuthenticatedProvider(),
            new ContextProvider\RoleProvider(),
        ]);
    }

    /**
     * @return DefaultHashGenerator
     */
    public static function instance()
    {
        if (self::$instance === null){
            self::$instance = new HashGenerator();
        }

        return self::$instance->hashGenerator;
    }
}