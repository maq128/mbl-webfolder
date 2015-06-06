设备信息
========

	西部数据（WD）My Book Live 3.5英寸家庭网络硬盘2TB(WDBACG0020HCH)


hack 资料
=========

Hacking WD MyBook World Ed - [MyBook Live](http://mybookworld.wikidot.com/mybook-live)


关于 webdav
===========

#### webdav 访问网址为类似这样：

	http://mybooklive/Public/

缺省只能用 deviceUserId / deviceUserAuthCode 访问 webdav。

#### 这里似乎有一个安全漏洞：

* 这组验证信息是由 wd2go.com 网站掌握的。
* deviceUserId / deviceUserAuthCode 会出现在 url 里。

#### 启用本地帐号的 webdav 访问：

* 编辑文件 /etc/nas/config/apache-php-webdav.conf，将 WEBDAV_ALLOW_SYSTEM_USER 的值修改为 "true"
* 然后通过控制面板修改一次密码即可。


系统定制
========

#### 启用 ssh

	http://mybooklive/UI/ssh

#### 禁用 miocrawlerd, mediacrawlerd

	# 参考资料 http://i.migege.com/disable-mediacrawler-on-my-book-live.html
	# 参考文件 etc/init.d/orion
	/usr/local/orion/miocrawler/miocrawlerd disable
	/usr/local/mediacrawler/mediacrawlerd disable

#### Putty 登录时中文乱码问题

	在 Window → Translation 中，Received data assumed to be in which character set 设置为 UTF-8

	在 shell 中执行如下命令（或者把这个命令写入 .bashrc 文件）：
	export LC_ALL='zh_CN.utf8'

#### 系统相关文件位置

	Apache2 配置文件
	/etc/apache2/apache2.conf

	web server 主目录
	/var/www/

	WebDAV 相关配置
	/etc/nas/apache2/auth/*

	PHP 配置文件
	/etc/php5/apache2/php.ini

常用命令
========

	# 查看 CPU、内存信息
	cat /proc/cpuinfo
	cat /proc/meminfo

	# 查看已安装的软件包的内容
	dpkg -L <foo>

	# 查看所有已安装的软件包
	dpkg -l

	# 查看所有软件包
	/opt/bin/ipkg list

	# 查看已安装软件包
	/opt/bin/ipkg list_installed

	# 安装或更新软件包
	/opt/bin/ipkg install <foo> <bar>

	# 卸载软件包
	/opt/bin/ipkg remove <foo> <bar>

	# 伪装成低版本的 firmware
	echo "02.01.06" > /etc/version


建立开发环境
============

	# 安装 Optware，并更新软件库（这个似乎后面没有用到……）
	wget http://mybookworld.wikidot.com/local--files/optware/setup-mybooklive.sh
	sh setup-mybooklive.sh
	/opt/bin/ipkg update

	# 更新几个证书（否则 aptitude update 会报错）
	gpg --keyserver pgp.mit.edu --recv-keys AED4B06F473041FA
	gpg --armor --export AED4B06F473041FA > key.pub
	apt-key add key.pub

	gpg --keyserver pgp.mit.edu --recv-keys 8B48AD6246925553
	gpg --armor --export 8B48AD6246925553 > key.pub
	apt-key add key.pub

	gpg --keyserver pgp.mit.edu --recv-keys 6FB2A1C265FFB764
	gpg --armor --export 6FB2A1C265FFB764 > key.pub
	apt-key add key.pub

	gpg --keyserver pgp.mit.edu --recv-keys 64481591B98321F9
	gpg --armor --export 64481591B98321F9 > key.pub
	apt-key add key.pub

	gpg --keyserver pgp.mit.edu --recv-keys 7638D0442B90D010
	gpg --armor --export 7638D0442B90D010 > key.pub
	apt-key add key.pub

	# 安装 gcc 的过程中总是需要升级 libc6-ppc64 [2.11.2-2 (now) -> 2.13-38 (stable)]
	# 但总是失败，可以像这样先禁止对这它升级（但似乎还是会导致一些安装失败）
	aptitude hold libc6-ppc64

	# 或者像这样强制升级（但不清楚是否会有什么副作用）
	dpkg -i --force-overwrite /var/cache/apt/archives/libc-bin_2.13-38_powerpc.deb
	dpkg -i --force-overwrite /var/cache/apt/archives/libc6_2.13-38_powerpc.deb

	# 安装 gcc / make
	aptitude update
	aptitude install gcc
	# update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.2 42 --slave /usr/bin/cpp cpp /usr/bin/cpp-4.2  ——此句似无必要
	aptitude install make

	# 安装 c++
	aptitude install g++

	# 安装 aclocal / autoconf / automake
	aptitude install automake


安装花生壳
==========

	# 官方文档 http://service.oray.com/question/116.html
	# 注意：官方文档中写的版本号是 2.0.2.16556，这是旧版，运行时连接验证失败
	cd /usr/local/src
	wget http://download.oray.com/peanuthull/phddns-2.0.5.19225.tar.gz
	tar zxvf phddns-2.0.5.19225.tar.gz

	# 修改代码，关闭 log 输出（避免总写硬盘）
	修改 /usr/local/src/phddns-2.0.5.19225/src/log.h 文件，
	把 #define LOG(level) 后面的内容去掉即可。

	# 编译
	cd /usr/local/src/phddns-2.0.5.19225
	aclocal
	autoconf
	automake
	./configure
	make
	cp /usr/local/src/phddns-2.0.5.19225/src/phddns /usr/bin/

	# 初次运行，设置帐号、密码、日志文件
	/usr/bin/phddns

	# 配置为自启动后台进程
	# 在 /etc/rc.local 文件中增加下面的内容
	/usr/bin/phddns -c /etc/phlinux.conf -d
	# 创建符号连接指向 rc.local（缺省是不执行 rc.local）
	ln -s /etc/rc.local /etc/rc2.d/S99rcLocal

	# ---- 以上方法似乎过于麻烦，更简单的办法是在 crontab 里配置一条命令即可 ----
	# 参考资料
	#	http://open.oray.com/wiki/doku.php?id=%E6%96%87%E6%A1%A3:%E8%8A%B1%E7%94%9F%E5%A3%B3:http%E5%8D%8F%E8%AE%AE%E8%AF%B4%E6%98%8E

	*/10 * * * * root curl http://username:password@ddns.oray.com/ph/update > /dev/null


安装脱机下载工具
================

	# 【智能路由】用路由器低成本打造NAS+迅雷离线下载+同步android文件
	# https://luolei.org/openwrt-router-wifi-android-sync-iclould/

	# 【DSM高阶篇】-安装aria2实现迅雷离线（更新完美版）
	# http://www.chiphell.com/thread-580013-1-1.html

	# linux 高速下载工具 aria2 的用法
	# http://blog.sina.com.cn/s/blog_8cf0057a01017nun.html

	# aria2 - The next generation download utility.
	# http://aria2.sourceforge.net/
	# http://aria2.sourceforge.net/manual/en/html/

	aptitude install libxml2-dev
	aptitude install libgnutls-dev
	aptitude install libgcrypt-dev
	aptitude install nettle-dev
	aptitude install libgmp-dev
	aptitude install libssh2-1-dev
	aptitude install libc-ares-dev
	aptitude install zlib1g-dev
	aptitude install libsqlite3-dev
	aptitude install pkg-config
	aptitude install libgpg-error-dev
	aptitude install libgcrypt-dev
	aptitude install libssl-dev

	cd /usr/local/src
	wget http://jaist.dl.sourceforge.net/project/aria2/stable/aria2-1.19.0/aria2-1.19.0.tar.gz
	tar -xvf aria2-1.19.0.tar.gz
	cd aria2-1.19.0
	./configure --with-ca-bundle=/etc/ssl/certs/ca-certificates.crt
	make
	make install

	# 安装 webui-aria2
	# https://github.com/ziahamza/webui-

	cd /usr/local/src
	wget -O webui-aria2-master.zip https://github.com/ziahamza/webui-aria2/archive/master.zip
	unzip webui-aria2-master.zip -d .
	chown -R www-data webui-aria2-master
	chmod -R 755 webui-aria2-master
	mv webui-aria2-master /var/www/webui-aria2

	aria2c --enable-rpc --rpc-listen-all --continue=true --dir=/DataVolume/shares/maq/bt &
	http://mybooklive/webui-aria2/
