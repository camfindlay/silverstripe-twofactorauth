<?php

namespace _2fa\Extensions;

use Rych\OTP\Seed;
use Rych\OTP\TOTP;
use _2fa\BackupToken;
use _2fa\Authenticator;
use Endroid\QrCode\QrCode;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Permission;
use SilverStripe\SiteConfig\SiteConfig;


/**
 * @property \Member $owner
 * @property bool $Has2FA
 * @property string $TOTPToken
 *
 * @method BackupToken BackupTokens()
 */
class Member extends DataExtension
{
    private static $db = array(
        'Has2FA' => 'Boolean',
        'TOTPToken' => 'Varchar(160)',
    );

    private static $has_many = array(
        'BackupTokens' => '_2fa\BackupToken',
    );

    public function validateTOTP($token)
    {
        assert(is_string($token));

        $seed = $this->OTPSeed();
        if (!$seed) {
            return false;
        }
        $window = (int) Config::inst()->get(Authenticator::class, 'totp_window');
        $totp = new TOTP($seed, array('window' => $window));

        $valid = $totp->validate($token);

        // Check backup tokens if unsuccessful
        if (!$valid) {
            $backup_tokens = $this->owner->BackupTokens()->filter('Value', $token);
            if ($backup_tokens->count()) {
                $candidate_backup_token = $backup_tokens->first();
                if ($token === $candidate_backup_token->Value) {
                    $valid = true;
                    $candidate_backup_token->delete();
                }
            }
        }

        return $valid;
    }

    private function getPrintableTOTPToken()
    {
        $seed = $this->OTPSeed();

        return $seed ? $seed->getValue(Seed::FORMAT_BASE32) : '';
    }

    private function OTPSeed()
    {
        if ($this->owner->TOTPToken) {
            return new Seed($this->owner->TOTPToken);
        }

        return;
    }

    /**
     * Allow other admins to turn off 2FA if it is set & admins_can_disable is
     * set in the config.
     * 2FA in general is managed in the user's own profile.
     *
     * @param \FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        // Generate default token (allows scanning the QR at the moment of
        // activation and (optionally) validate before activating 2FA)
        if (!$this->owner->TOTPToken
            && Config::inst()->get(Authenticator::class, 'validated_activation_mode')
        ) {
            $this->generateTOTPToken();
            $this->owner->write();
        }

        $fields->removeByName('TOTPToken');
        $fields->removeByName('BackupTokens');
        if (!(Config::inst()->get(Authenticator::class, 'admins_can_disable')
            && $this->owner->Has2FA && Permission::check('ADMIN'))
        ) {
            $fields->removeByName('Has2FA');
        }
    }

    public function updateFieldLabels(&$labels)
    {
        $labels['Has2FA'] = 'Enable Two Factor Authentication';
    }

    public function generateTOTPToken($bytes = 20)
    {
        $seed = Seed::generate($bytes);
        $this->owner->TOTPToken = $seed->getValue(Seed::FORMAT_HEX);
    }

    /**
     * Delete a member's backup tokens when deleting the member.
     */
    public function onBeforeDelete()
    {
        foreach ($this->owner->BackupTokens() as $bt) {
            $bt->delete();
        }
        parent::onBeforeDelete();
    }

    private function getOTPUrl()
    {
        if (class_exists(SiteConfig::class)) {
            $config = SiteConfig::current_site_config();
            $issuer = $config->Title;
        } else {
            $issuer = explode(':', $_SERVER['HTTP_HOST']);
            $issuer = $issuer[0];
        }
        $label = sprintf('%s: %s', $issuer, $this->owner->Name);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s',
            rawurlencode($label),
            $this->getPrintableTOTPToken(),
            rawurlencode($issuer)
        );
    }

    public function generateQRCode()
    {
        $qrCode = new QrCode();
        $qrCode->setText($this->getOTPUrl());
        $qrCode->setSize(175);

        return $qrCode->getDataUri();
    }
    
    public function regenerateBackupTokens()
    {
        $member = $this->owner;
        $backup_token_list = $member->BackupTokens();
        foreach ($backup_token_list as $bt) {
            $bt->delete();
        }
        foreach (range(1,Config::inst()->get('_2fa\BackupToken', 'num_backup_tokens')) as $i) {
            $token = BackupToken::create();
            $backup_token_list->add($token);
        }
    }
}


