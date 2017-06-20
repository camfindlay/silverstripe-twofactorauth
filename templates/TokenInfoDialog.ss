<div id="TokenInfoDialog">
<% if $AttemptedActivation && $ActivationSucceeded %>
    SUCCESS!
<% else %>
    <code class="twofactor_tokens"><pre>$PrintableTOTPToken</pre></code>
    <img src="$generateQRCode()" width=175 height=175 />

    <ol>
        <li><% _t("TWOFACTOR.SCANQRCODE", "Scan the QR code using your Two-Factor Authentication app") %></li>
        <li><% _t("TWOFACTOR.ENTERVERIFICATIONTOKEN", "Enter current token below to verify & activate Two-Factor Authentication") %></li>
    </ol>
    <% if $AttemptedActivation %>
        <div class="message bad">Token verification failed (two-factor authentication not activated)<br><strong>Please try again:</strong></div>
    <% end_if %>
    <div class="field text">
        <input type="text" class="text form-control" name="VerificationToken" id="VerificationToken"
               placeholder="<% _t("TWOFACTOR.VERIFICATIONTOKEN", "Verification token") %>">
        <button class="ss-ui-action-constructive" data-activationurl="$CurrentController.Link('verify_and_activate')" id="twofactor_activate">
            <% _t("TWOFACTOR.ACTIVATE", "Activate") %></button>
    </div>
<% end_if %>
</div>