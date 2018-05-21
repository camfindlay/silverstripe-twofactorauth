<?php

namespace _2fa\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\FieldType\DBBoolean;

class SiteConfigExtension extends DataExtension
{
    private static $db = array(
        'enable2fa' => DBBoolean::class,
        'require2fa' => DBBoolean::class,
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.TwoFactorAuthentication',
            array(
                FieldGroup::create(
                    CheckboxField::create('enable2fa', '')
                )
                ->setTitle(
                    _t(
                        "TWOFACTOR.ENABLETWOFACTOR",
                        "Enable Two Factor Authentication"
                    )
                )
                ->setDescription(
                    _t(
                        "TWOFACTOR.ENABLETWOFACTORDESCRIPTION",
                        "Allow users to turn two factor authenication on or off"
                    )
                ),
                FieldGroup::create(
                    CheckboxField::create('require2fa', '')
                )
                ->setTitle(
                    _t(
                        "TWOFACTOR.REQUIRETWOFACTOR",
                        "Require Two Factor Authentication"
                    )
                )
                ->setDescription(
                    _t(
                        "TWOFACTOR.REQUIRETWOFACTORDESCRIPTION",
                        "Require users to use two factor authenication"
                    )
                ),
            )
        );
    }
}
