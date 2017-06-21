<div id="DisplayBackupTokens" class="field readonly">
    <% if $BackupTokens.count %>
        <p>
            <% _t("TWOFACTOR.COPYPASTESAFEPLACE", "Please copy/print these and keep in a safe place.") %>
        </p>
        <code class="twofactor_tokens print_tokens"><pre><% loop $BackupTokens %>$Value
<% end_loop %></pre></code>
    <% else %>
        <p>
            <strong><% _t("TWOFACTOR.NOBACKUPTOKENS", "No backup Tokens found") %></strong><br>
            <% _t("TWOFACTOR.GENERATEBACKUPTOKENS", "Please (re)generate backup tokens.") %>
        </p>
    <% end_if %>
</div>