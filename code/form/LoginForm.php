<?php

namespace _2fa;

use Session;
use Director;

class LoginForm extends \MemberLoginForm
{
    public function doLogin($data)
    {
        if (Session::get('TOTP.ID')) {
            // Figure out what to do
            if (empty($data['TOTP'])) {
                return $this->returnToForm();
            } else {
                $member = \Member::get()->byID(Session::get('TOTP.ID'));
                if (!$member) {
                    Session::clear('TOTP.ID');

                    return $this->returnToForm();
                }
                if ($member->validateTOTP($data['TOTP'])) {
                    Session::clear('TOTP.ID');
                    $member->LogIn(Session::get('TOTP.Remember'));
                    $data = array('Remember' => Session::get('TOTP.Remember'));

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
                    Session::set('TOTP.ID', $member->ID);
                    Session::set('TOTP.Remember', !empty($data['Remember']));
                } else {
                    $member->LogIn(!empty($data['Remember']));

                    return $this->logInUserAndRedirect($data);
                }
            } else {
                Session::set('SessionForms.MemberLoginForm.Email',
                    $data['Email']);
                Session::set('SessionForms.MemberLoginForm.Remember',
                    !empty($data['Remember']));
            }
            $this->returnToForm();
        }
    }

    protected function returnToForm()
    {
        if (isset($_REQUEST['BackURL'])) {
            $backURL = $_REQUEST['BackURL'];
        } else {
            $backURL = null;
        }

        if ($backURL) {
            Session::set('BackURL', $backURL);
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
        $actions = parent::Actions();
        $fields = $this->Fields();

        // Remove the lost-password action from the TOTP token form
        if ($fields->fieldByName('TOTP')) {
            $actions->removeByName('forgotPassword');
        }
        return $actions;
    }

    public function Fields()
    {
        if (!Session::get('TOTP.ID')) {
            return parent::Fields();
        }
        $security_token = $this->getSecurityToken();
        $fields = \FieldList::create(
            \TextField::create('TOTP', 'Security Token'),
            \HiddenField::create('BackURL', null, Session::get('BackURL')),
            \HiddenField::create($security_token->getName(), null, $security_token->getSecurityID())
        );
        foreach ($this->getExtraFields() as $field) {
            if (!$fields->fieldByName($field->getName())) {
                $fields->push($field);
            }
        }

        return $fields;
    }
}
