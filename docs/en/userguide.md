# User guide

## Setting up two factor authentication
Users will need access to view their CMS profile (/admin/myprofile) to enable and manage two factor authentication. Depending on the configuration, the below screenshots may look slightly different, but the general process is the same.

1. Log into the CMS and navigate to your profile's "Two Factor Authentication" tab.
    <img width="550" alt="2fa activation" src="https://user-images.githubusercontent.com/1005986/27335909-4e292a6a-55ce-11e7-9bcd-ea871307a43b.png">
2. Check the "Enable Two Factor Authentication" checkbox and save your profile, or click the button. This will generate a Timed One-Time Password token and a QR code to set up in your second factor application (Google Authenticator, for example).
    <img width="550" alt="screen shot 2017-06-20 at 15 37 38" src="https://user-images.githubusercontent.com/1005986/27335947-6e11443e-55ce-11e7-9e15-e373757c781b.png">

3. Scan the QR code (or enter the token) into your two factor authentication application and use alongside your regular email and password when logging into the CMS.

    <img width="550" alt="qr code" src="https://user-images.githubusercontent.com/1005986/27372473-1bb53d02-5665-11e7-8c59-224d6800b1da.png">

## Installing Google Authenticator
See the [installation instructions for various mobile devices](https://support.google.com/accounts/answer/1066447?hl=en).

## Setting backup tokens
You can set up backup security tokens just in case you lose access to your second factor device.

1. Log into the CMS and navigate to your profile's "Two Factor Authentication" tab.
2. Click the "Create new two-factor backup tokens" button.
3. Immediately record your new backup tokens and save them securely. Any previous tokens are destroyed.
    <img width="550" alt="backup tokens" src="https://user-images.githubusercontent.com/1005986/27372138-d0252646-5663-11e7-8e6f-a98390eec7e8.png"> 

4. You can now use this token in place of two factor authentication.

Tokens are single-use, and each one will be removed from your pool of backup tokens once you have used it.
