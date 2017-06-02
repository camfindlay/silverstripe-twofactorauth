<?php

namespace _91Carriage;

/**
 * This namespaced class is kept to provide for migration of data across tables.
 *
 * DEPRECATION WARNING: Ensure you use _2fa\BackupToken for any extensions of the modules code. 
 * This class will be removed at some point in the future (likely any 2.x release).
 **/
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

    /**
     * Migrates the old namespace to new.
     */
    public function requireDefaultRecords()
    {
        $tokens = \_91Carriage\BackupToken::get();
        if ($tokens->exists()) {
            foreach ($tokens as $token) {
                $migrate = \_2fa\BackupToken::create();
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

    public function getTitle()
    {
        return $this->Value;
    }

    private static $singular_name = 'OTP Backup Token';

    private static $single_use = true;
}
