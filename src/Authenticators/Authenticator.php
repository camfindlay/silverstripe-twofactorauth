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
 * Use YAML _2fa/Authenticator: enforce_2fa or SiteConfig::enable2fa to
 * decide which LoginHandler to return. When enforce_2fa is set the
 * _2fa/LoginHandler will always be used, otherwise The regular
 * SilverStripe login handler is used.
 * 
 */
class Authenticator extends MemberAuthenticator
{
    /**
     * Selects the login handler based on enforce_2fa or enable2fa
     * 
     * @param string $link
     * @return LoginHandler
     */
    public function getLoginHandler($link)
    {
        if (Config::inst()->get(__CLASS__, 'enforce_2fa')
            || SiteConfig::current_site_config()->enable2fa
        ) {

            return LoginHandler::create($link, $this);
        }

        return SS_LoginHandler::create($link, $this);
    }
}
