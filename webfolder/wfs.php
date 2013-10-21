<?php
header("Cache-Control:no-cache");

if ( strStartsWith( strtolower(php_uname('s')), 'windows' ) ) {
	// 调试环境初始化
	$GLOBALS['wfs_root'] = 'D:/TEMP';
	$GLOBALS['wfs_shares'] = array( '测试1', '测试2' );
	$GLOBALS['wfs_platform_encoding'] = 'GBK';
} else {
	// 真实环境初始化
	require_once('./mybooklive.inc.php');
}

// 接口调度
$methods = array( 'list', 'download', 'upload', 'createDir', 'removeDir', 'removeFile', 'moveFso' );
$method = $_REQUEST['m'];
if ( ! in_array( $method, $methods ) ) {
	$method = 'list';
}
$method = 'wfs' . ucfirst( $method );
$method();

function wfsList()
{
	// 获取 path 参数，映射到真实路径
	$path = $_REQUEST['path'];
	$realDir = convRelativeToReal( $path );

	$files = array();
	$subdirs = array();

	// 如果是“根”，则返回 shares 列表
	if ( empty($realDir) ) {
		foreach ( $GLOBALS['wfs_shares'] as $share ) {
			$subdirs[] = array(
				'name'	=> $share,
			);
		}
		$result = array(
			'success'	=> true,
			'path'		=> '',
			'files'		=> $files,
			'subdirs'	=> $subdirs,
		);
		echo json_encode( $result );
		return;
	}

	// 遍历目录内容
	$fd = opendir( iconvUtf8ToPlatform( $realDir ) );
	if ( $fd ) {
		while ( FALSE !== ( $name = readdir( $fd ) ) ) {
			$name = iconvPlatformToUtf8( $name );
			if ( $name == '.' || $name == '..' ) continue;
			$realpath = iconvUtf8ToPlatform( $realDir . '/' . $name );
			if ( is_dir( $realpath ) ) {
				$subdirs[] = array(
					'name'	=> $name,
				);
			} else if ( is_file( $realpath ) ) {
				$files[] = array(
					'name'	=> $name,
					'size'	=> filesize( $realpath ),
					'mtime'	=> filemtime( $realpath ),
				);
			}
		}
		closedir( $fd );
	}

	$result = array(
		'success'	=> true,
		'path'		=> convRealToRelative( $realDir ),
		'files'		=> $files,
		'subdirs'	=> $subdirs,
	);
	echo json_encode( $result );
}

function wfsDownload()
{
	// 获取 path 参数，映射到真实路径
	$path = $_REQUEST['path'];
	$realpath = convRelativeToReal( $path );
	if ( empty($realpath) ) {
		echo '下载文件失败：' . $path;
		return;
	}

	// 提取出“基本文件名”
	$basename = array_pop( explode( '/', $realpath ) );

	// 读取文件
	$realpath = iconvUtf8ToPlatform( $realpath );
	$fp = fopen( $realpath, 'r' );
	if ( ! $fp ) {
		echo '读取文件失败：' . $path;
		return;
	}

	// 下载
	header( 'Content-type: application/stream' );
	header( 'Content-Disposition: attachment; filename=' . urlencode($basename) );
	$chunk = fread( $fp, 4096 );
	while ( $chunk !== FALSE && strlen($chunk) > 0 ) {
		echo $chunk;
		$chunk = fread( $fp, 4096 );
	}
	fclose( $fp );
}

