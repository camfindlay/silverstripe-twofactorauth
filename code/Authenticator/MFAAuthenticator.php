<?php

namespace _2fa\Authenticator;

use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

/**
 * Class MFAAuthenticator
 * @package _2fa\Authenticator
 */
class MFAAuthenticator extends MemberAuthenticator
{
    /**
     * @param string $link
     * @return MFALoginHandler|\SilverStripe\Security\MemberAuthenticator\LoginHandler
     */
    public function getLoginHandler($link)
    {
        return MFALoginHandler::create($link, $this);
    }
}