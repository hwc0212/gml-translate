# GML Translate / Core 合并前综合审查

日期：2026-11-25（Asia/Shanghai）。本轮仅本地及已授权 NAS 隔离环境，无生产连接、AI 调用、合并、部署或推送。

审查结论：**当前远端 rc.12 不宜直接合并**。发现三项 P2 边界缺陷，已集中修为本地 Translate 2.11.2-rc.13 / Core 0.9.24 候选。最终数据库与状态回归结果见下方验收表；只有最终候选的 CI 也通过后，才能针对更新后的 PR 作合并放行。

## 1. 真实版本、PR 与审查范围

| 项目 | 本轮远端审查基线 | 本地候选 |
| --- | --- | --- |
| Translate PR #3 | 2.11.2-rc.12，361c2e3dfeeffe245602ea6eb2bf16baa6c691e9 | 2.11.2-rc.13 |
| Core PR #1 | 0.9.23，83e5d9fda37113cf4249313e467452f6a420506a | 0.9.24，9891513070d3e215e712dc19030b827169cc0b8d |
| PR 目标 | 两者均为 codex/upgrade-lock-hotfix | 未改为 main，未合并 |

开始时两个工作区均干净，已 fetch 并核对远端头。Translate 累计差异 165 文件（包括内嵌 Core、语言包、文档、测试），Core 累计差异 24 文件。审查覆盖累计运行时代码及 rc.12 增量，并核对已有测试、工作流和现场证据。没有以版本号代替生产源码逐字一致性。

关键调用链：保存/SEO 字段变化 → manifest manager 失效及 durable dirty/recovery → discovery/backfill 的快照版本检查 → manifest store → readiness 的 manifest/resource/string 锁与版本校验 → public status → robots/canonical/hreflang；另审查 parser 提取/重建 → output buffer → URL helper → 浏览器动态链接改写、后台 bounded 状态查询、权限及人工译文保护。

本轮没有证明新的 readiness 竞态、重试漏恢复或人工译文被覆盖。保存失效、M→R→S 锁顺序、CAS 检查、陈旧 worker 拒写和已有关联恢复仍保留；没有为了缩短 noindex 而降低门槛、改内容指纹或另加队列。页面37历史上已恢复；本轮没有重新处理它，也没有凭历史延迟推断永久卡死。

## 2. 已复现并修复的三项问题

### P2-A：JSON-LD 上下文识别越界

`Core/src/class-breadcrumb-jsonld.php:74`：原代码扫描 @context 所有值，只要出现 schema.org 字符串就视为 schema 词汇表；isset 忽略显式 null；为 ListItem/item 注册路径时未检查其局部上下文。因此外来 @vocab 加 schema 别名、子级 @context:null 和局部外来上下文中的名称/链接会被错误改写。该能力由 rc.12 引入，已有 84 页现场检查未覆盖这些结构。

改为逐层判断上下文。只支持能在本地确定含义的 schema 字符串、@vocab 及少量无害元数据；别名、字段覆盖、opaque 远程上下文等保守跳过。未知父级字段映射作为歧义继续继承，直到显式 null 清除；子级只改 @vocab 不能错误清除父级映射。@context 自身不作为业务数据遍历。保留原 JSON 字符串跨度替换、大小/深度/条目上限及重复键拒绝，不整体重编码、不联网解析上下文。

注意：`null_nested_item_context` 粗粒度诊断里整个 JSON 仍可能变化，因为父 ListItem.name 仍合法。应核验被 null 重置的 item 子树字节不变，而非要求父对象也不翻译；正式回归已逐层断言这一点。

