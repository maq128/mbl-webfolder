<?php
require_once('../htdocs/secureCommon.inc');

$mapkey = $_SESSION['webfolder_mapkey'];

$devUserId = $_SESSION[$mapkey]["deviceUserId"];
$authCode =  $_SESSION[$mapkey]["deviceUserAuthCode"];

$shrObj = new ShareAccess();
$devUserDao = new DeviceUsersDB();
$userDao = new UsersDB();

if ($devUserDao->isValid($devUserId, $authCode)) {
   $deviceUser = $devUserDao->getDeviceUser($devUserId); 
   $user = $userDao->getUser($deviceUser["user_id"]);
} else {
	echo json_encode( array(
		'success'	=> false,
		'error'		=> '没有访问权限',
	));
	exit();
}

$GLOBALS['wfs_root'] = realpath( '/DataVolume/shares' );
$GLOBALS['wfs_shares'] = array();
$GLOBALS['wfs_platform_encoding'] = 'UTF-8';

$shares = $shrObj->getSharesForUser($deviceUser["user_id"]);
foreach ( $shares as $share ) {
	$GLOBALS['wfs_shares'][] = $share['share_name'];
}
