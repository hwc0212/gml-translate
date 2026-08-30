# GML Translate 安装指南

## 安装

1. 在 GitHub Release 下载 `gml-translate-2.11.0.zip`。
2. 进入 WordPress 后台“插件 -> 安装插件 -> 上传插件”。
3. 上传 ZIP、安装并启用。
4. 也可以把 ZIP 中的 `gml-translate/` 目录上传到 `wp-content/plugins/`。

Release ZIP 的第一层必须是 `gml-translate/`，不能再套一层父目录。

## 初始配置

1. 进入 `GML Translate -> Settings`。
2. 选择源语言和目标语言。
3. 先开启 Multilingual Site，保存后检查一个目标语言 URL。
4. 只有需要产生新译文时，才配置 Gemini 或 DeepSeek API Key 并开启 AI Translation。
5. 在 Language Switcher 中选择菜单、Widget、短代码或自动位置。
6. 先翻译一个语言和少量页面，检查术语、链接、布局、表单与 SEO Meta。
7. 完善 Glossary 和 Exclusions 后，再启动全站 Crawler。

Multilingual Site 与 AI Translation 是两个独立状态。删除 Key、额度不足或关闭 AI Translation，只停止新 AI 翻译，不会让已有语言 URL 和译文失效。

## 验证

至少检查：

- 语言首页、文章、页面、产品和分类返回 200。
- URL 只包含一次语言前缀和 WordPress 子目录。
- 切换器能从每种语言返回其他语言对应页面。
- canonical 自引用，hreflang 与回链一致。
- 未完成语言 noindex，且不进入 hreflang/sitemap。
- 登录与未登录前台的菜单、表单、购物车和页面构建器组件正常。

## 翻译失败

大量 failed 时不要批量重试：

1. 保持 Queue 暂停。
2. 检查 Provider、Model、Key、额度和账号状态。
3. 执行连接测试；成功只清除熔断，不自动恢复队列。
4. 选择一个语言，重试最多 25 条样本。
5. 样本通过后再逐步恢复。

## WP-Cron

低流量网站可在 `wp-config.php` 禁用访客触发 Cron：

```php
define( 'DISABLE_WP_CRON', true );
```

然后由服务器每分钟请求一次：

```bash
* * * * * wget -q -O - https://example.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

不要把 API Key 放入 Cron URL、命令行参数或日志。

## 升级与回滚

- 升级前备份数据库和 `wp-content`。
- 插件升级保留 `gml_*` option、数据库表、语言 URL、翻译记忆和人工译文。
- 若同时安装 GML AI SEO，先阅读 GML SEO 的显式接管说明；插件不会自动停用或删除 GML Translate。
- 回滚代码前应同时核对数据库 migration 兼容性，优先恢复整站备份或 staging 快照。
