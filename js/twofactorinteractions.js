(function($) {
    $.entwine("ss", function($) {

        /**
         * Two-factor auth interactions
         */

        $(".display-twofactorinfo-dialog").entwine({
            loadDialog: function(deferred) {
                var dialog = this.addClass("loading").children(".ui-dialog-content").empty();

                deferred.done(function(data) {
                    dialog.html(data).parent().removeClass("loading");
                });
            }
        });

        $(".twofactor_dialogbutton").entwine({

            onclick: function () {

                var dialog = $("<div></div>").appendTo("body").dialog({
                    modal: true,
                    resizable: false,
                    width: 600,
                    height: 360,
                    close: function () {
                        $(this).dialog("destroy").remove();
                    }
                });

                dialog.parent().addClass("display-twofactorinfo-dialog").loadDialog(
                    $.get($(this).data('infourl'))
                );
                $('.ui-dialog-content').html('<span class="twofactor_checkmark">✓</span>');

                return false;
            }

        });

        $(".twofactor_actionbutton").entwine({

            onclick: function () {
                $(".display-twofactorinfo-dialog").loadDialog(
                    $.ajax({
                        // headers: {"X-Pjax": 'CurrentField'},
                        headers: {"X-Pjax" : "CurrentForm"},
                        type: "POST",
                        url: $(this).data('actionurl'),
                        data: {
                            'VerificationInput': $('#VerificationInput').val()
                        },
                        success: function(data, textStatus, jqXHR){
                            // show feedback as well
                            if(data.CurrentForm){
                                // partial-update form in background
                                $('#Root_TwoFactorAuthentication').html( $(data.CurrentForm).find('#Root_TwoFactorAuthentication').html() );
                                // and close dialog after 3 seconds if user doesnt
                                $(".ui-dialog-titlebar-close").trigger('click');
                            }
                        }
                    })
                );

                return false;
            }

        });

    });
})(jQuery);