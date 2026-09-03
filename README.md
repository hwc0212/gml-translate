# GML Translate

**AI Multilingual Translation for WordPress**

GML Translate 是独立的 WordPress AI 多语言插件，专注解决一件事：以可控成本建立稳定、可维护、可人工修订的多语言网站。

它不包含 GSC、GA4、Google Ads、通用 SEO Audit、重定向、404、性能优化或完整 Schema 管理。这些属于 GML AI SEO。

## 2.11.1-rc.15 当前内容翻译范围候选版

- Translation Core 0.7.2 以当前完整 resource manifest 中的唯一原文 hash 计算语言进度和公开语言就绪度；网站改版后已删除或替换的旧文本不再长期压低完成率。
- 普通翻译批次、按语言最多 25 条的有限重试和安全熔断只处理当前网站仍需要且 Translation Memory 尚未满足的文本；旧 pending/failed 行不再重复消耗 token 或重试额度。
- 旧失败、旧 pending、Translation Memory、人工译文、术语表及审计明细全部保留，不做清库、迁移或静默删除。后台将不再相关的失败标为 `stored history`，并把当前 pending、failed 与 `not queued` 分开显示。
- 只有全站当前内容清单完成且没有当前渲染错误后，才启用新范围；重建期间公开语言 readiness 保持 fail-closed，队列继续沿用旧范围，避免部分扫描误放行 hreflang 或索引。
- 前台 canonical/hreflang 判断只读取带短时缓存的轻量当前覆盖率，不扫描历史队列表；当前失败计数也短时缓存并随队列、译文、manifest 和 backfill 变化失效。
- 语言公开就绪仍要求当前关键 SEO 文本全部完成且当前文本覆盖率至少 95%。这是机器就绪判断，不代表人工审批。

升级不会启动 AI、恢复暂停任务、重试失败、删除旧数据或改变 URL。首次完成当前内容清单后，像 CNXHE 改版遗留的历史错误仍可审计，但不会继续占用当前错误和进度名额。

## 2.11.1-rc.14 持久化 Readiness 失效与恢复候选版

- Translation Core 0.7.1 删除了会被不同高扇出 source hash 相互覆盖的单一 continuation 游标；现有 resource readiness 数据库行本身就是持久化待处理状态。
- Translation Memory 保存或删除后，用一条索引化 SQL 将所有当前关联的资源/语言行先原子标记为 `stale`，再异步计算；后台任务丢失或崩溃只会延迟恢复，不会留下假 `complete`。
- 通用 worker 每批最多认领 500 行，继续使用 owner-token lease；崩溃留下的 `rebuilding` 行在租约超时后可由后续 worker 从数据库直接恢复。
- 多个 hash 同时变化、同一 hash 重复变化，以及同一资源引用多个变化 hash 时，均由现有 resource/language 唯一行去重；较新的 `stale` 状态会阻止旧 worker 写回过期结果。
- 数据库只在现有 readiness 表增加 `status_id (status, id)` 索引。Translation Memory、人工译文、队列、术语表、排除规则、URL 和暂停状态均不迁移、不删除。
- 本版继续严格保持 shadow-only，不改变公开路由、canonical、hreflang、Sitemap、语言切换器、readiness 阈值或 AI Provider。

## 2.11.1-rc.13 资源清单与机器 Readiness Shadow 候选版

- Translation Core 0.7.0 为页面、文章、产品、公开 CPT、taxonomy、首页/文章页角色和明确支持的 archive 建立类型化 resource manifest。
- readiness 只根据当前资源的 required hash 判断：SEO 关键字符串必须全部存在，全部字符串至少完成 95%；语言全局完成度不再替代具体页面状态。
- 同源匿名签名渲染必须返回无跳转的完整 HTTP 200 HTML 才能认证 manifest；失败保持 `render_error`，不会误报 complete。
- 源内容变化立即只将对应资源标为 stale；共享菜单、主题、widget 和相关站点设置提升全局 generation 后由每批最多 5 个资源的后台任务重建。
- AI 关闭、无 API Key、Provider 不可用或 Queue 暂停时，manifest、Translation Memory 查找与 readiness 仍正常运行，并且 backfill 不会自动启动 AI。
- 这是 shadow infrastructure，不是人工审批。公开 canonical、hreflang、Sitemap、语言切换器和匿名访问保持原有行为。

## 2.11.1-rc.12 Queue 与 Crawler 原子锁候选版

