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
$scanner_id = sprintf('%s', $_REQUEST['scannerid']);
$pcid = $_REQUEST['pcid'];
$scanner_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$config_arr = json_decode(RMI(array('get_scanner_config' => '1', 'scanner_id' => $scanner_id, 'pcid' => $pcid)), TRUE);
$config = $config_arr['data'];
$suppressscannerloginafter = $config['suppressscannerloginafter'];
$scanner_password = $config['scanner_password'];
$override_password = $config['override_password'];
$client_logo = $config['client_logo'];
$scanner_ask_points = $config['scanner_ask_points'];
$hide_ath = $config['hide_ath'];
$hide_ath = isset($_REQUEST['ath']) || isset($_REQUEST['addtohome']) ? '1' : $hide_ath;
$addtohomeinrequest = '0';
if (isset($_REQUEST['addtohome'])) {
    $addtohomeinrequest = '1';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <script type="text/javascript" src="jsqrscanner.nocache.js?<?php echo uniqid(); ?>=<?php echo uniqid(); ?>"></script>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        <script src="//code.jquery.com/jquery-3.1.0.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <style>
            body{
                background-color:<?php echo empty($config['scanner_bg']) ? ('black') : ($config['scanner_bg']); ?>;
            }
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
                background-color:<?php echo empty($config['scanner_bg']) ? ('black') : ($config['scanner_bg']); ?>;
                height:100vh;
                width:100vw;
                opacity:.95;
            }
            #loyalty_scanner_heading {
                color:<?php echo empty($config['scanner_fg']) ? ('white') : ($config['scanner_fg']); ?>;
            }
            #alertmessage {
                color:<?php echo empty($config['scanner_fg']) ? ('white') : ($config['scanner_fg']); ?>;
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
            .modal-dialog{
                box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
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
        <script>
            var scanner_ask_points = '<?php echo $scanner_ask_points; ?>';
            var scanner_text = '<?php echo empty($config['scanner_text']) ? 'Loyalty Scanner' : $config['scanner_text']; ?>';
        </script>
    </head>
    <body>
        <div id="mainoverlay">
            <div id="overlay"></div>
            <div id="overlay2">
                <br>
                <img src="<?php echo $client_logo; ?>" style="    max-width: 300px;" >
                <br>
                <div id="loyalty_scanner_heading">Getting ready...</div>
                <br>
                <br>
                <p id="alertmessage"></p>
                <button onclick="scanPunchCardClicked(0)" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px;border-color: #007bff00; background: <?php echo $config['spcb_bg']; ?>; color: <?php echo $config['spcb_fg']; ?>"><?php echo $config['spcb_text']; ?></button>
                <button onclick="doOverridePunchClicked()" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px;border-color: #007bff00; background: <?php echo $config['dopb_bg']; ?>; color: <?php echo $config['dopb_fg']; ?>"><?php echo $config['dopb_text']; ?></button>
                <button id="manage_agents" onclick="manageAgentsClicked()" class="btn-primary" style=" display: none; width:80%;text-align:center;margin-bottom:10px;border-color: #007bff00; background: <?php echo $config['mab_bg']; ?>; color: <?php echo $config['mab_fg']; ?>"><?php echo $config['mab_text']; ?></button>
                <button id="athbtn" onclick="addToHomeClicked()" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px;display: <?php echo $hide_ath == '1' ? 'none' : 'none'; ?>;border-color: #007bff00; background: <?php echo $config['athsb_bg']; ?>; color: <?php echo $config['athsb_fg']; ?>"><?php echo $config['athsb_text']; ?></button>
                <button id="statsbtn" onclick="showStats('direct')" class="btn-primary" style="width:80%;text-align:center;margin-bottom:10px;display: <?php echo isset($_REQUEST['ath']) || isset($_REQUEST['addtohome']) ? '' : ''; ?>;border-color: #007bff00; background: <?php echo $config['sb_bg']; ?>; color: <?php echo $config['sb_fg']; ?>"><?php echo $config['sb_text']; ?></button>
                <div class="row">
                    <div class="col-md-12" style="text-align: center;">
                        <div class="checkbox-fade fade-in-primary">
                            <label>
                                <span class="j-label" style="font-size: 20px;">Keep Scanner Open</span>&nbsp;
                                <input id="keepscanningoption" onclick="setKeepScannerOption()" type="checkbox" checked="" style="width: 30px;height: 30px;float: right;margin-top: 14px;">
                                <span class="cr"><i class="cr-icon icofont icofont-ui-check txt-primary"></i></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>    		
        </div>
        <div id="scannerdiv" class="" style="overflow: hidden;display: none;">
            <div class="" id="cameraswitchdiv" style="    z-index: 200000;position: fixed;    width: 100%;    top: 0px;    right: 0px;    left: 0px; text-align: left;padding-left: 0px;padding-right: 0px;margin-bottom: 0px;margin-top: 0px;">
                <button onclick="backtomain()" id="backtomainmenubtn" class="btn btn-success btn-block" style="text-align:center;height: 40px;border: 0px solid white;border-radius: 0px;margin-top: 0px;"><i class="fa fa-arrow-left" aria-hidden="true" style="float: left;margin-top: 4px;"></i> <i class="fa fa-qrcode" aria-hidden="true" style="margin-top: 4px;"></i>&nbsp;Scanning...</button>
            </div>
            <div class="qrscanner" id="scanner"></div>
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
                                <button onclick="loginToScannerNow(this)" class="btn btn-block btn-danger" >Login Now</button>
                                <button id="scannerloginclosebtn" class="btn btn-block btn-dark" data-dismiss="modal" style="display: none;" >Close</button>
                            </div>
                        </div>
                    </div>
                    <div id="scannerpasswordmodalfooter" class="modal-footer" style="padding: 10px; display: none;">
                    </div>
                </div>
            </div>
        </div>
        <button id="transactionmodalhandle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#transactionmodal" style="display:  none;"></button>
        <div class="modal fade" id="transactionmodal" tabindex="-1" role="dialog" aria-labelledby="transactionmodalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog">
                <div class="modal-content">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title" id="transactionmodalLabel" style="font-size: 18px;"><b>Transaction Amount</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="transactionmodalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-12">
                                <label id="transaction_text"></label>                                
                            </div>
                            <div class="col-md-12">
                                <input id="transaction_amount" name="transaction_amount" class="form-control" type="number" placeholder="0.00" />
                            </div>
                            <div class="col-md-12" style="margin-top: 30px;">
                                <button onclick="processTransaction(this)" class="btn btn-block btn-danger" >Proceed</button>
                                <button onclick="resetTransactionData(0)" id="transactionclosebtn" class="btn btn-block btn-dark" data-dismiss="modal" style="" >Cancel</button>
                            </div>
                        </div>
                    </div>
                    <div id="transactionmodalfooter" class="modal-footer" style="padding: 10px; display: none;">
                    </div>
                </div>
            </div>
        </div>
        <button id="pointsmodalhandle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#pointsmodal" style="display:  none;"></button>
        <div class="modal fade" id="pointsmodal" tabindex="-1" role="dialog" aria-labelledby="pointsmodalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog">
                <div class="modal-content">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title" id="pointsmodalLabel" style="font-size: 18px;"><b>Custom Points</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="pointsmodalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-12">
                                <label id="points_text">How many points to give to customer?</label>                                
                            </div>
                            <div class="col-md-12">
                                <input id="points_amount" name="points_amount" onkeypress="return isNumber(event)" class="form-control" type="number" value="" placeholder="" />
                            </div>
                            <div class="col-md-12" style="margin-top: 30px;">
                                <button onclick="scanPunchCardClicked(2)" class="btn btn-block btn-danger" >Proceed</button>
                                <button onclick="resetpointsData(0)" id="pointsclosebtn" class="btn btn-block btn-dark" data-dismiss="modal" style="" >Cancel</button>
                            </div>
                        </div>
                    </div>
                    <div id="pointsmodalfooter" class="modal-footer" style="padding: 10px; display: none;">
                    </div>
                </div>
            </div>
        </div>
        <button id="manage_agents_modal_handle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#manage_agents_modal" style="display:  none;"></button>
        <div class="modal fade" id="manage_agents_modal" tabindex="-1" role="dialog" aria-labelledby="manage_agents_modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog" style=" width: 100%;max-width: 100%;  height: 100%;  margin: 0;  padding: 0;">
                <div class="modal-content" style="height: auto;  min-height: 100%;  border-radius: 0;">
                    <div  class="modal-header" style="padding: 10px;clear: both;">
                        <h5 class="modal-title pull-left" id="manage_agents_modalLabel" style="font-size: 12px;"><b>Manage Agents</b></h5>
                        <button onclick="addNewAgent()" class="btn btn-success btn-lg pull-right" style="font-size: 12px;"><i class="fa fa-user" aria-hidden="true"></i> Add Agent</button>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="manage_agents_modalbody" class="modal-body" style="padding: 10px;margin: 10px;overflow-y: scroll;">
                        <div class="row">
                            <div class="col-md-12" id="manage_agents_table_div" style="text-align: center;max-height: 600px; overflow-y: scroll;">
                            </div>
                        </div>
                    </div>
                    <div id="manage_agents_modalfooter" class="modal-footer" style="padding: 10px;">
                        <button id="manage_agents_modalclosebtn" type="button" class="btn btn-dark btn-block" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <button id="stats_modal_handle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#stats_modal" style="display:  none;"></button>
        <div class="modal fade" id="stats_modal" tabindex="-1" role="dialog" aria-labelledby="stats_modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog" style=" width: 100%;max-width: 100%;  height: 100%;  margin: 0;  padding: 0;">
                <div class="modal-content" style="height: auto;  min-height: 100%;  border-radius: 0;">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title pull-left" id="stats_modalLabel" style="font-size: 18px;"><b>Scanner Stats</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="stats_modalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row" >
                                    <div class="col-md-12">
                                        <select id="statsrange" onchange="showStatsfor(this)" style="width: 100%; margin-top: 14px;   margin-bottom: 14px;">
                                            <option value="0">Punches/Points Today</option>
                                            <option value="1">Punches/Points Yesterday</option>
                                            <option value="2">Punches/Points This Week</option>
                                            <option value="3">Punches/Points This Month</option>
                                            <option value="4">Punches/Points This Year</option>
                                            <option value="5">Punches/Points Last Month</option>
                                            <option value="6">Punches/Points Last Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12"><div id="stats_table_div" style="text-align: center; width: 100%;"></div></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row" >
                                    <div class="col-md-12">
                                        <select id="statsrangecpn" onchange="showStatsforCpn(this)" style="width: 100%;    margin-top: 14px;">
                                            <option value="0">Coupon Redemptions Today</option>
                                            <option value="1">Coupon Redemptions Yesterday</option>
                                            <option value="2">Coupon Redemptions This Week</option>
                                            <option value="3">Coupon Redemptions This Month</option>
                                            <option value="4">Coupon Redemptions This Year</option>
                                            <option value="5">Punches/Points Last Month</option>
                                            <option value="6">Punches/Points Last Year</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12"><div id="stats_table_coupon_div" style="text-align: center; width: 100%;"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="stats_modalfooter" class="modal-footer" style="padding: 0px;">
                        <button id="stats_modalclosebtn" type="button" class="btn btn-dark btn-block btn-lg" data-dismiss="modal" style="border-radius: 0px;">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <button id="historymodal_handle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#historymodal" style="display:  none;"></button>
        <div class="modal fade" id="historymodal" tabindex="-1" role="dialog" aria-labelledby="historymodalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog" style=" width: 100%;max-width: 100%;  height: 100%;  margin: 0;  padding: 0;">
                <div class="modal-content" style="height: auto;  min-height: 100%;  border-radius: 0;">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title pull-left" id="historymodalLabel" style="font-size: 18px;"><b>Punch History</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="historymodalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-12" id="punch_history_table_div" style="text-align: center;max-height: 600px; overflow-y: scroll;">
                            </div>
                        </div>
                    </div>
                    <div id="historymodalfooter" class="modal-footer" style="padding: 10px;">
                        <button id="historymodalclosebtn" type="button" class="btn btn-dark btn-block btn-lg" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <button id="add_agent_modal_handle" type="button" class="btn btn-primary" data-toggle="modal" data-backdrop="static" data-target="#add_agent_modal" style="display:  none;"></button>
        <div class="modal fade" id="add_agent_modal" tabindex="-1" role="dialog" aria-labelledby="add_agent_modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="dialog" style=" width: 100%;max-width: 100%;  height: 100%;  margin: 0;  padding: 0;">
                <div class="modal-content" style="height: auto;  min-height: 100%;  border-radius: 0;">
                    <div  class="modal-header" style="padding: 10px;">
                        <h5 class="modal-title pull-left" id="add_agent_modalLabel" style="font-size: 18px;"><b>Add Agent</b></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="display: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div id="add_agent_modalbody" class="modal-body" style="padding: 10px;margin: 10px;">
                        <div class="row">
                            <div class="col-md-12" id="manage_agents_table_div">
                                <label>Agent Name:</label>
                            </div>
                            <div class="col-md-12" id="manage_agents_table_div">
                                <input id="agent_name" name="agent_name" type="text" class="form-control" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12" id="manage_agents_table_div">
                                <label>Agent Email:</label>&nbsp;<label id="errlbl" style="color:red;"></label>
                            </div>
                            <div class="col-md-12" id="manage_agents_table_div">
                                <input id="agent_email" name="agent_email" type="email" class="form-control" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12" id="manage_agents_table_div">
                                <label>Agent Password:</label>&nbsp;<label id="errlbl" style="color:red;"></label>
                            </div>
                            <div class="col-md-12" id="manage_agents_table_div">
                                <input id="agent_password" name="agent_password" type="text" class="form-control" />
                            </div>
                        </div>
                    </div>
                    <div id="add_agent_modalfooter" class="" style="padding: 10px;">
                        <button type="button" class="btn btn-success btn-block btn-lg" onclick="saveAgentClicked(this)">Save Agent Now</button>
                        <button id="add_agent_modalclosebtn" type="button" class="btn btn-dark btn-block btn-lg" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="delagentmodal" tabindex="-1" role="dialog" aria-labelledby="delagentmodalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Delete Agent? </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" onclick="delete_agent_now()">Yes Delete Now</button>
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
            var camera = null;
            var camerasGlobal = null;
            var myScannerInterval = null;
            var jbScanner = null;
            var scannerReady = false;
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
            function stopScanning() {
                if (jbScanner != null) {
                    var scannerParentElement = document.getElementById("scanner");
                    if (scannerParentElement) {
                        jbScanner.removeFrom(scannerParentElement);
                    }
                }
            }
            function resetpointsData() {
                $('#points_amount').val("");
            }
            function isNumber(evt) {
                evt = (evt) ? evt : window.event;
                var charCode = (evt.which) ? evt.which : evt.keyCode;
                if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                    return false;
                }
                return true;
            }
            var points_amount = 0;
            function scanPunchCardClicked(mode) {
                if (mode == '2') {
                    if (foundInvalid('points_amount', '')) {
                        return;
                    }
                    points_amount = $('#points_amount').val();
                    $('#pointsclosebtn').click();
                }
                if (mode == '0') {
                    if (scanner_ask_points == '1') {
                        $('#points_amount').val('');
                        points_amount = 0;
                        $('#pointsmodalhandle').click();
                        return;
                    }
                }
                if (scannerReady == false) {
                    alert('Please wait scanner is getting ready!');
                    return false;
                }
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
                $('#alertmessage').text(obj['message']);
                setTimeout(function () {
                    $('#alertmessage').text('');
                }, 3000);
                is_override = '0';
                if (window.localStorage.getItem('ONSCANDONEACTION') == 'keepscanning') {
                } else {
                    $('#scannerdiv').hide();
                    $('#mainoverlay').fadeIn(500);
                    stopScanning();
                }
            }
            function addToHomeClicked() {
                window.location.href = '<?php echo $scanner_link . "&addtohome=1" ?>';
            }
            function doOverridePunchClicked() {
                $('#passwordmodalhandle').click();
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
                scanPunchCardClicked(1);
            }
            function loginToScannerNow(obj) {
                if (foundInvalid('scanner_password', '')) {
                    return false;
                }
                $(obj).text('Authorizing...');
                $('#scanner_password').css({'border': '1px solid #CCCCCC'});
                $.post('//engagesnap.com/accounts/ajax.php', {'scanner_login_verify': '1', 'scanner_id': scanner_id, scanner_password: $('#scanner_password').val()}, function (res) {
                    var objres = JSON.parse(res);
                    if (objres['is_valid'] == '1') {
                        setCookie('scannerp_' + scanner_id, scanner_password, suppressscannerloginafter);
                        setCookie('scanner_puncher_' + scanner_id, objres['puncher'], suppressscannerloginafter);
                        setCookie('scanner_puncher_agent_id_' + scanner_id, objres['puncher_agent_id'], suppressscannerloginafter);
                        $('#scannerloginclosebtn').click();
                        if (objres['puncher'] == 'admin' || objres['puncher'] == 'master') {
                            $('#manage_agents').show();
                            $('#statsbtn').show();
                        }
                    } else {
                        $('#scanner_password').css({'border': '2px solid red'});
                    }
                    $(obj).text('Login Now');
                });
                return false;
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
            var $ = jQuery.noConflict();
            $(document).ready(function () {
                if (getCookie('scannerp_' + scanner_id) == '') {
                    $('#scannerpasswordmodalhandle').click();
                }
                if (getCookie('scanner_puncher_' + scanner_id) == 'admin' || getCookie('scanner_puncher_' + scanner_id) == 'master') {
                    $('#manage_agents').show();
                    $('#statsbtn').show();
                } else {
                    $('#manage_agents').hide();
                    $('#statsbtn').hide();
                }
                if (isMobile.any()) {
                    $('#athbtn').show();
                } else {
                    $('#athbtn').hide();
                }
                if ('<?php echo $hide_ath ?>' == '1') {
                    $('#athbtn').hide();
                }
                if (window.navigator && navigator.serviceWorker) {
                    navigator.serviceWorker.getRegistrations().then(function (registrations) {
                        for (var i = 0; i < registrations.length; i++) {
                            if (registrations[i].active != null) {
                                var surl = registrations[i].active.scriptURL;
                                surl = "" + surl;
                                if (surl.indexOf('service-worker-scanner') >= 0) {
                                    console.log('found service-worker-scanner');
                                    registrations[i].unregister();
                                }
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
                $('#manage_agents_modalbody').css({'max-height': ($(window).height() - 150) + "px"});
                window.onresize = function (event) {
                    $('#manage_agents_modalbody').css({'max-height': ($(window).height() - 150) + "px"});
                };
                if (window.localStorage.getItem('ONSCANDONEACTION') == 'keepscanning') {
                    $('#keepscanningoption').attr('checked', true).prop('checked', true);
                } else {
                    $('#keepscanningoption').attr('checked', false).prop('checked', false);
                }
            });
            var lastUrl = '';
            var lastUrlData = {};
            var scanner_locked = false; // used to avoid frequent scans in seconds
            function onQRCodeScanned(scannedText) {
                if (scanner_locked) {
                    return;
                }
                if (scanner_locked == false) {
                    scanner_locked = true;
                }
                console.log('onQRCodeScanned');
                var puncher = getCookie('scanner_puncher_' + scanner_id);
                var puncher_agent_id = getCookie('scanner_puncher_agent_id_' + scanner_id);
                console.log("puncher: " + puncher);
                console.log("puncher_agent_id: " + puncher_agent_id);
                var URL = "" + scannedText;
                if (URL.indexOf('http://') >= 0 || URL.indexOf('https://') >= 0) {
                    URL = URL.replace('https://', '');
                    URL = URL.replace('http://', '');
                    URL = "https://" + URL;
                    $('#savingpunchmodalhandle').click();
                    $('#modalfooter').hide();
                    if (myScannerInterval != null) {
                        clearInterval(myScannerInterval);
                    }
                    resetTransactionData(1);
                    $.post(URL, {'scannerid': scanner_id, is_override: is_override, punch_mode: $('#punch_mode').val(), custom_punch_val: $('#custom_punch_val').val(), puncher: puncher, puncher_agent_id: puncher_agent_id, points_amount: points_amount}, function (res) {
                        var asked_for_amount = false;
                        var scanner_obj = JSON.parse(res);
                        if (scanner_obj.hasOwnProperty('enter_transaction_amount')) {
                            if (scanner_obj['enter_transaction_amount'] == '1') {
                                console.log('enter_transaction_amount');
                                lastUrl = URL;
                                lastUrlData = {'scannerid': scanner_id, is_override: is_override, punch_mode: $('#punch_mode').val(), custom_punch_val: $('#custom_punch_val').val(), puncher: puncher, puncher_agent_id: puncher_agent_id, points_amount: points_amount};
                                asked_for_amount = true;
                                $('#modalfooterclosebtn').click();
                                $('#transactionmodalhandle').click();
                                if (scanner_obj.hasOwnProperty('transaction_text')) {
                                    $('#transaction_text').text(scanner_obj['transaction_text']);
                                } else {
                                    $('#transaction_text').text('Enter transaction amount');
                                }
                                if (scanner_obj.hasOwnProperty('default_amount')) {
                                    $('#transaction_amount').val(scanner_obj['default_amount']);
                                } else {
                                    $('#transaction_amount').val('0.00');
                                }
                            }
                        } else if (scanner_obj.hasOwnProperty('enter_transaction_amount_card')) {
                            if (scanner_obj['enter_transaction_amount_card'] == '1') {
                                console.log('enter_transaction_amount_card');
                                lastUrl = URL;
                                lastUrlData = {'scannerid': scanner_id, is_override: is_override, punch_mode: $('#punch_mode').val(), custom_punch_val: $('#custom_punch_val').val(), puncher: puncher, puncher_agent_id: puncher_agent_id, points_amount: points_amount};
                                asked_for_amount = true;
                                $('#modalfooterclosebtn').click();
                                $('#transactionmodalhandle').click();
                                if (scanner_obj.hasOwnProperty('transaction_text_card')) {
                                    $('#transaction_text').text(scanner_obj['transaction_text_card']);
                                } else {
                                    $('#transaction_text').text('Enter transaction amount');
                                }
                                if (scanner_obj.hasOwnProperty('default_amount_card')) {
                                    $('#transaction_amount').val(scanner_obj['default_amount_card']);
                                } else {
                                    $('#transaction_amount').val('0.00');
                                }
                            }
                        }
                        if (!asked_for_amount) {
                            scanner_locked = false;
                            onScanDone(res);
                        }
                    });
                    myScannerInterval = setInterval(function () {
                        $('#modalfooter').show();
                        scanner_locked = false;
                    }, 5000);
                } else {
                    alert(scannedText);
                    backtomain();
                }
            }
            function startScanning() {
                jbScanner = new JsQRScanner(onQRCodeScanned);
                jbScanner.setSnapImageMaxSize(300);
                var scannerParentElement = document.getElementById("scanner");
                if (scannerParentElement) {
                    jbScanner.appendTo(scannerParentElement);
                }
            }
            function processTransaction(obj) {
                lastUrlData['transaction_amount'] = $('#transaction_amount').val();
                lastUrlData['proceed_transaction'] = '1';
                $.post(lastUrl, lastUrlData, function (res) {
                    scanner_locked = false;
                    onScanDone(res);
                    $('#transactionclosebtn').click();
                });
            }
            function resetTransactionData(source) {
                if (source == '0') {
                    $('#modalfooterclosebtn').click();
                }
                lastUrl = '';
                lastUrlData = {};
            }
            function JsQRScannerReady() {
                $('#loyalty_scanner_heading').text('Scanner Ready!');
                window.setTimeout(function () {
                    $('#loyalty_scanner_heading').text(scanner_text);
                }, 2000);
                scannerReady = true;
            }
            function manageAgentsClicked() {
                $('#manage_agents_modal_handle').click();
                $('#manage_agents_table_div').html('<img src="//engagesnap.com/accounts_assets/images/ajax-loading.gif" style="width:40px;" />');
                $.post('//engagesnap.com/accounts/ajax.php', {'get_manage_scanner_agents_html': '1', scanner_id: scanner_id}, function (res) {
                    $('#manage_agents_table_div').html(res);
                });
            }
            function addNewAgent() {
                $('#manage_agents_modalclosebtn').click();
                $('#add_agent_modal_handle').click();
                $('#agent_name').val('');
                $('#agent_password').val('');
                $('#agent_email').val('');
            }
            function saveAgentClicked(obj) {
                if (foundInvalid('agent_name', '')) {
                    return false;
                }
                if (foundInvalid('agent_password', '')) {
                    return false;
                }
                if (foundInvalid('agent_email', '')) {
                    return false;
                }
                $(obj).text('Saving...');
                $('#agent_password').css({'border': '1px solid #CCCCCC'});
                $('#agent_email').css({'border': '1px solid #CCCCCC'});
                $('#errlbl').text('');
                $.post('//engagesnap.com/accounts/ajax.php', {'add_scanner_agent': '1', scanner_id: scanner_id, agent_name: $('#agent_name').val(), agent_password: $('#agent_password').val(), agent_email: $('#agent_email').val(), location_url: window.location.href}, function (res) {
                    var obj3 = JSON.parse(res);
                    if (obj3['success'] == '1') {
                        $('#agent_name').val('');
                        $('#agent_password').val('');
                        $('#agent_email').val('');
                        window.location.reload();
                    } else {
                        $(obj).text('Save Agent Now');
                        $('#agent_password').css({'border': '2px solid red'});
                        $('#agent_email').css({'border': '2px solid red'});
                        $('#errlbl').text('Please use some other email/password.');
                    }
                });
            }
            var del_scanner_agent_id = 0;
            function get_del_agent_ready(scanner_agent_id) {
                del_scanner_agent_id = scanner_agent_id;
            }
            function delete_agent_now() {
                $.post('//engagesnap.com/accounts/ajax.php', {'delete_scanner_agent': '1', id: del_scanner_agent_id}, function (res) {
                    window.location.reload();
                });
            }
            function load_punch_history_html(id, puncher) {
                $('#historymodal_handle').click();
                $.post('//engagesnap.com/accounts/ajax.php', {'load_scanner_agent_punch_history': '1', id: id, puncher: puncher}, function (res) {
                    $('#punch_history_table_div').html(res);
                });
            }
            var rangeValue = '0';
            function showStats(source) {
                if (source != 'select') {
                    $('#statsrange').val('0');
                    rangeValue = '0';
                    $('#stats_modal_handle').click();
                }
                $('#stats_table_div').html('');
                $('#stats_table_div').html('<canvas id="myChart"></canvas>');
                var ctx = document.getElementById('myChart').getContext('2d');
                var form_data = new FormData();
                form_data.append("get_scanner_stats", true);
                form_data.append("scanner_id", scanner_id);
                form_data.append("rangeValue", rangeValue);
                $.ajax({
                    url: "//engagesnap.com/accounts/ajax.php",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data, // Setting the data attribute of ajax with file_data
                    type: 'post',
                    success: function (res) {
                        var obj = JSON.parse(res);
                        var config = {
                            type: 'doughnut',
                            radius: "100%",
                            data: {
                                labels: obj['labels'],
                                datasets: obj['datasets']
                            },
                            options: {
                                maintainAspectRatio: true,
                                responsive: true,
                                legend: {
                                    position: "bottom",
                                    align: "start"
                                },
                                animation: {
                                    animateScale: true,
                                    animateRotate: true
                                },
                                showLabels: true,
                                plugins: {
                                    labels: {
                                        render: 'value',
                                        precision: 0,
                                        fontSize: 14,
                                        fontColor: '#fff',
                                        fontStyle: 'bold',
                                        fontFamily: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                                    }
                                }
                            }
                        };
                        var chart = new Chart(ctx, config);
                    }
                });
                if (source == 'direct') {
                    showStatsCoupon(source);
                }
            }
            var rangeValueCoupon = '0';
            function showStatsCoupon(source) {
                if (source != 'select') {
                    $('#statsrangecpn').val('0');
                    rangeValueCoupon = '0';
                }
                $('#stats_table_coupon_div').html('');
                $('#stats_table_coupon_div').html('<canvas id="myChart2"></canvas>');
                var ctx = document.getElementById('myChart2').getContext('2d');
                var form_data = new FormData();
                form_data.append("get_scanner_stats_coupon", true);
                form_data.append("scanner_id", scanner_id);
                form_data.append("rangeValue", rangeValueCoupon);
                $.ajax({
                    url: "//engagesnap.com/accounts/ajax.php",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data, // Setting the data attribute of ajax with file_data
                    type: 'post',
                    success: function (res) {
                        var obj = JSON.parse(res);
                        var config = {
                            type: 'doughnut',
                            radius: "100%",
                            data: {
                                labels: obj['labels'],
                                datasets: obj['datasets']
                            },
                            options: {
                                maintainAspectRatio: true,
                                responsive: true,
                                legend: {
                                    position: "bottom",
                                    align: "start"
                                },
                                animation: {
                                    animateScale: true,
                                    animateRotate: true
                                },
                                showLabels: true,
                                plugins: {
                                    labels: {
                                        render: 'value',
                                        precision: 0,
                                        fontSize: 14,
                                        fontColor: '#fff',
                                        fontStyle: 'bold',
                                        fontFamily: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
                                    }
                                }
                            }
                        };
                        var chart = new Chart(ctx, config);
                    }
                });
            }
            function showStatsfor(obj) {
                rangeValue = $(obj).val();
                showStats('select');
            }
            function showStatsforCpn(obj) {
                rangeValueCoupon = $(obj).val();
                showStatsCoupon('select');
            }
            function setKeepScannerOption() {
                if ($('#keepscanningoption').is(':checked')) {
                    window.localStorage.setItem('ONSCANDONEACTION', 'keepscanning');
                } else {
                    window.localStorage.setItem('ONSCANDONEACTION', '');
                }
            }
        </script>
        <script src="//cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
        <script src="//cdn.jsdelivr.net/gh/emn178/chartjs-plugin-labels/src/chartjs-plugin-labels.js"></script>
        <script src="utils.js"></script>
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
