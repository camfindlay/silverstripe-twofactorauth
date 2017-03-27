# Configuration

All configuration is done through the [Configuration API](http://doc.silverstripe.org/framework/en/3.1/topics/configuration).

## Options

 * `_2fa\Extensions\Member.totp_window` -- The number of tokens in the window users
 have to get a correct token. If this is 0, then only the current token is
 accepted. **Note:** this is the total size of the window, not the size either
 side of the current token. For example, the default value of 2 allows for users
 to specify the previous or the next tokens as well as the current one.
* `_2fa\admins_can_disable` -- Default false. If set to true, then the 2FA
 option appears in the Security section, which allows an admin to turn 2FA _off_,
 but not _on_. Only the Member can turn 2FA _on_.

## Example configuration
```yaml
 _2fa\Extensions\Member:
   totp_window: 2
```

## Note about SilverStripe CMS reauthentication (3.1.7 upwards)
Due to the way SilverStripe handles reauthentication (expired sessions), it does not include at present the option to reauthenticate using two factor authentication tokens. To ensure the securty of of the user, this module disables reauthentication instead opting to ensure the user logs back into the CMS securely.
