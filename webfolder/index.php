<?php
$os = explode( ' ', strtolower(php_uname('s')) );
if ( $os[0] == 'windows' ) {
	// 调试环境初始化
	session_start();
	$_SESSION['wfs_user_id'] = 1;
} else {
	// 真实环境初始化
	session_start();

	// 记录下 HTTP 和 HTTPS 的实际访问端口（由于存在 NAT，所以可能与 Server 端的配置不同）
	if ( $_SERVER['HTTPS'] ) {
		$_SESSION['https_port'] = $_SERVER['SERVER_PORT'];
	} else {
		$_SESSION['http_port'] = $_SERVER['SERVER_PORT'];
	}

	// 如果没有认证身份……
	if ( empty($_SESSION['wfs_user_id']) ) {
		// 以下部分代码来自 /var/www/Admin/webapp/htdocs/secureCommon.inc
		ini_set(
			'include_path',
			implode( ':', array(
				'.',
				$_SERVER["__ADMIN_API_ROOT"] . '/webapp/includes/',
				$_SERVER["__ADMIN_API_ROOT"] . '/webapp/classes/api/',
				ini_get('include_path'),
			))
		);

		if ( isset($_REQUEST['login_user']) ) {
			$username = $_REQUEST['login_user'];
			$password = $_REQUEST['login_pass'];
			require_once("security.inc");
			$_SESSION['wfs_user_id'] = authenticateLocalUser( $username, $password );
			header( 'Location: ' . getThisUrl( false ) );
			exit();
		}
	}

	if ( isset($_REQUEST['logout']) ) {
		session_destroy();
		header( 'Location: ' . getThisUrl( true ) );
		exit();
	}
}

// 当浏览器通过 mybooklive-deviceXXXXXX.wd2go.com 访问时，由于 wd2go.com 的中转
// 作用，PHP 程序实际收到的 SERVER_NAME/SERVER_ADDR 不一定跟浏览器地址栏中一致。
// 本函数确保取到跟浏览器地址栏中一致的 url。
function getThisUrl( $secure )
{
	$crack = parse_url( $_SERVER['REQUEST_URI'] );
	$host = $crack['host'] ? $crack['host'] : $_SERVER['SERVER_NAME'];
	$path = $crack['path'];
	if ( $secure ) {
		$port = $_SESSION['https_port'] ? $_SESSION['https_port'] : 443;
		return "https://{$host}:{$port}{$path}";
	}
	$port = $_SESSION['http_port'] ? $_SESSION['http_port'] : 80;
	return "http://{$host}:{$port}{$path}";
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
<div id="logout"><a href="?logout">退出</a></div>
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
?>
<fieldset>
	<legend>请输入登录信息：</legend>
	<form method="post" action="<?php echo getThisUrl( true ); ?>">
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
