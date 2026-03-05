# 生成流程逻辑说明

本文描述当前项目从“创建生成任务”到“任务完成回调”的完整链路，覆盖入口、队列、服务商提交、回退策略、状态流转与回调语义。

## 1. 参与模块

- `app/Http/Controllers/v1/ModelController.php`
  - 入口控制器：接收创建请求与服务商回调请求。
- `app/Logic/v1/ModelLogic.php`
  - 业务编排：幂等创建任务、投递队列、处理完成回调。
- `app/Jobs/GenerateSubmit.php`
  - 异步提交任务到上游服务商，处理主服务商失败后的回退。
- `app/AIModels/ModelDispatch.php`
  - 模型分发器：按模型名路由到具体模型实现。
- `app/AIModels/bytedance/seedream/v4_5/TextToImage.php`
  - 具体模型实现：参数校验、按服务商转发请求。
- `extensions/src/API/Fal.php`、`extensions/src/API/WaveSpeed.php`
  - 服务商 API 适配层：发起 HTTP 请求、解析上游响应。
- `app/Support/WebhookNotifier.php`
  - 通用回调发送器：统一向业务侧发送回调通知。
- `app/Models/TaskRecord.php`
  - 任务落库模型：任务状态、输出、错误信息、耗时等字段。

## 2. 创建任务（业务侧 -> 本系统）

入口：`ModelController::generate()`

### 2.1 参数校验

主要字段：

- `app_id`：应用 ID（ULID，且必须存在）
- `provider`：主服务商（`wavespeed` / `fal`）
- `model`：模型标识（必须在配置中存在）
- `webhook_url`：业务侧回调地址（必填 URL）
- `custom_id`：业务侧任务 ID（应用内唯一）
- `parameters`：模型输入参数
- `fallback_provider`：回退服务商（可选）
- `delay_generation`：延迟执行秒数（0~120，可选）

### 2.2 幂等创建

`ModelLogic::generate()` 先按 `(app_id, custom_id)` 查询：

- 若已存在：直接返回已创建任务，不重复建单。
- 若不存在：创建 `task_record`，初始状态 `IN_QUEUE`。

### 2.3 投递异步任务

创建成功后投递 `GenerateSubmit` 队列任务，参数包括：

- 任务 ID
- 模型名
- 主服务商
- 参数
- 业务侧 `webhook_url`
- 回退服务商（可空）

若 `delay_generation > 0`，按秒延迟执行。

## 3. 提交流程（本系统 -> 上游服务商）

入口：`GenerateSubmit::handle()`

### 3.1 前置处理

- 记录 `startedAt`（秒级时间戳）
- 查询任务记录，不存在则直接向业务侧回调失败并结束
- 将任务状态更新为 `IN_PROGRESS`

### 3.2 主服务商提交

通过 `submitToProvider()` 调用 `ModelDispatch::submit()`：

1. `ModelDispatch` 按模型名找到处理器（当前为 `TextToImage`）
2. `TextToImage` 校验并规范化参数（如 `size` 必须满足 `宽*高`）
3. 按 provider 选择：
   - `fal` -> `Fal::submit()`
   - `wavespeed` -> `WaveSpeed::submit()`

### 3.3 回退策略

当“主服务商提交失败”且配置了“可用回退服务商”时：

- 记录 `fallback_used = true`
- 记录 `primary_error_payload`
- 使用 `fallback_provider` 再提交一次
- 以回退服务商结果作为最终提交结果收敛

回退可用条件：

- `fallback_provider` 非空
- `fallback_provider !== provider`

### 3.4 提交阶段状态收敛

`finalizeTaskAfterSubmit()` 规则：

- 提交成功：状态置为 `IN_QUEUE`（表示已进入上游队列，尚未最终完成）
- 提交失败：状态置为 `FAILED`
- 同时记录：
  - `final_provider`
  - `requested_provider_task_id`（上游任务 ID）
  - `final_error_payload`（失败时）
- 仅失败终态会写：
  - `completed_at`
  - `duration_ms`

### 3.5 提交阶段回调（回给业务侧）

提交阶段结束后统一通过 `WebhookNotifier::notify()` 回调业务侧：

- `code`：成功 `200`，失败 `500`
- `msg`：`Task callback`
- `receipt`：
  - `taskId`
  - `status`
  - `receipt`（上游提交回执，可空）
  - `error`（失败信息，可空）

若回调发送失败，仅记录日志，不抛异常影响主流程。

## 4. 服务商完成回调（上游 -> 本系统 -> 业务侧）

入口：`ModelController::webhook($provider, $taskId)`

### 4.1 回调接入协议

- `fal`：
  - 要求 `status === 'OK'`
  - 从 `payload.images[*].url` 提取输出数组
- `wavespeed`：
  - 要求 `code === 0 && status === 'completed'`
  - 使用 `outputs` 作为输出数组

不满足条件时返回占位成功（`None`），避免上游反复重试。

### 4.2 完成态收敛

`ModelLogic::webhook($taskId, $outputs)`：

- 查询任务，不存在则记告警并返回
- 更新任务为 `COMPLETED`
- 写入：
  - `provider_outputs`
  - `completed_at`
  - `duration_ms`
- 再次回调业务侧，通知“任务最终完成”

完成态回调载荷：

- `task_id`
- `custom_id`
- `status = COMPLETED`
- `outputs`
- `completed_at`

## 5. 状态流转总览

常见路径如下：

1. 创建成功：`IN_QUEUE`
2. 队列开始执行：`IN_PROGRESS`
3. 提交上游成功：`IN_QUEUE`（等待上游异步完成回调）
4. 上游完成回调到达：`COMPLETED`

失败路径：

- 主服务商失败且无回退：`FAILED`
- 主服务商失败，回退也失败：`FAILED`
- 队列执行异常：`FAILED`

## 6. 关键持久化字段（`task_record`）

- 标识与路由：
  - `id`, `app_id`, `custom_id`, `model`
- 服务商相关：
  - `requested_provider`, `requested_provider_task_id`
  - `fallback_provider`, `fallback_provider_task_id`, `fallback_used`
  - `final_provider`
- 输入输出：
  - `parameters`, `metadata`, `provider_outputs`
- 错误与耗时：
  - `primary_error_payload`, `final_error_payload`
  - `started_at`, `completed_at`, `duration_ms`
- 回调地址：
  - `webhook_url`

## 7. 回调设计约定

- 回调发送统一走 `WebhookNotifier`，避免重复实现。
- 回调失败只记录日志，不阻断状态落库。
- 提交阶段与完成阶段都会回调业务侧：
  - 提交阶段：告知是否已入上游队列或提交失败
  - 完成阶段：告知最终产出与完成时间

## 8. 当前已接入模型与服务商

- 模型：`bytedance/seedream/v4.5/text-to-image`
- 服务商：`fal`、`wavespeed`

后续新增模型时，只需在 `ModelDispatch::MODEL_HANDLERS` 增加映射并提供对应模型处理类。
