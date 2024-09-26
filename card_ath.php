<?php
$manifest = Utils::curl_operations('https://engagesnap.com/accounts/api_card.php', array('get_card_manifest_json' => '1', 'punch_card_id' => $pciddecoded, 'current_url_clean' => $current_url_clean));
file_put_contents("card_manifest_$pciddecoded.json", $manifest);


// also make different service worker
$sw_contents = "self.addEventListener('beforeinstallprompt', function (e) {    return e.userChoice.then(function (choiceResult) {        if (choiceResult.outcome == 'accepted') {        } else {        }    });});self.addEventListener('fetch', function (event) {});";
file_put_contents("service-worker-$pciddecoded.js", $sw_contents);
?>
<link rel="manifest" href="<?php echo "card_manifest_$pciddecoded.json?rand=" . uniqid(); ?>">