规范依据：[W3C JSON-LD 1.1](https://www.w3.org/TR/json-ld11/)。这是受限支持，不宣称完整实现 JSON-LD 处理器。

### P2-B：嵌套/未闭合技术块的保护边界不完整

`Core/src/class-html-parser.php:288,335`、`class-output-buffer.php:789`：只匹配第一个 code/pre 结束标签，会让外层技术块剩余内容进入普通替换；若相同文本在块外已有译文，块内示例文字和链接会被改写。嵌套重建问题原先已存在，rc.12 新增的 code/pre URL 保护也没有覆盖嵌套。未闭合 raw-text 块同样可能暴露内部伪链接。

复用并扩展原 SVG 边界助手，为 code/pre/SVG 共用平衡扫描；跳过注释、CDATA、引号内伪结束标签和脚本等 raw-text。未闭合技术块保护到文末。块外相同文字及正常导航继续翻译。无新增通用解析框架。浏览器 MutationObserver 的二次改写也增加相同技术块及 notranslate/大小写 translate=NO 祖先保护；新增80个初始、动态插入、属性改变用例在旧 JS 中全部失败，在候选中全部通过，避免服务端保护被前端改写抵消。

### P2-C：SEO 所有者关闭 canonical 的返回值被覆盖

`Translate/includes/class-seo-hreflang.php:90`：filter_canonical_url(false/null/空串) 会生成当前 URL，抵消 SEO 所有者的关闭意图。SEOPress 的空 markup 已在此前保护，但通用过滤入口尚未保护。这是累积代码中的已有兼容性缺陷，**不是本轮现场证明的 Yoast/Rank Math 故障，也不是 rc.12 才引入**。

最小修复：非字符串和空白 canonical 原样返回；真实 URL 仍走既有当前分页/语言适配。已有 SEOPress 和 noindex 策略保持不变。Yoast 明确支持以 false 关闭 canonical，见[官方 API 合约](https://developer.yoast.com/features/seo-tags/canonical-urls/api/)。本轮测过滤入口及原生 SEOPress，未安装完整 Yoast/Rank Math 做全插件验收。

## 3. CI、现场复测与本地验证分开记录

远端 rc.12 的 PR/push 六个 check 均 success：PHP 7.4、8.3 和 WordPress 7.1/MariaDB 10.11 数据库回归各两次。读取 PR run 45529972502 原始日志，确认当时的 250 项面包屑/比较符、869 项分页、Core 0.9.23、122 场景/2342 断言及状态 14 场景/106 断言确实执行。证据为 REMOTE_CI.json、rc12-pr-ci.log。Core 无独立 Actions，实际由消费方锁定准确 Core commit 后执行，不能宣称两套独立 CI。

**本地候选在本轮覆盖范围内未发现剩余已知阻断项。** 本地候选未 push，**没有 rc.13 的 GitHub CI 结果**；rc.12 绿灯不能替代候选 CI，也不能覆盖新添的边界。

现场报告只作为用户提供的只读复测证据，未由本轮重新访问生产：

| 现场范围 | 支持的结论 | 不能扩展到 |
| --- | --- | --- |
| 首18页 + 余66页，去重为原84页 | JSON-LD 唯一且可解析，目标语言名称/链接正确 | 全站所有 JSON-LD、所有上下文、84页全量 hreflang |
| 首18页 | 15 商品/分类页 index + 自指 canonical；3 分页 noindex 且无 canonical | 本批新的 hreflang 逐项验收 |
| 余66页 | canonical 自指，5 个正确 hreflang 及三语互返、HTML robots 正常 | HTTP X-Robots-Tag、其余22英文页自身互返、OPcache/缓存字节 |
| 三语规格句 | 各一条完整记录、非空译文，前台完整翻译且数值/单位保持；西语 manual、德俄 auto | 历史何时创建、算法单独因果、所有商品全文质量 |

三语规格 URL 已包含在首18页内，不能加成87页。原84页图片、移动端、询盘、邮件、站点地图和其他 SEO 插件并非本轮现场全量验收。三个报告已记录 SHA-256，见 FIELD_EVIDENCE.json；之前已经添加到两个 PR 的现场说明仍有效。

本地验收（全部在 NAS；PHP 8.3.33，原生 WP 7.1.4 / WooCommerce 10.5.3 / SEOPress Free 9.7.4，MariaDB 10.6；SEOPress Pro 结构为合约夹具）：

| 验证 | 结果/证据 |
| --- | --- |
| 原始 rc.12 边界复现 | baseline-boundaries.json，证明上述改写而非推测 |
| 最终解析/数据保护 | 360 项，含 JSON 上下文、字段歧义继承、嵌套/未闭合块、实体、比较符、JSON 精度及不相关 sibling 字节保护 |
| 最终分页/canonical | 1013 项，多语言、安装路径、页码及 false/null/空白关闭值 |
| 现有集成和语法 | 32 个集成脚本、133 个 PHP 文件通过；static.json |
| 安装包内运行时 | 360 + 1013 通过；104 项 ZIP、49 个 Core 文件、CRC 与每项源码字节/锁哈希一致；package-test.json、PACKAGE.json |
| 动态前端链接 | 1588 项（原1508 + 新80），另图库初始/动态媒体与导航测试通过；browser-navigation.json，无 JS 错误 |
| 原生浏览器矩阵 | 最终12匿名页面 + 3登录页面，16次切换，360项；真实 page2 商品 ID、人工标题、JSON-LD、规格数值、分页 noindex 均验证 |
| 缺译/恢复/再次人工修改 | 每态两次匿名读取；缺译 noindex/无 hreflang，恢复后重新就绪，自动写入跳过新人工译文，无 AI/队列调用 |
| 只读访问的数据保护 | 对 TM 全字段、队列、manifest、readiness 做前后哈希，见 native-data-preservation.json |
| 完整数据库 | 122 场景 / 2342 断言通过；full.json，最终 Core 源码指纹见 FULL_FINAL_STARTED.json；覆盖安装/升级、并发、manifest/readiness、重试、人工审核/删除、权限和卸载保留边界 |
| 状态分离/延迟/失败/恢复及管理员竞态 | 14 场景 / 106 断言通过；state.json，最终源码，含脚本删除、真实文本变化、延迟/失败/重调度、旧快照拒用、critical missing 和后台读取竞态 |

测试过程也保留失败和局限：复用旧假数据的首轮扫描断言失败，不能计为通过；清空仅命名测试库后重跑。一次长命令本地窗口终止，未完成结果作废，改为 NAS 保留日志运行。浏览器首轮因未指定已有 Chromium 路径而未执行，补环境变量后通过。上下文歧义继承的扩展用例曾让首版修复失败，随后收紧实现并通过，见 context-inheritance-first-revision-failure.json。没有将这些记录删掉或当生产故障。

## 4. 交付、影响与合并条件

候选 ZIP：`plugins/gml-translate-2.11.2-rc.13.zip`，438704 字节；SHA-256 `0d10809c60a88ce73e18becad9af42b6cd2521d25271cbd9618c0f3e9b2cd0ab`。

Core 已有本地 commit 9891513070d3e215e712dc19030b827169cc0b8d，产品锁及内嵌49个源文件准确对应。安装包不包含测试、文档、构建工具或凭据。最小差异为 Core.patch、Translate.patch，版本说明见 CHANGELOG。验收汇总及原始日志哈希见 ACCEPTANCE.json；原始日志保留在本地同名报告目录，版本化说明不冒充日志本身。

风险：保守跳过不确定 JSON-LD 上下文可能使此类自定义结构保留原名；缺失结束标签的技术块将保护余下输出，需网站作者修正原 HTML 后才恢复后续翻译。这些处理优先避免改坏业务/技术内容。常见 WooCommerce 与 SEOPress 结构已保持通过。本轮无 DB/TM 迁移，未改语言 slug、库存价格、表单业务值或发布门槛。

合并前仍需：明确授权推送本地候选到现有开发分支，Core 先推、Translate 后推以确保锁定对象可获取；更新两个 PR/附件，并验证新 HEAD 的 PHP7.4/8.3 和数据库 CI。当前两 PR 仍 open、未合并。不要以 GitHub mergeable/clean 等同代码放行，也不要把目标分支自动改成 main。

后续生产验收另行安排：先在站点副本安装准确 ZIP，检查该站 SEO 所有者实际输出、图片/导航、文章保存后的异步就绪、匿名响应头和 HTML、人工译文及真实缺译门禁；在另行授权前不得连接生产、清缓存、重译或调用付费 API。

回滚：本轮仅本地候选，无生产回滚动作；代码和锁文件可整体退回原 rc.12 配对版本，不做数据库回滚或删除译文，但 rc.12 会恢复上述已知边界缺陷，不宜据此发布。若未来生产确需回退，先保存该次部署前文件和设置，再依据准确原版本包操作，并保留现有业务数据。

结论分层：现场范围内 rc.12 已通过；新增边界在隔离环境复现；修复与候选测试结果按本表记录；未 push、未合并、未上线。本报告不承诺穷尽所有第三方组合或未来数据输入。
