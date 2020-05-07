<?php

namespace Opencontent\FosHttpCache\ContextProvider;

use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\UserContext;

class UserIdProvider implements ContextProvider
{
    public function updateUserContext(UserContext $context)
    {
        $context->addParameter('id', \eZUser::currentUserID());
    }

}