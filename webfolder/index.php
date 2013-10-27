<?php
$os = explode( ' ', strtolower(php_uname('s')) );
if ( $os[0] == 'windows' ) {
	// 调试环境初始化
	session_start();
	$_SESSION['wfs_user_id'] = 1;
} else {
	// 真实环境初始化
	require_once('../htdocs/secureCommon.inc');

	// 如果没有认证身份……
	if ( empty($_SESSION['wfs_user_id']) ) {
		$host = $_SERVER['SERVER_NAME'];
		$uri = $_SERVER['REQUEST_URI'];

		if ( isset($_REQUEST['login_user']) ) {
			$username = $_REQUEST['login_user'];
			$password = $_REQUEST['login_pass'];
			$_SESSION['wfs_user_id'] = authenticateLocalUser( $username, $password );
			$url = str_replace( 'https://', 'http://', $_SERVER['SCRIPT_URI'] );
			header( 'Location: ' . $url );
			exit();
		}
	}

	if ( isset($_REQUEST['logout']) ) {
		session_destroy();
		$url = str_replace( 'https://', 'http://', $_SERVER['SCRIPT_URI'] );
		header( 'Location: ' . $url );
		exit();
	}
}
?><!DOCTYPE html>
<html>
<head>
<title>个人云盘</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="res/fineuploader.css" />
<link rel="stylesheet" href="res/webfolder.css" />
<!-- script language="JavaScript" src="res/modernizr.js" --></script><!-- http://modernizr.com/ -->
</head>
<body>
<?php
if ( $_SESSION['wfs_user_id'] ) {
?>
<div id="toolbar"></div>

<table id="list">
<thead>
<tr>
	<th>名称</th>
	<th width="100">大小</th>
	<th width="150">修改时间</th>
</tr>
</thead>
<tbody id="list-files"></tbody>
</table>

<div class="page-mask"><img class="waiting" src="res/processing.gif" /></div>
<div class="upload-zone"></div>

<script language="JavaScript" src="res/jquery-1.10.2.min.js"></script><!-- http://api.jquery.com/ -->
<script language="JavaScript" src="res/fineuploader.js"></script><!-- http://docs.fineuploader.com/ -->
<script language="JavaScript" src="res/webfolder.js"></script>
<?php
} else {
	$authUrl = str_replace( 'http://', 'https://', $_SERVER['SCRIPT_URI'] );
?>
<fieldset>
	<legend>请输入登录信息：</legend>
	<form method="post" action="<?php echo $authUrl; ?>">
		帐号：<input type="text" name="login_user" />
		<br>
		密码：<input type="password" name="login_pass" />
		<br>
		　　　<input type="submit" />
	</form>
</fieldset>
<?php
}
?>
</body>
</html>
