# GML Translate 2.11.2-rc.5 审阅附件

2026-11-19 隔离验收完成。插件内置 Core 0.9.17。

[安装包](gml-translate-2.11.2-rc.5.zip) · [SHA-256](gml-translate-2.11.2-rc.5.zip.sha256) · [验收结果](acceptance-results.json) · [原验收构建](validated-build.json) · [审阅提交映射](github-review-commits.json)

安装包 SHA-256 为 8795f250e7ae5f907b7513e451b1b2a5b00868e0a78c24a69309a7d82818b352，共 421410 字节。ZIP CRC、内部路径、安装文件及源文件字节检查通过。

## 修复范围

相对于已发布 2.11.1，本次累计包含 rc.1–rc.5 的表单 UI、inventory/readiness 恢复、并发保护和链接重写修复。当前浏览器与 PHP 的导航策略覆盖系统服务路径、媒体与静态文件、显式下载、自定义内容目录、完整 origin 和子目录边界，并保留查询串、片段及已有语言前缀。动态锚点、动态表单与后续 href/action 更新采用同一策略。

[对应 Core PR](https://github.com/hwc0212/gml-translation-core/pull/1)

## 实际验证

| 检查 | 结果 |
| --- | --- |
| PHP 导航断言，45 类地址、两种安装路径、锚点及表单 | 180/180 通过，旧版同组 72 项失败 |
| Chromium 初始 DOM、动态节点及属性更新 | 722/722 通过，旧版同组 388 项失败 |
| 集成测试 | 29 个脚本通过 |
| PHP 语法 | 111 个文件通过 |
| REST、WordPress AJAX、WooCommerce AJAX、feed 和下载 | 西、德、俄三语共 18 次请求通过，直接返回预期内容 |
| 商品图库 | 75 个译文页、150 个原图 href 通过 |
| 原图直接打开 | 两款样例的三语主图及附图共 12 次、英文对照另 4 次通过，返回 200 image/jpeg，无重定向 |
| 图片和灯箱 | 16 张图片成功解码，8 个灯箱打开成功 |
| 实际导航点击 | 商品、分类、语言切换共 9 次通过 |
| 数据检查 | 商品、媒体、译文库、语言设置及 AI 开关的前后指纹一致 |
| Core 来源 | 已提交 Core 源码、vendor 逐文件哈希和 lock 一致，标准 verify 命令通过 |

实际站点使用 WordPress 7.1.4、WooCommerce 10.5.3、Chromium 156.0.8078.79、独立数据库和匿名浏览器。自动翻译关闭。AJAX 表单提交仅进入隔离测试回调。

导航回归覆盖根目录及子目录、标准和自定义 REST 前缀、WordPress/WooCommerce 服务查询、自定义上传目录、签名编码、HTML 属性实体、URL 片段、不同端口、相似主机名、协议相对 URL、非 HTTP 协议及显式下载。

商品样本中两款采用问题报告中的路径，另 23 款为合成商品。生产站完整的原商品清单和全部原图仍待逐项验收。自定义路径及子目录由 PHP 和 Chromium 夹具覆盖。PDF 是下载请求夹具。这里公开汇总验收结果，原始隔离站记录保存在本地。

## 审阅基线与构建追溯

PR 以承载已发布 2.11.1 的 codex/upgrade-lock-hotfix 为基线，对应 Core PR 以已发布 0.9.13 的同名分支为基线。

GitHub 的私人邮箱保护拒绝了首次推送。审阅分支使用 GitHub noreply 邮箱重建未发布的提交，Core 各源码树与原验收提交一致。插件 Core 锁文件指向新的等价 Core 提交。安装包内容仍与原验收包逐字节一致。

原插件代码验收提交为 1b480297db29cfd9fb09c5ed771a4e256270f149。原 Core 验收提交为 517f5deb416a3e9ef363865e95608829c99dab7f。公开 Core 审阅提交为 b383f1c400870edcd46f5e420d18a4be6db2ac46。完整映射见随附 JSON。

## 复验入口

PHP 回归使用 tests/integration/test-navigation-links.php，浏览器回归使用 tests/browser/navigation-links.py 与 gallery-links.py。完整插件检查入口为 tests/run-all.sh。GitHub 工作流按 translation-core.lock.json 检出对应 Core，并执行标准来源校验。
