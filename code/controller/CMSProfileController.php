<?php

namespace _2fa;

class CMSProfileController extends \CMSProfileController
{
    private static $allowed_actions = [
        'regenerate_backup_tokens',
    ];

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $member = \Member::currentUser();
        if (!$member) {
            return $form;
        }

        $actions = $form->Actions();
        $fields = $form->Fields();

        $fields->removeByName('BackupTokens');

        $fields->addFieldsToTab('Root.TwoFactorAuthentication',
            \CheckboxField::create('Has2FA', 'Enable Two Factor Authentication', $member->Has2FA)
        );

        $this->addTokenInfo($fields);

        $this->addBackupTokenInfo($fields, $actions);

        return $form;
    }

    private function addTokenInfo(\FieldList &$fields)
    {
        $member = \Member::currentUser();
        if (!$member) {
            return;
        }

        if ($member->Has2FA) {
            // add token QR code
            $two_factor_fields[] = \LiteralField::create('TokenSecurityWarning',
                _t("TWOFACTOR.TOKENSECURITYWARNING","<p><br>
                    The button below reveals your security token for scanning.<br>
                    <strong>Please reveal this only when no one else is observing your screen.</strong>
                    </p>")
            );
            $two_factor_fields[] = \ToggleCompositeField::create('SecurityToken', 'Security token',
                \LiteralField::create('PrintableTOTPToken', $member->renderWith('TokenInfo'))
            );
            $fields->addFieldsToTab('Root.TwoFactorAuthentication', $two_factor_fields);
        }
    }

    private function addBackupTokenInfo(\FieldList &$fields, \FieldList &$actions)
    {
        $member = \Member::currentUser();
        if (!$member) {
            return;
        }

        if ($member->Has2FA) {
            // backup-token info
            $backup_token_fields[] = \LiteralField::create('BackupTokensSecurityWarning',
                _t("TWOFACTOR.BACKUPTOKENSECURITYWARNING","<p><br>
                    The button below reveals your backup tokens. These can each be used only once.<br>
                    <strong>Please reveal them only when no one else is observing your screen.</strong>
                    </p>")
            );
            $backup_token_fields[] = \ToggleCompositeField::create('BackupTokens', 'Backup tokens', [
                \LiteralField::create('DisplayBackupTokens', $member->renderWith('BackupTokenInfo'))
            ]);
            $fields->addFieldsToTab('Root.TwoFactorAuthentication', $backup_token_fields);

            // backup-tokens interaction
            $actions->push(
                \FormAction::create('regenerate_backup_tokens')
                    ->setTitle('(Re)generate two-factor backup tokens')
//                    ->addExtraClass('ss-ui-action-constructive')
                    ->addExtraClass('ss-ui-action-destructive')
                    ->addExtraClass('twofactor_regeneratetokens')
            );
        }
    }

    public function regenerate_backup_tokens($data, $form)
    {
        $member = \Member::currentUser();
        $backup_token_list = $member->BackupTokens();
        foreach ($backup_token_list as $bt) {
            $bt->delete();
        }
        foreach (range(1, \Config::inst()->get('_2fa\BackupToken', 'num_backup_tokens')) as $i) {
            $token = BackupToken::create();
            $backup_token_list->add($token);
        }
        $this->response
                ->addHeader('X-Status', 'Your old backup tokens are gone. Please record these new ones!')
                ->addHeader('X-Reload', true);

        return $this->getResponseNegotiator()->respond($this->request);
    }
}
