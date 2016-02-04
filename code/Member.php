<?php

namespace _2fa;

use Rych\OTP\TOTP;
use Rych\OTP\Seed;
use Endroid\QrCode\QrCode;

class Member extends \DataExtension
{
    private static $db = array(
        'Has2FA' => 'Boolean',
        'TOTPToken' => 'Varchar(160)',
    );

    private static $has_many = array(
        'BackupTokens' => '_2fa\BackupToken',
    );

    private static $totp_window = 2;

    public function validateTOTP($token)
    {
        assert(is_string($token));

        if (!$this->owner->Has2FA) {
            return true;
        }
        $seed = $this->OTPSeed();
        if (!$seed) {
            return true;
        }
        $window = (int)\Config::inst()->get(__CLASS__, 'totp_window');
        $totp = new TOTP($seed, array('window' => $window));

        $valid = $totp->validate($token);

        if (!$valid) {
            foreach ($this->owner->BackupTokens() as $bt) {
                if ($bt->Value == $token) {
                    $valid = true;
                    if ($bt::config()->single_use) {
                        $bt->delete();
                    }
                }
            }
        }

        return $valid;
    }

    public function getPrintableTOTPToken()
    {
        $seed = $this->OTPSeed();
        return $seed ? $seed->getValue(Seed::FORMAT_BASE32) : '';
    }

    public function OTPSeed()
    {
        if ($this->owner->TOTPToken) {
            return new Seed($this->owner->TOTPToken);
        }
        return null;
    }

    public function updateCMSFields(\FieldList $fields)
    {
        if ($this->owner->Has2FA) {
            // HACK HACK HACK
            $field = \LiteralField::create(
                'PrintableTOTPToken',
                sprintf(
                    '<div id="PrintableTOTPToken" class="field readonly">
	<label class="left" for="Form_EditForm_PrintableTOTPToken">TOTP Token</label>
	<div class="middleColumn">
	<span id="Form_EditForm_PrintableTOTPToken" class="readonly">
		%s<br />
<img src="%s" width=175 height=175 />
	</span>
	</div>
</div>',
                    $this->getPrintableTOTPToken(),
                    $this->generateQRCode()
                )
            );
            $fields->replaceField('TOTPToken', $field);
        } else {
            $fields->removeByName('TOTPToken');
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

    public function onBeforeWrite()
    {
        if ($this->owner->isChanged('Has2FA', 2) && $this->owner->Has2FA) {
            $this->generateTOTPToken();
        }
    }

    public function getOTPUrl()
    {
        if (class_exists('SiteConfig')) {
            $config = \SiteConfig::current_site_config();
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
        $qrCode->setPadding(10);
        $data = $qrCode->get(QrCode::IMAGE_TYPE_GIF);
        $data = base64_encode($data);
        return sprintf('data:image/gif;base64,%s', $data);
    }
}
