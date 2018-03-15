<?php

namespace _2fa;


use SilverStripe\Control\Session;
use SilverStripe\Security\Member;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;


class LoginForm extends MemberLoginForm
{
    private static $allowed_actions = array('cancel');

    /**
     * Action to unset the TOTP.ID session var to allow going back to the normal (email/pw) login form
     */
    public function cancel() {

        throw new \Exception("_2fa\LoginForm is likely no longer needed. Check what's calling this.");
        
        $this->getRequest()->getSession()->clear('TOTP.ID');
        Controller::curr()->redirectBack();
    }

    public function doLogin($data)
    {
        throw new \Exception("_2fa\LoginForm is likely no longer needed. Check what's calling this.");

        $session = $this->getRequest()->getSession();
        
        if ($session->get('TOTP.ID')) {
            // Figure out what to do
            if (empty($data['TOTP'])) {
                return $this->returnToForm();
            } else {
                $member = Member::get()->byID($session->get('TOTP.ID'));
                if (!$member) {
                    $session->clear('TOTP.ID');

                    return $this->returnToForm();
                }
                if ($member->validateTOTP($data['TOTP'])) {
                    $session->clear('TOTP.ID');
                    $member->LogIn($session->get('TOTP.Remember'));
                    $data = array('Remember' => $session->get('TOTP.Remember'));

                    return $this->logInUserAndRedirect($data);
                } else {
                    $this->sessionMessage('Incorrect security token', 'bad');

                    return $this->returnToForm();
                }
            }
        } else {
            $member = call_user_func(
                array($this->authenticator_class, 'authenticate'),
                $data, $this
            );
            if ($member) {
                if ($member->Has2FA) {
                    $session->set('TOTP.ID', $member->ID);
                    $session->set('TOTP.Remember', !empty($data['Remember']));
                } else {
                    $member->LogIn(!empty($data['Remember']));

                    return $this->logInUserAndRedirect($data);
                }
            } else {
                $session->set('SessionForms.MemberLoginForm.Email',
                    $data[Email::class]);
                $session->set('SessionForms.MemberLoginForm.Remember',
                    !empty($data['Remember']));
            }
            $this->returnToForm();
        }
    }

    protected function returnToForm()
    {
        $session = $this->getRequest()->getSession();
        
        if (isset($_REQUEST['BackURL'])) {
            $backURL = $_REQUEST['BackURL'];
        } else {
            $backURL = null;
        }

        if ($backURL) {
            $session->set('BackURL', $backURL);
        }

        // Show the right tab on failed login
        $loginLink = Director::absoluteURL(
            $this->controller->Link('login')
        );
        if ($backURL) {
            $loginLink .= '?BackURL='.urlencode($backURL);
        }
        $loginLink .= '#'.$this->FormName().'_tab';
        $this->controller->redirect($loginLink);
    }

    public function Actions()
    {
        throw new \Exception("_2fa\LoginForm is likely no longer needed. Check what's calling this.");
        
        $actions = parent::Actions();
        $fields = $this->Fields();

        // Remove the lost-password action from the TOTP token form and insert a cancel button
        if ($fields->fieldByName('TOTP')) {
            $actions->push(\FormAction::create("cancel", _t('LeftAndMain.CANCEL', "Cancel")));
            $actions->removeByName('forgotPassword');
            $actions->push(\FormAction::create("cancel", _t('LeftAndMain.CANCEL', "Cancel")));
        }

        return $actions;
    }

    public function Fields()
    {
        throw new \Exception("_2fa\LoginForm is likely no longer needed. Check what's calling this.");
        
        $session = $this->getRequest()->getSession();
        
        if (!$session->get('TOTP.ID')) {
            return parent::Fields();
        }
        $security_token = $this->getSecurityToken();
        $fields = FieldList::create(
            TextField::create('TOTP', 'Security Token'),
            HiddenField::create('BackURL', null, $session->get('BackURL')),
            HiddenField::create($security_token->getName(), null, $security_token->getSecurityID())
        );
        foreach ($this->getExtraFields() as $field) {
            if (!$fields->fieldByName($field->getName())) {
                $fields->push($field);
            }
        }

        return $fields;
    }
}
