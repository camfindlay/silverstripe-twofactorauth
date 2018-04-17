<?php

namespace _2fa;

use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

class Authenticator extends MemberAuthenticator
{
    /**
     * @inherit
     */
    public function getLoginHandler($link)
    {
        return LoginHandler::create($link, $this);
    }
}
