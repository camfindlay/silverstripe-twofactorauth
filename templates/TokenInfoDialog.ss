<div id="TokenInfoDialog">
    <% if $Has2FA %>

        <p><br><br><br><% _t("TWOFACTOR.DEACTIVATEINFO", "Deactivate Two-Factor Authentication:") %></p>
        <% if $VerificationError %>
            <div class="message bad">
                <% _t("TWOFACTOR.PASSWORDFAILED", "Password incorrect (two-factor authentication not deactivated)<br><strong>Please try again:</strong>") %>
            </div>
        <% end_if %>
        <div class="field text">
            <form action="$CurrentController.Link('verify_and_deactivate')" method="post">
                <input type="password" class="text form-control" name="VerificationInput" id="VerificationInput"
                       placeholder="<% _t("TWOFACTOR.ACCOUNTPASSWORD", "Your account password") %>">
                <button class="ss-ui-action-destructive twofactor_actionbutton" data-actionurl="$CurrentController.Link('verify_and_deactivate')">
                    <% _t("TWOFACTOR.DEACTIVATE", "Deactivate") %></button>
            </form>
        </div>

    <% else %>

        <code class="twofactor_tokens"><pre>$PrintableTOTPToken</pre></code>
        <img src="$generateQRCode()" width=175 height=175 />

        <ol>
            <li><% _t("TWOFACTOR.SCANQRCODE", "Scan the QR code using your Two-Factor Authentication app, eg. ‘Google Authenticator’") %>
                (<a href='https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2' target='_blank'>Android</a>
                / <a href='https://itunes.apple.com/app/google-authenticator/id388497605' target='_blank'>iOS/iPhone</a>)</li>
            <li><% _t("TWOFACTOR.ENTERVERIFICATIONTOKEN", "Enter current token below to verify & activate Two-Factor Authentication") %></li>
        </ol>
        <% if $VerificationError %>
            <div class="message bad">
                <% _t("TWOFACTOR.VERIFICATIONFAILED", "Token verification failed (two-factor authentication not activated)<br><strong>Please try again:</strong>") %>
            </div>
        <% end_if %>
        <div class="field text">
            <form action="$CurrentController.Link('verify_and_activate')" method="post">
                <input type="text" class="text form-control" name="VerificationInput" id="VerificationInput"
                       placeholder="<% _t("TWOFACTOR.VERIFICATIONTOKEN", "Verification token") %>">
                <button type="submit" class="ss-ui-action-constructive twofactor_actionbutton" data-actionurl="$CurrentController.Link('verify_and_activate')">
                    <% _t("TWOFACTOR.ACTIVATE", "Activate") %></button>
            </form>
        </div>

    <% end_if %>
</div>