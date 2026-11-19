# rc.7 全面复查与 rc.8 修正记录

2026-11-19，本轮复查 GML Translate PR #3 的 ea215793 与 Core PR #1 的 b207d2fb，重点覆盖 readiness 的源快照、译文快照、失败回退和重试读写交接。发现并修正三类问题。当前候选为 **GML Translate 2.11.2-rc.8 / Core 0.9.20**，Core 提交为 f4b582bd031d697e1be39741c49478d6effbc891。

## 审查发现及修复

1. **P1，源快照未变时，旧计算仍能恢复过时的 complete。已修复。**

   rc.7 在每语言事务之前计算译文指纹和完成数量，只在事务里核对源 manifest。确定性交错用例通过正常 TM API 删除关键译文，确认 stale 已提交，但旧 readiness 仍能将机器状态写回 complete。rc.7 基线在该断言上失败。

   现在先锁定并核对 manifest，再锁定当前 readiness 行，然后在该事务内读取指纹和完成数量。两个结果受同一事务及译文失效锁约束。删除、missing-only 补齐、人工修改和操作员停用分别验证，保存后的指纹与实际 TM 一致，人工文字保留。数据库读取错误返回未完成的工作，不继续确认成功。

   此问题已证明机器状态与译文快照会失真。本轮没有证明它曾造成生产页面误公开，关键字段公开资格仍有实时覆盖检查。修复后测试同时检查机器状态与公开资格。

2. **P2，旧失败后的读取可以把累计退避绑定到新原文。已修复。**

   rc.7 的重试记录带有源快照，但该快照取自 discover 返回后的数据库读取。如果此时已有新编辑，旧失败会把自己的累计等待时间附到新版本。新回归用例在 render_error 提交之后、结果读取之前修改原文，旧逻辑未立即处理新版本。

   discovery 和 Store 现在可返回本次尝试的起始或已提交快照，原有 bool/WP_Error 返回契约保留。worker 使用这个结果设置 source_snapshot，后读到的新版本只用于判断是否已过期。未变化资源的正常退避及状态写入回滚后的指数退避另有断言。旧格式兼容、晚 transient 写入、全局代次变化和失败恢复继续通过。

3. **P2，译文失效查询与 readiness 替换存在索引锁反转。已修复。**

   扩大到完整数据库夹具后，真实双进程记录到死锁。译文失效的多表 UPDATE 可能从 language_status 扫描其他同语言行，再等待目标主键；readiness 已持有主键，REPLACE 又等待这些二级索引锁。事务可因此回滚，导致正常的译文删除失败。小库的首次并发检查没有触发这个执行计划。

   现在先按 source_hash 索引物化相关资源 ID，再以 manifest → readiness → 资源字符串的顺序连接，强制 readiness 使用 resource_language 索引并复核当前 manifest 代次。避免从语言状态索引扫描无关资源。原有事务回滚和缓存失效检查保留。修改后相同数据规模的双进程检查及完整数据库套件均通过。

涉及 Core 的 class-resource-readiness.php、class-resource-manifest-discovery.php、class-resource-manifest-store.php 和 class-resource-manifest-manager.php。产品同步这四个文件及锁文件，并递增候选版本以区分安装包和缓存版本。

## 验证结果

| 验证范围 | 实测结果 |
| --- | --- |
| NAS WordPress 7.1.4 / MariaDB 完整套件 | 122 个计数场景、2331 项断言通过，exit 0，stderr 为空 |
| inventory / readiness / retry 专项 | 146 项断言，包含原有源代次、晚回退、失败持久化、暂停及重置保护 |
| 真实双进程 TM 删除与 readiness 写回 | REPEATABLE READ、READ COMMITTED，各覆盖 readiness 已有行和缺失行，共四组；两进程及最终校验均成功 |
| 译文及费用保护 | 既有 fixture 译文全字段不变、付费队列条数不变，人工文字不会被后续自动 upsert 覆盖 |
| 导航浏览器回归 | 376 项 PHP 断言，1508 项 Chromium 检查，通过；覆盖根目录、普通和编码子目录 |
| 插件集成与语法 | 29 个集成脚本、111 个 PHP 文件语法检查通过 |
| 最终 ZIP 激活 | 直接使用此 ZIP，激活、schema、停用均通过；AI 不可用、队列 0、HTTP 尝试 0 |
| 来源与完整性 | 48 个 Core 文件匹配源码、vendor 和 lock；ZIP 103 项 CRC 与源码字节核验通过 |

完整套件还涵盖激活、旧版本升级、并发请求、人工审批、公开资格、队列关闭、卸载保留及明确删除流程。新竞态既有确定性交错用例，也有两个独立 PHP/数据库连接的测试。数据规模扩大后的死锁记录作为本地调查证据保留，最终验收重新运行完整套件，没有跳过失败场景。

## 安全、兼容与性能边界

没有数据库结构或现有译文迁移，公开 URL 和就绪阈值不变。付费翻译与 inventory 恢复保持分离。本轮不改变人工权限或审核要求。

源页网络渲染仍在数据库事务之外。每语言事务内读取指纹与计数，较 rc.7 的批量预读取增加后台查询，事务仅锁相关资源。失效查询按哈希索引筛选资源，保留原有反向关联范围。未做生产压力基准，不能保证所有数据库执行计划和任意外部 SQL 写入均不存在锁冲突。

NAS 使用 PHP 8.3.33，GitHub 的 PHP 7.4、8.3 与 WordPress/MariaDB 套件最终结果见 PR 检查页。本轮未独立验证 MySQL 服务器，也未完成生产 25 款商品清单、真实 CF7 提交或邮件投递验收。源页 HTTP 使用隔离夹具，浏览器为隔离 Chromium 回归，不能冒充生产 HTTP 验收。没有调用付费提供商，没有合并、打标签、发布 release 或上线。

## 交付

[插件 PR #3](https://github.com/hwc0212/gml-translate/pull/3) · [Core PR #1](https://github.com/hwc0212/gml-translation-core/pull/1) · [安装包](gml-translate-2.11.2-rc.8.zip) · [校验文件](gml-translate-2.11.2-rc.8.zip.sha256) · [验收 JSON](review-results.json)

安装包共 422481 字节，SHA-256 为 d1af2261dce1a8b143c52078e88d33b0f327cd10c554aaa14fd6be8459d4f358。历史 rc.7 交付保留。
