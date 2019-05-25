<?php

namespace Opencontent\FosHttpCache\ContextProvider;

use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\UserContext;

class IsAuthenticatedProvider implements ContextProvider
{
    public function updateUserContext(UserContext $context)
    {
        $context->addParameter('authenticated', \eZUser::currentUser()->isRegistered());
    }
}
