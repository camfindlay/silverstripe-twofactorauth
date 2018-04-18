<?php

namespace _2fa;

use SilverStripe\Security\Security;
use SilverStripe\Security\MemberAuthenticator\LoginHandler as SS_LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;

class LoginHandler extends SS_LoginHandler
{

    private static $allowed_actions = [
        'step2',
        'secondStepForm',
        'twofactorsetup',
        'twoFactorSetupFrom',
        'verify_and_activate',
        'twofactorcomplete',
        'show_backup_tokens',
    ];

    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        if ($member = $this->checkLogin($data, $request, $result)) {
            $session = $request->getSession();
            $session->set('CustomLoginHandler.MemberID', $member->ID);
            $session->set('CustomLoginHandler.Data', $data);
            if($member->Has2FA) {
                return $this->redirect($this->link('step2'));
            } else {
                return $this->redirect($this->link('twofactorsetup'));
            }
        }

        // Fail to login redirects back to form
        return $this->redirectBack();
    }

    public function step2()
    {
        return [
            "Form" => $this->secondStepForm()
        ];
    }

    public function twofactorsetup()
    {
        return [
            "Form" => $this->twoFactorSetupFrom()
        ];
    }

    public function twofactorcomplete()
    {
        return $this->redirectAfterSuccessfulLogin();
    }
    
    public function twoFactorSetupFrom()
    {
        $session  = $this->request->getSession();
        $memberID = $session->get('CustomLoginHandler.MemberID');
        $member   = Member::get()->byID($memberID);
        $member->generateTOTPToken();
        $member->write();

        return $member
                ->customise(array(
                    'CurrentController' => $this,
                ))
                ->renderWith('TokenInfoDialog');
    }
    
    /**
     * Function to allow verification & activation of two-factor-auth via Ajax
     *
     * @param $data
     * @param $form
     * @return \SS_HTTPResponse
     */
    public function verify_and_activate($request)
    {
        $session  = $this->request->getSession();
        $memberID = $session->get('CustomLoginHandler.MemberID');
        $member   = Member::get()->byID($memberID);
        if (!$member) {
            return;
        }

        $TokenCorrect   = $member->validateTOTP((string) $request->postVar('VerificationInput'));

        if ($TokenCorrect) {
            $member->Has2FA = true;
            $member->write();

            $data = $session->get('CustomLoginHandler.Data');
            if (!$member) {

                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);

            return $this->redirect($this->link('show_backup_tokens'));
        }

        // else: show feedback
        return [
            "Form" => $member->customise(array(
                    'CurrentController' => $this,
                    'VerificationError' => true,
                ))
                ->renderWith('TokenInfoDialog')
        ];
    }

    public function show_backup_tokens()
    {
        $member = Security::getCurrentUser();
        
        if(!$member->BackupTokens()->count()) {
            $member->regenerateBackupTokens();
        }
        
        return [
            "Title" => 'Two Factor Back Up Tokens',
            "Content" => $member->customise(array(
                "backUrl" => $this->getBackURL()
            ))
            ->renderWith('ShowBackUpTokens')
        ];
    }
    
    public function secondStepForm()
    {
        return new Form(
            $this,
            "secondStepForm",
            new FieldList(
                new TextField('SecondFactor', 'Access Token')
            ),
            new FieldList(
                new FormAction('completeSecondStep', 'Log in')
            )
        );
    }

    public function completeSecondStep($data, Form $form, HTTPRequest $request)
    {
        $session = $request->getSession();
        $memberID = $session->get('CustomLoginHandler.MemberID');
        $member = Member::get()->byID($memberID);
        if ($member->validateTOTP($data['SecondFactor'])) {
            $data = $session->get('CustomLoginHandler.Data');
            if (!$member) {
                
                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);
            
            return $this->redirectAfterSuccessfulLogin();
        }

        // Fail to login redirects back to form
        return $this->redirectBack();
    }

}
