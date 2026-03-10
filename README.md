# GML - Gemini Dynamic Translate for WordPress

AI-powered dynamic translation plugin using Google Gemini API with Weglot-style architecture and native i18n hybrid mode.

**作者**: huwencai.com  
**版本**: 2.8.2  

**许可**: GPL v2 or later

---

## 📋 目录

- [核心特性](#-核心特性)
- [系统要求](#-系统要求)
- [快速开始](#-快速开始)
- [语言切换器](#-语言切换器)
- [翻译管理](#-翻译管理)
- [排除规则](#-排除规则)
- [术语表](#-术语表)
- [核心架构](#-核心架构)
- [性能指标](#-性能指标)
- [高级配置](#-高级配置)
- [故障排除](#-故障排除)
- [开发者指南](#-开发者指南)

---

## 🎯 核心特性

### 混合型拦截机制（创新架构）
- **第一层**: WordPress 核心 UI 使用原生 .mo 语言包（节省 Token）
- **第二层**: 自定义内容使用 Gemini AI 翻译（高质量）

### 全站自动翻译（v2.7.0 新增）
- **内容爬虫**: 自动发现所有已发布的页面、文章、产品，无需手动访问即可完成全站翻译
- **优先级机制**: 被访问的页面优先翻译，爬虫作为补充确保未访问页面也能被翻译
- **进度追踪**: 实时显示爬取进度，支持随时启停

### 翻译内容管理（v2.7.0 新增）
- **可视化编辑器**: 类似 Weglot 的翻译管理界面，浏览、搜索、编辑所有翻译
- **手动修改**: 手动编辑的翻译标记为 `manual`，不会被自动翻译覆盖
- **筛选功能**: 按 All / Auto / Manual 筛选翻译内容

### 失败重试（v2.7.0 新增）
- 翻译失败的条目可一键重试（单语言或全部）
- 失败数量在管理面板清晰显示

### 浏览器语言自动检测（v2.8.0 新增）
- **智能重定向**: 首次访问首页时自动检测浏览器语言偏好，重定向到匹配的语言版本
- **Cookie 记忆**: 通过 cookie 记住用户选择，回访不重复重定向
- **SEO 安全**: 自动跳过搜索引擎爬虫，不影响 SEO 索引
- **可选功能**: 管理后台一键开关

### 翻译排除规则（v2.8.0 新增）
- **URL 排除**: 精确匹配、前缀匹配、包含匹配、正则匹配
- **CSS 选择器排除**: 按 class 或 ID 排除特定元素
- **可视化管理**: 管理后台 Exclusion Rules 标签页

### 术语表 / 翻译规则（v2.8.0 新增）
- **固定翻译**: "Always translate X as Y" 规则，确保术语翻译一致
- **按语言设置**: 不同语言可以有不同的翻译规则
- **AI 集成**: 规则注入到 Gemini API prompt 中

### 多语言 XML Sitemap（v2.8.0 新增）
- **自动生成**: `/gml-sitemap.xml` 包含所有语言版本的 hreflang 标注
- **按类型分组**: 页面、文章、产品各自独立的子站点地图
- **robots.txt 集成**: 自动添加 sitemap 链接

### 全局哈希去重
- 基于 MD5 的自动去重
- 页头/页脚相同文本只翻译一次
- 缓存命中率 >95%

### SEO 优化
- SEO 友好的 URL 结构（/en/, /ja/ 等）
- 自动注入 hreflang 标签
- Canonical URL 支持
- 针对 SEO Meta 使用专门 Prompt

### 品牌词保护
- 自动识别品牌词（GML, WordPress 等）
- 翻译时保持品牌词不变
- 三重验证机制

### 异步队列处理
- WP Cron 自动处理
- 批量翻译（30条/批次，按语言+类型分组单次 API 调用）
- 优先级系统（SEO > 属性 > 短文本 > 长文本）
- 错误重试机制（最多 3 次）

### 语言切换器
- 4种显示样式（dropdown, links, flags, buttons）
- Shortcode / Widget / PHP 函数支持
- 自定义 CSS

---

## 📋 系统要求

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Google Gemini API Key

---

## 🚀 快速开始

### 1. 安装插件

上传 ZIP 文件到 WordPress 后台（插件 → 安装插件 → 上传插件），或通过 FTP 上传到 `wp-content/plugins/` 目录。

### 2. 激活并配置

1. 激活插件
2. 进入 WordPress 后台 → GML Translate
3. 输入 Gemini API Key（获取地址: https://makersuite.google.com/app/apikey）
4. 选择源语言和目标语言
5. 保存设置

### 3. 添加语言切换器

```
[gml_language_switcher style="dropdown"]
```

### 4. 开始翻译

**方式 A: 自动翻译全站（推荐）**

进入 GML Translate → Translations 标签页，点击「🚀 Start Auto-Translate」，插件会自动爬取所有内容并加入翻译队列。

**方式 B: 按需翻译**

访问带语言前缀的 URL（如 `https://yoursite.com/en/about/`），页面内容会自动加入翻译队列，1-2 分钟后刷新即可看到翻译。

### 5. 管理翻译

在 Translations 标签页，点击每个语言旁的 ✏️ 按钮，可以浏览、搜索和手动编辑翻译内容。

---

## 🎨 语言切换器

### 显示样式

| 样式 | Shortcode | 说明 |
|------|-----------|------|
| Dropdown | `[gml_language_switcher style="dropdown"]` | 紧凑，适合移动端 |
| Links | `[gml_language_switcher style="links"]` | 清晰易读 |
| Flags | `[gml_language_switcher style="flags"]` | 视觉直观 |
| Buttons | `[gml_language_switcher style="buttons"]` | 现代美观 |

### 自定义选项

```
[gml_language_switcher style="dropdown" show_flags="yes" show_names="yes"]
```

---

## ✏️ 翻译管理

### Translations 标签页功能

- **全局控制**: Start All / Pause All 控制所有语言的翻译
- **单语言控制**: 每个语言可独立启停
- **自动翻译**: 🚀 Start Auto-Translate 爬取全站内容
- **失败重试**: 🔄 Retry Failed 重试失败的翻译
- **缓存管理**: 清除待处理队列 / 清除所有翻译

### 翻译编辑器

点击每个语言旁的 ✏️ 按钮打开编辑器：

- **浏览**: 分页显示所有翻译（每页20条）
- **搜索**: 按原文或译文搜索
- **筛选**: All / Auto / Manual
- **编辑**: 点击 Edit 修改译文，保存后标记为 Manual（不会被自动翻译覆盖）
- **删除**: 删除单条翻译（下次访问页面时会重新加入队列）

---

## 🚫 排除规则

### Exclusion Rules 标签页

在管理后台 GML Translate → Exclusion Rules 中管理翻译排除规则：

| 规则类型 | 示例 | 说明 |
|----------|------|------|
| URL is exactly | `/checkout/` | 精确匹配，排除结账页 |
| URL starts with | `/my-account/` | 前缀匹配，排除所有账户页 |
| URL contains | `cart` | 包含匹配，排除含 "cart" 的 URL |
| URL matches regex | `/^\/api\//` | 正则匹配 |
| CSS selector | `.no-translate` | 排除带此 class 的元素 |
| CSS selector | `#legal-notice` | 排除此 ID 的元素 |

每条规则可独立启用/禁用，支持添加备注。

---

## 📖 术语表

### Glossary 标签页

管理翻译术语规则，确保特定术语在所有页面中翻译一致：

**Protected Terms（永不翻译）**: 品牌名、产品名等永远保持原文不翻译。

**Glossary Rules（固定翻译）**: 指定特定术语必须翻译为指定译文。

| 源术语 | 翻译为 | 语言 |
|--------|--------|------|
| Add to Cart | Agregar al carrito | Spanish |
| Free Shipping | 包邮 | Chinese |
| Contact Us | お問い合わせ | Japanese |

规则会注入到 AI 翻译的 prompt 中，确保 Gemini 遵循。修改规则后建议清除受影响语言的翻译缓存。

---

## 🗺️ 多语言 Sitemap

插件自动生成多语言 XML 站点地图：

- **索引**: `https://yoursite.com/gml-sitemap.xml`
- **子地图**: `https://yoursite.com/gml-sitemap-page.xml`、`gml-sitemap-post.xml`、`gml-sitemap-product.xml`

每个 URL 包含所有语言版本的 hreflang 标注，帮助搜索引擎理解多语言页面关系。自动添加到 `robots.txt`。

---

## 🏗️ 核心架构

### 数据流

```
用户请求 → Output Buffer 拦截 → HTML Parser 提取文本
  → Translator 查缓存 → 命中：直接替换 / 未命中：加入队列
  → Queue Processor (WP Cron) 批量调用 Gemini API → 保存到索引
```

### 核心组件

```
GML_Translate (主类)
  ├── GML_Output_Buffer      (前端 HTML 拦截与翻译)
  ├── GML_HTML_Parser         (HTML 解析与重建)
  ├── GML_Translator          (翻译引擎，哈希去重)
  ├── GML_Gemini_API          (Gemini API 集成)
  ├── GML_Queue_Processor     (异步队列处理)
  ├── GML_Content_Crawler     (全站内容爬虫)
  ├── GML_Translation_Editor  (翻译编辑器 AJAX)
  ├── GML_Language_Detector   (浏览器语言检测) ← v2.8.0
  ├── GML_Exclusion_Rules     (翻译排除规则) ← v2.8.0
  ├── GML_Glossary            (术语表/翻译规则) ← v2.8.0
  ├── GML_Sitemap             (多语言 XML Sitemap) ← v2.8.0
  ├── GML_SEO_Router          (URL 路由)
  ├── GML_SEO_Hreflang        (hreflang 标签)
  └── GML_Language_Switcher   (语言切换器)
```

### 数据库表

| 表名 | 用途 |
|------|------|
| `wp_gml_index` | 翻译记忆库（source_hash → translated_text） |
| `wp_gml_queue` | 异步翻译队列（pending/processing/completed/failed） |

---

## 📊 性能指标

| 指标 | 数值 | 说明 |
|------|------|------|
| 缓存命中率 | >95% | 第二次访问直接读缓存 |
| 页面加载增加 | <200ms | 输出缓冲处理时间 |
| API Token 节省 | ~90% | 批量翻译 + 哈希去重 + 缓存 |
| 队列处理 | 30 条/批次 | 按语言+类型分组，单次 API 调用 |

---

## 🔧 高级配置

### 自定义排除选择器

```php
add_filter('gml_exclude_selectors', function($selectors) {
    $selectors[] = '.my-custom-class';
    return $selectors;
});
```

### 自定义品牌词保护

```php
add_filter('gml_protected_terms', function($terms) {
    $terms[] = 'MyBrand';
    return $terms;
});
```

### 系统 Cron（低流量网站推荐）

```php
// wp-config.php
define('DISABLE_WP_CRON', true);
```

```bash
# 服务器 crontab
* * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

---

## 🐛 故障排除

| 问题 | 解决方案 |
|------|----------|
| 翻译不生效 | 检查 API Key、语言是否启用、URL 是否含语言前缀，等待 1-2 分钟 |
| 队列不处理 | 检查 WP Cron 是否运行，低流量网站建议配置系统 Cron |
| 页面布局错乱 | 清除浏览器/WordPress 缓存，检查主题兼容性 |
| 翻译失败 | 在 Translations 标签页点击 Retry Failed 重试 |
| 自动翻译遗漏 | 使用 🚀 Start Auto-Translate 爬取全站内容 |

详细日志查看: `wp-content/debug.log`

---

## 📄 许可证

GPL v2 or later

**作者**: huwencai.com  
**版本**: 2.8.2  
**最后更新**: 2026-03-09
