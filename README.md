# 中华人民共和国行政区划分

最全全国省、市、区(县)、街道、社区（村）代码划分
* 数据来源：[国家统计局](http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2019/index.html)
* 数据更新时间：2019年10月31日
* 数据下载：
    * json格式数据：[data.josn](./data.json)
    * 压缩文件(json、csv)：[data.zip](./data.zip)

# 使用方法
在命令行中执行下面命令会将行政区域数据自动保存在文件 `data.json`、`data.csv`中.
```php
php index.php
``` 
**注**：由于网络不稳定或 [国家统计局](http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2019/index.html) 网站响应比较慢等原因，可能会存在部分请求失败导致数据不完整，每次获取到数据后缓存到 `file` 目录中。如果有请求失败，会会将失败原因记录在 `log` 文件中，当日志文件 `log`中有数据时，说明本次执行有请求失败的情况，为保证数据的完整性，需要再次执行上面命令直到 `log` 为文件不存在或文件内容为空为止。
