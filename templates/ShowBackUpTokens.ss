<div id="DisplayBackupTokens" class="field readonly">
    <p>
        These tokens can be used if you can't access your 2FA device. Each one can only be used once.
    </p>
    <p>
        Please copy/print these and keep in a safe place.
    </p>
<code class="twofactor_tokens print_tokens"><pre><% loop $BackupTokens %>$Value
<% end_loop %></pre></code>
    <p><a class="button-link-primary" href="{$backUrl}">Continue</a></p>
</div>
