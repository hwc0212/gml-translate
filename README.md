# GML Translate

**AI Multilingual Translation for WordPress**

GML Translate 是独立的 WordPress AI 多语言插件，专注解决一件事：以可控成本建立稳定、可维护、可人工修订的多语言网站。

它不包含 GSC、GA4、Google Ads、通用 SEO Audit、重定向、404、性能优化或完整 Schema 管理。这些属于 GML AI SEO。

## 2.11.1-rc.6 翻译控制分离候选版

本节替代 RC5 的操作限制。正常待翻译队列不再被失败小样本独占；仍保留 API 熔断和失败重试上限。

- **Start All Pending / 启动全部待翻译任务**：启动所有已启用语言的正常 pending 记录，不重置或重试历史 failed 记录。
- **每个语言行的播放/暂停按钮**：只控制该语言的正常任务。全局暂停后单独启动德语，不会同时恢复西语、俄语等其他语言。
- **Resume Sample / Pause Sample**：只控制当前已批准的最多 25 条失败样本，有独立入口。正常队列不会吸收已暂停的样本；样本完成不会停止已主动启动的正常任务。
- **Pause All / 暂停全部**：停止后续正常任务和样本批次；已经发出的单个批次需要结束。再次启动正常任务不会自动继续失败样本。
- 内容扫描不再受样本独占限制，仍受 AI 可用性和熔断保护；扫描只收集并入队，不改变翻译范围或暂停状态。已主动运行的正常队列可处理扫描新发现的文字。
- 启动使用持续的每分钟 WordPress 排程，替代可能只执行一次的旧排程。关闭 WP-Cron 的服务器仍需系统 Cron 调用 WordPress；排程存在不等于 API 已经执行。

升级不会将旧样本自动扩大到全站；不删表、不清译文、不重置失败记录。正常任务可能持续消耗 API 额度，点击前确认语言范围。回退前先“暂停全部”，再恢复旧插件文件，保留数据库；RC5 不识别新的独立控制逻辑，回退后不要直接恢复旧任务。

候选版通过隔离 WordPress / MariaDB、模拟 AI 和本地后台回归，不等于生产 API、全站翻译覆盖或多语言 SEO 已全部验收。

## 2.11.1-rc.5 小样本暂停恢复候选版（历史）

修复“暂停后开始按钮一直灰色”的缺陷：原来的受限重试样本仍有待处理条目，但全量启动被保护规则锁定，缺少继续该样本的入口。

- 新增 **Resume Sample / 继续小样本**，全局工具栏和对应语言行都可继续同一组已批准条目；显示样本语言和剩余数量。
- 继续不新增样本、不重置已完成条目或尝试次数，不解除其他语言暂停；最多 25 条，完成后仍自动暂停。
- 没有 Key、未启用 AI/多语言、服务商熔断、上个批次尚未结束、权限或 nonce 不通过时拒绝继续。排程失败保留暂停和样本，修复后可再次点击继续。
- 小样本存在期间，全量启动和内容扫描仍受限。不要为了恢复按钮删除译文、清队列或“重试全部”。
- 保留 RC4 的语言路由恢复与队列轮转修复，本包可一次更新，无需先安装 RC4。

升级不会自动恢复线上翻译。完成备份并安装候选包后，进入 Translations 页面，由管理员手动点击“继续小样本”。回退仅恢复升级前的插件文件，不卸载或删表；旧版本可能再次缺少该恢复入口。

## 2.11.1-rc.4 路由与队列修复候选版

- 修复插件在 WordPress `init` 之后激活时，语言路由没有写入固定链接缓存，导致 `/es/` 等入口返回 404。激活时先注册语言规则再保存；普通管理员后台请求也会检查并补齐缺失规则，保留其他插件的路由。
- 前台、AJAX、Cron 不执行该路由修复；没有 Key 或 AI 暂停不会阻止已有语言页访问。没有真实内容的 URL 仍返回 404，不把错误页面伪装成首页。
- 共享 Core 0.4.6 按语言轮流处理队列，避免西语积压时俄语一直等待。保持原有每次一个批次、互斥、暂停、熔断和小样本限制，不新增 API 并发，不重置进度。
- 安装后进入一次普通后台页面，再用未登录窗口核对语言首页和内页。路由恢复不代表全部译文完成；缺译应在连接验证后小批量补齐，不要清库重译。
- 回退时恢复升级前备份的插件文件即可，保留当前数据库和已修复的语言规则；仅在确定路由修复本身有问题时才恢复规则快照，否则旧快照可能重新造成 404。不执行卸载、删表或全站缓存清理。

候选包已做隔离 WordPress 7.1 / MariaDB 的根目录、子目录、激活、路由丢失修复与队列轮转回归；不代表整站译文、生产主题/CDN/Redis 或真实 API 全部验收。

