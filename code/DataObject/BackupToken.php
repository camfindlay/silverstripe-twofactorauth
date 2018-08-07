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
    /**
     * @var array
     */
    private static $db = [
        'Value' => 'Varchar(255)',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Member' => 'SilverStripe\Security\Member',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Value',
    ];

    /**
     * @var string
     */
    private static $singular_name = 'OTP Backup Token';

    /**
     * @var int
     */
    private static $num_backup_tokens = 5;

    /**
     * @var string
     */
    private static $table_name = '_2fa_BackupToken';

    /**
     * BackupToken constructor.
     * @param null $record
     * @param bool $isSingleton
     * @param null $model
     */
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

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->Value;
    }
}
