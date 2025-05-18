<?php
$nationalities = $birthcountries = lic('getNationalities');
?>
<div class="lic-mbox-container nationality-main-popup <?= phive()->isMobile() ? 'limits-info mobile': '' ?> ">
    <div class="center-stuff">
        <p>
            <?php et('select.nationality.description') ?>
        </p>

        <div class="nationality-select-box">
            <span class="styled-select">
                 <?php dbSelect('nationality-select', array_merge(['' => t('nationality.default.select.option')], $nationalities)) ?>
            </span>
            <br />
            <p class="error hidden nationality-error"><?php et('nationality.error.description') ?></p>
        </div>
    </div>

    <br/>
    <div class="center-stuff province-footer">
        <button class="btn btn-l btn-default-l w-100-pc" onclick="licFuncs.nationalityPopupHandler().sendCountrySelected()"><?php et('confirm') ?></button>
    </div>
</div>