## 2.11.1-rc.3 凭据修复候选版

本轮修复保存与读取的两处缺陷：解密失败不得把密文当 Key 发出，数据库写入失败不得报告成功。保留原 options 和加密格式，不更换数据库表，也不会清空译文或自动重译。

在 Settings 中输入 Key 后，**Save Changes** 仅加密保存并回读核对，不调用 AI；然后 **Test Saved AI Connection** 对同一个已保存值发出一次短测试。密码框始终为空、不回显 Key；留空保存不会修改原 Key。“本地可读”不等于“连接验证通过”。若显示不可解密，请重新输入并保存，这可能与站点迁移/盐值变化或记录损坏有关，但不能仅凭错误截图认定根因。

更换 Key、切换引擎和重新启用 AI 均保留暂停；连接测试成功也不启动队列。完成小样本验证后再手动恢复，不要直接“重试全部”。已有语言路由和译文不受 AI Key 失效影响。旧的 Gemini/DeepSeek 明文格式保留只读兼容；不能识别的旧明文需重新输入，新的不透明格式 Key 则可以正常加密保存。

这是本地候选包，**尚未验证 cnxhe.com 当前 API 400 的真实原因或恢复情况**。隔离测试使用合成 Key 和模拟响应，没有使用真实 Key 或 API 额度。回退到本站已验证可用的插件文件即可，不执行卸载或删表；请仍先备份和在测试站验证。

## 2.11.1-rc.2 修复说明

此版本用于测试站验证，尚未作为正式稳定版发布。不要在生产站继续使用 2.11.0 的旧升级流程；该流程可能在大翻译队列上阻塞数据库。

- 升级只在插件激活或有权限的普通后台请求中进行；访客、AJAX 和 Cron 不再执行翻译数据库升级。
- 保留旧表和全部翻译数据，不自动全表去重、改表或清理历史记录；新版建表仍包含唯一键。
- 数据库升级使用不等待的互斥锁、短元数据锁超时和失败保护。失败不会标记升级完成，也不会反复阻塞每个后台请求。
- 原 Auto-Translate 改为两个明确操作：Scan Website Content 只发现内容并入队；Start/Pause Translation 单独控制 AI 工作。扫描不会解除全局或语言暂停，也不会直接调用付费 Provider。开启扫描时，已在运行的队列仍按原状态继续处理。
- Queue Status 区分暂停、排队、实际处理批次、调度缺失和超时，并显示最近一次工作时间；不再把未暂停直接称为 Running。调度失败不会改变暂停状态。
- Refresh Page Cache 只更新页面缓存版本，不删除译文、人工修订或队列；旧版清缓存请求也不再删表。需要删除译文时，进入语言编辑器逐条审核确认。
- 修复 Markdown 清理误删技术参数符号：正文、标题、Meta、图片 ALT 和 Provider 输出保留 `90*45*30mm`、`10**3`、SKU 下划线等。
- 默认英文并提供新增简体中文文案；状态轮询不再整页刷新，避免打断未保存的人工编辑。
- 补上同一请求内切换插件的加载保护。GML SEO 已加载内置翻译时，再激活独立 GML Translate 会被明确阻止；请继续用 SEO 的 Translation 页面。确需更换产品时先停用原插件，再在新的后台请求中启用目标插件，数据不删除。原先已同时启用两个插件的受控接管流程保持不变。

**cnxhe.com 注意：** 本次修复不会自动生成新版网站缺失的译文，也不会修正库中已保存的错误数字。用户反馈的约 15% 覆盖率仍需后续小批量补译验证；不要清库或直接全站重译。若译文库里的尺寸已丢失符号，应先按原文逐条核对和修订。缓存刷新不等于补译，插件页面缓存之外的 CDN/服务器缓存须按站点流程另行处理。

**验证与恢复：** 先备份数据库及插件文件，在子目录测试站安装候选包，保持 AI 暂停；核对后台、未登录页面、已有语言页与人工译文，再测试一个语言的小样本。不要直接“重试全部”。出现问题时停用候选插件并恢复此前验证可用的插件文件，不执行卸载或删表。本次保留旧表、旧 options 与旧 URL 结构，便于回退；回退包必须针对本站验证，不能把有问题的 2.11.0 当作恢复目标。

真实 WordPress 7.1 / MariaDB 10.11 测试覆盖 13 万条旧队列、5.2 万条人工译文、并发、升级权限失败、暂停恢复及 WordPress 激活。主题、CDN、Redis 和真实付费 API 的测试站验收仍需另行完成。

## 产品定位

GML Translate 提供：

