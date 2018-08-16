<?php

namespace _2fa\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\DataExtension;

class Group extends DataExtension
{
    private static $db = [
        'Require2FA' => 'Boolean(0)'
    ];
    
    public function updateCMSFields(FieldList $fields)
    {
        $fields->insertAfter('Description', CheckboxField::create(
            'Require2FA',
            'Require 2FA for all Group Members',
            $this->owner->Require2FA
        ));
    }
}
