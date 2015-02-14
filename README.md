# 目前支持的有 CHD/CMCT/TTG/OPENCD, 其它 PT 我没有账号无法确定适配, 因为 XML 格式可能不一样.
## 使用方法:
* 修改源码中的 rss, server, port, rpcPath, user, password
* 测试没问题后, 放到定时任务运行 `*/10 * * * * php rss.php`

## todo:
* 记录日志
* 完善 Transmission class, 可单独抽出成 Transmission Webui 使用.
