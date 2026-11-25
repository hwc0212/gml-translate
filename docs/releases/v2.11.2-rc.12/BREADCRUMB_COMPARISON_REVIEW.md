# GML Translate 2.11.2-rc.12：比较条件文本与面包屑 JSON-LD

2026-11-25（Asia/Shanghai）

**两项代码缺口已修复并通过隔离验证，交付 rc.12 审阅候选安装包。未访问生产、合并或部署；GitHub 交付状态见本目录 README 与两个 PR。** 现场 84 页异常及原图复测结果来自用户附件，不作为本轮独立生产测试。

## 基线和源码

- Translate 起点：84bfe99d11ced382c5127ce5f89efd0a68e29c57，2.11.2-rc.11；审阅候选 2.11.2-rc.12，通过 Translate PR #3 交付。
- Core 起点：f0e86e26fe1c82615d4945a9cb77c41459f1cc9d，0.9.22。
- 修复 Core 提交：83e5d9fda37113cf4249313e467452f6a420506a，0.9.23，通过 Core PR #1 交付。锁文件及 49 个生成的 vendor 文件均与该提交匹配。
- 修改集中在 Core 的 class-html-parser.php、class-output-buffer.php，以及新增的 class-breadcrumb-jsonld.php；没有另外安装插件，也没有修改第三方源码。
- [Core 差异](https://github.com/hwc0212/gml-translation-core/pull/1/files)、[Translate 差异](https://github.com/hwc0212/gml-translate/pull/3/files)、[机器结果](review-results.json)、[包信息](PACKAGE.json)。

## 已证明的执行缺口与修复

### 含原始小于号的完整句子

rc.11 的 DOM 提取能够得到完整句子，但重建扫描遇到原始小于号时，将其至后续尖括号间的内容当成标记保护，随后按小于号分割正文，造成整句匹配失败。

离线基线中，即使明确提供完整目标译文，原始小于号和“小于号加空格”两种条件仍保持英文；对应实体编码以及大于号对照能够回填。见 reproduction.json。这证明了回填缺口，不证明生产数据库中该句的实际 TM 状态或缓存是唯一影响因素。

修复将真正的 HTML 标记与普通小于号分开识别，并在受保护的标记 token 之间匹配完整正文。排除块提取和导航扫描复用同一判断，避免比较符吞掉后面的闭合标记或链接。仍然不使用 DOM saveHTML 重建页面。

专项验证保留 5–8 ppm、15°C、20°C、<500 µS/cm 等原参数和方向，覆盖原始、命名/数字/十六进制实体、正负值、空格、混合比较符及 title、正文、属性保护。

### BreadcrumbList 缺少本地化处理

旧实现保护所有 script，普通脚本安全，但也没有为合法面包屑 JSON-LD 提供名称与 URL 处理。基线中，可见分类名称和导航已翻译时，同页 JSON-LD 仍完整保留源名称及源地址。

新增 helper 只处理受支持 schema.org 上下文内 BreadcrumbList 的以下字符串：

- ListItem.name；
- ListItem.item 为字符串时的页面 URL；
- ListItem.item 对象中的 name、@id、id、url。

名称进入已有解析、按页 TM 查找、缺译发现及就绪门禁，复用人工译文；目标名称按现有纯文本规则清理并安全 JSON 编码。最多两次解码名称中的 HTML 实体，以对齐可见文本的 TM 键。没有可用译文时保留原字段，不猜译名、不覆盖人工记录。

URL 使用现有公开导航规则：保留源站安装边界、分页号、查询及 fragment；外站、媒体、服务地址等仍受原有排除规则约束。没有复制当前请求参数，也没有修改资源身份或创建每页独立资源。

**只替换明确 JSON 路径上的字符串字面量，不重建整段 JSON。** 其他 Product/Offer 节点、金额、SKU、超大整数、浮点字面格式、空对象/数组、graph 标识及无关字段保留原字节；没有创建重复 schema，没有接管 SEO owner。

普通脚本、配置 JSON、非目标词汇表、无效 JSON、重复键、超限数据、translate=no / notranslate / 已配置排除块均不转换。支持的边界为单段 JSON 256 KiB、JSON 深度 64、每个面包屑最多 128 项、最多 1,024 个可处理字段；超出时保留原样，不宣称这些格式已支持。

## 隔离验收

原生环境：已授权 NAS，WordPress 7.1.4、PHP 8.3.33、WooCommerce 10.5.3、SEOPress Free 9.7.4、MariaDB 10.6，内部网络、无发布端口，独立合成数据库。

原生 WooCommerce 实际生成面包屑 JSON-LD；另外用本地 SEOPress 源码对应的现代 item.id/item.url 形状作契约 fixture。**没有安装或激活 SEOPress Pro，因此不把契约 fixture 冒充 Pro 完整运行验收。** 最小测试主题及人工测试译文用于验证数据流程，不代表生产主题或真实 AI 翻译质量。

| 验收 | 实际结果 |
| --- | --- |
| 新专项 | 250 项通过：比较符、JSON 路径、实体、手工译名、注入防护、无关字节、异常/重复键、排除块及 URL 边界 |
| 既有集成 | 32 个脚本通过，包含原分页 869 项；PHP 语法 133 个文件通过 |
| 原生浏览器 | 12 个匿名页面、3 个真实登录页面，360 项检查通过；商品、分类首页及分类第 2 页，EN/ES/DE/RU |
| 分页保真复核 | 四来源 × 四目标，16/16 实际跳转正确；每次目标仍为第 2 页商品 14、16，非只保留地址 |
| SEO | 分类/商品保留 index/follow、1 条自指 canonical、5 条正确 hreflang；第 2 页保留 noindex/follow、无 canonical/hreflang |
| 数据保持 | 原生浏览器请求前后，TM 全字段、队列、manifest/readiness 和资源键集合哈希一致 |
| 缺译 | 仅在测试库删除 DE 条件句译文，两次 GET 均保留原始参数、源文及原手工标题，noindex，hreflang 为 0，队列 0 |
| 恢复 | 测试库恢复人工译文并使用既有 manifest 接口评估，两次 GET 均恢复德语整句与就绪，hreflang 为 5 |
| 再次人工改名 | 保存第二个人工标题后，模拟 auto 写入被 skipped_manual 保护拒绝；两次 GET 中 H1 与 JSON-LD 同步读取第二个人工标题，条件句不变，队列 0 |
| 数据库综合套件 | 122 场景 / 2,342 条 PASS；新增 Core 断言纳入既有 technical-text 场景 |
| 状态与交错套件 | 12+2 场景 / 106 条 PASS，终止标记核对通过 |
| 精确 ZIP 回读 | 直接加载解压包内代码，新专项 250 项及原分页 869 项通过；包内全部文件与原生测试 stage 一致 |

主要证据：

- [原生页面与 JSON-LD 读回](evidence/native-browser.json)、[分页实际跳转](evidence/browser-candidate.json)；
- [TM 与队列等数据保持](evidence/native-data-preservation.json)；
- [缺译](evidence/native-missing.json)、[恢复](evidence/native-restore.json)、[再次人工改名](evidence/native-manual.json)；
- [数据库综合套件](evidence/full.json)、[状态套件](evidence/state.json)、[集成及语法](evidence/static.json)；
- [ZIP 内代码执行](evidence/package-test.json)；
- [德语最小测试主题效果](evidence/native-de-manual.png)：第二个人工标题与完整条件句；这是测试主题的 main 区截图，不是生产页面。

复用的缓存专项另通过三个目标语言各两次真实 HTTP 请求，共 6 次，确认 GML 缓存 HTML 与响应相等、页码/导航和分页 SEO 正确。该专项仅在测试 MU 中停用语言偏好 cookie writer，产品缓存保护不变；正常 Set-Cookie 响应仍按原规则绕过缓存。此项不等于生产 CDN/源站外层缓存验收，也不扩大为逐个 schema 字段的独立缓存矩阵。

原生测试最初对英文源节点的实体编码和首页无尾斜杠作了过严假设，已修正基线预期后重跑。恢复夹具也修正了“恢复前要求被删除记录存在”的前置条件。上述失败尝试保留为 discarded 本地记录，不计入通过结果；没有因此修改产品运行时代码或掩盖失败。

## 行为、安全、性能与数据影响

- Core 不增加端点、数据表、并行队列、调度或 AI 调用方式；原有 readiness/review、版本校验、暂停和人工保护规则保持。
- 新发现的面包屑名称仍按站点原有 AI/暂停设置进入既有发现/排队流程；本轮 AI 关闭，未调用付费服务。没有真实询盘或邮件发送。
- 如果生产 TM 本就缺少目标句译文，或人为保存/保留了英文，本候选不会凭空生成译文或越过人工保护。后续生产验收需要区分“回填已修复”和“实际译文尚未提供”。
- 没有迁移或改写既有 TM、产品源文、slug、分类、URL、数字参数、主键或发布阈值，没有全站重翻、全站重建或清缓存。
- JSON 处理有大小/深度/字段上限，字典只读取当前页面涉及的 hash，不扫描整站字典。增加的是目标面包屑字符串的有界解析与已有查找。
- 本轮没有重测附件列出的 25 款真实商品或所有图片。原图现场通过仍以用户 rc.11 报告的 306 个 href 与 12 次代表打开为边界；本候选回归保留服务/媒体 URL 的原有规则。

## 安装包、风险与回滚

安装包：plugins/gml-translate-2.11.2-rc.12.zip，438,149 bytes，104 项。
SHA-256：b3208778cf010c260133ad366c6ba71494b816c1f4ed451025d491121a275609。
CRC、全部源码字节及 49 个 Core 锁文件 SHA-256 校验通过。测试代码、fixture、报告、私有配置和容器资料不进入安装包；不需要安装独立 Core 插件。

生产报告中的 WordPress 7.1.5、实际 SEO/主题版本、生产译文记录、完整 JSON-LD 原文、共享主机负载、LiteSpeed / Cloudflare / OPcache 本轮未独立复核。支持的常见 JSON-LD 形状已测，不能声称全部第三方 builder 或自定义词汇表均已验收。

没有数据库迁移，回滚可恢复事先备份的 rc.11 插件文件；不要为了回滚文件而覆盖期间真实业务数据库。新发现的译文保留，旧版仍会有本次两项处理缺口。后续发布前，应核对实际文件前值与目标语言译文，并在原 URL 上只读验收名称、URL、完整条件、响应头和 SEO 策略；需要任何指定 URL 的外部缓存操作时另行安排。

**交付状态：隔离缺陷复现通过；本地修复和候选包验证通过；Core 与产品通过开发分支 PR 交付，当前交付信息见 README 与 PR；未合并、未部署生产。**
