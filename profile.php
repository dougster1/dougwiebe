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
spl_autoload_register(function ($class_name) {
    include_once $class_name . '.php';
});

$punch_card_id = '0';
if (isset($_REQUEST['pcid'])) {
    $punch_card_id = Encoder::decode_string($_REQUEST['pcid']);
}

$card_customer_id = '0';
if (isset($_REQUEST['cid'])) {
    $card_customer_id = Encoder::decode_string($_REQUEST['cid']);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
        <title></title>
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="viewport" content="width=device-width,user-scalable=no">
        <meta http-equiv="Pragma" content="no-cache">
        <link href="//fonts.googleapis.com/css?family=Arimo" rel="stylesheet">
        <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="//engagesnap.com/accounts_assets/css/animateSTC.css" />
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
        $requst_arr_body_html = array('get_body_html' => '1');
        foreach ($_REQUEST as $key => $value) {
            $requst_arr_body_html[$key] = $value;
        }
        echo RMI($requst_arr_body_html);
        $_REQUEST['key'] = $punch_card_id;
        $request_arr_script = http_build_query($_REQUEST);
        ?>
        <script src="//engagesnap.com/accounts/api_smartform.php?get_body_script=1&<?php echo $request_arr_script; ?>"></script>
    </body>
</html>
<?php

function RMI($data = NULL) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_URL, 'https://engagesnap.com/accounts/api_smartform.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!is_null($data)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
?>