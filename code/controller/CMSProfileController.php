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
        $two_factor_fields = [\CheckboxField::create('Has2FA', 'Enable Two Factor Authentication', $member->Has2FA)];
        if ($member->Has2FA) {
            $two_factor_fields[] = \LiteralField::create('SecurityWarning',
                    '<p>The button below reveals your security token for scanning and any backup tokens you may have. '.
                    'Please reveal them only when no one else is observing your screen.');
            $two_factor_fields[] = \ToggleCompositeField::create('SecurityTokens', 'Security tokens', [
            \LiteralField::create(
                'PrintableTOTPToken',
                sprintf('<div id="PrintableTOTPToken" class="field readonly">'.
                        '<label class="left" for="Form_EditForm_PrintableTOTPToken">TOTP Token</label>'.
                        '<div class="middleColumn">'.
                        '<span id="Form_EditForm_PrintableTOTPToken" class="readonly">%s<br />'.
                        '<img src="%s" width=175 height=175 /></span>'.
                        '</div></div>', $member->getPrintableTOTPToken(), $member->generateQRCode())),
            \LiteralField::create('DisplayBackupTokens', '<div id="DisplayBackupTokens" class="field readonly">'.
                '<p><b>Backup Tokens:</b> Please copy these and keep in a safe place.</p>'.
                implode('<br />', $member->BackupTokens()->column('Value')).'</div>'), ]
            );
        }
        $fields->addFieldsToTab('Root.TwoFactorAuthentication', $two_factor_fields);
        if ($member->Has2FA) {
            $actions->push(
                \FormAction::create('regenerate_backup_tokens')
                    ->setTitle('Create new two-factor backup tokens')
                    ->addExtraClass('ss-ui-action-constructive')
                    ->addExtraClass('ss-ui-action-destructive')
            );
        }

        return $form;
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
