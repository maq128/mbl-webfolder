<?php
require_once("secureCommon.inc");

$pos = strpos($_SERVER["HTTP_USER_AGENT"], "Macintosh");

// Get webdav path and start directory
$webdav_path = $_GET['webdav_path'];
$map_key = $_GET['mapkey'];
//echo '###'.$map_key.'^^^'.$_GET['mapkey'];
if (empty($map_key)) {
    header('Location: /Admin/webapp/htdocs/accessDenied.php');
    return;
}

//get list of shares accesible for this user
$devUserId = $_SESSION[$map_key]["deviceUserId"];
$authCode =  $_SESSION[$map_key]["deviceUserAuthCode"];
//echo "1. " . $devUserId;
//echo "2. " . $authCode;

$shrObj = new ShareAccess();
$devUserDao = new DeviceUsersDB();
$userDao = new UsersDB();

if ($devUserDao->isValid($devUserId, $authCode)) {
   $deviceUser = $devUserDao->getDeviceUser($devUserId); 
   $user = $userDao->getUser($deviceUser["user_id"]);
} else {
	header('Location: /Admin/webapp/htdocs/accessDenied.php');
    return;
}


$shares = $shrObj->getSharesForUser($deviceUser["user_id"]);

$isLan = "false";

$upnpStatusConfig = getUpnpStatus("config");
if (isset($upnpStatusConfig)) {
	$deviceStatus=$upnpStatusConfig["COMMUNICATION_STATUS"];
	if (isLanRequest()) {
		$portNumber=$upnpStatusConfig["INTERNAL_PORT"];
		$sslPortNumber=$upnpStatusConfig["DEVICE_SSL_PORT"];
		$isLan = "true";
	} else if (strcasecmp("relayed",trim($deviceStatus)) == 0) {
		$portNumber=80;
		$sslPortNumber=443;
	} else {
		$portNumber=$upnpStatusConfig["EXTERNAL_PORT"];
		$sslPortNumber=$upnpStatusConfig["EXTERNAL_SSL_PORT"];
	}	
}

$portNumber = empty($portNumber)? 80: $portNumber;
$sslPortNumber = empty($sslPortNumber)? 443: $sslPortNumber;




// Get host name/IP and port number

if (strpos($_SERVER["REQUEST_URI"], "http") !== false) {
//relayed
	$urlComp = parse_url($_SERVER["REQUEST_URI"]);
	$portStr= "";
	if (isset($urlComp['port'])) {
		$portStr = ":".$urlComp['port'];
	}
	$name = $urlComp['host'];

	
	list($sub1, $sub2, $dns1, $dns2) = split("\.", $name,4);

	if (!empty($sub1) && !empty($sub2) && !empty($dns1) && !empty($dns2) && !is_numeric($dns2)) {
		$subDHostName = "$sub1-$sub2.$dns1.$dns2";		
	} else {
		$subDHostName = $name;	
	}

	$subDUrl = $urlComp['scheme']."://".$urlComp['host'].$portStr;
	$appletUrl = "http://".$urlComp['host'];
} else {
//portforwarded
	$subDHostName = $_SERVER["SERVER_NAME"];
	$subDPort = $_SERVER["SERVER_PORT"];
	$subDProtocol = "http://";
	if ($_SERVER["HTTPS"] === "on") {
	    $subDProtocol = "https://";  
	}
	$subDUrl = $subDProtocol . $subDHostName . ":" . $subDPort;
	$appletUrl = "http://" . $subDHostName . ":$portNumber";
}




foreach ($shares as $share) {
    $urlArray[] =  "/" . $share["share_name"];
}


?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" >
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <LINK REL=StyleSheet HREF="css/main.css" TYPE="text/css">
    <script type="text/javascript" src="js/jquery.js"> </script>
    <script type="text/javascript" src="js/jquery.blockUI.js"> </script>
	<title>My Book Live<?php echo getDeviceUserDisplayName();?></title>
    <link rel="shortcut icon" href="images/WD2go_Favicon.ico" type="image/x-icon"/>
    
</head>
<body>
	<applet id="WDJavaTester" code="WDTester.class"  align="baseline" width="0" height="0">no java sdk found!!</applet>

	<div id="outerShell" style="position: fixed; width: 100%; height: 100%; overflow-y:auto">
			<?php include('header.inc') ?>
            <div class="topGrad" >
                <img src='images/WD2go_ColorStrip.png'/>


                <div class="contentTables" style="width: 600px">
                    <span class='title'><?php echo gettext("SHARES") ?> (<?php echo count($urlArray); ?>)</span>
                    <div class='titleSeperatorSpacing'>
                        <div class='titleSeperator'>
                        </div>
                    </div>
					<div style="width: 100%; height: 30px">
			
					</div>
					<div id="appletWrap" >
						<script src="js/deployJava.js"></script>
						<script>
						 var attributes = { code:'com.wd.nas4g.mapdrive.MapDrive.class',
						    archive:'<?php echo $appletUrl ?>/Admin/webapp/htdocs/MapDrive.jar?v=18',
						    width:'100%', height:'100%', MAYSCRIPT:'true', alt: "<?php echo gettext("GET_JAVA"); ?>"};
						 var parameters =
							{
								codebase_lookup: 'false',
								mayscript: 'true',
								portalUrl: '<?php echo $portalUrl ?>',
								paths: "<?php echo join(',', $urlArray);?>",
								host: "<?php echo $subDHostName; ?>",
								port: "<?php echo $portNumber; ?>",
								sslPort: "<?php echo $sslPortNumber;?>",
								deviceUserId:"<?php echo $devUserId;?>",
								deviceUserAuthCode:"<?php echo $authCode;?>",
								locale: "<?php echo $locale;?>",
								browser: navigator.userAgent,
								isLan: "<?php echo $isLan; ?>",
								debug:"<?php echo $_GET['debug'];?>"
							} ;
						 deployJava.runApplet(attributes, parameters, '1.6');
						</script>

					</div>

<div style="margin-top:20px; border:2px solid yellow;">
	<a href="../webfolder?mapkey=<?php echo $map_key; ?>">个人云盘（web 版）</a>
</div>

                </div>

                <div class='bottomGlow'>
                    <img src="images/WD2go_Glow.png" align='bottom'/>
                </div>

            </div>
	</div>
</body>
<script type="text/javascript" >
    function blockUI() {

		$('div#outerShell').block({
			message: null,
			forceIframe: true,
			css: {
				backgroundColor: '#fff'
	   		}
		});

    }
    
    function unblockUI() {

		$('div#outerShell').unblock();

    }

    $(document).ready(function() {
        //var appH = $('applet').height();
		var ratio = 1;
		if (window.screen.deviceXDPI) {
			ratio = window.screen.deviceXDPI/96; 
		}

		var resize = true;

		if (/Mac OS X 10.7/i.test(navigator.userAgent)) {
		   resize = typeof $('#WDJavaTester').attr('Version') !== 'undefined';
		}

		if (resize) {
		
	        $('div#appletWrap').height( <?php echo count($urlArray); ?> * 58 / ratio).width(605);
		}

		$('#WDJavaTester').remove();
		$('applet').css('outline', 'none');
		$(window).bind('beforeunload', function() {
			return '<?php echo gettext("LOGOUT_WARNING") ?>';
		});

    });
</script>
</html>

