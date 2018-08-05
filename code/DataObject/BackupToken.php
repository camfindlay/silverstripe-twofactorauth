<?php

namespace _2fa\DataObject;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * @property string $Value
 *
 * @method Member Member()
 */
class BackupToken extends DataObject
{
    private static $db = [
        'Value' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Member' => 'SilverStripe\Security\Member',
    ];

    private static $summary_fields = [
        'Value',
    ];

    private static $singular_name = 'OTP Backup Token';

    private static $num_backup_tokens = 5;

    private static $table_name = '_2fa_BackupToken';

    public function __construct($record = null, $isSingleton = false, $model = null)
    {
        parent::__construct($record, $isSingleton, $model);
        if (!$this->ID) {
            $new_value = '';
            foreach (range(1, 10) as $i) {
                $new_value .= mt_rand(0, 9);
            }
            $this->Value = $new_value;
        }
    }

    public function getTitle()
    {
        return $this->Value;
    }
}
