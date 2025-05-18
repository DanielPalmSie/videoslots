function showPrivacySettings() {
    const params = {
        file: 'privacy_dash_board_modal',
        boxid: 'privacy-dash-board-modal',
        boxtitle: 'privacy.update.form.title',
        module: 'DBUserHandler',
        extra_css: 'privacy-dashboard__modal',
        closebtn: 'no'
    };
    const extraOptions = isMobile() ? {
        width: '100vw',
        height: '100vh',
        containerClass: 'privacy-dashboard__modal privacy-dashboard__modal--mobile'
    } : {
        width: '740',
        containerClass: 'privacy-dashboard__modal privacy-dashboard__modal--desktop'
    };

    extBoxAjax('get_html_popup', 'privacy-dash-board-modal', params, extraOptions, top);
}


function showMoreInfoBox(title, html){
    mboxMsg(html, false, '', 360, true, true, title);
}

function closePrivacySettingsBox(){
    window.location.reload(true);
}

function privacyAction(action, mobile, post) {
    if (typeof mobile === 'undefined') {
        return;
    }

    if (action === 'close')
        action = 'cancel';

    if (registration_mode != 'onestep' && registration_mode != 'bankid') {
        showLoader(undefined, true);
        mboxClose();
    }

    mgAjax({action: 'update-privacy-settings', privacyaction: action, mobile: mobile}, function (res) {
        var result = JSON.parse(res);
        if (post === 'registration') {
            if (result.status === 'ok') {
                if (registration_mode === 'paynplay') {
                    jsReloadWithParams();
                    return;
                }

                if (registration_mode === 'onestep' || registration_mode === 'bankid') {
                    $('#privacy-confirmation-notification .multibox-close').click();
                    mboxClose('privacy-confirmation-notification');
                    return;
                }

                var language = (cur_lang !== default_lang) ? ('/' + cur_lang) : '';
                parentGoTo(language + '/' + '?show_deposit=true');
                return;
            } else {
                mboxMsg(res.error, true, '', 360, true, true);
            }
        } else if (action === 'accept') {
            goTo(result['link']);
        } else {
            jsReloadWithParams();
        }
    });

}

/**
 * Show a popup with confirmation request for ALL privacy settings:
 * - accept will set all them true
 * - edit:
 *   - on registration - will optout of all of them
 *   - on normal website - will show the full privacy dashboard popup
 * may be later will redirect to privacy-dash board
 * @param mobile - we require to know when request is coming from mobile context to properly redirect to the correct page.
 * @param post - context of the request can be: normal|registration|popup
 * @param isReconfirm
 * @param width
 */
function showPrivacyConfirmBoxInternal(mobile, post, isReconfirm, width) {
    if ($('#bankid-account-verification-popup').length > 0) {
        mboxClose('bankid-account-verification-popup');
    }

    if ($('#bankid_registration_popup').length > 0) {
        mboxClose('bankid_registration_popup');
    }

    const params = {
        file: 'privacy_confirmation_popup',
        closebtn: 'no',
        boxid: 'privacy-confirmation-notification',
        boxtitle: 'confirm',
        module: 'DBUserHandler',
        mobile: mobile,
        post: post
    };

    if (isReconfirm) {
        params.privacyRecon = true;
    }

    const extraOptions = isMobile()
        ? {
            width: '100vw',
            height: '100vh',
            containerClass: 'flex-in-wrapper-popup button-fix--mobile'
        }
        : {
            width: width,
            containerClass: 'flex-in-wrapper-popup'
        };

    extBoxAjax('get_html_popup', 'privacy-confirmation-notification', params, extraOptions, top);
}

function showPrivacyConfirmBox(mobile, post, showClose = false, width = 450) {
    showPrivacyConfirmBoxInternal(mobile, post, false, width);
}

function showPrivacyReConfirmBox(mobile, post, showClose = false, width = 450) {
    showPrivacyConfirmBoxInternal(mobile, post, true, width);
}


