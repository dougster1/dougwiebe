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
$scanner_id = $_REQUEST['scannerid'];
$pcid = $_REQUEST['pcid'];
$scanner_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$config_arr = json_decode(RMI(array('get_scanner_config' => '1', 'scanner_id' => $scanner_id, 'pcid' => $pcid)), TRUE);
$config = $config_arr['data'];
$suppressscannerloginafter = $config['suppressscannerloginafter'];
$scanner_password = $config['scanner_password'];
$override_password = $config['override_password'];
$client_logo = $config['client_logo'];

$addtohomeinrequest = '0';
if (isset($_REQUEST['addtohome'])) {
    $addtohomeinrequest = '1';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        <script src="//ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script type="text/javascript" src="is.js"></script>
        <style>
            #errordivelement{
                width: 100%;
                background: snow;
                text-align: left;
                border: 1px solid red;
                padding: 2px;
                border-radius: 3px;
                color: red;
                display: none;
            }
            #alertdivelement{
                width: 100%;
                background: snow;
                text-align: left;
                border: 1px solid green;
                padding: 2px;
                border-radius: 3px;
                color: green;
                display: none;
            }
            #scanningwindow{
                height: 1000px;
                overflow-x: hidden;
                overflow-y: hidden;
                border: 1px solid lightgray;
                border-radius: 2px;
                border-bottom: 0px;
            }
            #outputwindow{
                height: 1000px;
                overflow-x: hidden;
                overflow-y: hidden;
                border: 1px lightgray;
                border-radius: 2px;
                border-bottom: 0px;
            }
            #iframewindow{
                width: 100%;
                height: 1000px;
                overflow-x: hidden;
                border: 0px lightgray;
            }
            #overlay {
                position:absolute;
                z-index:1000;
                background-color:black;
                height:100vh;
                width:100vw;
                opacity:.95;
            }
            #overlay2 {
                position:absolute;
                z-index:1001;
                height:100vh;
                width:100vw;
                text-align:center;
                color:white;
                font-size:30px;
            }
        </style>
        <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <?php
        if (isset($_REQUEST['addtohome'])) {
            $manifest = Utils::curl_operations('https://engagesnap.com/accounts/api_card.php', array('get_scanner_manifest_json' => '1', 'scanner_id' => $scanner_id, 'pcid' => $pcid, 'start_url' => $scanner_link));
            file_put_contents("sm_$scanner_id.json", $manifest);
            ?>
            <link rel="manifest" href="<?php echo "sm_$scanner_id.json?rand=" . uniqid(); ?>">
            <?php
        }
        ?>
    </head>


    <body>
        <div id="mainoverlay">
            <div id="overlay"></div>
            <div id="overlay2">
                <br>
                <img src="<?php echo $client_logo; ?>" style="    max-width: 300px;" >
                <br>
                Loyalty Scanner
                <br>
                <br>
                <p id="alertmessage"></p>
                <button onclick="scanPunchCardClicked()" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px">Scan Punch Card</button>
                <button onclick="doOverridePunchClicked()" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px">Do Override Punch</button>
                <button id="athbtn" onclick="addToHomeClicked()" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px;display: <?php echo isset($_REQUEST['ath']) || isset($_REQUEST['addtohome']) ? 'none' : ''; ?>">Add to Home Screen</button>
            </div>    		
        </div>


        <div id="scannerdiv" class="" style="overflow: hidden;display: none;">
            <div class="row">
                <div class="col-md-12" id="scanningwindow">
                    <div class="row">
                        <div class="col-md-12" style="text-align: center;">
                            <h4>Point Camera Over The QR Code</h4>
                            <hr style="    margin-bottom: 4px;" />
                        </div>
                    </div>
                    <hr/>
                    <div class="row" id="cameraswitchdiv" style="text-align: left;padding-left: 6%;padding-right: 6%;margin-bottom: 4px;">
                        <button onclick="backtomain()" id="backtomainmenubtn" class="btn btn-success" style="text-align:center;width: 24%;height: 40px;margin-right: 1%;"><i class="fa fa-arrow-left" aria-hidden="true"></i></button>
                    </div>
                    <hr/>
                    <div class="row">
                        <div class="col-md-1" style="text-align: center;"></div>
                        <div class="col-md-10" style="text-align: center;">
                            <div id="errordivelement" style="margin-bottom: 10px;"></div>
                            <video id="preview" style="width: 100%;"></video>
                        </div>
                        <div class="col-md-1" style="text-align: center;"></div>
                    </div>
                </div>
            </div>
        </div>
        <button id="savingpunchmodalhandle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#savingpunchmodal" style="display:  none;"></button>
        <div class="modal fade" id="savingpunchmodal" tabindex="-1" role="dialog" aria-labelledby="savingpunchmodalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog">
                <div class="modal-content">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title" id="savingpunchmodalLabel" style="font-size: 18px;"><b>Saving Punch...</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="modalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>
                    </div>
                    <div id="modalfooter" class="modal-footer" style="padding: 10px; display: none;">
                        <!--<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Save</button>-->
                        <button id="modalfooterclosebtn" type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <button id="passwordmodalhandle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#passwordmodal" style="display:  none;"></button>
        <div class="modal fade" id="passwordmodal" tabindex="-1" role="dialog" aria-labelledby="passwordmodalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog">
                <div class="modal-content">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title" id="passwordmodalLabel" style="font-size: 18px;"><b>Override Punch</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="passwordmodalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-12">
                                <label>Enter Approve Code</label>                                
                            </div>
                            <div class="col-md-12">
                                <input id="password" name="password" class="form-control" type="password" />
                            </div>

                            <div class="" style="width: 100%;">
                                <div class="col-md-12">
                                    <label style="    margin-top: 10px;">Punch Mode</label>                                
                                </div>
                                <div class="col-md-12">
                                    <select onchange="punchModeChanged(this)" id="punch_mode" name="punch_mode" class="form-control">
                                        <option value="auto">Auto</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <div id="custom_punch_mode_div" class="col-md-12" style="margin-top: 10px; display: none;">
                                    <div class="row" >
                                        <div class="col-md-12">
                                            <label>How many punches you want to give?</label>        
                                        </div>
                                        <div class="col-md-12">
                                            <input id="custom_punch_val" name="custom_punch_val" class="form-control" type="number" />
                                        </div>
                                    </div>

                                </div>
                            </div>


                            <div class="col-md-12" style="margin-top: 30px;">
                                <button onclick="overridePunchNow()" class="btn btn-block btn-danger" >Submit</button>
                                <button id="closeoverridebtn" class="btn btn-block btn-dark" data-dismiss="modal" >Close</button>
                            </div>
                        </div>

                    </div>
                    <div id="passwordmodalfooter" class="modal-footer" style="padding: 10px; display: none;">
                        <!--<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Save</button>-->
                        <!--<button id="modalfooterclosebtn" type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>-->
                    </div>
                </div>
            </div>
        </div>


        <button id="scannerpasswordmodalhandle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#scannerpasswordmodal" style="display:  none;"></button>
        <div class="modal fade" id="scannerpasswordmodal" tabindex="-1" role="dialog" aria-labelledby="scannerpasswordmodalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog">
                <div class="modal-content">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title" id="scannerpasswordmodalLabel" style="font-size: 18px;"><b>Scanner Login</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="scannerpasswordmodalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-12">
                                <label>Enter Password</label>                                
                            </div>
                            <div class="col-md-12">
                                <input id="scanner_password" name="scanner_password" class="form-control" type="password" />
                            </div>
                            <div class="col-md-12" style="margin-top: 30px;">
                                <button onclick="loginToScannerNow()" class="btn btn-block btn-danger" >Login Now</button>
                                <button id="scannerloginclosebtn" class="btn btn-block btn-dark" data-dismiss="modal" style="display: none;" >Close</button>
                            </div>
                        </div>
                    </div>
                    <div id="scannerpasswordmodalfooter" class="modal-footer" style="padding: 10px; display: none;">
                        <!--<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Save</button>-->
                        <!--<button id="modalfooterclosebtn" type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>-->
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">


            var suppressscannerloginafter = '<?php echo $suppressscannerloginafter; ?>';
            var scanner_password = '<?php echo $scanner_password; ?>';
            var scanner_id = '<?php echo $scanner_id; ?>';
            var override_password = '<?php echo $override_password; ?>';
            var addtohomeinrequest = '<?php echo $addtohomeinrequest; ?>';
            var is_override = '0';
            var scanner = null;
            var camera = null;
            var camerasGlobal = null;
            var myScannerInterval = null;
            var isMobile = {
                Android: function () {
                    return navigator.userAgent.match(/Android/i);
                },
                BlackBerry: function () {
                    return navigator.userAgent.match(/BlackBerry/i);
                },
                iOS: function () {
                    return navigator.userAgent.match(/iPhone|iPad|iPod/i);
                },
                Opera: function () {
                    return navigator.userAgent.match(/Opera Mini/i);
                },
                Windows: function () {
                    return navigator.userAgent.match(/IEMobile/i);
                },
                any: function () {
                    return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
                }
            };


            function setCookie(cname, cvalue, exhrs) {
                var d = new Date();
                d.setTime(d.getTime() + (exhrs * 60 * 60 * 1000));
                var expires = "expires=" + d.toUTCString();
                document.cookie = cname + "=" + cvalue + "; " + expires;
            }
            function getCookie(cname) {
                var name = cname + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ')
                        c = c.substring(1);
                    if (c.indexOf(name) == 0) {
                        var rv = c.substring(name.length, c.length);
                        if (rv == null || rv == 'null' || rv == 'undefined' || typeof rv === 'undefined') {
                            rv = '';
                        }
                        return rv;
                    }
                }
                return "";
            }

            function punchModeChanged(obj) {

                $('#custom_punch_mode_div').hide();

                var mode = $(obj).val();
                if (mode === 'custom') {
                    $('#custom_punch_mode_div').fadeIn(500);
                }
            }

            var runCameraAtIndex = getCookie('runCameraAtIndex');
            runCameraAtIndex = Number(runCameraAtIndex);

            function runCamera(obj) {
                runCameraAtIndex = Number($(obj).val());
                scanner.start(cameras[runCameraAtIndex]);
                if (runCameraAtIndex == '1') {
                    $('#preview').css({'transform': 'scaleX(1)'});
                } else {
                    $('#preview').css({'transform': 'scaleX(-1)'});
                }

                setCookie('runCameraAtIndex', runCameraAtIndex, 8760);
            }
            function startScanning() {



                scanner = new Instascan.Scanner({video: document.getElementById('preview')});
                scanner.addListener('scan', function (content) {
                    var URL = content.toString();
                    URL = URL.replace('https://', '');
                    URL = URL.replace('http://', '');
                    URL = "https://" + URL;
                    $('#savingpunchmodalhandle').click();
                    $('#modalfooter').hide();

                    if (myScannerInterval != null) {
                        clearInterval(myScannerInterval);
                    }

                    $.post(URL, {'scannerid': scanner_id, is_override: is_override, punch_mode: $('#punch_mode').val(), custom_punch_val: $('#custom_punch_val').val()}, function (res) {
                        onScanDone(res);
                    });

                    myScannerInterval = setInterval(function () {
                        $('#modalfooter').show();
                    }, 5000);

                });
                Instascan.Camera.getCameras().then(function (camerasGlobal) {
                    cameras = camerasGlobal;
                    if (cameras.length > 0) {

                        runCameraAtIndex = getCookie('runCameraAtIndex');
                        runCameraAtIndex = Number(runCameraAtIndex);

                        $('#camera_selection').remove();
                        $('#cameraswitchdiv').append('<select id="camera_selection" onchange="runCamera(this)" class="btn btn-success" style="width: 74%;text-align: center;height: 40px;margin-left: 1%;"></select>');
                        var sel = document.getElementById('camera_selection');
                        for (var o = 0; o < (sel.options).length; o++) {
                            sel.removeChild(sel.options[o]);
                        }
                        for (var i = 0; i < cameras.length; i++) {
                            var opt = document.createElement('option');
                            opt.appendChild(document.createTextNode('Use Camera #' + i));
                            opt.value = i;
                            sel.appendChild(opt);
                        }

                        $('#camera_selection').val(runCameraAtIndex);

                        camera = cameras[runCameraAtIndex];
                        scanner.start(cameras[runCameraAtIndex]);
                        if (runCameraAtIndex == '1') {
                            $('#preview').css({'transform': 'scaleX(1)'});
                        } else {
                            $('#preview').css({'transform': 'scaleX(-1)'});
                        }
                        setCookie('runCameraAtIndex', runCameraAtIndex, 8760);

                    } else {
                        $('#errordivelement').html('No cameras found.');
                        $('#errordivelement').fadeIn(500);
                        $('#backtomainmenubtn').fadeIn(500);
                    }
                }).catch(function (e) {
                    console.error(e);
                    $('#errordivelement').html(e);
                    $('#errordivelement').fadeIn(500);
                    $('#backtomainmenubtn').fadeIn(500);
                });
            }
            function stopScanning() {
                scanner.stop(camera);
                scanner = null;
            }
            function scanPunchCardClicked() {


                $('#mainoverlay').hide();
                $('#scannerdiv').hide();
                $('#scannerdiv').fadeIn(500);
                $('#alertmessage').text('');
                startScanning();
            }
            function backtomain() {
                $('#mainoverlay').hide();
                $('#scannerdiv').hide();
                $('#mainoverlay').fadeIn(500);
                $('#alertmessage').text('');
                stopScanning();
            }
            function onScanDone(res) {
                var obj = JSON.parse(res);
                $('#modalfooterclosebtn').click();
                $('#modalfooter').hide();
                $('#mainoverlay').hide();
                $('#scannerdiv').hide();
                $('#mainoverlay').fadeIn(500);
                $('#alertmessage').text(obj['message']);
                is_override = '0';



                stopScanning();
            }
            function addToHomeClicked() {
                window.location.href = '<?php echo $scanner_link . "&addtohome=1" ?>';
            }
            function doOverridePunchClicked() {
                $('#passwordmodalhandle').click();

                // forget last custom selection
                $('#punch_mode').val('auto');
                $('#custom_punch_mode_div').hide();
                $('#custom_punch_val').val('');
            }
            function overridePunchNow() {
                is_override = '0';
                if (foundInvalid('password', '')) {
                    return false;
                }

                if ($('#password').val() != override_password) {
                    $('#password').css({'border': '2px solid red'});
                    return false;
                } else {
                    $('#password').css({'border': '1px solid #CCCCCC'});
                }

                if ($('#punch_mode').val() == 'custom') {
                    if (foundInvalid('custom_punch_val', '')) {
                        return false;
                    }
                }

                is_override = '1';
                $('#closeoverridebtn').click();
                scanPunchCardClicked();
            }
            function loginToScannerNow() {
                if (foundInvalid('scanner_password', '')) {
                    return false;
                }
                if ($('#scanner_password').val() != scanner_password) {
                    $('#scanner_password').css({'border': '2px solid red'});
                    return false;
                } else {
                    $('#scanner_password').css({'border': '1px solid #CCCCCC'});
                }
                setCookie('scannerp_' + scanner_id, scanner_password, suppressscannerloginafter);
                $('#scannerloginclosebtn').click();
            }
            function foundInvalid(elem_id, error_if) {
                if ($('#' + elem_id).val().trim() == error_if) {
                    $('#' + elem_id).css({'border': '2px solid red'});
                    return true;
                } else {
                    $('#' + elem_id).css({'border': '1px solid #CCCCCC'});
                }
                return false;
            }




            $(document).ready(function () {

                if (getCookie('scannerp_' + scanner_id) != scanner_password) {
                    $('#scannerpasswordmodalhandle').click();
                }
                if (isMobile.any()) {
                } else {
                    $('#athbtn').hide();
                }

                if (window.navigator && navigator.serviceWorker) {
                    navigator.serviceWorker.getRegistrations().then(function (registrations) {
                        for (var i = 0; i < registrations.length; i++) {

                            var surl = registrations[i].active.scriptURL;
                            surl = "" + surl;
                            if (surl.indexOf('service-worker-scanner') >= 0) {
                                console.log('found service-worker-scanner');
                                registrations[i].unregister();
                            }
                        }
                    });
                }

                if (addtohomeinrequest == '1') {
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('service-worker-scanner.js?rand=' + Math.random()).then(function (reg) {
                        }).catch(function (err) {
                        });
                    }
                }

            });
        </script>
    </body>
</html>
<?php

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
?>
