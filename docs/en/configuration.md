# Configuration

All configuration is done through the [Configuration API](http://doc.silverstripe.org/framework/en/3.1/topics/configuration).

## Options

* `_2fa\Extensions\Member.admins_can_disable` -- Default false. If set to true, then the 2FA
 option appears in the Security section, which allows an admin to turn 2FA _off_,
 but not _on_. Only the Member can turn 2FA _on_.
* `_2fa\Extensions\Member.totp_window` -- The number of tokens in the window users
 have to get a correct token. If this is 0, then only the current token is
 accepted.  
 **Note:** this is the total size of the window, not the size either
 side of the current token. For example, the default value of 2 allows for users
 to specify the previous or the next tokens as well as the current one.
* `_2fa\Extensions\Member.validated_activation_mode`  
 With this set to **false (default)**, the user's secret/token gets regenerated upon each (re)activation of 2FA. Also, the QR code is available continuously (hidden only by a ToggleField).  
 With this set to **true**, the token is fixed and only changes upon (manually) clicking the 'Regenerate token' button.  
  In this *validated_activation_mode*, the QR is only shown once (in the activation process) and the user gets asked for a verification token upon activating 2FA. Only if the token is correct (=user has set up Authenticator correctly), 2FA will actually be activated. For deactivation, a user is instead asked to enter their password as an extra validation. This prevents users from locking themselves out of their account and is based on the same flow Google uses for 2FA activation & deactivation.

## Example configuration
```yaml
 _2fa\Extensions\Member:
   totp_window: 2
   validated_activation_mode: true
```

## Note about SilverStripe CMS reauthentication (3.1.7 upwards)
Due to the way SilverStripe handles reauthentication (expired sessions), it does not include at present the option to reauthenticate using two factor authentication tokens. To ensure the securty of of the user, this module disables reauthentication instead opting to ensure the user logs back into the CMS securely.

## Screenshots 
### Default activation mode/flow
<img width="885" alt="2fa-inactive" src="https://user-images.githubusercontent.com/1005986/27372083-9e2e2f98-5663-11e7-84b4-466e6d05a937.png">  
-
<img width="879" alt="2fa-active" src="https://user-images.githubusercontent.com/1005986/27372086-a0dfa7c6-5663-11e7-92e0-61e52562e49e.png">  
-
<img width="883" alt="2fa-qr-backupcodes" src="https://user-images.githubusercontent.com/1005986/27372088-a2fe7b2c-5663-11e7-9fa1-d3f34560c9c5.png">  
-

### Validated activation mode/flow
<img width="805" alt="2fa-validated-inactive" src="https://user-images.githubusercontent.com/1005986/27335909-4e292a6a-55ce-11e7-9bcd-ea871307a43b.png">
-
<img width="649" alt="2fa-validated-qrcode" src="https://user-images.githubusercontent.com/1005986/27335947-6e11443e-55ce-11e7-9e15-e373757c781b.png">
-
<img width="644" alt="2fa-validated-validationerror" src="https://user-images.githubusercontent.com/1005986/27335971-7e995fda-55ce-11e7-8fa0-8b3069cbf7f5.png">
-
<img width="800" alt="2fa-validated-active" src="https://user-images.githubusercontent.com/1005986/27336010-9729bcde-55ce-11e7-86a3-8248547e6c42.png">
-
<img width="644" alt="2fa-validated-deactivation" src="https://user-images.githubusercontent.com/1005986/27336036-a84a6f40-55ce-11e7-8d1b-57444236909c.png">
-
<img width="799" alt="2fa-validated-deactivated" src="https://user-images.githubusercontent.com/1005986/27336047-b4f05e6c-55ce-11e7-950a-5d5212e0cde6.png">