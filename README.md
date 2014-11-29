# 目前仅仅支持 [CHDBits](https://chdbits.org), 使用方法:
* 打开 https://chdbits.org/myrss.php
* 复制订阅地址
* 修改源码中的 rssLink, server, port, rpcPath, user, password
* 放到定时任务运行 `*/10 * * * * php rss.php`


# todo:
* 同时支持多个其他 pt 的 rss
* 增加本地/远程服务器判断
* 记录日志
* 完善 Transmission class, 可单独抽出成 Transmission Webui使用
