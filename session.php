<?php

header("Access-Control-Allow-Origin: *");
spl_autoload_register(function ($class_name) {
    include_once $class_name . '.php';
});
$_REQUEST['white_label'] = 'PT1BYTBSSGN6cHpMdlVtYm5GMlpsTm5iaEJuTGo5V2J2RTJZajlXZHVSM2N2RW1haGhuTHdoR2M=';
if (isset($_REQUEST['resetCardCustomerPreferences'])) {
    @session_start();
    $punch_card_id = $_REQUEST['punch_card_id'];
    unset($_SESSION["card_customer_id_$punch_card_id"]);
    unset($_SESSION["cc_email_$punch_card_id"]);
    unset($_SESSION["cc_mobile_number_$punch_card_id"]);
    unset($_SESSION["cc_password_$punch_card_id"]);
    session_destroy();
    Utils::curl_operations(Encoder::decode_string($_REQUEST['white_label']), $_REQUEST);
    $referrer = $_SERVER['HTTP_REFERER'];
    echo '<script>window.location.href = "' . $referrer . '"</script>';
    exit();
}
if (isset($_REQUEST['card_customer_auto_login'])) {
    @session_start();
    $punch_card_id = $_REQUEST['punch_card_id'];
    $resjson = Utils::curl_operations(Encoder::decode_string($_REQUEST['white_label']), $_REQUEST);
    $resjsonarr = json_decode($resjson, TRUE);
    $card_customer_id = $resjsonarr['card_customer_id'];
    $_SESSION["card_customer_id_$punch_card_id"] = $card_customer_id;
    $_SESSION["cc_email_$punch_card_id"] = $_REQUEST['email'];
    $_SESSION["cc_mobile_number_$punch_card_id"] = $_REQUEST['mobile_number'];
    $_SESSION["cc_password_$punch_card_id"] = $_REQUEST['password'];
    echo json_encode(array('success' => '1', 'card_customer_id' => $card_customer_id));
    exit();
}
if (isset($_REQUEST['save_card_pdf'])) {
    $resjson = Utils::curl_operations(Encoder::decode_string($_REQUEST['white_label']), $_REQUEST);
    $resjsonarr = json_decode($resjson, TRUE);
    $card_customer_id = $resjsonarr['card_customer_id'];
    @session_start();
    $punch_card_id = $_REQUEST['punch_card_id'];
    $_SESSION["card_customer_id_$punch_card_id"] = $card_customer_id;
    $_SESSION["cc_email_$punch_card_id"] = $_REQUEST['email'];
    $_SESSION["cc_mobile_number_$punch_card_id"] = $_REQUEST['mobile_number'];
    $_SESSION["cc_password_$punch_card_id"] = $_REQUEST['password'];
    $referrer = $_SERVER['HTTP_REFERER'];

    if ($resjsonarr['smart_form_enabled'] == '1') {
        echo '<script>window.location.href = "profile.php?pcid=' . Encoder::encode_string($punch_card_id) . '&cid=' . Encoder::encode_string($card_customer_id) . '&ref=' . $referrer . '"</script>';
        exit();
    }

    echo '<script>window.location.href = "' . $referrer . '"</script>';
    exit();
}
if (isset($_REQUEST['card_customer_login'])) {
    @session_start();
    $resjson = Utils::curl_operations(Encoder::decode_string($_REQUEST['white_label']), $_REQUEST);
    $resjsonarr = json_decode($resjson, TRUE);
    $card_customer_id = $resjsonarr['card_customer_id'];
    if ($card_customer_id === FALSE || $card_customer_id < 1) {
        $referrer = $_SERVER['HTTP_REFERER'];
        $referrer_arr = explode('?', $referrer);
        preg_match_all('/\w+=.*/', $referrer, $matches);
        parse_str($matches[0][0], $output);
        $output["err_login"] = '1';
        $redirectourl = $referrer_arr[0] . '?' . http_build_query($output);
        echo '<script>window.location.href = "' . $redirectourl . '"</script>';
        exit();
    }
    $punch_card_id = $_REQUEST['punch_card_id'];
    $_SESSION["card_customer_id_$punch_card_id"] = $card_customer_id;
    $_SESSION["cc_email_$punch_card_id"] = $_REQUEST['email'];
    $_SESSION["cc_mobile_number_$punch_card_id"] = $_REQUEST['mobile_number'];
    $_SESSION["cc_password_$punch_card_id"] = $_REQUEST['password'];
    $referrer = $_SERVER['HTTP_REFERER'];
    $referrer_arr = explode('?', $referrer);
    preg_match_all('/\w+=.*/', $referrer, $matches);
    parse_str($matches[0][0], $output);
    $output["err_login"] = '';
    $redirectourl = $referrer_arr[0] . '?' . http_build_query($output);
    echo '<script>window.location.href = "' . $redirectourl . '"</script>';
    exit();
}