function wfsUpload()
{
	header( 'Content-type: text/plain' );

	// 初步确认上传正常
	$path = $_REQUEST['path'];
	$realDir = convRelativeToReal( $path );
	$mtime = intval($_REQUEST['mtime'] / 1000);
	if ( empty($realDir)
		|| empty($_FILES['qqfile'])
		|| $_FILES['qqfile']['error']
		|| empty($_FILES['qqfile']['tmp_name'])
		|| empty($_FILES['qqfile']['size'])
		|| empty($mtime) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '上传失败',
		));
		return;
	}

	// 如果上传的文件带有路径
	$extraPath = '';
	if ( $_REQUEST['fullpath'] ) {
		$extraPath = dirname( $_REQUEST['fullpath'] );
		$extraPath = mkdirIfNecessary( $realDir, $extraPath );
	}

	// 计算得到一个合适的目标文件名
	$name = $_FILES['qqfile']['name'];
	$realpath = $realDir . $extraPath . '/' . $name;
	$seq = 0;
	while ( file_exists( iconvUtf8ToPlatform( $realpath ) ) ) {
		$name = '复制(' . (++$seq) . ')' . $_FILES['qqfile']['name'];
		$realpath = $realDir . $extraPath . '/' . $name;
		if ( $seq >= 10 ) {
			echo json_encode( array(
				'success'	=> false,
				'error'		=> '上传失败（文件名重复太多）',
			));
			return;
		}
	}

	// 把上传的临时文件移动到目标位置
	if ( FALSE === move_uploaded_file( $_FILES['qqfile']['tmp_name'], iconvUtf8ToPlatform( $realpath ) ) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '上传失败（无法上传到指定目录）',
		));
		return;
	}

	touch( iconvUtf8ToPlatform( $realpath ), $mtime );
	$result = array(
		'success'	=> true,
		'dir'		=> convRealToRelative( $realDir . $extraPath ),
		'name'		=> $name,
	);
	echo json_encode( $result );
}

function wfsCreateDir()
{
	$realpath = convRelativeToReal( $_REQUEST['path'] );
	if ( empty($realpath) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '没有指定的目录',
		));
		return;
	}

	if ( ! isSafeFilename( $_REQUEST['name'] ) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '错误的目录名',
		));
		return;
	}

	$name = $_REQUEST['name'];

	if ( mkdir( iconvUtf8ToPlatform( $realpath . '/' . $name ) ) ) {
		echo json_encode( array(
			'success'	=> true,
		));
	} else {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '不能创建指定的目录',
		));
	}
}

function wfsRemoveDir()
{
	$realpath = convRelativeToReal( $_REQUEST['path'] );
	if ( empty($realpath) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '没有指定的目录',
		));
		return;
	}

	if ( rrmdir( iconvUtf8ToPlatform( $realpath ) ) ) {
		echo json_encode( array(
			'success'	=> true,
		));
	} else {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '不能完全删除指定的目录',
		));
	}
}

function wfsRemoveFile()
{
	$realpath = convRelativeToReal( $_REQUEST['path'] );
	if ( empty($realpath) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '没有指定的文件',
		));
		return;
	}

	if ( unlink( iconvUtf8ToPlatform( $realpath ) ) ) {
		echo json_encode( array(
			'success'	=> true,
		));
	} else {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '不能删除指定的文件',
		));
	}
}

function wfsMoveFso()
{
	/*
		path	: 原目录/文件全路径，必须存在
		toDir	: 目标目录，必须存在
		toName	: 目标文件名，可以不存在
		force	: true 表示当目标文件存在时，强行覆盖
	 */
	$realpath = convRelativeToReal( $_REQUEST['path'] );
	$toDir = convRelativeToReal( $_REQUEST['toDir'] );
	$toName = $_REQUEST['toName'];
	$bForce = $_REQUEST['force'] == 'true';

	if ( empty($realpath) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '原文件不存在',
		));
		return;
	}

	if ( empty($toDir) || !is_dir( iconvUtf8ToPlatform( $toDir ) ) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '目标目录不存在',
		));
		return;
	}

	if ( ! isSafeFilename( $toName ) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '目标文件名不合法',
		));
		return;
	}

	$realDest = $toDir . '/' . $toName;
	if ( file_exists( iconvUtf8ToPlatform( $realDest ) ) ) {
		if ( !$bForce || !unlink( iconvUtf8ToPlatform( $realDest ) ) ) {
			echo json_encode( array(
				'success'	=> false,
				'error'		=> '目标文件已经存在，不能覆盖',
			));
			return;
		}
	}

	if ( !rename( iconvUtf8ToPlatform( $realpath ), iconvUtf8ToPlatform( $realDest ) ) ) {
		echo json_encode( array(
			'success'	=> false,
			'error'		=> '操作失败',
		));
		return;
	}

	echo json_encode( array(
		'success'	=> true,
	));
}

