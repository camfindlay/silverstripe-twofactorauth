<?php

namespace _91Carriage\Migration;

use SilverStripe\ORM\DataObject;

/**
 * This namespaced class is kept to provide for migration of data across tables.
 *
 * DEPRECATION WARNING: Ensure you use _2fa\BackupToken for any extensions of the modules code. 
 * This class will be removed at some point in the future (likely any 2.x release).
 **/
class BackupToken extends DataObject
{
    /**
     * @var array
     */
    private static $db = array(
        'Value' => 'Varchar',
    );

    /**
     * @var array
     */
    private static $has_one = array(
        'Member' => 'Member',
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Value',
    );

    /**
     * @var string
     */
    private static $table_name = "_91Carriage_BackupToken";

    /**
     * Migrates the old namespace to new.
     */
    public function requireDefaultRecords()
    {
        $tokens = BackupToken::get();
        if ($tokens->exists()) {
            foreach ($tokens as $token) {
                $migrate = \_2fa\DataObject\BackupToken::create();
                $migrate->ID = $token->ID;
                $migrate->Created = $token->Created;
                $migrate->Value = $token->Value;
                $migrate->MemberID = $token->MemberID;
                $migrate->write(false, true, true);
                $token->delete();
            }
            \DB::alteration_message('Two factor auth backup tokens migrated from version 1.0.x to 1.1.x', 'obsolete');
        }
    }

    /**
     * @return mixed|string
     */
    public function getTitle()
    {
        return $this->Value;
    }

    /**
     * @var string
     */
    private static $singular_name = 'OTP Backup Token';

    /**
     * @var bool
     */
    private static $single_use = true;
}
