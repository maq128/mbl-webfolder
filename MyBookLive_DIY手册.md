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


启用 ssh
========

	http://mybooklive/UI/ssh


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


准备工作
========

	# 更新软件包列表
	apt-get update

	# 安装 Optware，并更新软件库
	wget http://mybookworld.wikidot.com/local--files/optware/setup-mybooklive.sh
	sh setup-mybooklive.sh
	/opt/bin/ipkg update


建立开发环境
============

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

	# 安装 gcc 的过程中总是需要升级 libc6-ppc64 [2.11.2-2 (now) -> 2.13-38 (stable)]
	# 但总是失败，所以这里先禁止对这它升级
	aptitude hold libc6-ppc64

	dpkg -i --force-overwrite /var/cache/apt/archives/libc-bin_2.13-38_powerpc.deb
	dpkg -i --force-overwrite /var/cache/apt/archives/libc6_2.13-38_powerpc.deb

	# 安装 gcc / make
	aptitude update
	aptitude install gcc-4.2
	update-alternatives --install /usr/bin/gcc gcc /usr/bin/gcc-4.2 42 --slave /usr/bin/cpp cpp /usr/bin/cpp-4.2
	aptitude install make

	# 安装 c++
	aptitude install g++

	# 安装 aclocal / autoconf / automake
	aptitude install automake


尚未成功的尝试
==============

#### 安装花生壳（似乎建立连接时验证信息算法有误）

	# http://service.oray.com/question/116.html
	cd /usr/local/src
	wget http://download.oray.com/peanuthull/phddns-2.0.2.16556.tar.gz
	tar zxvf phddns-2.0.2.16556.tar.gz
	cd phddns-2.0.2.16556
	aclocal
	autoconf
	automake
	./configure
	make
	cd src
	./phddns
	# 输入帐号、密码等