function postPrivacySettings(mobile, skipAllEmptyCheck = false, mode='popup') {

    $('.error-message-table').remove();

    function redirectAfterPrivacySetting() {
        if(mode === 'registration') {
            showPermanentLoader(undefined, null);
            if (registration_mode !== 'bankid') {
                var language = (cur_lang !== default_lang) ? ('/' + cur_lang) : '';
                parentGoTo(language + '/' + '?show_deposit=true');
            } else {
                if (isMobile()) {
                    Registration.submitStep2(top.registration1.Registration.getFormStep2());
                } else {
                    top.registration1.goTo('/' + cur_lang + '/registration-step-2/', '_self', false);
                }
            }
        } else {
            jsReloadBase();
        }
    }

    mgAjax({action: 'update-privacy-settings', params: $("#privacy-settings-form").serializeArray()}, function(res){
        var result   = JSON.parse(res);
        if(result['status'] != 'ok') {
            mboxMsg(result['message'], false, '', 260, true, true, result['title']);
        } else {
            mboxMsg(result['message'], true, redirectAfterPrivacySetting, 260, true, true, result['title'], undefined, undefined, undefined, undefined, 'privacy-confirmation-popup');
        }

    });
}

function validatePrivacyForm() {
    var count = 0;
    $(".privacy-mandatory-group").each(function () {
        var res = $(this).find(':checkbox:checked').length;
        if ($(this).find(':checkbox:checked').length === 0) {
            count++;
            $(this).find('.account-sub-headline').append("<p class='error-message-table'>(*) "+ $('#error-message-content').val() +"</p>");
        }
    });

    return count === 0;
}

function setupPrivacy() {
    $('input[name="do-all"]').change(function() {
        const checked = this.checked;
        $('#privacy-settings-form .checkbox-select-all input:checkbox').each(function() {
            $(this).prop('checked', checked).prop('disabled', false);
        });
        $('#privacy-settings-form .opt-out-container input:checkbox').prop('checked', !checked);
    });


    $('#privacy-settings-form').on('change', '.opt-in-check input:checkbox', function() {
        const $checkbox = $(this);
        let $group = findGroup($checkbox);
        if (!$group.length) return;

        const $optOutToggle = $group.find('.opt-out-container input:checkbox').first();
        const $childCheckboxes = $group.find('.opt-in-check input:checkbox');

        if ($childCheckboxes.filter(':checked').length > 0) {
            $optOutToggle.prop('checked', false);
            $childCheckboxes.prop('disabled', false);
        } else {
            $optOutToggle.prop('checked', true);
        }

        if (!$checkbox.prop('checked')) {
            $('input[name="do-all"]').prop('checked', false);
        }
    });

    // Opt-out toggle change
    $('#privacy-settings-form').on('change', '.opt-out-container input:checkbox', function() {
        const $toggle = $(this);

        let $group = findGroup($toggle);
        if (!$group.length) return;

        const $childCheckboxes = $group.find('.opt-in-check input:checkbox');

        if ($toggle.prop('checked')) {
            $childCheckboxes.prop('checked', false).prop('disabled', true);
            $('input[name="do-all"]').prop('checked', false);
        } else {
            $childCheckboxes.prop('disabled', false);
        }
    });


    // Helper to find related group
    function findGroup($element) {
        // Check if inside table new structure
        const $row = $element.closest('tr');
        if ($row.hasClass('privacy-notification-section')) {
            return $row.add($row.nextUntil('tr.privacy-notification-section'));
        }
        if ($row.hasClass('opt-category-row')) {
            const $sectionRow = $row.prevAll('tr.privacy-notification-section:first');
            return $sectionRow.add($sectionRow.nextUntil('tr.privacy-notification-section'));
        }

        // Otherwise old div structure
        return $element.closest('.account-sub-box.privacy-options-group');
    }
}
