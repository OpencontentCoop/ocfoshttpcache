<?php

namespace Opencontent\FosHttpCache\ContextProvider;

use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\UserContext;

class RoleProvider implements ContextProvider
{
    public function updateUserContext(UserContext $context)
    {
        $user = \eZUser::currentUser();
        $roles = implode('.', $user->roleIDList());
        $limitations = implode('.', $user->limitValueList());

        $context->addParameter('roles_and_limitations', $roles . '-' . $limitations);
    }
}
