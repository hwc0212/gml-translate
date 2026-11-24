# GML Translate 分页语言链接修复候选

日期：2026-11-24（Asia/Shanghai）

**结论：附件中的两类输出错误已在隔离环境复现并修复，交付 2.11.2-rc.11 审阅候选。未访问或修改生产站，未合并或部署。GitHub 交付状态见本目录 README 与 PR #3。** Core 保持 0.9.22，锁文件及 48 个内嵌 Core 文件不变。

## 目标与基线

修复归档第 2 页切换语言回到第 1 页，以及目标语言分页 hreflang 错指第 1 页。保留现有 noindex、SEO 插件 canonical 所有权和翻译资源身份。

- 产品仓库：gml-translate，分支 codex/2.11.2-rc.5-review；起点 3d2d2c42a3a60b39eb0305f2b1a5323f752e8dee（2.11.2-rc.10）。
- Core：gml-translation-core，分支 codex/0.9.17-review；f0e86e26fe1c82615d4945a9cb77c41459f1cc9d（0.9.22），工作树干净。
- 本轮运行时代码仅修改 includes/class-seo-router.php、includes/class-seo-hreflang.php；另更新版本、CHANGELOG 和测试。
- 附件记录是生产现场证据；本轮没有独立复访生产，也未验证生产文件逐字一致性。
- 本候选通过 Translate PR #3 交付；Core PR #1 本轮无新增修改。

## 根因与修改

语言选择器调用 Router::get_language_urls()，旧实现直接使用 current_public() 返回的基础分类 URL。term/CPT 资源身份不包含当前请求页码，因此分页上下文在生成按钮 URL 时丢失。

Hreflang 的输入还存在源语言与目标语言差异：源语言保留分页路径，目标语言 canonical 适配却使用 public status 的基础资源 URL，随后取到基础归档的语言集。资源按分类归一化本身无需改变；错误在于将资源基础 URL 直接作为当前请求 URL。

最小修改：

1. Router 新增 get_current_source_url()，使用已有资源 URL、WordPress 实际解析的 paged 和 pagination_base 构造当前归档页 URL。支持子目录、查询式分页和自定义分页名；不复制未经筛选的请求参数或原始路径。
2. 导航和 canonical 适配使用该分页上下文。404 不生成看似有效的第一页语言集；没有新增分页资源或数据表。
3. 分页 hreflang 仍由已有 Core 基础资源 eligibility 决定成员，再映射到同页码 URL。原有 readiness/review 门禁保留。
4. 在现有 SEO 过滤器只读观察 robots；将多语言 head 输出延后到 SEO owner 输出之后。本候选对明确 noindex 的分页统一不输出 hreflang，同时语言按钮仍保留页码。
5. 保留 SEOPress 的空 canonical 输出，不因 GML 适配自行生成已被 SEO owner 关闭的标签。允许索引的隔离分页仍验证同页码自指 canonical 与互指 hreflang。

这次补上了此前综合回归遗漏的“原生 WooCommerce 归档分页 + SEOPress”组合。先前检查不能作为这个组合已被覆盖的证据。

## 隔离环境与证据边界

在已授权 NAS 创建独立原生 WordPress 测试站，内部 Docker 网络，无发布端口、无生产数据。使用真实 WordPress 主查询、WooCommerce 商品/分类和 SEOPress wp_head 输出；测试主题声明 WooCommerce 支持。不是仅以伪造 HTML 代替原生归档。

- WordPress 7.1.4、WooCommerce 10.5.3、SEOPress 9.7.4、PHP 8.3.33、MariaDB 10.6。
- 附件报告生产 WordPress 7.1.5，生产 SEOPress 版本未知；本轮没有声称完全复制生产版本和主题。
- 7 件非零测试价商品、两个商品分类、普通 taxonomy 和自定义文章类型归档；每页 2 条，原生第 2 页商品 ID 为 14、16。
- 译文和就绪 fixture 离线写入独立测试库；AI、外部 HTTP、邮件与后台自动调度被隔离护栏阻断，没有调用翻译服务或提交询盘。
- 第二个既有回归容器只重置其明确命名的合成测试库，用于原有 122 场景。两个测试环境使用后均已停止。

基线主题起初未声明 WooCommerce 支持，产生的 dummy 商品 ID 0 结果被排除；修正测试主题后重新完成基线。自定义分页名 fixture 起初在设置 rewrite base 前注册 CPT，该无效 fixture 也被排除，修正注册顺序后重新运行。无效的调查过程保留在本地，不计入通过结果；本目录 evidence 只附有效验收证据。

## 复现与修复后读回

rc.10 原生基线：四语页面自身均是第 2 页，真实商品为 14、16；16 个语言目标全部指向第 1 页。英文 hreflang 为 0，三个目标语言各 5 条且全为基础归档 URL。四语仍为 noindex, follow、无 canonical。与附件现象一致。

rc.11 原生最终读回：

| 当前语言 | 页面内容 | 四个语言按钮目标 | HTML robots | canonical | hreflang | X-Robots-Tag |
| --- | --- | --- | --- | --- | --- | --- |
| EN | paged=2，商品 14、16 | 全部为各语言 /page/2/ | follow, noindex | 0 | 0 | 无 |
| ES | paged=2，商品 14、16 | 全部为各语言 /page/2/ | follow, noindex | 0 | 0 | 无 |
| DE | paged=2，商品 14、16 | 全部为各语言 /page/2/ | follow, noindex | 0 | 0 | 无 |
| RU | paged=2，商品 14、16 | 全部为各语言 /page/2/ | follow, noindex | 0 | 0 | 无 |