- Translation Core 0.6.2 以数据库 CAS lease 取代过期锁的 `get/delete/add` 流程，并为语言导入增加幂等的延迟 rewrite 刷新；批量导入不逐项 flush，相同路由不重复 flush。
- owner token 同时约束续租、释放和 Queue `processing` 恢复；旧 worker 在 lease 过期后不能删除新锁、恢复活动任务、保存迟到的 AI 结果或覆盖当前批次状态。
- 旧版 Queue array 与 Crawler integer 锁会在自然过期后原子接管，不需要清表、清队列或迁移 Translation Memory。
- 不修改 Provider、prompt、URL、readiness、canonical、hreflang 或 sitemap；升级不会自动启动 AI 翻译。
- 语言切换 shortcode 使用纯字符串渲染，可安全运行在其他插件的 output-buffer callback 内；widget、菜单和自动位置继续复用同一输出。

## 2.11.1-rc.11 卸载数据保留候选版

- Settings 底部新增 **Uninstall Data Retention**。默认保留全部设置、加密凭据、语言配置、术语表、队列、人工译文和翻译库，停用、升级或删除后重新安装仍可继续使用。
- 只有管理员选择永久删除并准确输入 `DELETE` 后，WordPress 从 Plugins 页面真正删除插件时，才清理翻译表、旧版兼容表、options、任务、页面缓存和专用缓存目录。
- 选择永久删除只保存未来的卸载行为，不会立即删数据、清缓存、停止队列或调用 AI；可随时切回默认保留模式。
- GML SEO 仍安装时，共享翻译数据不会由 GML Translate 删除。若最终需要清除共享数据，必须在最后保留的 GML 产品中启用永久删除后再删除该产品。
- 多站点按各站点自己的选择处理；卸载不会删除 WordPress 标准内容字段，例如媒体库的图片 ALT。

## 2.11.1-rc.10 跨站点语言与切换器候选版

- 每个目标语言可以选择由当前 WordPress 站点的子目录提供，或链接到独立 HTTPS 域名/子域名，例如英文站 `cnxhe.com` 与中文站 `cnxhe.cn`。
- 外部站点支持“匹配当前路径”和“始终打开外站首页”两种映射。只有两个站点存在等价路径时才使用同路径；首页模式不会错误地把外站首页声明为每个内页的 hreflang 对应页。
- 外部语言不会注册成本地 `/zh/` 路由，也不会进入本站 Crawler、Glossary 或 AI Translation Queue；已有本地译文和队列记录不会被删除，切回本地模式即可继续使用。
- 跨域链接只接受无账号、无 query、无 fragment 的 HTTPS 基础 URL，切换时不复制 `gclid`、`utm_*` 等参数，也不会向远程服务器写入内容或设置。
- 语言切换器新增继承主题、描边、实色三种外观，以及自动/左/右下拉对齐；默认仍继承主题，避免升级后改变前台样式。

这是跨站导航与 SEO 关系配置，不是跨服务器内容同步。两个独立站点必须分别维护内容，并在另一台服务器上配置反向语言关系。

## 2.11.1-rc.9 增量同步与 Provider 对齐候选版

- 编辑并保存已发布页面、文章或产品后，插件会记录一次轻量增量扫描。保存动作本身不调用 AI；后续 Cron 只发现新文本并生成新 `source_hash`，不会自动恢复已暂停的队列。
- Google Gemini、DeepSeek、Qwen 和 OpenAI 均可用于翻译。四家凭据分别加密保存，模型和端点互不串用；保存 Key 仍不等于真实连接测试成功。
- 相同文本在同一批次只发送一次；术语表和保护词只发送当前批次实际命中的规则，短标题和 Meta 使用更小的有界输出预算，减少无效 token 而不省略翻译上下文。
- `<40°C`、`<70%`、`90*45*30mm` 等纯技术参数直接保留，不再消耗 AI token；带说明的完整句子仍正常翻译。
- 原文修改不会覆盖旧翻译历史。新 hash 等待翻译期间，旧译文仍可用于回滚和 Translation Memory，但页面 readiness 会按当前文本重新判断。

本候选版不会自动启动生产队列或补齐现有缺译。升级后先保持暂停，检查增量扫描和一个语言的小批量，再由管理员决定恢复范围。

## 2.11.1-rc.8 翻译失败恢复候选版

