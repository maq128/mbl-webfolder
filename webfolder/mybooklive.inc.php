<?php
require_once('../htdocs/secureCommon.inc');

$GLOBALS['wfs_root'] = realpath( '/DataVolume/shares' );
$GLOBALS['wfs_shares'] = array();
$GLOBALS['wfs_platform_encoding'] = 'UTF-8';

if ( $_SESSION['wfs_user_id'] ) {
	$shrObj = new ShareAccess();
	$shares = $shrObj->getSharesForUser( $_SESSION['wfs_user_id'] );
	foreach ( $shares as $share ) {
		$GLOBALS['wfs_shares'][] = $share['share_name'];
	}
}
