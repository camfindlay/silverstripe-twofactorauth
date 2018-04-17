<?php

namespace _2fa;

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
        'twofactorsetup',
        'twoFactorSetupFrom',
        'secondStepForm',
        'verify_and_activate',
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

        $member->Has2FA = true; // needed voor validation process
        $TokenCorrect   = $member->validateTOTP((string) $request->postVar('VerificationInput'));
        $member->Has2FA = false; // reset just to be sure

        if ($TokenCorrect) {
            $member->Has2FA = true;
            $member->write();

            $data = $session->get('CustomLoginHandler.Data');
            if (!$member) {

                return $this->redirectBack();
            }
            $this->performLogin($member, $data, $request);

            return $this->redirectAfterSuccessfulLogin();
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

    public function secondStepForm()
    {
        return new Form(
            $this,
            "secondStepForm",
            new FieldList(
                new TextField('SecondFactor', 'Your 2FA (12345)')
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

//    protected function checkSecondFactor($data)
//    {
//        return $data['SecondFactor'] === '12345';
//    }
}