- Translation 页面现在把失败分成“上次连接测试前已确认的历史记录”和“测试后新增错误”，并提供最近 20 条脱敏明细。历史失败总数是数据库记录，不等于本次运行实际发送的 API 请求数。
- `429`、5xx、网络和超时属于临时错误：插件保留 pending 状态、不增加任务 attempts，按服务商 `Retry-After` 与有界指数退避自动等待，冷却结束后由原有 Cron 继续。
- `400`、认证/权限失败和模型不存在属于配置错误：继续触发安全暂停。修正配置并测试成功后，队列仍保持暂停，由管理员选择一个语言继续正常 pending，或对 failed 每次重试最多 25 条。
- 明细会将旧的 HTTP 400/404/429、“Empty translation result”等记录归类；明确重试时先对照 Translation Memory，把已有译文的旧失败行标记为已解决，不重复调用 AI。
- 全语言 readiness 采用 95% 存储覆盖阈值，不再因极少数历史失败关闭整种语言；每个前台页面仍须具备完整 SEO 标题/描述和至少 95% 页面译文，否则保持 `noindex` 且不输出 hreflang。
- 新文字入队时立即失效 readiness 与页面缓存，避免旧的索引状态滞留。升级不改表、不清队列、不删译文、不改 URL，也不会自动恢复生产翻译。

建议先安装在测试站，运行一次已保存 AI 连接测试，再查看“新增失败”和明细。已有大量历史失败或未完成译文的网站仍需按语言分批处理，本版本不会自动补齐网站内容。

## 2.11.1-rc.7 Gemini 测试与缓存确认候选版

- “刷新页面缓存”先打开确认框，必须输入大写 `REFRESH` 再确认。空输入、错误输入、取消均不刷新；后端同样校验文本、权限和 nonce，旧版清空缓存请求也不能绕过。
- 刷新只失效已生成的多语言页面缓存，不删除译文、人工修改、词库或队列，不解除翻译暂停，不触发 AI 重译。
- 修复连接测试输出预算过小的问题：独立翻译原为 20 token，SEO 设置测试原为 8 token，现为统一的 1024 token 有界预算，测试不自动重试。
- 共用 Gemini 响应解析：合并最终答案的多个文本片段，跳过思考片段；不再只读第一个 part。截断、内容拦截和空答案分别报错，不把部分内容作为完整译文或 SEO 结果保存。
- 错误信息仅显示允许的结束原因及服务商返回的 token 数量，不展示 Key、思考文本或原始响应。具体线上失败原因需要看新的诊断，旧的“没有文本”不足以认定 Key 无效。
- 不更换已保存模型、不添加未经确认的模型参数、不改变 RC6 的队列范围与暂停逻辑；安装或成功测试不会自动启动翻译。

安装前备份并暂停任务。本候选包通过隔离数据库、模拟响应和本地后台测试，未代替生产站点执行真实 API 测试或刷新缓存。回退先暂停全部，再恢复旧插件文件，保留数据库。

响应格式依据：[Google Gemini GenerateContent 接口](https://ai.google.dev/api/generate-content)及[思考片段说明](https://ai.google.dev/gemini-api/docs/generate-content/thinking?hl=en)。

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
- 真实 404 不显示 shortcode、自动位置或导航菜单语言切换器，也不输出 multilingual canonical/hreflang；不会再从 `/missing/` 制造 `/de/missing/` 等无效入口。有效页面只有在 SEO 关键文本完整且页面译文达到 95% 后才发布 reciprocal hreflang。
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

## 卸载与数据保留

WordPress 原生删除插件流程不能在点击 Delete 后显示插件自己的选择框，因此卸载策略需要预先在 **GML Translate → Settings → Uninstall Data Retention** 中保存。

- **Retain all plugin data（默认、推荐）**：删除插件文件，但保留翻译库和设置，重新安装后可以继续使用。
- **Permanently delete all plugin data**：必须输入 `DELETE` 才能启用；仅在随后真正删除插件时执行，且无法恢复。
- 停用插件和普通更新永远不会删除持久数据。

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
- Qwen
- OpenAI

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

## 独立域名语言配置

以 `cnxhe.com` 为英文国际站、`cnxhe.cn` 为中文国内站为例：

1. 在 `cnxhe.com` 的 GML Translate → Settings 中添加简体中文，Delivery 选择 External domain or subdomain，URL 填 `https://cnxhe.cn/`。
2. 若两站页面 slug 一致，Page Mapping 选择 Match the current path；否则选择 Always open the external homepage。后者只在首页输出跨域 hreflang，避免制造错误的一对一页面关系。
3. 在 `cnxhe.cn` 上独立安装并配置插件，把英文设为外部语言并指向 `https://cnxhe.com/`，形成双向切换与 reciprocal hreflang。
4. 在未登录窗口检查首页和代表性内页。确认链接、canonical 和 hreflang 后再启用浏览器语言自动跳转。

插件不会登录或修改另一台服务器，也不会自动复制页面。若两站路径不同且需要内页一一对应，应先统一 URL 结构；当前版本不会猜测不同 slug 的页面关系。
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
