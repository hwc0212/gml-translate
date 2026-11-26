# GML Translate / Core 完整审查与候选验收

2026-11-23。候选 <code>2.11.2-rc.10</code> / Core <code>0.9.22</code>。一次汇总审查两个 PR 的累计差异及关联执行入口，集中修复，统一验收后交付。本报告记录推送前本地结果；精确提交和新 CI 结果见 [Translate PR #3](https://github.com/hwc0212/gml-translate/pull/3)、[Core PR #1](https://github.com/hwc0212/gml-translation-core/pull/1)。

## 结论与范围

已完成下表范围的源码审查与隔离回归，未发现剩余已复现的阻塞问题；本轮集中修复三类问题。不能把有边界的审查当作所有环境永无缺陷的保证。

起点为 Translate <code>e51198a</code>（rc.9）和 Core <code>2ceb8b4</code>（0.9.21），工作树均干净。累计 PR 仍以已发布 2.11.1 / Core 0.9.13 的保留分支为审阅基线；没有以默认分支覆盖历史候选。当前 Core 已提交 <code>f0e86e26fe1c82615d4945a9cb77c41459f1cc9d</code>，产品锁和 48 个 vendor 文件均同步到真实提交。

| 审查范围 | 实际检查 |
| --- | --- |
| 生命周期 | 安装、受权限控制的旧库升级、前台不迁移、历史手工译文/重复队列保护、停用、默认保留与显式完整卸载 |
| HTML / 表单 | 提取与回填、textarea 原文、hidden/用户值、named submit、属性编码、注释、script/style/code/SVG、排除规则、原生 CF7 |
| URL | 源站根目录、子目录、编码路径、跨域/服务/媒体/下载/签名 URL、初始与动态 DOM、语言前缀、查询和 fragment 保留 |
| 后台任务 | 保存失效、增量提示、持久状态、锁/租约、版本围栏、失败与退避、backfill checkpoint、任务中断后恢复 |
| 并发 | 原文保存、TM 删除/人工修改/停用/缺失写入、重试交接、真实双进程与两种事务隔离级别、存在/缺失 readiness 行 |
| SEO / 缓存 | 当前版本资格、关键字段/长度/hold/review 门禁、匿名 robots/header、canonical/hreflang、既有 sitemap 与缓存代际回归 |
| 管理入口 | 权限/nonce/转义、设置注册、人工翻译保护、页面表快照与时间语义、语言和外部站点配置 |
| 交付 | 版本、Core 提交、锁哈希、包内源文件、CRC、开发文件排除、最终 ZIP 独立加载 |

## 集中修复的问题

### R1：排除节点不代表重建时的同文本受保护（P2）

parser 的 DOM walk 能跳过 notranslate / translate=no / 已配置简单 class、id 选择器，但重建的可见文本替换仍可能改写其中的同名文本；title 的单独回填也未完整沿用排除状态。初始 9 个探针有 8 个失败。真实输出 buffer 用现有手工 TM 验证了不排除外侧相同文本的正常翻译。

修复在现有 tokenizer 的祖先栈上保留文本/标签排除上下文，隐藏被排除的文本片段，title 遵循同一状态；translate=no 按 HTML 规则忽略值大小写。排除块原字节保留，外部相同文本继续翻译。不改 textarea 正文、named submit 的业务值，不清理或重写 TM。

### R2：两个 HTML 扫描入口的引号与 SVG 边界不一致（P2）

parser 已采用 HTML 引号规则，output buffer 的 find_tag_end 仍把反斜杠当成引号转义，导致有效 HTML 中紧随其后的导航可能未加语言前缀。buffer 还用第一个 SVG 闭标签截断整个 SVG，嵌套 SVG 或引号中的闭标签文本可让技术锚点进入导航重写。

新增 16 个边界用例在原 buffer 全部复现差异；原有 376 个 URL 用例无失败。修复 quote 终止规则，并让 buffer 复用 parser 已有的平衡 SVG 边界函数。没有新增另一套扫描器、URL 队列或重写 src/srcset。

### R3：页面表读取中 TM 已更新并重算，仍可能展示旧“SEO ready”（P2）

确定性交错：读取旧公共资格后，通过原生 TM 删除测试标题并重算，再读取后台状态行。真实 gate 已返回 critical_missing / false，旧 UI 仍显示 ready，因为它仅核对原文/全局版本并接受 complete 或 incomplete 两种状态，没有比对译文指纹。

新增读取不增加查询，只在已有 bounded SELECT 返回 translation_fingerprint；必须与列表的既有指纹一致、machine status 与刚读到的状态一致，才展示当前结果。遇到读取不一致保守显示待评估。没有改变前台资格算法，不宣称已消除所有只读界面与后续编辑间的时间窗口。

## 统一验收

NAS 内部网络、无发布端口、专用合成数据库；WordPress 7.1.4 / PHP 8.3.33 / MariaDB 10.6。无生产数据、真实邮件、询盘或付费 AI。

| 验收 | 结果 |
| --- | --- |
| 完整既有数据库套件 | 122 个场景、2331 项断言，终止标记通过，stderr 空；包含真实激活/停用的源码生命周期测试 |
| 状态、界面交错、真实 TM/output buffer | 14 个场景、106 项断言；完整保留 TM 字段、队列、未涉及资源和其他语言状态 |
| 策略 / 集成 / 语法 | 18 条 policy PASS、30 个集成脚本、118 个 PHP 文件语法检查 |
| PHP 导航 / parser 专项 | 392 个 URL/markup 检查，11 个排除/原字节回填检查 |
| Chromium 导航与图库 | 1508 项检查，无失败或 pageerror；根目录和子目录的初始/动态图库链接另通过 |
| Chromium 当前状态 | stale、ready、真实缺译、失败四状态 × 五语言，20 个 HTTP 检查；后台 en/zh_CN、1440/390px，无页面异常或横向溢出 |
| 原生 CF7 | 既有只读 6.2.5 测试副本，19 项断言；四语只改两处 UI 属性，队列不增、HTTP/邮件为 0，不提交表单 |
| 最终 ZIP | 从解压后的准确安装包独立加载，版本、策略字段、TM/队列保护、无外联通过；未将该 ZIP 原生安装/激活到站点 |

第一次 full runner 的数据库参数使用了 CI 默认值，尚未执行产品场景即连接失败；校正测试 adapter 后完整通过。一次状态/浏览器编排启动重叠，重复假页面 slug 也使 canonical 断言失败；这两组首次结果不计入验收，已分别使用干净测试库串行重跑。没有据此修改产品路由或弱化 canonical 断言。

状态浏览器使用 GET-only 包装器调用真实 Gate、SEO adapter 和输出保护，不冒充完整生产主题或真实 CF7 提交。测试禁用自动 WP-Cron，使用受控派发、延迟与故障注入；不能证明生产 Cron 的实际执行或耗时。

证据：[验收 JSON](review-results.json) · [待评估](evidence/stale-zh_CN-desktop.png) · [缺译/长度测量](evidence/missing-zh_CN-desktop.png) · [恢复就绪](evidence/ready-zh_CN-desktop.png)。完整测试源码均在仓库 tests 下；CI 使用消费端锁定的 Core 提交，结果在两个 PR 说明中单独记录。

## 安全、性能与兼容性

没有新增公开端点或绕过权限/nonce；管理员诊断继续转义且不包含原文、译文、锁令牌或远端错误正文。没有 provider 凭据、客户数据或生产配置进入候选和报告。

parser 复用已有扫描/祖先状态，SVG 边界在两个入口共享；额外 token 仅用于被排除文本，不新增 DOM 序列化或网络请求。UI 没有新增查询，只增加已有 bounded 查询的一列和比较。没有新增前台状态查询，也未改变调度/租约/退避算法、98% 默认、源版本校验或数据库结构。

只同步 GML Translate 的 Core，不修改其他消费端。PHP 7.4/8.3 的新提交 CI 结果另见 PR；本地实际运行 PHP 8.3.33。没有声称已完成独立 MySQL、真实共享主机负载、所有主题/CDN 适配或生产压力验收。

## 安装包、风险和回退

[安装包](gml-translate-2.11.2-rc.10.zip) · [校验值](gml-translate-2.11.2-rc.10.zip.sha256)。433777 bytes、103 项，48 个 Core 文件与源提交和锁一致，CRC/逐项内容核对通过。SHA-256：<code>758b45cebf033962fc67338107ff1ed000509e31e6a39169bc8550f84077cbc9</code>。

主要可见影响：排除设置更完整地保留文本；SVG 技术链接不再被误改；后台遇到译文快照变化时显示待评估。既有手工译文和队列不迁移。可在后续获授权的隔离安装验收中对照这些行为、原图库链接和匿名 SEO 输出。

若不采用可直接保留上一候选。后续测试部署若需回退，应恢复此前完整插件包和对应 Core 锁，保留数据库，不混装 vendor，也不整库回滚。无本轮生产回退任务。

原先报告中的现场页面已经恢复，本轮没有重新处理它，也没有确认其延迟由何任务触发；仍缺现场逐次执行日志和当时版本/映射证据。本次完成的是隔离审查、候选修复与开发分支交付，**未合并、未发布、未部署生产**。
