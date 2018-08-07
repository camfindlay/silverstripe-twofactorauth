<?php

namespace _2fa\Authenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\LoginHandler;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;
use SilverStripe\Security\Security;

/**
 * Class MFALoginHandler
 * @package _2fa\Authenticator
 */
class MFALoginHandler extends LoginHandler
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'mfaToken',
        'mfaForm',
        'cancel'
    ];

    /**
     * Action to unset the TOTP.ID session var to allow going back to the normal (email/pw) login form
     */
    public function cancel() {
        $request = Controller::curr()->getRequest();
        $session = $request->getSession(); /** @var Session $session */
        $session->clear('TOTP.ID');
        $session->clear('MFAAuthenticator.Data');
        return $this->redirectBack();
    }

    /**
     * @param array $data
     * @param MemberLoginForm $form
     * @param HTTPRequest $request
     * @return \SilverStripe\Control\HTTPResponse
     */
    public function doLogin($data, MemberLoginForm $form, HTTPRequest $request)
    {
        $failureMessage = null;

        $this->extend('beforeLogin');

        /** @var ValidationResult $result */
        if ($member = $this->checkLogin($data, $request, $result)) {

            // Do normal login for non 2FA member
            if (!$member->Has2FA) {
                parent::doLogin($data, $form, $request);
            }

            $request = Controller::curr()->getRequest();
            $session = $request->getSession();

            $session->set('MFAAuthenticator.MemberID', $member->ID);
            $session->set('MFAAuthenticator.Data', $data);
            return $this->redirect($this->link('mfaToken'));
        }

        // Fail to login redirects back to form
        $this->extend('failedLogin');

        $message = implode("; ", array_map(
            function ($message) {
                return $message['message'];
            },
            $result->getMessages()
        ));

        $form->sessionMessage($message, 'bad');

        // Failed login

        /** @skipUpgrade */
        if (array_key_exists('Email', $data)) {
            $rememberMe = (isset($data['Remember']) && Security::config()->get('autologin_enabled') === true);
            $this
                ->getRequest()
                ->getSession()
                ->set('SessionForms.MemberLoginForm.Email', $data['Email'])
                ->set('SessionForms.MemberLoginForm.Remember', $rememberMe);
        }

        // Fail to login redirects back to form
        return $form->getRequestHandler()->redirectBackToForm();
    }


    /**
     * @return array
     */
    public function mfaToken()
    {
        return [
            "Form" => $this->mfaForm()
        ];
    }

    /**
     * @return Form
     */
    public function mfaForm()
    {
        return new Form(
            $this,
            "mfaForm",
            new FieldList([
                TextField::create('TOTP', 'Security Token')
            ]),
            new FieldList([
                FormAction::create('completeMFALogin', 'Login'),
                FormAction::create('cancel', 'Cancel')
            ])
        );
    }

    /**
     * @param $data
     * @param Form $form
     * @param HTTPRequest $request
     * @return \SilverStripe\Control\HTTPResponse
     */
    public function completeMFALogin($data, Form $form, HTTPRequest $request)
    {
        $request = Controller::curr()->getRequest();
        $session = $request->getSession();

        if (!$session->get('MFAAuthenticator.MemberID')) {
            return parent::redirectBack();
        }

        /** @var Member $member */
        $member = Member::get()->byID($session->get('MFAAuthenticator.MemberID'));

        if (!$member) {
            return parent::redirectBack();
        }

        if ($member->validateTOTP($data['TOTP'])) {
            $loginData = $session->get('MFAAuthenticator.Data');
            $this->performLogin($member, $loginData, $request);

            $session->clear('TOTP.ID');
            $session->clear('MFAAuthenticator.Data');

            // Allow operations on the member after successful login
            $this->extend('afterLogin', $member);

            return $this->redirectAfterSuccessfulLogin();
        } else {
            $message = "An incorrect token was specified.";
            $form->sessionMessage($message, 'bad');
        }

        // Fail to login redirects back to form
        return $this->redirect($this->link('mfaToken'));
    }



}
