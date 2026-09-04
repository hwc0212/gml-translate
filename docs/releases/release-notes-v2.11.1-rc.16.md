# GML Translate 2.11.1-rc.16 发行说明

## 本版目标

本候选版完成 Phase 2C：在现有 resource manifest 与机器 readiness 之上增加资源级人工审核。机器判断“翻译完整”只代表当前文本覆盖满足规则，不再被误认为已经由人工确认或可以公开发布。

## 主要变化

- 新增独立 **Review** 页面，管理员按资源和目标语言分页检查源文与当前译文。
- 只允许逐项批准或拒绝，不提供批量批准；批准前必须勾选确认，拒绝必须填写原因。
- 决定绑定当前 manifest generation、manifest fingerprint、global generation 和 translation generation。
- 源内容、当前 manifest 或公开译文变化后，旧决定自动标记为 `stale`。
- 内容完全相同的权威重复扫描保持幂等，不会仅因重扫而撤销有效批准。
- 人工决定写入当前状态表，同时追加不可覆盖的审计事件；审计不复制页面正文或译文。
- 译文修改、readiness 失效和批准 generation 提升使用同一数据库事务，失败时整体回滚。

## 数据与升级

数据库版本升至 `3.1.0`，只新增以下三张表：

- `gml_resource_translation_versions`
- `gml_resource_reviews`
- `gml_resource_review_audit`

不重命名或删除既有 Translation Memory、人工译文、Queue、manifest、readiness、语言、术语表、排除规则或 Provider 配置。默认卸载策略继续保留新增审核数据；只有管理员预先确认永久删除并真正卸载最后一个 GML 翻译宿主时才会清理。

## 明确边界

Phase 2C 仍是 **shadow-only**。批准或拒绝不会：

- 自动发布或隐藏语言页面；
- 修改路由、canonical、hreflang、Sitemap 或语言切换器；
- 启动 AI、恢复暂停队列或调用外部 Provider；
- 改变现有匿名访问行为。

公开 eligibility 门控属于后续 Phase 2D，必须另行审计、测试和授权。

## 测试

- WordPress 7.1 与 MariaDB 10.11 根目录及 `/ygnaglul` 子目录回归。
- 13 万条历史 Queue 与 5.2 万条人工译文升级校验，字节校验和保持不变。
- 批准、拒绝、相同重扫、源内容变化、译文变化、失败事务回滚和审计历史测试。
- Review 列表保持两条有界查询，详情文本分页读取。
- 默认保留与确认永久删除两种卸载路径。
- 所有测试禁止真实外部 AI 与 HTTP 请求。

## 安装后建议

本版仅用于继续验证 Review 状态机。先在测试站查看少量机器完整资源，分别验证批准、拒绝和译文修改后的 `stale` 状态。不要把 Review 中的 Approved 当作已经对公众开放；Phase 2D 完成前，前台策略仍维持原状。
