<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $location);
    exit;
}
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('X-Frame-Options: ALLOW-FROM https://t-xt.net');

spl_autoload_register(function ($class_name) {
    include_once $class_name . '.php';
});
@session_start();

include_once 'data.php';

$puchviaqr = '0';
if (isset($_REQUEST['punchcardqr'])) {
    if (isset($_REQUEST['qr_key'])) {
        $qr_key = $_REQUEST['qr_key'];
        $pcid2 = Encoder::decode_string($_REQUEST['pcid']);
        if (!empty($qr_key)) {
            $_SESSION["card_customer_id_$pcid2"] = $_REQUEST['card_customer_id'];
            $puchviaqr = '1';
        }
    }
}

$card_customer_id = $_SESSION["card_customer_id_$pciddecoded"];
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$actual_link_arr = explode('?', $actual_link);
preg_match_all('/\w+=.*/', $actual_link, $matches);
parse_str($matches[0][0], $actual_link_arr2);
unset($actual_link_arr2['err_login']);
if (isset($_REQUEST['punchcardqr'])) {
    unset($actual_link_arr2['punchcardqr']);
    unset($actual_link_arr2['qr_key']);
    unset($actual_link_arr2['card_customer_id']);
}
$current_url_clean = $actual_link_arr[0] . '?' . http_build_query($actual_link_arr2);
$_REQUEST['current_url_clean'] = $current_url_clean;
$sessionarray = array(
    "card_customer_id_$pciddecoded" => $_SESSION["card_customer_id_$pciddecoded"],
    "cc_email_$pciddecoded" => $_SESSION["cc_email_$pciddecoded"],
    "cc_password_$pciddecoded" => $_SESSION["cc_password_$pciddecoded"],
    "cc_mobile_number_$pciddecoded" => $_SESSION["cc_mobile_number_$pciddecoded"]
);
$_REQUEST['REMOTE_SESSION'] = json_encode($sessionarray);
$_REQUEST['user_ip'] = Utils::getUserIP();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title></title>
        <meta name="apple-mobile-web-app-capable" content="yes">
        <!--<meta name="viewport" content="width=device-width,user-scalable=no">-->
        <meta http-equiv="Pragma" content="no-cache">
        <?php
        $requst_arr_og = array('get_og_tags' => '1', 'key' => $punch_card_id);
        foreach ($_REQUEST as $key => $value) {
            $requst_arr_og[$key] = $value;
        }
        echo RMI($requst_arr_og);
        ?>
        <link href="//fonts.googleapis.com/css?family=Arimo" rel="stylesheet">
        <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//engagesnap.com/accounts_assets/css/animateSTC.css" />
        <?php
        include_once 'card_ath.php';
        ?>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
        <?php
        $fontsarr = GoogleFonts::get_fonts_arr();
        $fontsstr = implode('|', $fontsarr);
        ?>
        <link href="https://fonts.googleapis.com/css?family=<?php echo $fontsstr; ?>" rel="stylesheet">
        <?php
        echo "<style>";
        $requst_arr_css = array('get_head_css' => '1', 'key' => $punch_card_id);
        foreach ($_REQUEST as $key => $value) {
            $requst_arr_css[$key] = $value;
        }
        echo RMI($requst_arr_css);
        echo "</style>";
        ?>
    </head>
    <body style="background: ">
        <?php
        $requst_arr_body_html = array('get_body_html' => '1', 'key' => $punch_card_id);
        foreach ($_REQUEST as $key => $value) {
            $requst_arr_body_html[$key] = $value;
        }
        echo RMI($requst_arr_body_html);
        $_REQUEST['key'] = $punch_card_id;
        $request_arr_script = http_build_query($_REQUEST);
        ?>
        <script src="//engagesnap.com/accounts/api_card.php?get_body_script=<?php echo uniqid(); ?>&<?php echo $request_arr_script; ?>"></script>
        <script src="//cdn.jsdelivr.net/npm/sharer.js@latest/sharer.min.js"></script>
    </body>
</html>
