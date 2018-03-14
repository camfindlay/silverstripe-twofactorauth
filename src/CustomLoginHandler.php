<?php

namespace _2fa;

use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Dev\Debug;

class CustomLoginHandler extends LoginHandler
{

    private static $allowed_actions = [
        'step2',
        'secondStepForm',
    ];

    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        if ($member = $this->checkLogin($data, $request, $result)) {
            $session = $request->getSession();
            $session->set('CustomLoginHandler.MemberID', $member->ID);
            $session->set('CustomLoginHandler.Data', $data);
            return $this->redirect($this->link('step2'));
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

    public function secondStepForm()
    {
        return new Form(
            $this,
            "secondStepForm",
            new FieldList(
                new TextField('SecondFactor', 'Your 2FA (12345)')
            ),
            new FieldList(
                new FormAction('completeSecondStep', 'Login in')
            )
        );
    }

    public function completeSecondStep($data, Form $form, HTTPRequest $request)
    {
        if ($this->checkSecondFactor($data)) {
            $session = $request->getSession();
            $memberID = $session->get('CustomLoginHandler.MemberID');
            $member = Member::get()->byID($memberID);
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

    protected function checkSecondFactor($data)
    {
        return $data['SecondFactor'] === '12345';
    }
}
