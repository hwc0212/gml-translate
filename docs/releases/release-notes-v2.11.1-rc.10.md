# GML Translate v2.11.1-rc.10

本候选版增强语言选择器，并支持把某个语言连接到另一台服务器上的独立 HTTPS 域名或子域名。

## 主要变化

- 每个目标语言可选择“当前 WordPress 子目录”或“外部域名/子域名”。
- 外部站点可按当前路径映射，或始终打开外站首页。
- 外部语言不会创建本地 rewrite，也不会进入本站扫描、术语处理和 AI 翻译队列。
- 跨域链接不携带 query/fragment，不远程读写另一台服务器。
- 语言切换器增加继承主题、描边、实色外观和下拉方向设置。
- 同路径外站可进入 hreflang；首页模式只在首页建立关系，避免错误 SEO 信号。

## cnxhe.com / cnxhe.cn 配置

1. 在 `cnxhe.com` 将中文设为 External，URL 填 `https://cnxhe.cn/`。
2. 两站 URL 路径完全对应时选择 Match the current path；否则选择 Always open the external homepage。
3. 在 `cnxhe.cn` 反向把英文指向 `https://cnxhe.com/`。两个站点不在同一服务器也可以使用，但必须分别配置。
4. 保持 AI 队列暂停，先在未登录窗口检查首页、产品页和文章页的切换链接、canonical 与 hreflang。

升级不会删除译文、队列、Glossary 或现有 URL。外部模式仅让该语言停止本站路由与 AI 处理；切回 Local 后原数据仍可继续使用。需要回退时先暂停翻译，再恢复旧插件文件。
