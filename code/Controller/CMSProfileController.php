<?php

namespace _2fa\Controller;

use _2fa\DataObject\BackupToken;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class CMSProfileController extends \SilverStripe\Admin\CMSProfileController
{
    private static $allowed_actions = [
        'regenerate_backup_tokens',
        'regenerate_token',
        'load_token_data',
        'verify_and_activate',
        'verify_and_deactivate',
    ];

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $member = Security::getCurrentUser();

        if (!$member) {
            return $form;
        }

        $actions = $form->Actions();
        $fields = $form->Fields();

        // cleanup
        $fields->removeByName('BackupTokens');

        if(\_2fa\Extension\Member::validated_activation_mode()){
            // remove direct activation option
            $fields->removeByName('Has2FA');

            // activate/deactivate through button+popup instead
            $alterbutton = FormAction::create('open_deactivation_dialog')
                ->addExtraClass('twofactor_button twofactor_dialogbutton')
                ->setAttribute('data-infourl', $this->Link('load_token_data'))
                ->setUseButtonTag(true);

            if ($member->Has2FA) {
                $alterbutton
                    ->setButtonContent(
                        _t("TWOFACTOR.ACTIVATE2FA",'Two-Factor Authentication is: <strong>ACTIVE</strong><br><small>click to deativate</small>'))
                    ->setAttribute('data-icon', 'accept');
            } else {
                $alterbutton
                    ->setButtonContent(
                        _t("TWOFACTOR.ACTIVATE2FA",'Two-Factor Authentication is: <strong>NOT ACTIVE</strong><br><small>click to ativate</small>'))
                    ->setAttribute('data-icon', 'accept_disabled');
            }
            $fields->addFieldToTab('Root.TwoFactorAuthentication', $alterbutton);

            // token regeneration
            $actions->push(
                FormAction::create('regenerate_token')
                    ->setTitle('Reset two-factor KEY')
//                    ->addExtraClass('ss-ui-action-constructive')
                    ->addExtraClass('ss-ui-action-destructive')
                    ->addExtraClass('twofactor_regeneratetokens')
            );

        } else {
            // tokens will be regenerated upon each (re)activation of 2FA, in this modus the QR is shown AFTER reactivation
            $fields->addFieldToTab('Root.TwoFactorAuthentication',
                CheckboxField::create('Has2FA', 'Enable Two Factor Authentication', $member->Has2FA)
            );

            $this->addTokenInfo($fields);
        }

        // Backup tokens may always be shown
        $this->addBackupTokenInfo($fields, $actions);

        return $form;
    }

    private function addTokenInfo(FieldList &$fields)
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return;
        }

        if ($member->Has2FA) {
            // add token QR code
            $two_factor_fields[] = LiteralField::create('TokenSecurityWarning',
                _t("TWOFACTOR.TOKENSECURITYWARNING","<p><br>
                    The button below reveals your security token for scanning.<br>
                    <strong>Please reveal this only when no one else is observing your screen.</strong>
                    </p>")
            );
            $two_factor_fields[] = ToggleCompositeField::create('SecurityToken', 'Security token',
                LiteralField::create('PrintableTOTPToken', $member->renderWith('TokenInfo'))
            );
            $fields->addFieldsToTab('Root.TwoFactorAuthentication', $two_factor_fields);
        }
    }

    private function addBackupTokenInfo(FieldList &$fields, FieldList &$actions)
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return;
        }

        if ($member->Has2FA) {
            // backup-token info
            $backup_token_fields[] = LiteralField::create('BackupTokensSecurityWarning',
                _t("TWOFACTOR.BACKUPTOKENSECURITYWARNING","<p><br>
                    The button below reveals your backup tokens. These can each be used only once.<br>
                    <strong>Please reveal them only when no one else is observing your screen.</strong>
                    </p>")
            );
            $backup_token_fields[] = ToggleCompositeField::create('BackupTokens', 'Backup tokens', [
                LiteralField::create('DisplayBackupTokens', $member->renderWith('BackupTokenInfo'))
            ]);
            $fields->addFieldsToTab('Root.TwoFactorAuthentication', $backup_token_fields);

            // backup-tokens interaction
            $actions->push(
                FormAction::create('regenerate_backup_tokens')
                    ->setTitle('(Re)generate BACKUP tokens')
//                    ->addExtraClass('ss-ui-action-constructive')
                    ->addExtraClass('ss-ui-action-destructive')
                    ->addExtraClass('twofactor_regeneratetokens')
            );
        }
    }

    /**
     * This form action may get triggered to manually refresh secret/token when running in 'fixed' token mode (regenerate_on_activation = false)
     *
     * @param $data
     * @param $form
     * @return \SS_HTTPResponse
     */
    public function regenerate_token($data, $form)
    {
        $member = Security::getCurrentUser();
        // set new secret/token on member
        $member->generateTOTPToken();
        // if we're manually regenerating, user needs to re-activate & verify 2FA after token change
        $member->Has2FA = false;
        $member->write();

        $this->response
            ->addHeader('X-Status', 'Token/secret regenerated, please re-activate two-factor authentication')
            ->addHeader('X-Reload', true);

        return $this->getResponseNegotiator()->respond($this->request);
    }

    /**
     * Function to allow loading secret/QR code via Ajax
     *
     * @param $data
     * @param $form
     * @return \SS_HTTPResponse
     */
    public function load_token_data($request)
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return;
        }

        // If we're in validated activation mode, this is the appropriate moment to refresh the token
        if(\_2fa\Extension\Member::validated_activation_mode() && !$member->Has2FA){
            // set new secret/token on member
            $member->generateTOTPToken();
            $member->write();
        }

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
        $member = Security::getCurrentUser();
        if (!$member) {
            return;
        }

        $member->Has2FA = true; // needed voor validation process
        $TokenCorrect = $member->validateTOTP( (string) $request->postVar('VerificationInput') );
        $member->Has2FA = false; // reset just to be sure

        if($TokenCorrect){
            $member->Has2FA = true;
            $member->write();

            $this->response
                ->addHeader('X-Status', 'Two-Factor authentication activated successfully.')
                ->addHeader('X-Pjax', 'CurrentForm');

            return $this->getResponseNegotiator()->respond($this->request);
        }

        // else: show feedback
        return $member
            ->customise(array(
                'CurrentController' => $this,
                'VerificationError' => true,
            ))
            ->renderWith('TokenInfoDialog');
    }

    /**
     * Function to allow verification of password & dectivation of two-factor-auth via Ajax
     *
     * @param $data
     * @param $form
     * @return \SS_HTTPResponse
     */
    public function verify_and_deactivate($request)
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return;
        }

        $PasswordCorrect = $member->checkPassword((string) $request->postVar('VerificationInput'));
        if($PasswordCorrect->valid()){

            $member->Has2FA = false;
            $member->write();

            $this->response
                ->addHeader('X-Status', 'Two-Factor authentication deactivated successfully.')
                ->addHeader('X-Pjax', 'CurrentForm');

            return $this->getResponseNegotiator()->respond($this->request);
        }

        // else: show feedback
        return $member
            ->customise(array(
                'CurrentController' => $this,
                'VerificationError' => true,
            ))
            ->renderWith('TokenInfoDialog');
    }

    public function regenerate_backup_tokens($data, $form)
    {
        $member = Security::getCurrentUser();
        $backup_token_list = $member->BackupTokens();
        foreach ($backup_token_list as $bt) {
            $bt->delete();
        }
        foreach (range(1, Config::inst()->get('_2fa\DataObject\BackupToken', 'num_backup_tokens')) as $i) {
            $token = BackupToken::create();
            $backup_token_list->add($token);
        }
        $this->response
                ->addHeader('X-Status', 'Your old backup tokens are gone. Please record these new ones!')
                ->addHeader('X-Pjax', 'CurrentForm')
                ->addHeader('X-Reload', true);

        return $this->getResponseNegotiator()->respond($this->request);
    }
}
