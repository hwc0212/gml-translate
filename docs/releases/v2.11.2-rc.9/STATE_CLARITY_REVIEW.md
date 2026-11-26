# GML Translate rc.9 状态说明改进与候选验收

2026-11-23。版本 <code>2.11.2-rc.9</code> / Core <code>0.9.21</code>。本报告记录提交推送前的本地验收；精确开发分支 SHA 与 GitHub CI 结果以 [Translate PR #3](https://github.com/hwc0212/gml-translate/pull/3) 和 [Core PR #1](https://github.com/hwc0212/gml-translation-core/pull/1) 的最新说明、Checks 为准。

## 结论与证据边界

**现场已恢复**来自用户提供的观察。本轮未访问生产页面、SSH、数据库，没有取得现场逐次任务日志或原始 Windows 证据，也未独立核验生产安装文件和资源映射。

**隔离复现通过**：已有译文 163/163、100%、未解决 0、AI 队列 idle，可以与当前版本 stale、长度未测、目标语言 noindex 同时存在。受控派发原 worker 后恢复，已有译文没有重写。结论为处理阶段不同且状态说明不足；没有证明永久卡死、死锁、漏调度或翻译失败。现场恢复延迟由什么任务、重试或访问触发，以及是否超出设计预期，仍然未知。

**本地候选通过**：本版改善管理员显示，公开已有长度测量输入，不改变执行链和索引门禁。**生产未部署**；源码推送与候选打包不等于生产验收。

## 版本与关键调用链

上轮基线：Translate <code>d4133f576790bd1413b1e419cc0fb4be6b9069ff</code>（rc.8），Core <code>f4b582bd031d697e1be39741c49478d6effbc891</code>（0.9.20）。本次 Core 已提交为 <code>2ceb8b400423284b715197ce3a8e6ae964527b49</code>，锁文件、vendor 和 ZIP 均绑定该提交，不再使用未提交源文件配旧 commit 的本地锁。

| 状态 | 数据与限制 |
| --- | --- |
| 译文条目完整度 | 清单/readiness 的保存统计；旧版本 100% 不证明保存后版本已重算。 |
| AI 翻译队列 idle | Core <code>class-translation-controls.php::queue_status()</code> 的片段队列、暂停/退避及翻译 worker 状态；不表示清单和就绪任务全部完成。 |
| 当前版本 SEO 就绪 | <code>class-resource-readiness.php</code>、<code>class-page-readiness-policy.php</code>、<code>class-public-eligibility.php</code> 合并版本、关键字段、长度、暂挂、审核和源页面资格；不是搜索引擎收录保证。 |

保存 → <code>class-resource-manifest-manager.php::post_changed()</code> → manifest/readiness 失效、现有待处理提示 → 登记清单事件 → <code>process_dirty()</code> 领取工作和租约 → 原文渲染/parser → <code>class-resource-manifest-store.php::save_complete()</code> 绑定版本 → 各语言 readiness → 公共资格 → 产品 <code>class-publication-gate.php</code>、<code>class-seo-hreflang.php</code> 和输出保护。

保存会按既有规则失效；parser 忽略 script，所以删脚本可以不改变译文哈希集合，但仍需重新绑定当前版本。本轮不改变指纹算法、调度、锁、重试或门槛。

## 隔离过程与时间

NAS 专用假数据环境：WordPress 7.1.4 / PHP 8.3.33 / MariaDB 10.6，内部网络、无发布端口、空 active_plugins；由测试 bootstrap 加载源码。CF7 为短代码替身，不发真实询盘；外联和邮件阻断。没有生产数据库、真实 API 或站点数据。

以下时间来自本次 rc.9 runner，相对发起测试保存，**不是生产耗时或自动 cron 性能指标**：

| 阶段 | 相对保存时间 | 状态 |
| --- | --- | --- |
| 保存脚本删除 | +0.177s | stale；163/163；AI idle；长度待评估 |
| 故意暂停派发器，事件已到期 | +33.032s | 仍 stale，索引门禁保持 |
| 派发原 worker，渲染中 | +33.923s | 仍 stale |
| 原 worker 完成 | +34.412s | 四语恢复就绪、长度 100% |

基线还观察到工作领取后 stale + resource_pending=false + 全站锁占用。因此不在待处理列表不能证明完成，全站锁也不能证明某一页面正在执行。

真实文本变化测试中，162/163、已知字节可见比例 100%，但 unknown_source_count=1；原有门禁仍拒绝就绪。新 UI 显示“测量不完整”，不再把已知字节比例当作完整测量。缺关键标题也继续阻止发布。

## 最小改动

- 页面表把“译文条目完整度 / 保存的清单版本”和“当前版本 SEO 就绪”分开。旧统计不再冒充当前缺失数或当前长度结论。
- 设置页与页面行明确标注 AI 翻译队列。idle 旁说明不代表清单/就绪工作完成。
- 折叠详情复用已有 diagnostics，显示 pending 列表、全站 inventory 锁、清单/就绪状态、登记事件/重试及最近失败。标注失败可能是历史记录、时间不是 ETA、锁不是页面活跃证明。
- complete/incomplete 的 calculated_at 标为保存评估时间；stale/rebuilding 标为状态记录时间。读取中发生保存时保守显示待评估。
- Core 策略返回值仅新增两行：将已算出的 unknown_count 公开为 nullable unknown_source_count；决策逻辑不变。版本、README 和单测另行更新。
- 少量 CSS、中文映射及现有十种语言目录更新。其他语言新文案保留英文回退。
- 没有新队列、表、调度器、诊断端点或数据库迁移；不改 robots/hreflang 的输出代码。

管理员新增读取限制在当前 25 个资源/语言行，并按资源复用 diagnostics；没有新增前台查询。假夹具整块 UI 23—26 次查询、42—77ms，不能当作新增查询数或共享主机负载结论。

## 验收结果

| 范围 | 结果与证据范围 |
| --- | --- |
| rc.9 正式新增 runner | 12 场景、86 断言通过，检查 bootstrap 与终止标记；涵盖脚本/真实文本/关键字段变化、任务延迟、失败重试、丢事件恢复、UI 读取间保存、TM 失效/领取、多语言隔离 |
| 当前 Core 策略、插件集成、语法 | 18 条策略 PASS、29 个集成脚本、116 项 PHP 语法检查通过，stderr 为空 |
| 最终安装 ZIP 独立加载 | 版本 rc.9、Core 未知长度输入及门禁、待评估文案通过；从解压 ZIP 加载，不安装/激活插件；TM/队列不变、外联尝试 0 |
| 同一运行时代码的前序本地审查 | 既有 inventory/readiness/durable/public eligibility 共 313 断言通过；本次只补版本、锁和测试入口，没有因此改变运行时行为 |
| 同一运行时代码的 Chromium 检查 | stale/ready/缺译/失败 × 5 语言共 20 个匿名 HTTP 响应检查；后台 en/zh_CN、1440/390px，无 pageerror 或横向溢出 |
| 数据保护 | TM 全字段、翻译队列全行、未涉及资源的 manifest/readiness 不变；单语失效不改其他目标语言 |

浏览器使用 GET-only 包装器调用真实 Gate、SEO adapter 与输出保护，不包含完整主题、原生安装/激活、完整翻译 buffer 或真实 CF7。stale/失败/真实缺译保留目标语言 noindex；就绪后五种语言均为 200、允许索引、6 条 hreflang、正确自指 canonical。

本地设置 DISABLE_WP_CRON=true；runner 故意保持派发器 32 秒，部分故障场景提前派发，重试用例受控修改到期时间。本轮没有验证生产自动 WP-Cron、流量触发或真实站点的恢复耗时。GitHub 新增数据库步骤使用单独一次性 schema，避免前一回归的设置污染；CI 独立验证原有完整链和当前 Core 锁，其结果另见 PR Checks。

证据：[验收 JSON](review-results.json) · [待评估截图](evidence/stale-zh_CN-desktop.png) · [长度测量不完整截图](evidence/missing-zh_CN-desktop.png) · [恢复截图](evidence/ready-zh_CN-desktop.png)。截图为版本号更新前的同一运行时本地候选，均为假数据。测试源码位于 <code>tests/database/state-clarity.php</code>、<code>tests/fixtures/state-clarity.php</code> 和 <code>tests/browser/state-clarity.py</code>。

## 安装包与影响、回退

[候选安装 ZIP](gml-translate-2.11.2-rc.9.zip)：433477 bytes，103 项；48 个 Core 文件与提交、vendor、锁哈希一致；ZIP CRC、每项源文件字节核对通过，排除测试/开发文件。SHA-256：<code>931f1d339d0b006306c453703721c24df98cde21e2364dfb4948c3ffc0e063e1</code>。

候选只影响管理员状态显示与只读结果字段，无 schema/译文/队列数据迁移。尚无生产压力、真实 CF7、邮件或付费 AI 验收。

本轮不处理已恢复的现场页面，不清缓存、不重译、不安装插件。审阅后若不采用，可放弃候选；如后续获准测试部署后需回退，可恢复此前完整插件包，保留原数据库，不单独拼接 Core。不得为了消除短暂 noindex 绕过版本或索引门禁。

生产验收需另行授权。先只读核验实际安装文件与资源映射、已有任务记录，再对照匿名源/目标语言的 HTTP、robots、canonical/hreflang 与后台阶段；不要重新保存已恢复页面或全站重建。仍缺精确现场恢复时刻、逐次任务日志和当时版本，不能宣称已经确定或修复了现场延迟的执行根因。
