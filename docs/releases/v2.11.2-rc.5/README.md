# GML Translate 2.11.2-rc.5 审阅附件

2026-11-19 隔离验收及 PHP 7.4 CI 兼容修复。插件内置 Core 0.9.17。

[当前安装包](gml-translate-2.11.2-rc.5.zip) · [SHA-256](gml-translate-2.11.2-rc.5.zip.sha256) · [验收结果](acceptance-results.json) · [当前构建](review-build.json)

安装包 SHA-256 为 1f4bf6dd75c279616b12c8a6fd6b1e6315dc5f5f89ee82dfadbe3b457c96804d，共 421459 字节。ZIP CRC、内部路径及源文件字节校验通过。

## 修复范围

相对于已发布 2.11.1，此次累计包含 rc.1–rc.5 的表单 UI、inventory/readiness 恢复、并发保护和链接重写修复。PHP 与浏览器的导航策略覆盖系统服务路径、媒体和静态文件、显式下载、自定义内容目录、完整 origin 与子目录边界，并保留查询串、片段及已有语言前缀。动态节点和后续 href/action 更新采用同一策略。

GitHub 的 PHP 7.4 检查发现空查询串和空片段分隔符会丢失。Core 现按原始后缀保留这些字符，并新增绝对地址及协议相对地址的回归案例。安装包已同步重新构建。

[对应 Core PR](https://github.com/hwc0212/gml-translation-core/pull/1)

## 验收结果

| 检查 | 结果 |
| --- | --- |
| PHP 导航断言，47 类地址、根目录和子目录、锚点及表单 | 188/188 通过 |
| Chromium 初始 DOM、动态节点及属性更新 | 754/754 通过 |
| 集成测试及 PHP 语法 | 29 个脚本、111 个文件通过 |
| 三语 REST、WordPress AJAX、WooCommerce AJAX、feed 及下载 | 18 次实际请求通过，直接返回预期内容 |
| 商品图库 | 75 个译文页、150 个原图 href 通过 |
| 两款样例原图直开 | 三语共 12 次、英文对照另 4 次通过，返回 200 image/jpeg，无重定向 |
| 图片和灯箱 | 16 张图片成功解码，8 个灯箱打开成功 |
| 实际导航点击 | 商品、分类和语言切换共 9 次通过 |
| 数据检查 | 商品、媒体、译文库、语言设置与 AI 开关指纹一致 |

实际站点使用独立数据库的 WordPress 7.1.4、WooCommerce 10.5.3 与匿名 Chromium 156.0.8078.79，自动翻译关闭。AJAX 表单提交进入隔离测试回调。子目录和自定义路径由 PHP/浏览器夹具覆盖。PDF 是下载请求夹具。23 款商品为合成样本，原生产商品完整清单仍待逐项验收。

最初 45 类地址的对照测试中，rc.4 有 72/180 项 PHP 失败及 388/722 项浏览器失败。当前测试扩展至 47 类地址。初始构建与验收记录分别保存在 [原始构建](validated-build.json) 和 [初始验收](initial-acceptance.json)，本次当前构建以 review-build.json 和随包 SHA-256 为准。GitHub 的 PHP 7.4、8.3 及数据库回归状态见 PR 检查页。

## 审阅基线和追溯

两个 PR 均以承载当前已发布版本的 codex/upgrade-lock-hotfix 为基线，插件对应 2.11.1，Core 对应 0.9.13。

GitHub 的私人邮箱保护拒绝了首次推送。审阅分支改用 noreply 身份重建未发布提交，[提交映射](github-review-commits.json) 保留最初元数据调整阶段的对照。其后追加 PHP 7.4 兼容修复。当前 Core 提交为 c1d1344476e44cee7d73cb293facf6260a1ca819，插件锁文件与该提交的源码哈希一致。

PHP 回归入口为 tests/integration/test-navigation-links.php，浏览器入口为 tests/browser/navigation-links.py，整体插件检查入口为 tests/run-all.sh。CI 按锁文件检出 Core 并执行标准来源校验。
