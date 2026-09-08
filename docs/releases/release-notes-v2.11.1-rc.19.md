# GML Translate 2.11.1-rc.19

这是针对网站改版后翻译不同步问题的测试候选版，不是 stable release。

## 本次修复

- 当前页面必须达到 100% 翻译覆盖才向匿名访客公开，避免新英文与旧译文混合。
- 重新扫描只让新增或变化字符串进入 Queue，未变化译文和人工译文继续复用。
- 删除内容退出当前 manifest、readiness 和前台渲染，但历史 Translation Memory 与失败记录保留。
- 修改链接、SEO title、SEO description 或正文结构后，以当前权威 HTML 为准。
- GeneratePress、Oxygen、Elementor、WordPress reusable block/FSE template 的保存或删除会触发全站 manifest 重新发现。
- Human Review 默认改为可选；明确拒绝的当前快照仍然阻止公开。

## 安全与性能

- 不变更数据库结构，不删除翻译，不清空历史记录，不自动恢复 Queue。
- 页面保存请求只记录失效状态，不同步抓取页面，也不调用 AI。
- 后台按小批次重新发现页面；普通前台不扫描历史 Queue。
- 测试覆盖 WordPress 7.1、PHP 8.3、MariaDB 10.11、Redis、根目录和子目录。

## CNXHE 测试副本验收

1. 安装 RC 后保持 AI Queue 暂停，先执行当前内容扫描。
2. 检查首页、主要产品页、产品分类、Contact 和改动较大页面的 manifest/readiness。
3. 未完整页面的匿名语言 URL 应 302 到对应英文源页面，管理员预览应带 `noindex`。
4. 只对一个语言启动少量新增字符串翻译，不要清空 Translation Memory，也不要全站重译。
5. 完成后确认目标页不含新英文、旧宣传段落或旧链接。
6. 检查 canonical、完整 reciprocal hreflang、语言选择器及 Sitemap 只包含已完整页面。
7. 观察 WordPress page cache、Redis/object cache 和 CDN，确认没有继续返回旧页面 HTML。

只有测试副本验收通过后，才考虑生产部署或 OzonGenerators 迁移演练。
