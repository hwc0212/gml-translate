# GML Translate 2.11.2-rc.14 / Core 0.9.25

2026-11-26。开发候选与隔离验收；不代表生产已部署。本轮不处理 `post:page:11525`，不修改生产站、资料、数据库、缓存、队列或安全/广告配置，不合并或发布 Release。

## 问题与最终行为

隔离 WordPress + WooCommerce 原生 `[product_page]` 页面中已复现：通过商品正常保存入口更改短描述后，商品自身收到 dirty 提示，引用它的聚合页自身版本不变且仍显示 manifest complete / resource_pending=false；真实当前渲染已经读到新摘要，清单却仍含旧摘要。这证明通用的聚合依赖通知缺口，**不能据此确认页面11525的现场唯一根因**。本候选没有新建自动依赖图，也不自动扫描所有聚合页。

原 Core 已提供 `discover(subject, '')`；原后台 priority 操作却会按语言发现缺译、入队并提高优先级，不是“不调用AI”的刷新入口。此次增加独立的“Refresh inventory only — all configured languages”，只重新发现当前资源清单并用既有 TM 重算该资源配置语言：

- 复用现有 authoritative renderer、HTML parser、清单事务、锁、版本 CAS、TM 查询和 readiness/public-eligibility 政策。
- 原有 manual/auto 记录逐行保留；旧摘要成员退出，当前摘要加入；真实缺译只报告，不补译已移除摘要、不生成翻译任务、不修改优先级、暂停、安全断路或回填设置。
- `manage_options`、原 AJAX nonce、资源/版本 HMAC 均必需。过期/篡改快照拒绝；渲染及解析期间观察到的内部缓存 epoch 变化按冲突处理。
- 显式路径在就绪部分写入失败时报告失败并尝试 fail closed，不新建 dirty/retry 工作；旧 discover / 后台恢复默认行为不变。
- 整次操作及其带原有站点密钥签名的源渲染使用最小 GML 初始化，避免原启动路径自修复 AI/回填/缓存任务；不自动 rewrite repair，不自动 reload 普通后台页。未验签的 inventory header 不能绕过正常启动。旧表格行仍是加载时快照，结果文案明确提示这一点。
- Core `c439cb7f9a38d0e6eb30a953297e86b3319ec006`（0.9.25），49 个内嵌源文件与锁定提交一致；Core不是单独安装的 WordPress 插件。Translate ZIP 独立可用。

## 缓存副作用与失败边界

**保存刷新不是零缓存操作。** 原 `invalidate_resources` 会登记该资源的 durable outbox，并 `force_invalidate` 轮换全站内部 `gml_page_cache_generation`。它不等于全局内容清单代际，也不是直接调用 Cloudflare/Nginx purge。其他资源的清单/就绪不改，但其 GML HTML 缓存可能变冷；专项中首次改变清单及五个语言就绪时共轮换6次，相同快照重复刷新没有再次推进。次数不作为固定承诺。

管理员必须明确勾选缓存影响。outbox 涉及该资源当前和保留的历史源/本地语言 URL；不是仅当前语言的一个 URL。既有 Cron consumer 可能稍后经注册 adapter 逐 URL 消费；无 adapter、异常、失败、受阻 URL 或并发 token 变化不会确认成功。仓库只有接口与测试替身；**本轮未核实生产 adapter，更没有证明生产未配置 adapter 或真实外部 purge 成功**。若要求严格零缓存变化，只能不保存，不能关闭一致性保护伪装满足条件。

清单事务与各语言就绪分别提交，**没有全局原子回滚**。注入关系写入失败时旧成员回滚，但后续错误状态/缓存写入可提交；注入部分语言失败时新 manifest 和部分语言行已提交，随后 best-effort 标 stale。显式再次刷新恢复已测试；旧资源/批准/外部缓存的一键反向恢复未实现或演练。持续数据库错误时不能保证 stale 标记也成功，须重新读取实际状态后处理，不手改代际或强制 ready/index。

epoch 围栏只观察已通知的变化，不是产品依赖的原子事务。直接 SQL、自定义字段/构建器缓存、最后检查后的并发依赖写入仍有边界。要求生产处理时，应另行约定页面和相关产品不同时编辑的窗口。

## 验收证据及测试条件

原隔离调查为 2026-11-26 冻结证据：基线8项，候选48项，缓存8项，完整HTTP 3场景/11项；既有7个数据库脚本336项、32个消费端集成脚本及2个Core脚本、186个PHP路径语法检查。新版本交付重新从**最终ZIP解包代码**运行48项、缓存8项、HTTP 3场景/11项，并核对所有包内运行文件与提交源一致；版本、包哈希、完整数据库及静态重跑结果见 [ACCEPTANCE.json](ACCEPTANCE.json)、[PACKAGE.json](PACKAGE.json)和 evidence。

