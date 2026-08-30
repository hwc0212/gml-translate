# GML Translate

**AI Multilingual Translation for WordPress**

GML Translate 是独立的 WordPress AI 多语言插件，专注解决一件事：以可控成本建立稳定、可维护、可人工修订的多语言网站。

它不包含 GSC、GA4、Google Ads、通用 SEO Audit、重定向、404、性能优化或完整 Schema 管理。这些属于 GML AI SEO。

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
