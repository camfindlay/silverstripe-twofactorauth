<?php

namespace _2fa;
use SilverStripe\Security\Member;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Admin\CMSProfileController as SS_CMSProfileController;

/**
 * Adds in handling of token creation and validation in the CMS 'My Profile' 
 * section
 */
class CMSProfileController extends SS_CMSProfileController
{
    private static $allowed_actions = [
        'regenerate_backup_tokens',
        'regenerate_token',
        'load_token_data',
        'verify_and_activate',
        'verify_and_deactivate',
    ];

    /**
     * Adds in Two Factor Authentication tab. Tab state changes depending on
     * whether the member has TwoFactor enabled or not
     *
     * @param int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $member = Member::currentUser();
        if (!$member) {
            return $form;
        }

        $actions = $form->Actions();
        $fields = $form->Fields();

        // cleanup
        $fields->removeByName('BackupTokens');

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
                    _t(
                        "TWOFACTOR.ACTIVATE2FA",
                        'Two-Factor Authentication is: <strong>ACTIVE</strong>
                        <br><small>click to deactivate</small>'
                    )
                )
                ->setAttribute('data-icon', 'accept');
        } else {
            $alterbutton
                ->setButtonContent(
                    _t(
                        "TWOFACTOR.ACTIVATE2FA",
                        'Two-Factor Authentication is:
                        <strong>NOT ACTIVE</strong>
                        <br><small>click to activate</small>'
                    )
                )
                ->setAttribute('data-icon', 'accept_disabled');
        }
        $fields->addFieldToTab('Root.TwoFactorAuthentication', $alterbutton);

        // token regeneration
        $actions->push(
            FormAction::create('regenerate_token')
                ->setTitle('Reset two-factor KEY')
                ->addExtraClass('ss-ui-action-destructive')
                ->addExtraClass('twofactor_regeneratetokens')
        );

        // Backup tokens may always be shown
        $this->addBackupTokenInfo($fields, $actions);

        return $form;
    }

    /**
     * Undocumented function
     *
     * @param FieldList $fields
     * @return void
     */
    private function addTokenInfo(FieldList &$fields)
    {
        $member = Member::currentUser();
        if (!$member) {
            return;
        }

        if ($member->Has2FA) {
            // add token QR code
            $two_factor_fields[] = LiteralField::create('TokenSecurityWarning',
                _t(
                    "TWOFACTOR.TOKENSECURITYWARNING",
                    "<p><br>The button below reveals your security token for
                    scanning.<br>
                    <strong>Please reveal this only when no one else is
                    observing your screen.</strong></p>"
                )
            );
            $two_factor_fields[] = ToggleCompositeField::create(
                'SecurityToken',
                'Security token',
                LiteralField::create(
                    'PrintableTOTPToken',
                    $member->renderWith('TokenInfo')
                )
            );
            $fields->addFieldsToTab(
                'Root.TwoFactorAuthentication',
                $two_factor_fields
            );
        }
    }

    private function addBackupTokenInfo(FieldList &$fields, FieldList &$actions)
    {
        $member = Member::currentUser();
        if (!$member) {
            return;
        }

        if ($member->Has2FA) {
            // backup-token info
            $backup_token_fields[] = LiteralField::create(
                'BackupTokensSecurityWarning',
                _t("TWOFACTOR.BACKUPTOKENSECURITYWARNING",
                    "<p><br>The button below reveals your backup tokens. These
                    can each be used only once.<br>
                    <strong>Please reveal them only when no one else is
                    observing your screen.</strong></p>"
                )
            );
            $backup_token_fields[] = ToggleCompositeField::create(
                'BackupTokens',
                'Backup tokens',
                [
                    LiteralField::create(
                        'DisplayBackupTokens',
                        $member->renderWith('BackupTokenInfo')
                    )
                ]
            );
            $fields->addFieldsToTab(
                'Root.TwoFactorAuthentication',
                $backup_token_fields
            );

            // backup-tokens interaction
            $actions->push(
                FormAction::create('regenerate_backup_tokens')
                    ->setTitle('(Re)generate BACKUP tokens')
                    ->addExtraClass('ss-ui-action-destructive')
                    ->addExtraClass('twofactor_regeneratetokens')
            );
        }
    }

    /**
     * This form action may get triggered to manually refresh secret/token when
     * running in 'fixed' token mode (regenerate_on_activation = false)
     *
     * @param $data Array
     * @param $form Form
     * @return SS_HTTPResponse
     */
    public function regenerate_token($data, $form)
    {
        $member = Member::currentUser();
        // set new secret/token on member
        $member->generateTOTPToken();
        // if we're manually regenerating, user needs to re-activate & verify
        // 2FA after token change
        $member->Has2FA = false;
        $member->write();

        $this->response
            ->addHeader(
                'X-Status',
                'Token/secret regenerated, please re-activate two-factor authentication'
            )
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
        $member = Member::currentUser();
        if (!$member) {
            return;
        }

        // If we're in validated activation mode, this is the appropriate moment
        // to refresh the token
        if(!$member->Has2FA) {
            // set new secret/token on member
            $member->generateTOTPToken();
            $member->write();
        }

        return $member->customise(['CurrentController' => $this])
            ->renderWith('TokenInfoDialog');
    }

    /**
     * Function to allow verification & activation of two-factor-auth via Ajax
     *
     * @param $request
     * @return \SS_HTTPResponse
     */
    public function verify_and_activate($request)
    {
        $member = Member::currentUser();
        if (!$member) {
            return;
        }

        $TokenCorrect = $member->validateTOTP(
            (string) $request->postVar('VerificationInput')
        );

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
            ->customise(
                [
                    'CurrentController' => $this,
                    'VerificationError' => true,
                ]
            )
            ->renderWith('TokenInfoDialog');
    }

    /**
     * Function to allow verification of password & dectivation of two-factor-auth via Ajax
     *
     * @param $request
     * @return \SS_HTTPResponse
     */
    public function verify_and_deactivate($request)
    {
        $member = Member::currentUser();
        if (!$member) {
            return;
        }

        $PasswordCorrect = $member->checkPassword(
            (string) $request->postVar('VerificationInput')
        );
        if ($PasswordCorrect->isValid()) {

            $member->Has2FA = false;
            $member->write();

            $this->response
                ->addHeader('X-Status', 'Two-Factor authentication deactivated successfully.')
                ->addHeader('X-Pjax', 'CurrentForm');

            return $this->getResponseNegotiator()->respond($this->request);
        }

        // else: show feedback
        return $member
            ->customise(
                [
                    'CurrentController' => $this,
                    'VerificationError' => true,
                ]
            )
            ->renderWith('TokenInfoDialog');
    }

    public function regenerate_backup_tokens($data, $form)
    {
        $member = Member::currentUser();
        $member->regenerateBackupTokens();
        $this->response
                ->addHeader('X-Status', 'Your old backup tokens are gone. Please record these new ones!')
                ->addHeader('X-Pjax', 'CurrentForm')
                ->addHeader('X-Reload', true);

        return $this->getResponseNegotiator()->respond($this->request);
    }
}