| 专项范围 | 已验证行为 |
| --- | --- |
| 成员和译文 | 旧摘要退出、新摘要及真缺译加入；全部 manual/auto 行不变；历史 TM 保留；四语真缺译按原政策受限 |
| 工作与其他资源 | 已有 pending 哨兵 priority=7、attempts=2 不变；queue/TM/Cron/dirty/retry/backfill/控制摘要不变；无 provider 请求尝试；其他资源清单/成员/各语言就绪不变 |
| 权限和并发 | 管理员成功；匿名/普通用户、无效nonce、无缓存确认、篡改或过期快照拒绝；渲染/解析冲突拒绝，显式重试恢复 |
| 故障 | 渲染失败、关系写入故障、部分语言写入故障均有错误；TM保留，无新重试；再次显式刷新修复一致性 |
| 缓存 adapter | 当前/历史 URL 精确保留；未配置不确认；异常保留；成功全部URL后确认；旧 token 不能确认新变化 |
| 原生HTTP | 源页面经真实PHP/WordPress/Woo渲染；真实管理员Cookie/nonce AJAX；冷调度状态不被该请求修复；伪造签名不能跳过正常启动 |

测试站：NAS internal Docker 网络、无宿主机端口，WordPress7.1 / WooCommerce10.5.3 / PHP8.3.33 / MariaDB10.6.28。仅合成商品、译文、账号和离线假key，不连接生产 DB/缓存/队列/provider，不发邮件或询盘。故障注入使用现有 wpdb query filter，日志中的预期 SQL 故障不是现场故障。

**完整HTTP测试限制不能省略：** `DISABLE_WP_CRON` 和外网阻断用于测试隔离；WooCommerce独立 Action Scheduler 可能在请求结束后另起普通启动请求，最终对照通过其现有 `action_scheduler_allow_async_request_runner` 测试过滤器持住无关异步 runner。该 guard 没有放入产品或安装包，也不建议生产全局禁用任务。候选只保证自己的两条请求路径不主动修复/新增 GML 调度；不能承诺站点其他插件、原有 worker 或独立请求在整个时间窗口不运行。

新增 Woo/native-HTTP 脚本需显式离线 Woo 测试副本和精确的 `GML_INVENTORY_HTTP_HOME`，不是既有 Woo-free CI 自动包含的场景；最终交付另行运行。CI结果只描述其实际脚本范围，不替代 native/browser 结果。

## 新版打包复验结果

- 最终包：104项、440,074 bytes；CRC、全部运行文件与源字节、49个Core源/vendor/锁一致。SHA-256：`2e78cbf07b5dc517e334b5ce3fb0e738ff04b907266ed600877d69f7a82d84f9`。
- 直接加载解包代码：48项候选、8项缓存、3场景/11项原生HTTP全部通过。
- 全量原有数据库套件：122场景 / 2,342项断言，已核对终止标记。静态重跑：32个集成脚本和全部消费端PHP语法通过。
- GitHub CI尚未作为本冻结报告的结论；推送后的精确HEAD、作业和状态在两个PR更新。

## 累计修复与未覆盖项

远端PR此前停在rc.12 / Core0.9.23；此次同时携带本地已有rc.13 / Core0.9.24的scoped JSON-LD context、嵌套/未闭合保护区域和SEO owner canonical抑制修复。此前验收见 [rc.13 REPORT](../v2.11.2-rc.13/REPORT.md)。它们不是本轮新增的生产部署。

rc.12的84页JSON-LD与三语规格现场复测仍只具有原记录范围：[Translate历史复测](https://github.com/hwc0212/gml-translate/pull/3#issuecomment-7152157215)、[Core历史复测](https://github.com/hwc0212/gml-translation-core/pull/1#issuecomment-7152158287)。不能冒充rc.14生产验收，也不把84页JSON-LD结论扩展为84页hreflang、HTTP头或全部缓存节点通过。

本轮未访问生产站或生产SSH，未取得11525当前构建依赖/真实文件/任务日志/adapter回执，未核验其现场唯一根因。没有新的真实浏览器点击/DOM矩阵、真实Redis/Memcached、所有构建器/主题、高负载、真实AI质量/费用或真实外部缓存验收。GML SEO未同步此Core锁，也未验收新的手动入口适配。

## 页面11525停止点、前置条件与恢复

本交付只提供审阅分支和安装包，**不安装、不刷新11525、不重算其就绪、不清缓存**。之后如另获授权：按 `post:page:11525` 重新解析当前 resource ID，不能用历史609直接执行；先核对实时源渲染是否含现行摘要、该资源及审批/URL/outbox/版本前值、TM和队列摘要、锁与并发编辑、真实 adapter 和缓存范围。若源渲染仍旧，应继续查构建器/缓存，停止清单写入。缓存影响需明确接受；任何安装、协调既有调度或生产写入均另行安排。

验收按旧/新成员、TM逐行、缺译、语言版本/长度/关键字段/原因及匿名 robots/canonical/hreflang分别核对，不预设全部语言 eligible。代码回退不撤销已提交清单、语言行、内部代号或外部purge；失败后先读回，不能将错误等同于没有写入。显式再次刷新已在隔离环境恢复，整体旧备份反向恢复仍是未验证缺口。
