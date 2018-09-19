<?php

namespace _2fa;

use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\LoginHandler as SS_LoginHandler;

/**
 * Wrapper over MemberAuthenticator to replace:
 *  SilverStripe\Security\MemberAuthenticator\LoginHandler
 * with
 *  _2fa\LoginHandler
 *
 * Use YAML _2fa/Authenticator: enable_2fa or SiteConfig::enable2fa to
 * decide which LoginHandler to return. When enable_2fa is set the
 * _2fa/LoginHandler will always be used, otherwise The regular
 * SilverStripe login handler is used.
 *
 */
class Authenticator extends MemberAuthenticator
{
    /**
     * Selects the login handler based on enable_2fa or enable2fa
     *
     * @param string $link
     * @return LoginHandler
     */
    public function getLoginHandler($link)
    {
        if ($this->is2FAenabled()) {
            return LoginHandler::create($link, $this);
        }

        return SS_LoginHandler::create($link, $this);
    }

    public function is2FAenabled()
    {
        return (Config::inst()->get(Authenticator::class, 'enable_2fa')
            || SiteConfig::current_site_config()->enable2fa);
    }

    public function is2FArequired($member)
    {
        return  $member->is2FArequired()
            || (Config::inst()->get(Authenticator::class, 'require_2fa')
            || SiteConfig::current_site_config()->require2fa);
    }
}