- 多语言 URL 与 WordPress 路由。
- AI 翻译队列、翻译记忆与页面缓存。
- 术语表、保护词、排除规则和人工翻译编辑。
- 语言切换器与浏览器语言检测。
- hreflang、alternate URL、SEO Meta 翻译与多语言 sitemap 的最低必要 SEO 支持。

插件可独立安装。Release ZIP 包含全部运行代码，不要求 Composer、npm、Git submodule、第三个 Core 插件或 GML AI SEO。

后台默认源语言为英文，遵循 WordPress `gml-translate` text domain。发行包包含简体中文、繁体中文、德语、法语、西班牙语、葡萄牙语、日语、韩语、俄语和阿拉伯语 starter packs；尚未翻译的字符串安全回退到英文。

## 为什么存在

普通自动翻译工具常见的问题是 URL 不稳定、翻译无法修改、队列失败后反复扣费、页面更新后缓存不刷新，以及 canonical、hreflang 和 sitemap 互相冲突。GML Translate 把这些问题拆成可观察、可暂停、可恢复的工作流。

核心原则：

- 多语言站点与 AI 翻译是两个状态。
- AI 只负责产生新译文，不能决定已翻译页面是否继续存在。
- API Key 删除、额度不足或 Provider 故障时，已有语言 URL 和译文仍应正常访问。
- 普通前台请求不调用 AI，也不执行全站扫描。
- 队列恢复必须小批量、人工确认、可暂停。

## 适用对象

- 只需要 WordPress 多语言与 AI 翻译，不需要完整 SEO Suite 的网站。
- 需要固定 `/de/`、`/fr/`、`/es/` 等语言路径的企业站和内容站。
- 需要术语一致、品牌词保护和人工修订的外贸网站。
- 需要从自动翻译逐步过渡到人工审核内容的团队。

## 主要功能

- Multilingual Site：语言路由、切换器、已有译文、hreflang 和 sitemap。
- AI Translation：新翻译、Crawler、Queue 与 AI 重翻译。
- Translation Queue：有界批次、锁、熔断、失败状态、单语言人工恢复。
- Translation Memory：相同原文复用译文，人工译文优先。
- Translation Editor：搜索、筛选、编辑和删除单条译文。
- Glossary：指定术语译法和永不翻译的品牌/型号。
- Exclusions：按 URL、CSS selector 和规则跳过页面或元素。
- Content Crawler：在后台/WP-Cron 中发现已发布内容与模板文本。
- Language Switcher：菜单、短代码、Widget 与自动位置。
- Browser Language Detection：只在受控条件下建议或跳转到可用语言。
- Multilingual SEO：translated title/meta、canonical、hreflang、alternate URL 和 sitemap。

## 支持的 AI Provider

独立版当前支持：

- Google Gemini
- DeepSeek

Provider 层统一处理：

- API Key 加密保存。
- 精确官方 HTTPS host 白名单。
- 不在 URL、HTML、REST、日志或错误信息中暴露 Key。
- 不跟随重定向，限制请求与响应大小。
- 最多一次受控重试、失败分类和熔断。

切换 Provider 不会删除已有译文。SEO Prompt 与 Translation Prompt 分离；GML Translate 只维护翻译 Prompt。

## 快速开始

1. 安装并启用 `gml-translate-x.x.x.zip`。
2. 在 Settings 选择源语言和目标语言。
3. 开启 Multilingual Site，先验证每个语言首页、页面、产品和分类 URL。
4. 需要新翻译时，保存 Gemini 或 DeepSeek Key，再开启 AI Translation。
5. 先选择一个目标语言和少量页面试译，检查品牌词、链接、布局、表单和 SEO Meta。
6. 填写 Glossary 与 Exclusions，再启动全站 Crawler。
7. 在 Translations 中观察 pending、processing、failed 和完成度；不要在大量失败时“全部重试”。
8. 完成后检查 canonical、hreflang、语言回链和 sitemap，再决定是否允许搜索引擎索引。

## 语言与路由

语言 URL 使用站点相对路径工具生成，兼容根目录和 WordPress 子目录安装。例如站点位于 `/staging/` 时：

```text
正确：https://example.com/staging/de/about/
错误：https://example.com/staging/de/staging/about/
```

每次在 WPvivid 或其他子目录测试站启用多语言后，应固定验证：

1. `/子目录/de/页面/` 返回 200。
2. 语言切换器只包含一次站点子目录。
3. canonical、hreflang 和 sitemap URL 只包含一次子目录。
4. 内部链接和重定向不会跳回源语言或重复前缀。
5. 推送主站后 URL 中不残留 staging 目录。

修改语言后，插件只标记 rewrite rules 在安全时机刷新，不在每个前台请求执行昂贵刷新。

## 翻译工作流

Multilingual Site Enabled 控制：

- 语言 URL、Router、Switcher。
- 已保存译文的读取与页面显示。
- hreflang 与 sitemap language variants。
- 翻译缓存读取。

