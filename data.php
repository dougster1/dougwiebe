<?php

spl_autoload_register(function ($class_name) {
    include_once $class_name . '.php';
});

if (isset($_REQUEST['l'])) {
    $clickyhere = file_get_contents("https://engagesnap.com/accounts/ClickyHere.php?get_qs_json=" . $_REQUEST['l']);
    $clickyhere_arr = json_decode($clickyhere, TRUE);
    if ($clickyhere_arr['success'] == '1') {
        $clickyhere_data = $clickyhere_arr['data'];
        if (count($clickyhere_data) > 0) {
            foreach ($clickyhere_data as $k => $v) {
                $_REQUEST[$k] = $v;
            }
        }
    }
}

include_once 'config.php';
$l = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
if (isset($_REQUEST['auto_update'])) {
    $zip_name = RMI2(array('get_download_ready' => '1'));
    downloadFile("https://engagesnap.com/card_export/$zip_name", $zip_name);
    $unzip = new ZipArchive();
    $out = $unzip->open($zip_name);
    if ($out === TRUE) {
        $unzip->extractTo(getcwd());
        $unzip->close();
        unlink($zip_name);
    }
    if (!isset($_REQUEST['norefreshonupdate'])) {
        echo "<script>window.location.href = '$l'</script>";
    }
    exit();
}
if (isset($_REQUEST['pcid'])) {
    $_REQUEST['key'] = $_REQUEST['pcid'];
}
if (isset($_REQUEST['key'])) {
    $punch_card_id = $_REQUEST['key'];
} else if (isset($_GET['key'])) {
    $punch_card_id = $_GET['key'];
} else {
    $punch_card_id = $card_key;
}
if ($punch_card_id == 'YOUR_CARD_KEY_HERE') {
    echo "Invalid card key! Please update your config file or add URL parameter like index.php?key=YOUR_CARD_KEY_HERE";
    exit();
}
$punch_card_id = sprintf('%s', $punch_card_id);
$pciddecoded = Encoder::decode_string($punch_card_id);
$pciddecoded = sprintf('%s', $pciddecoded);
RMI(array('punch_card_id' => $punch_card_id, 'ref_export' => $l));

function downloadFile($url, $path) {
    $newfname = $path;
    $file = fopen($url, "rb");
    if ($file) {
        $newf = fopen($newfname, "wb");
        if ($newf)
            while (!feof($file)) {
                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
            }
    }
    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
}

function RMI2($data = NULL) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_URL, 'https://engagesnap.com/card_export/api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!is_null($data)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function RMI($data = NULL) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_URL, 'https://engagesnap.com/accounts/api_card.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!is_null($data)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
