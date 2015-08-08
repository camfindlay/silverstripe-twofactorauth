# Configuration

All configuration is done through the [Configuration API](http://doc.silverstripe.org/framework/en/3.1/topics/configuration). 

## Options

 * `_2fa\Member.totp_window` -- The number of tokens in the window users
 have to get a correct token. If this is 0, then only the current token is
 accepted. **Note:** this is the total size of the window, not the size either
 side of the current token. For example, the default value of 2 allows for users
 to specify the previous or the next tokens as well as the current one.
 
 * `_2fa\BackupToken.single_use` -- If backup tokens are single use. If
 set to true, if a backup token is used to login then it is deleted. Defaults to
 true.

## Example configuration
```yaml
 _2fa\Member:
   totp_window: 2
 _2fa\BackupToken:
   single_use: true
```

## Note about SilverStripe CMS reauthentication (3.1.7 upwards)
Due to the way SilverStripe handles reauthentication (expired sessions), it does not include at present the option to reauthenticate using two factor authentication tokens. To ensure the securty of of the user, this module disables reauthentication instead opting to ensure the user logs back into the CMS securely.