AI Translation Enabled 控制：

- 产生新 AI 译文。
- Crawler 翻译、Queue 处理和 AI 重翻译。

没有 Key 或 AI Translation 关闭时，只停止新 AI 工作。人工译文、翻译记忆和已存在的语言页面不会失效。

队列采用有界恢复：Provider 异常、账号额度或模型错误会打开熔断并暂停。修复 Key/模型后，先测试连接，再对一个语言重试最多 25 条；连接成功不会自动恢复数万条队列。

## 术语表

Glossary 用于指定产品、行业和品牌术语：

- “Always translate X as Y” 可按目标语言设置固定译法。
- Protected Terms 保留品牌、型号、认证和专有名词。
- 规则数量、单项长度、Prompt 长度和正则回溯均有限制，避免管理员输入拖慢后台或 Provider 请求。

先完善术语表再大批量翻译，可以减少重复重翻译和 API 成本。

## 翻译编辑器

Translation Editor 支持：

- 按原文或译文搜索。
- 按语言、自动/人工状态筛选。
- 编辑单条译文并标记为 manual。
- 删除错误译文，让指定内容重新进入受控流程。

人工译文优先，不应被普通自动任务覆盖。保存后只失效相关语言与对象的缓存，不清空全站 Redis/Memcached。

## SEO 兼容

独立运行时，GML Translate 提供多语言网站最低限度 SEO：

- 正确语言 URL 与自引用 canonical。
- 源语言、目标语言和 `x-default` hreflang。
- translated title、description 与社交 Meta。
- 多语言 sitemap 或对现有 sitemap 的语言关系集成。
- 未完成到可索引标准的语言页面默认 noindex，不进入 hreflang/sitemap。

它不是完整 SEO 插件。Titles & Meta 策略、通用 Schema、重定向、404、GSC 和性能优化应由 GML AI SEO 或其他单一 SEO authority 负责。

当 GML AI SEO 与 GML Translate 同时启用时：

- GML Translate 暂时保留路由、切换器、译文渲染和已有翻译。
- GML AI SEO 负责最终 canonical、meta、Open Graph、Schema、hreflang 和 sitemap。
- 不输出重复 canonical、hreflang、sitemap、router 或 switcher。
- 管理员在 GML AI SEO 中确认接管后，独立插件才会被停用；数据不会删除。

## 性能与安全

- 普通前台只做路由、当前页面译文 lookup、缓存读取与轻量渲染。
- AI、Crawler 和 Queue 只在后台、WP-Cron 或管理员触发的异步任务中运行。
- 当前页面只查询页面上实际出现的 source hashes，不预加载整个语言字典。
- 页面缓存区分登录、WooCommerce/session、密码、nonce/CAPTCHA、private/no-store 和追踪参数。
- 内容、菜单、term、主题或译文更新时使用 generation 失效，兼容 Redis/Memcached，不执行全局 cache flush。
- Crawler 只请求同一 scheme、host、port 和 WordPress 安装路径，使用签名请求、不跟随重定向，并限制响应大小。

API Key 使用 OpenSSL 加密。OpenSSL 不可用时，插件拒绝保存新的明文 Key，并保留原有安全值。

## 限制

- 动态 JavaScript 应用、会员区、结账、账户、个性化价格和复杂表单默认不适合共享 HTML 缓存，应通过排除规则处理。
- 页面构建器、缓存/CDN 和非标准 Nginx rewrite 仍需逐站测试。
- 自动翻译不能保证产品参数、法律、医疗、认证和商业承诺准确，发布前必须人工审核。
- 低流量站点的 WP-Cron 可能运行不及时，建议配置服务器 Cron。
- 完成度不足的语言不应直接上线索引。

## 与 GML AI SEO 的关系

> If you only need multilingual translation, use GML Translate.
>
> If you want the complete GML SEO suite with technical SEO, search data and AI SEO workflow, use GML SEO.
>
> GML SEO already includes multilingual translation, so installing both is normally unnecessary.

简而言之：只做多语言用 GML Translate；需要完整 SEO、搜索/广告/询盘数据和 AI SEO 工作流用 GML AI SEO。GML AI SEO 已内置翻译能力，通常不需要同时安装两个插件。

## Changelog 与开发

- [完整版本记录](CHANGELOG.md)
- [共享 Translation Core 说明](docs/TRANSLATION-CORE.md)
- 测试：`bash tests/run-all.sh`
- 验证 vendored Core：`php bin/translation-core.php verify`
- 更新 vendored Core：`php bin/translation-core.php sync /path/to/gml-translation-core`

共享 Core 只在开发/构建阶段同步。两个产品锁定同一 Core commit，CI 会阻止发布包发生漂移。

## License

GPL v2 or later.
