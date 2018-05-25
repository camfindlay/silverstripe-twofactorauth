<?php

namespace _2fa;

use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordForm as SS_ChangePasswordForm;

class ChangePasswordForm extends SS_ChangePasswordForm
{
    public function doChangePassword(array $data)
    {
        $backURL = $this->controller->Link('login');
        $_REQUEST['BackURL'] = $backURL;
        $loggedIn = Member::currentUser();
        parent::doChangePassword($data);
        if (!$loggedIn) {
            $member = Member::currentUser();
            if ($member && $member->Has2FA) {
                $member->logOut();
                $form = $this->controller->LoginForm();
                $form->sessionMessage(
                    'Password successfully changed. Please login.',
                    'good'
                );
            }
        }
    }
}
