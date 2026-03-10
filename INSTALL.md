# GML 安装指南

GML - Gemini Dynamic Translate 插件的安装和配置指南。

---

## 📦 安装方法

### 方法 1: WordPress 后台上传（推荐）

1. 下载插件 ZIP 文件（`gml-translate-v2.8.0.zip`）
2. 登录 WordPress 后台
3. 进入 **插件 → 安装插件 → 上传插件**
4. 选择 ZIP 文件，点击 **"现在安装"**
5. 安装完成后点击 **"激活插件"**

### 方法 2: FTP 上传

1. 解压 ZIP 文件
2. 上传 `gml-translate` 文件夹到 `wp-content/plugins/`
3. 在 WordPress 后台 → 插件 → 激活

---

## ⚙️ 初始配置

### 步骤 1: 获取 Gemini API Key

1. 访问 https://makersuite.google.com/app/apikey
2. 登录 Google 账号，点击 "Create API Key"
3. 复制生成的 API Key

### 步骤 2: 配置插件

1. 进入 WordPress 后台 → **GML Translate** → **Settings** 标签页
2. 粘贴 API Key
3. 选择源语言（网站原始语言）
4. 添加目标语言（要翻译成的语言）
5. 点击 **"保存设置"**

### 步骤 3: 添加语言切换器

在页面/文章中添加 Shortcode：
```
[gml_language_switcher style="dropdown"]
```

或在主题中使用 PHP：
```php
<?php
if (function_exists('gml_language_switcher')) {
    gml_language_switcher(['style' => 'flags']);
}
?>
```

### 步骤 4: 启动翻译

进入 **GML Translate → Translations** 标签页：

- 点击 **▶ Translate All** 启动翻译队列
- 点击 **🚀 Start Auto-Translate** 自动爬取全站内容（无需手动访问每个页面）

### 步骤 5: 管理翻译

- 点击每个语言旁的 **✏️** 按钮，打开翻译编辑器
- 可以浏览、搜索、手动编辑翻译内容
- 手动修改的翻译不会被自动翻译覆盖

---

## 🔍 验证安装

### 检查数据库表

在 phpMyAdmin 中确认以下表已创建：
- `wp_gml_index` — 翻译索引
- `wp_gml_queue` — 翻译队列

### 检查 WP Cron

安装 "WP Crontrol" 插件（可选），确认以下事件已注册：
- `gml_process_queue` — 每分钟（翻译队列处理）
- `gml_crawl_content` — 每2分钟（内容爬虫，仅在启动自动翻译后出现）

### 测试翻译

访问 `https://yoursite.com/en/`（或其他语言前缀），页面内容应开始翻译。

---

## 🚨 常见问题

### 安装失败: "No valid plugins were found"

ZIP 包目录结构不正确。确保 ZIP 根目录直接是 `gml-translate/` 文件夹（不能有额外的父目录）。

### 激活失败

- 检查 PHP 版本 ≥ 7.4
- 检查 WordPress 版本 ≥ 6.0
- 查看 `wp-content/debug.log`

### API Key 无效

- 确保 API Key 没有多余空格
- 确认 API Key 已启用且配额未用完
- 重新从 Google AI Studio 获取

### 翻译不生效

1. 确认 URL 包含语言前缀（如 `/en/`）
2. 等待 1-2 分钟让队列处理
3. 清除浏览器缓存
4. 在 Translations 标签页检查队列状态

### 翻译失败（failed）

在 Translations 标签页点击 **🔄 Retry Failed** 重试。常见原因：
- API 配额用完
- 网络超时
- 文本过长

### WP Cron 不运行（低流量网站）

在 `wp-config.php` 中添加：
```php
define('DISABLE_WP_CRON', true);
```

在服务器 crontab 中添加：
```bash
* * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

---

## 🔧 升级说明

### 从 v2.7.x 升级到 v2.8.0

1. 在 WordPress 后台停用旧版插件
2. 删除旧版插件
3. 上传并安装 `gml-translate-v2.8.0.zip`
4. 激活插件

升级不会丢失已有的翻译数据（存储在数据库中）。

### v2.8.0 新功能

- **浏览器语言自动检测**: 首次访问自动重定向到匹配的语言版本
- **翻译排除规则**: 按 URL 或 CSS 选择器排除特定页面/元素
- **术语表**: "Always translate X as Y" 规则，确保翻译一致性
- **多语言 XML Sitemap**: 自动生成包含 hreflang 的站点地图

### 从 v2.6.x 升级到 v2.7.0

1. 在 WordPress 后台停用旧版插件
2. 删除旧版插件
3. 上传并安装 `gml-translate-v2.7.0.zip`
4. 激活插件

升级不会丢失已有的翻译数据（存储在数据库中）。

### v2.7.0 新功能

- **自动翻译全站**: 无需访问每个页面，一键爬取全站内容
- **翻译编辑器**: 浏览、搜索、手动编辑翻译（类似 Weglot）
- **失败重试**: 一键重试失败的翻译

---

**版本**: 2.8.0  
**最后更新**: 2026-03-08