// 递归删除整个目录
function rrmdir( $dir )
{
	if ( ! is_dir( $dir ) ) return false;
	foreach ( scandir( $dir ) as $item ) {
		if ( $item == '.' || $item == '..' ) continue;
		$fullpath = $dir . '/' . $item;
		if ( is_dir( $fullpath ) ) {
			if ( ! rrmdir( $fullpath ) ) return false;
		} else if ( is_file( $fullpath ) ) {
			if ( ! unlink( $fullpath ) ) return false;
		}
	}
	return rmdir( $dir );
}

function mkdirIfNecessary( $realParent, $subPath )
{
	// 直接创建指定的目录
	$fullpath = $realParent . $subPath;
	mkdir( iconvUtf8ToPlatform( $fullpath ), 0777, true );

	// 验证目录是否存在，以及路径是否安全
	$realpath = iconvPlatformToUtf8( realpath( iconvUtf8ToPlatform( $fullpath ) ) );
	if ( strStartsWith( $realpath, $realParent ) ) {
		return strStripPrefix( $realpath, strlen($realParent) );
	}
	return '';
}

function isSafeFilename( $filename )
{
	//   \ / : * ? " < > |
	preg_match( '[\\\\\\/\\:\\*\\?\\"\\<\\>\\|]', $filename, $match );
	return empty($match);
}

// 把相对路径转换为绝对路径
// 入口参数的相对路径格式要求比较宽松
// 返回值为标准化的绝对路径，若无法得到有效结果，则返回 null
function convRelativeToReal( $path )
{
	if ( ! strStartsWith( $path, '/' ) ) {
		$path = '/' . $path;
	}
	$realpath = realpath( iconvUtf8ToPlatform( $GLOBALS['wfs_root'] . $path ) );
	if ( $realpath === FALSE ) {
		return null;
	}

	$realpath = iconvPlatformToUtf8( $realpath );

	// 如果不在 root 范围内，则禁止访问
	if ( ! strStartsWith( $realpath, $GLOBALS['wfs_root'] . '/' ) ) {
		return null;
	}

	// 只允许访问 shares 指定的目录
	$rela = strStripPrefix( $realpath, strlen( $GLOBALS['wfs_root'] ) + 1 );
	$share = explode( '/', $rela );
	if ( ! in_array( $share[0], $GLOBALS['wfs_shares'] ) ) {
		return null;
	}

	return $realpath;
}

// 把绝对路径转换为相对路径
// 入口参数须为标准的绝对路径格式（不含 .. 字样）
// 返回值为相对路径，不含前导分隔符。若无法得到有效结果，则返回 ''
function convRealToRelative( $realpath )
{
	// 指定的绝对路径必须在 root 范围之内，否则返回 ''
	if ( ! strStartsWith( $realpath, $GLOBALS['wfs_root'] . '/' ) ) {
		return '';
	}

	// 截取出相对路径（不带前导分隔符）
	return strStripPrefix( $realpath, strlen($GLOBALS['wfs_root']) + 1 );
}

// 把字符串编码从“运行平台文件系统接口所使用的编码”转换为“程序处理所使用的标准的 UTF-8 编码”
function iconvPlatformToUtf8( $str )
{
	$str = str_replace( '\\', '/', $str );
	if ( $GLOBALS['wfs_platform_encoding'] == 'UTF-8' ) {
		return $str;
	}
	return iconv( $GLOBALS['wfs_platform_encoding'], 'UTF-8', $str );
}

// 把字符串编码从“程序处理所使用的标准的 UTF-8 编码”转换为“运行平台文件系统接口所使用的编码”
function iconvUtf8ToPlatform( $str )
{
	if ( $GLOBALS['wfs_platform_encoding'] == 'UTF-8' ) {
		return $str;
	}
	return iconv( 'UTF-8', $GLOBALS['wfs_platform_encoding'], $str );
}

function strStartsWith( $str, $prefix )
{
	if ( strlen( $str ) < strlen( $prefix ) ) return false;
	if ( substr( $str, 0, strlen( $prefix ) ) === $prefix ) return true;
	return false;
}

function strStripPrefix( $str, $len )
{
	$str = substr( $str, $len );
	if ( $str === FALSE ) {
		$str = '';
	}
	return $str;
}
