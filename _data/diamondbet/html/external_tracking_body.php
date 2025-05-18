<?php

use DBUserHandler\Libraries\GoogleEventAggregator;

require_once __DIR__ . '/../../phive/phive.php';

$events = GoogleEventAggregator::getEvents();
$user = cuRegistration();

if(empty($user)) {
    $user = null;
}
/*
 Sending event to Google by AJAX, sometimes when the page is redirected like documents,
 the events are not fired and we need to send by ajax
*/
if ($_POST['action'] == 'get-pending-events') {

    $results = [];

    if (empty($user)) {
        echo json_encode($results);
        exit;
    }

    foreach ($events as $key) {
        $analytics_object = GoogleEventAggregator::getAnalyticsObject($user, $key);
        if (!empty($analytics_object)) {
            $results[] = $analytics_object;
        }
    }
    echo json_encode($results);
    // If we are requesting the info via ajax, we don't need to process anything else in this file.
    exit;
}

$physicalGeo    = phive('IpBlock')->getCountry(remIp());
$userGeo        = ($user instanceof DBUser) ? $user->getCountry() : null;
$onLoad         = <<<HTML
<script type="text/javascript">
$(window).on("load", function(){checkForGACookieId();dataLayer.push({_pgeo:'{$physicalGeo}'});dataLayer.push({_ugeo:'{$userGeo}'});});
</script>
HTML;

echo $onLoad;

/**
 * Main logic to push events in case of page reloads
 * https://developer.mozilla.org/en-US/docs/Web/API/Window/parent
 * window.parent: If a window does not have a parent, its parent property is a reference to itself.
 */

$skip_consent_check = phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_KUNGASLOTTET;
$has_consent = isset($_COOKIE['cookies_consent_info']);

if (isExternalTrackingEnabled() && ($skip_consent_check || $has_consent)) { ?>

    <script type="text/javascript">
        initializeConsent();
        var user_id = <?php echo !empty($user) ? $user->getId(): 'null' ?>;
        if (user_id !== null) dataLayer.push({userId: user_id});
        updateConsent();
        google_key('<?php echo phive()->getDomainSetting('google_analytic_tag_key') ?>', dataLayer);
    </script>

    <?php
    // Handling all the supported events for each tracking company
    foreach ($events as $key) {
        $ecommerce_object = GoogleEventAggregator::getAnalyticsObject($user, $key);
        if (empty($ecommerce_object)) {
            continue;
        }

        if (phive()->getSetting('enable_logs_in_google_events')) {
            phive('Logger')->getLogger('google-analytics')->info($ecommerce_object, ['tag'=>"google-analytics-sent-to-google-{$key}", 'user_id'=>$user->getId()]);
        }

        echo '<script>window.parent.google_datalayer(' . json_encode($ecommerce_object) . ')</script>';

    }

    // Remove all keys that have been fired to google.
    GoogleEventAggregator::removeKeys($events, $user);
}
