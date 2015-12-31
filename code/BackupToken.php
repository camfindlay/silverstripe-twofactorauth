<?php

namespace _2fa;

class BackupToken extends \DataObject
{
    private static $db = array(
        'Value' => 'Varchar',
    );

    private static $has_one = array(
        'Member' => 'Member',
    );

    private static $summary_fields = array(
        'Value',
    );

    public function getTitle()
    {
        return $this->Value;
    }

    private static $singular_name = 'OTP Backup Token';

    private static $single_use = true;
}
