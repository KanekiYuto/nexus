# 媒体文件直传接口说明

本文描述客户端如何通过临时凭证将文件直传至对象存储，无需经由服务器中转。

## 1. 接口概览

| 项目     | 内容                          |
|--------|-------------------------------|
| 路由     | `POST /v1/media/upload/temporary` |
| 控制器   | `App\Http\Controllers\v1\media\UploadController::temporary()` |
| 鉴权     | 同其他 v1 接口                |
| 凭证有效期 | 5 分钟                       |

---

## 2. 申请临时凭证

### 请求

**Method:** `POST`  
**URL:** `/v1/media/upload/temporary`  
**Content-Type:** `application/json`

#### 请求参数

| 字段       | 类型      | 必填 | 说明                                                      |
|----------|---------|----|-----------------------------------------------------------|
| filename | string  | 是  | 原始文件名，用于提取后缀（如 `photo.jpg`）。最大 255 字符                   |
| size     | integer | 是  | 文件字节数（`File.size`）。上限由服务端按文件类型固定，见下表 |

**文件类型大小限制**

| 类型 | 后缀                                      | 最大大小  |
|----|------------------------------------------|-------|
| 图片 | jpg / jpeg / png / gif / webp / svg      | 10 MB |
| 视频 | mp4 / mov / avi / mkv / webm / flv       | 50 MB |
| 其他 | —                                        | 不限    |

#### 示例请求体

```json
{
  "filename": "photo.jpg",
  "size": 2097152
}
```

### 响应

```json
{
  "code": 200,
  "msg": "success",
  "receipt": {
    "url": "https://your-bucket.s3.amazonaws.com/uploads/01JNXXXXX.jpg?X-Amz-...",
    "headers": {
      "Content-Type": "image/jpeg"
    },
    "path": "uploads/01JNXXXXX.jpg"
  }
}
```

#### 响应字段说明

| 字段              | 类型     | 说明                                             |
|-----------------|--------|------------------------------------------------|
| receipt.url     | string | 预签名上传地址，客户端直接对此 URL 发起 `PUT` 请求       |
| receipt.headers | object | 上传请求必须携带的 HTTP 头，缺少会导致签名校验失败           |
| receipt.path    | string | 文件在存储桶中的路径，后续提交任务时作为文件引用传入           |

### 文件路径规则

服务端根据传入的后缀自动生成唯一路径，格式为：

```
uploads/<ULID>.<ext>
```

例如：`uploads/01JNXXXXX.jpg`

- 后缀统一转小写
- 无后缀时仅使用 ULID，不补充点号

---

## 3. 直传文件至对象存储

拿到凭证后，客户端直接向 `receipt.url` 发起 `PUT` 请求，**必须带上 `receipt.headers` 中的全部请求头**。

### 示例（cURL）

```bash
curl -X PUT "https://your-bucket.s3.amazonaws.com/uploads/01JNXXXXX.jpg?X-Amz-..." \
  -H "Content-Type: image/jpeg" \
  --data-binary @/path/to/photo.jpg
```

### 示例（JavaScript / fetch）

```js
const { url, headers, path } = receipt;

await fetch(url, {
  method: 'PUT',
  headers: headers,
  body: file, // File 或 Blob 对象
});

// 上传成功后，将 path 传给后续业务接口
```

---

## 4. 完整使用流程

```
客户端                         本系统                      对象存储
  |                               |                            |
  |-- POST /upload/temporary  --> |                            |
  |                               |-- 生成预签名 URL ---------->|
  |                               |<-- url + headers + path ---|
  |<-- receipt(url,headers,path)--|                            |
  |                               |                            |
  |-- PUT {url} (带 headers) --------------------------------->|
  |<------------------------------------------ 204 No Content -|
  |                               |                            |
  |-- POST /model/generate        |                            |
  |   (parameters.image = path) ->|                            |
```

1. 调用本接口获取上传凭证（`url`、`headers`、`path`）。
2. 客户端直接 `PUT` 文件至 `url`，携带 `headers` 中全部头信息。
3. 上传成功（HTTP 2xx）后，将 `path` 填入后续任务接口的对应参数中。

---

## 5. 错误处理

| 场景                      | HTTP 状态码 | 说明                                        |
|-------------------------|--------|-------------------------------------------|
| `filename` / `size` 未传  | 422    | Laravel 参数校验失败，响应体含 `errors` 字段           |
| 图片 `size` 超过 10 MB      | 422    | 超出服务端限制，直接拒绝，不会生成凭证                      |
| 视频 `size` 超过 50 MB      | 422    | 超出服务端限制，直接拒绝，不会生成凭证                      |
| 凭证过期后上传               | 403    | 对象存储拒绝，需重新申请凭证                            |
| 使用错误 `headers`          | 403    | 签名不匹配，严格按 `receipt.headers` 传值             |
| 实际上传字节数与 `size` 不符    | 403    | S3 强制校验 `ContentLength`，字节数不一致直接拒绝上传      |

---

## 6. 注意事项

- 凭证有效期为 **5 分钟**，请在申请后尽快完成上传，过期后须重新申请。
- `receipt.path` 由服务端生成，客户端**不可自行指定**上传路径。
- 每次调用均会生成新的路径和凭证，重复调用不会覆盖已有文件。
- 文件上传完成后，本系统**不会主动感知**，需由客户端在后续业务接口中传入 `path`。
