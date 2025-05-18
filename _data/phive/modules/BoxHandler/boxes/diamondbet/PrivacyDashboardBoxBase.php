<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class PrivacyDashboardBoxBase extends DiamondBox
{
    /** @var DBUser $cur_user */
    public $cur_user;

    public function printStandalone()
    {
        $this->printHTML(cu(), true);
    }

	public function printNonStandalone()
	{
		$this->printHTML(cu());
	}

    /**
     * @param DBUser $cur_user
     * @param bool $standalone
     */
    public function printHTML($cur_user = null, $standalone = false)
    {
        $this->cur_user = empty($cur_user) ? cu() : $cur_user;
        if (!$this->cur_user) {
            $this->cur_user = cuRegistration();
        }
        loadJs("/phive/js/privacy_dashboard.js");
        $this->cur_user->setMissingSetting('has_privacy_settings', 1);
		$mobile = phive()->isMobile() ? 1 : 0;
        $popupMode = $this->cur_user->hasDeposited() ? 'popup' : 'registration';
        $skipCheck = (!empty($_REQUEST['skip_all_empty_check']) || $mobile) ? 'true' : 'false';
        ?>
        <script>
            const error_message_content_popup = `<?php echo htmlspecialchars(et('privacy.settings.error.message.html')); ?>`;
            $(document).ready(function(){
                setupPrivacy();
            });
        </script>
        <?php moduleHtml('DBUserHandler', 'privacyConfirmationPopupFields');?>
        <div class="privacy-box-content general-account-holder">
            <?php if ($standalone): ?>
                <div class="privacy-box-header">
                    <div class="privacy-box-header-right">

                    </div>
                    <div class="privacy-box-header-left"></div>
                    <div class="privacy-box-header-center">
                        <?php et('privacy.update.form.title'); ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="simple-box pad-stuff-ten">
                <div class="privacy-headline">
                    <div class="account-headline account-privacy-info"><?php et('privacy.dashboard.title'); ?></div>
                    <div class="account-privacy-info"><?php et('privacy.dashboard.subtitle'); ?></div>
                </div>
                <div class="account-sub-box top-privacy-sub-box">
                    <div class="checkbox-main-privacy">
                        <?php dbCheck("do-all") ?>
                        <label for="do-all"><?php et('privacy.dashboard.select.all.top.option') ?></label>
                    </div>
                </div>
                <form name="privacy-settings" id="privacy-settings-form" method="POST">
                    <?php
                        $this->printTable(phive('DBUserHandler/PrivacyHandler')->getPrivacySectionsForHTML($this->cur_user));
                        foreach (phive('DBUserHandler')->getPrivacyBoxes($this->cur_user) as $key => $box) {
                            $this->printSubBox($key, $box);
                        }
                    ?>
                </form>
                <br clear="all">
                <div class="privacy-btn">
                    <?php btnDefaultL(t('privacy.dashboard.save.button'), '', "postPrivacySettings('{$mobile}', {$skipCheck}, '{$popupMode}')", '330') ?>
                </div>
                <br clear="all">
                <div class="account-privacy-info"><?php et('privacy.dashboard.footer.html')?>
                </div>
            </div>
        </div>
        <br/>
    <?php }


    /**
     * Generates HTML for a checkbox input element
     * @param string $name The name and ID attribute for the checkbox
     * @param bool|null $check Optional boolean to determine if the checkbox should be checked
     *                        If null, checkbox will be unchecked
     * @return string HTML string for the checkbox input element
     */
    private function checkbox(string $name, ?bool $check = null)
    {
        $checked = ($check) ? 'checked="checked"' : '';
        return "<input type='checkbox' name='{$name}' id='{$name}' $checked />";
    }

    /**
     * Renders the privacy settings table with sections and their respective options
     *
     * @param array $sections Array of privacy sections containing:
     *                       - headline: Section title
     *                       - subheadline: Section description
     *                       - optAll: Boolean indicating if all options are enabled
     *                       - opt: Array of channel options (email, sms, app)
     *                       - products: Optional array of product-specific options
     * @return void
     */
    public function printTable($sections)
    {
        ?>
        <div class="account-sub-box">
            <table class="account-privacy-table">
                <thead>
                <tr class="opt-channel-headers">
                    <td></td>
                    <th class="privacy-op-col"><?php et('email') ?></th>
                    <th class="privacy-op-col"><?php et('sms') ?></th>
                    <th class="privacy-op-col"><?php et('notification') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php $sectionIndex = 0; ?>
                <?php foreach ($sections as $type => $section): ?>
                    <?php if ($sectionIndex > 0): ?>
                        <tr><td colspan="4"><div class="opt-section-divider"></div></td></tr>
                    <?php endif; ?>

                    <tr class="privacy-options-group privacy-mandatory-group privacy-notification-section">
                        <td colspan="4">
                            <div class="opt-section-header">
                                <div class="account-headline"><?php echo $section['headline']; ?></div>
                                <div class="opt-out-check opt-out-container">
                                    <span class="opt-out-text"><?php et('optout') ?></span>
                                    <label class="opt-toggle-switch">
                                        <?php
                                        echo $this->checkbox($type, $section['optOutAll']);
                                        ?>
                                        <span class="opt-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="account-sub-headline opt-section-description"><?php echo $section['subheadline']; ?></div>
                        </td>
                    </tr>

                    <?php if (!empty($section['products'])): ?>
                    <?php
                        $isInJurisdiction = $this->cur_user->getJurisdiction() === 'UKGC';
                        if (!$isInJurisdiction) {
                            $section['products'] = [
                                'casino' => $section['products']['casino']
                            ];
                        }
                    ?>
                        <?php foreach ($section['products'] as $product => $opts): ?>
                            <tr class="privacy-options-group privacy-mandatory-group opt-category-row">
                                <td class="opt-category-name"><?php et('privacy.confirmation.' . htmlspecialchars($product)); ?></td>
                                <?php foreach ($opts['opt'] as $channel => $opt): ?>
                                    <td class="privacy-op-col checkbox-select-all opt-in-check">
                                        <?php echo $this->checkbox("{$channel}.{$type}.{$product}", $opt); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="privacy-options-group privacy-mandatory-group opt-category-row opt-three-columns">
                            <td class="privacy-op-col checkbox-select-all opt-in-check">
                                <?php et('email'); ?>
                                <?php echo $this->checkbox("email.{$type}", $section['opt']['email']); ?>
                            </td>
                            <td class="privacy-op-col checkbox-select-all opt-in-check">
                                <?php et('sms'); ?>
                                <?php echo $this->checkbox("sms.{$type}", $section['opt']['sms']); ?>
                            </td>
                            <td class="privacy-op-col checkbox-select-all opt-in-check">
                                <?php et('notification'); ?>
                                <?php echo $this->checkbox("app.{$type}", $section['opt']['app']); ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php $sectionIndex++; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Renders a sub-box section for additional privacy settings
     *
     * @param string $key The unique identifier for this sub-box section
     * @param array $content Array containing:
     *                      - alias: Translation key for the section
     *                      - no-opt-out: Boolean to disable opt-out functionality
     *                      - sub-sub-headline: Optional flag to show additional headline
     *                      - options: Array of checkbox options with their translation keys
     * @return void
     */
    public function printSubBox($key, $content)
    { ?>
        <div class="account-sub-box privacy-options-group <?php echo empty($content['no-opt-out']) ? 'privacy-mandatory-group' : ''?>">
            <?php if(empty($content['no-opt-out'])): ?>
                <div class="sub-box-check opt-out-check opt-out-container ">
                    <span class="opt-out-text"><?php et('optout') ?></span>
                    <label class="opt-toggle-switch" for="<?= "optout-{$key}" ?>">
                        <?php
                        dbCheck("optout-{$key}", dbCheckSubSettingSelected($this->cur_user, $key, $content));
                        ?>
                        <span class="opt-slider"></span>
                    </label>
                </div>

            <?php endif;?>
            <div class="account-headline"><?php et("privacy.settings.{$content['alias']}.headline")?></div>
            <div class="account-sub-headline"><?php et("privacy.settings.{$content['alias']}.subheadline")?></div>
            <?php if(isset($content['sub-sub-headline'])): ?>
                <div class="account-sub-sub-headline"><?php et("privacy.settings.{$content['alias']}.under.subheadline")?></div>
            <?php endif;?>
            <br clear="all">
            <div class="checkbox-group opt-in-check">
                <?php foreach ($content['options'] as $option_key => $option): ?>
                    <div class="checkbox-float-left<?php echo empty($content['no-opt-out']) ? ' checkbox-select-all' : ''?>">
                        <?php dbCheckSetting($this->cur_user, "privacy-{$key}-{$option_key}", false, true) ?>
                        <label for="<?= "privacy-{$key}-{$option_key}"?>"><?php et("privacy.settings.{$option}") ?>
                            <a onclick="showMoreInfoBox('<?php et("privacy.settings.{$option}") ?>', '<?php echo htmlspecialchars(t("privacy.settings.{$option}.moreinfo.html")) ?>')" href="javascript:void(0);">
                                <img class="privacy-moreinfo" src="/diamondbet/images/<?= brandedCss() ?>moreinfo-rtp_active.png"></a></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <br clear="all">
        </div>
        <?php
    }

}
