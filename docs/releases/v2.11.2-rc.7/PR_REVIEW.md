# rc.6 第二轮复查与 rc.7 修复记录

2026-11-19，复查插件 PR #3 的 94e1042 与 Core PR #1 的 551df4e，聚焦 rc.6 的快照写入、重试兼容和路径编码改动。发现两个仍缺少保护的晚回调边界，已修复并补测。当前候选为 **GML Translate 2.11.2-rc.7 / Core 0.9.19**。

1. **P1，readiness 阶段仍可能影响较新的源快照。已修复。**

   rc.6 的发现阶段已加入版本校验，但每语言 readiness 写入仍基于较早读取的 manifest，无条件替换状态。夹具在首种语言提交后完成了一轮较新的发现，旧计算随后将其他语言写回旧代次。另一条路径是旧 readiness 写入失败后的回退，它仍会无条件将较新 manifest 标为 stale。

   每语言写入现在先按主键锁定 manifest 并校验源快照。回退失效也绑定刚提交的源快照，遇到新版本返回 gml_resource_changed。readiness 和回退写入同时失败时仍保留待处理资源。相关文件为 Core src/class-resource-readiness.php 和 src/class-resource-manifest-store.php。

2. **P2，较晚写回的旧重试记录可能重新阻塞新编辑。已修复。**

   rc.6 在编辑钩子中清理重试记录，但旧失败任务可能在清理之后才完成 transient 写入。夹具在旧任务写入退避前修改同一资源，旧记录随后恢复，下一轮跳过了已编辑资源。

   重试记录现在携带源快照标识，使用退避前核对 manifest。较旧记录即使晚到，也会失效。旧格式记录可以补全标识，内容未变化时继续遵守原退避。相关文件为 Core src/class-resource-manifest-manager.php。

| 验证 | 本轮结果 |
| --- | --- |
| WordPress/MariaDB inventory 专项 | 116 项断言通过，stderr 为空 |
| 较新发现插入旧 readiness 的语言写入之间 | 新代次状态保留，旧任务返回源已变化 |
| readiness 失败后的较晚回退 | 较新 manifest 保留 |
| readiness 与回退 SQL 均失败 | 待处理资源仍持久保留 |
| 原文编辑插入旧重试记录写入之前 | 下一轮立即处理新源内容 |
| 旧退避、暂停、重置、译文及付费队列保护 | 回归通过 |
| 导航回归 | 376 项 PHP、1508 项 Chromium 检查通过 |
| 插件回归与语法 | 29 个集成脚本、111 个 PHP 文件通过 |
| 安装包来源 | Core 源码、vendor、锁文件、ZIP CRC 及文件字节核对通过 |

本轮以 NAS 隔离 WordPress 7.1.4/MariaDB 数据库运行。源页响应使用夹具，新竞态通过查询和 transient 过滤器安排确定性交错。测试核对夹具已有译文逐字和状态一致，付费队列条数一致。浏览器回归仍覆盖根目录、普通子目录、编码空格及编码 UTF-8 子目录。先前 WooCommerce 图库与接口验收保留在历史候选材料中。

每语言事务新增一次按 manifest 主键的锁定读，外部渲染请求位于这些事务之外。源快照检查只对已有重试记录增加读取，沿用现有有界任务批次。本轮未做生产压力测试或生产清单逐项验收。GitHub PHP 7.4、8.3 和数据库回归的最终状态见 PR 检查页。

候选版本递增以便区分本次修复，并更新页面缓存与前端资源版本。历史 rc.6 安装包保留。

[插件 PR #3](https://github.com/hwc0212/gml-translate/pull/3) · [Core PR #1](https://github.com/hwc0212/gml-translation-core/pull/1) · [rc.7 安装包](gml-translate-2.11.2-rc.7.zip) · [SHA-256](gml-translate-2.11.2-rc.7.zip.sha256) · [验证 JSON](review-results.json)

Core 提交为 b207d2fb65924f01c12dc89de5efa49b32e3de97。安装包 SHA-256 为 4cb13ea88b32b9064b7c9673f5d06332fdc1a2454f3dad7e255e258af39c450a，共 421887 字节。