实际执行四个来源 × 四个目标的 16 次浏览器导航，**16/16 到达正确目标，页码和真实商品 ID 均保持第 2 页**；当前语言 aria-current 正确。不是只检查 href。

原始证据：[rc.10 浏览器](evidence/browser-baseline.json)、[rc.11 浏览器](evidence/browser-candidate.json)、[可直接阅读的前后读回](evidence/readback.json)。

## 回归结果

| 项目 | 结果与范围 | 证据 |
| --- | --- | --- |
| 原生 noindex 矩阵 | 40 场景、397 断言通过；两个分类首页、商品详情、分页 2/3/末页 4、无效 999、真实登录与匿名重复访问 | matrix-noindex.json |
| 原生允许索引矩阵 | 40 场景、581 断言通过；商品分类、普通 taxonomy、CPT archive 的 2/3/末页，四语同页 5 条互指、自指 canonical，越界无有效第一页语言集 | matrix-index.json |
| 组合配置 | 40 场景、581 断言通过；/catalogue 子目录、西语源语言、英文目标前缀、自定义 seite 分页名 | matrix-index-variant.json |
| 原有数据库综合回归 | 122 场景、2,331 条 PASS；覆盖生命周期、解析、队列、就绪竞态、权限及 SEO 等既有断言 | full.json |
| 独立集成脚本 | 31/31 脚本通过；新增分页上下文专项 869 项通过 | static.json |
| PHP 语法 | 127 个源代码及测试文件通过 | static.json |
| Core 锁及内嵌文件 | 0.9.22 / f0e86e2，48/48 匹配 | static.json、PACKAGE.json |
| 精确安装包回读 | 直接载入 ZIP 解压后的运行时代码，分页专项 869 项通过；全部包内文件与原生验收所用 stage 一致 | package-test.json |
| 数据保持 | 核心访问阶段前后，TM 全字段、队列、manifest/readiness 与资源键集合哈希一致 | unchanged-after-core-case.json |

索引 fixture 的页 1/商品详情保持原有正确语言链接、五条 hreflang、index/follow 与单一自指 canonical。允许索引 fixture 与现场 noindex 配置分开验证，未改变生产设置。

### 缓存验证的准确范围

正常原生请求会写语言偏好 Set-Cookie；现有 GML 安全判断因此不缓存该响应，这是正确的绕过路径。不能把这部分声称为缓存命中测试。

另外在测试 MU 夹具中仅停用语言偏好 cookie writer，保留产品缓存安全检查原样，完成三个目标语言各两次真实 HTTP 请求，共 6 次。已确认持久缓存存在、缓存 HTML 与响应相等、内容仍为第 2 页、按钮保留页码、hreflang 为 0。见 cache-safety-probe.json、cache.json。

未测试生产 LiteSpeed、Cloudflare、OPcache；未清理任何生产缓存。本次结论不等于这些外部层已验收。

## 数据、SEO、安全及性能影响

- 不修改分类/商品 slug、源文、译文、主键、资源结构、readiness、阈值、锁或队列；没有新增分页资源、迁移、全站重建或翻译请求。
- 现场要求的 noindex/follow 保留；canonical 继续由 SEOPress 管理。noindex 分页 hreflang 统一省略是本候选明确采用的输出策略，导航独立保持可用。
- 仅从已解析的正整数页码构造 URL，沿用现有 URL helper 与转义。未增加远程请求或后台任务。
- 分页只使用既有基础资源成员判断，不逐分页扫描数据库；head 输出顺序改变，对其他 SEO owner 的完整原生联测仍是兼容性边界。
- Yoast、Rank Math 及独立 GML SEO 的生产组合未在本轮逐一搭建；原有 GML SEO 输出所有权早退逻辑保留，不能据此声称这些组合均已完成原生验收。

## 安装包、diff 与回滚

- 安装包：plugins/gml-translate-2.11.2-rc.11.zip，434,920 bytes，103 个条目。
- SHA-256：8b29584999fb63db9b8c89060a4db2e02f030430b47699371b9e85c4bc92344c。
- 包 CRC、逐文件源码读回及 48 个 Core 文件校验通过；tests、tools、bin、docs 与实验配置未进入安装包。
- 代码差异见 PR #3 的 Files changed；本地原始 candidate.patch 另行保留；逐项结果见 review-results.json；原始有效验收日志见 evidence/。
- 仅为候选，尚未安装到生产；GitHub 交付见 README 与 PR #3。此包包含 rc.10 既有累计修复，新增差异在本报告及 CHANGELOG 标明。
- 无数据库迁移，回滚可恢复已备份的 rc.10 插件文件；由于旧版会重新产生本次分页错误，应按变更目的决定是否回滚。未来发布前应保留实际生产文件备份，而非仅凭后台版本号认定文件一致。

## 后续生产验收边界

本候选交付供审阅。若另行授权更新测试/生产站，再记录实际 PHP、WordPress、WooCommerce、SEOPress、主题版本和插件文件前值；在原 noindex 设置下核验四语原 URL、16 个实际目标、真实分页商品、响应头及 HTML。首页和商品页同时抽查。

如旧外部缓存影响验收，应先取得指定 URL 清理授权，不进行默认全站清理、重翻译或清单重建。无需为本次代码问题重新处理已恢复的其他页面。

状态区分：**生产现场问题来自附件记录；隔离复现通过；本地修复及安装包回读通过；生产未部署。**
