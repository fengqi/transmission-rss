使用 php 写的 [Transmission](https://www.transmissionbt.com/) RSS 下载.

目前仅仅支持 [CHDBits](https://chdbits.org), 使用方法:
* 打开 https://chdbits.org/myrss.php
* 复制订阅地址
* 修改源码中的 $rssLink, 服务器地址/端口/rpc地址/用户密码
* 放到定时任务运行 `*/10 * * * * php rss.php`