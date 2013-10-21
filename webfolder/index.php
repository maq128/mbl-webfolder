<?php
$os = explode( ' ', strtolower(php_uname('s')) );
if ( $os[0] != 'windows' ) {
	// 真实环境初始化
	session_start();
	$mapkey = $_GET['mapkey'];
	if ( empty($mapkey) || empty($_SESSION[$mapkey]) ) {
	    header('Location: /Admin/webapp/htdocs/accessDenied.php');
	    exit();
	}
	$_SESSION['webfolder_mapkey'] = $mapkey;
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
</body>
</html>
