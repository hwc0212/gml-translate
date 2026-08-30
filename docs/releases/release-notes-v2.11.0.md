# GML Translate v2.11.0

本版把产品名称统一为 **GML Translate - AI Multilingual Translation for WordPress**，并重点解决两个长期风险：AI 故障不应让已翻译页面消失，以及 GML SEO 与独立翻译插件不应各维护一份逐渐分叉的核心代码。

## 本版重点

- 对外名称从 Gemini Dynamic Translate 改为 GML Translate。插件目录、text domain、option、数据库表和语言 URL 保持不变，不影响旧安装。
- 与 GML SEO 使用同一个 Translation Core 源码，并在构建时完整放入独立 ZIP；不增加 Composer、npm、submodule 或第三插件依赖。
- Multilingual Site 与 AI Translation 分成两个开关。关闭 AI 或删除 Key 后，已有 URL、译文、切换器、人工译文、hreflang 和 sitemap 继续工作。
- 两个插件同时启用时，GML Translate 暂时保留多语言运行，GML SEO 负责最终 canonical、meta、Open Graph、Schema、hreflang 和 sitemap，避免重复输出。
- Settings API 注册从超大后台渲染类中拆出，为后续逐步拆分 Controller、View 和 Service 建立边界。

## 安全改进

- API Key 只使用加密 option 保存；OpenSSL 不可用时拒绝写入新明文 Key。
- Provider 请求使用精确官方 HTTPS host、Header 认证、零重定向、大小限制和错误脱敏。
- Crawler 使用签名同站请求、子目录边界、并发锁和 512 KB 响应上限。
- 队列增加进程锁、失败分类、熔断和人工小批量恢复，不再提供“重试全部”。

## 性能改进

- 普通前台只执行路由、当前页译文 lookup、缓存读取和轻量输出。
- 当前页只查询实际 source hash，不再把整种语言字典载入内存。
- Redis/Memcached 使用 generation 失效，不执行全局 cache flush。
- Provider batch、Prompt、输出、规则和 Worker 均有明确上限。
- 术语表、排除规则、selector 和保护词等可能增长的数组不再进入 WordPress 全局 autoload；旧站点会执行一次版本化迁移。

## 升级与恢复

1. 升级前备份数据库，尤其是 `gml_*` 表和 option。
2. 升级后先保持 AI Queue 暂停，验证源语言和一个目标语言页面。
3. 检查语言首页、文章、产品、分类的 URL、canonical、hreflang、回链和 sitemap。
4. 测试 Provider 后，只对一个语言重试最多 25 条。
5. 若与 GML SEO 同时安装，先在 GML SEO 中阅读接管说明；不会自动停用或删除 GML Translate。

## 验证

- Shared Core：`0.4.2`，锁定 commit `85c952135ac0`。
- 已通过全部 PHP 语法、JavaScript 语法、Core lock 和 GML Translate 集成测试。
- Release ZIP 包含全部运行代码，不要求额外安装 GML SEO 或 Translation Core。
