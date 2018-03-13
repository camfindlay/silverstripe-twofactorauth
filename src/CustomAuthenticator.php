<?php

namespace _2fa;

use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

class CustomAuthenticator extends MemberAuthenticator
{
    /**
     * @inherit
     */
    public function getLoginHandler($link)
    {
        return CustomLoginHandler::create($link, $this);
    }
}
