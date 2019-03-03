<?php
namespace _2fa;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;
use Rych\OTP\TOTP;

class MemberExtensionTest extends FunctionalTest
{
    protected static $fixture_file = '2FAMemberTest.yml';
    protected $orig = array();
    
    protected $totp_window = 2;

    public function __construct()
    {
        parent::__construct();
    }

    public function setUp()
    {
        parent::setUp();
        Member::set_password_validator(null);
    }


    public function testValidateTOTP()
    {
        $member = $this->objFromFixture(Member::class, 'admin');
        $this->assertNotNull($member);
        
        $otp = 'test';
        $member->Has2FA = true;
        $member->TOTPToken = 'testTOTPTokenSeed';
        
        $this->assertFalse($member->validateTOTP($otp));
        
        $otp = $this->genetrateOTP($member->TOTPToken);
        
        $this->assertTrue($member->validateTOTP($otp));
    }

    public function testValidateBackUpToken()
    {
        $member = $this->objFromFixture(Member::class, 'admin');
        $this->assertNotNull($member);
        
        $member->Has2FA = true;
        $member->TOTPToken = 'testTOTPTokenSeed';
        $member->BackupTokens()->add(
            BackupToken::create()
        );
        $backupToken = $member->BackupTokens()->first()->Value;
        $this->assertTrue($member->validateTOTP($backupToken));
    }
    
    public function testgenerateTOTPToken()
    {
        $member = $this->objFromFixture(Member::class, 'admin');
        $this->assertNotNull($member);

        $member->Has2FA    = true;
        $member->TOTPToken = 'testTOTPTokenSeed';
        
        $this->assertEquals('testTOTPTokenSeed', $member->TOTPToken);
        $member->generateTOTPToken();
        $this->assertNotEquals('testTOTPTokenSeed', $member->TOTPToken);
    }

    public function testgenerateQRCode()
    {
        $member = $this->objFromFixture(Member::class, 'admin');
        $this->assertNotNull($member);

        $member->Has2FA    = true;
        $member->TOTPToken = 'testTOTPTokenSeed';

        $QRCode = $member->generateQRCode();
        
        $this->assertStringStartsWith('data:image/png;base64', $QRCode);
    }

    public function genetrateOTP($TOTPToken)
    {
        $totp    = new TOTP($TOTPToken, array('window' => $this->totp_window));
        $current = max(0, time() - intval(floor($this->totp_window / 2)));
        return $totp->calculate($current);
    }
}
