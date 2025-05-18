<script>
  var sent = false;
  var tcAction = function (mgAjaxAction, action, keyAction, redirectUrl = '/') {
    if (action == 'close' && sent) {
      return;
    } else if (action == 'close') {
      action = 'cancel';
    }

    var params = {action: mgAjaxAction};
    params[keyAction] = action;

    mgAjax(params, function (res) {
      sent = true;
      mboxClose('mbox-msg');
      if (redirectUrl) {
          gotoLang(redirectUrl);
      }
    });

  }
  function generateShowTerms(mgAjaxAction, isMobile, cancelLabel, okLabel, title, keyAction, redirectUrl = '/') {
    $(document).ready(function () {
      setTimeout(function () {
        mboxDialog($("#tac-holder").html(),
          "tcAction('" + mgAjaxAction + "', 'cancel', '" + keyAction + "', '" + redirectUrl + "')",
          cancelLabel,
          "tcAction('" + mgAjaxAction + "', 'accept', '" + keyAction + "', '" + redirectUrl + "')",
          okLabel,
          function () {
            tcAction("'" + mgAjaxAction + "'", 'close', "'" + keyAction + "', '" + redirectUrl + "'");
          },
          600,
          false,
          'btn-cancel-l',
          title,
          '',
          isMobile ? 'tac-mobile' : ''
        );
      }, isMobile ? 0 : 2000);
    });
  }
</script>

<?php if (!empty($_GET['showtc']) || !empty($_GET['showstc'])
    || !empty($_GET['showbtc'])
):

    $_GET['signup'] = true;

    ?>
    <div style="display: none;" id="tac-holder">
        <div style="height: 200px">
            <?php
            if (!empty($_GET['showstc'])) {
                et(lic('getTermsAndConditionPage', ['sports']));
            } elseif (!empty($_GET['showtc'])) {
                et(lic('getTermsAndConditionPage'));
            } elseif (!empty($_GET['showbtc'])) {
                et(lic('getBonusTermsAndConditionPage'));
            }
            ?>
        </div>
    </div>
<?php endif ?>
<?php if ($_GET['showtc']): ?>
    <script>
        generateShowTerms(
            'tac-action',
            <?= phive()->isMobile() ? 1 : 0 ?>,
            '<?php et('do.not.accept') ?>',
            '<?php et('accept') ?>',
            '<?php et('new.tac') ?>',
            'tacation',
            '<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>'
        )
    </script>
<?php elseif ($_GET['showbtc']): ?>
    <script>generateShowTerms(
        'bonus-tac-action',
            <?= phive()->isMobile() ? 1 : 0 ?>,
            '<?php et('do.not.accept') ?>',
            '<?php et('accept') ?>',
            '<?php et('new.bonus-tac') ?>',
            'btcaction',
            '<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>'
        )</script>
<?php elseif ($_GET['showstc']): ?>
    <script>
        generateShowTerms('tac-sport-action',
            <?= phive()->isMobile() ? 1 : 0 ?>,
            '<?php et('do.not.accept') ?>',
            '<?php et('accept') ?>',
            '<?php et('new.tac') ?>',
            'tacation',
            '<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>'
        )</script>
<?php endif ?>

<?php if (!empty($_GET['showpp'])
    && (phive('DBUserHandler')->getSetting("pp_on") === true)
): ?>
    <div style="display: none;" id="prp-holder">
        <div style="height: 400px">
            <?php et('simple.1261.html') ?>
        </div>
    </div>
    <script>
      var tacSent = false;

      function prAction(action) {
        if (action == 'close' && tacSent) {
          return;
        } else if (action == 'close') {
          action = 'cancel';
        }
        mgAjax({action: 'prp-action', prpaction: action}, function (res) {
          tacSent = true;
          mboxClose('mbox-msg');
          if (action == 'cancel') {
            gotoLang('/');
          }
        });
      }

      $(document).ready(function () {
        setTimeout(function () {
          mboxMsg($("#prp-holder").html(), '<?php et('accept') ?>', function () {
            prAction('close');
          }, 600, false, 'btn-cancel-l', '<?php et('privacy.policy.title') ?>', "<?php echo htmlentities(t('privacy.policy.button')) ?>", 'full');
        }, 1500);
      });
    </script>
<?php endif ?>

<?php
$user = cu();
$needsPrivacyConfirm = false;
$privacyPopupMode = 'registration';

if (!empty($user)) {
    $onPrivacyDashboard = strpos($_SERVER['REQUEST_URI'], '/privacy-dashboard/') !== false;
    $needsPrivacyConfirm = !$onPrivacyDashboard && phive('DBUserHandler')->needPrivacySettingConfirm($user);
    $privacyPopupMode = phive('DBUserHandler')->shouldShowPrivacyReconfirmPopup($user) ? 'popup' : 'registration';
}

?>

<?php if (!empty($user) && $needsPrivacyConfirm): ?>
    <input id="confirm-message-yes" type="hidden" value="<?php et('yes') ?>" />
    <input id="confirm-message-no" type="hidden" value="<?php et('no') ?>" />
    <input id="confirm-message-title" type="hidden" value="<?php et('privacy.dashboard.confirmation.message.popup.title') ?>" />
    <input id="confirm-message-content-popup" name="confirm-message-content-popup" type="hidden" value="<?php et('privacy.dashboard.confirmation.message.popup') ?>">
    <script type="text/javascript">
        $(document).ready(function () {
            showPrivacyConfirmBox('<?= phive()->isMobile() ? 1 : 0; ?>', '<?= $privacyPopupMode; ?>');
        });
    </script>
<?php endif; ?>
<?php if (!empty($user) && phive('DBUserHandler')->shouldReconfirmPrivacySettings($user)): ?>
    <input id="confirm-message-yes" type="hidden" value="<?php et('yes') ?>" />
    <input id="confirm-message-no" type="hidden" value="<?php et('no') ?>" />
    <input id="confirm-message-title" type="hidden" value="<?php et('privacy.dashboard.confirmation.message.popup.title') ?>" />
    <input id="confirm-message-content-popup" name="confirm-message-content-popup" type="hidden" value="<?php et('privacy.dashboard.confirmation.message.popup') ?>">
	<?php moduleHtml('DBUserHandler', 'privacyConfirmationPopupFields'); ?>
    <script type="text/javascript">
        $(document).ready(function () {
            showPrivacyReConfirmBox('<?= phive()->isMobile() ? 1 : 0; ?>', 'popup');
        });
    </script>
<?php endif; ?>

<?php wsUpdateBalance() ?>
