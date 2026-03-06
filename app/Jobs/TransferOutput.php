<?php

namespace App\Jobs;

use App\Models\TaskResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * 单资源转存 Job。
 *
 * 职责边界：
 * - 下载一个服务商输出 URL（流式，不全量载入内存）
 * - 上传至自有 S3 存储
 * - 写入 task_result 记录
 *
 * 由 ModelLogic::webhook() 通过 Bus::batch() 按 URL 粒度派发，
 * 全部完成后由 batch then() 回调推送 webhook。
 */
class TransferOutput implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param string $taskRecordId 所属任务 ID
     * @param string $originalUrl  服务商原始输出 URL
     * @param int    $orderIndex   在输出列表中的位置（0-based）
     */
    public function __construct(
        public readonly string $taskRecordId,
        public readonly string $originalUrl,
        public readonly int    $orderIndex,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        // throw() 确保 HTTP 非 2xx 时立即抛异常，不继续处理错误响应体
        $response = Http::timeout(60)->throw()->get($this->originalUrl);

        $mimeType  = $this->detectMimeType($response);
        $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? '';
        $key       = 'outputs/' . $this->taskRecordId . '/' . Str::ulid() . ($extension ? '.' . $extension : '');

        // 流式写入 S3，避免大文件全量载入内存
        Storage::writeStream($key, $response->toPsrResponse()->getBody()->detach());

        TaskResult::query()->create([
            'task_record_id' => $this->taskRecordId,
            'key'            => $key,
            'original_url'   => $this->originalUrl,
            'order_index'    => $this->orderIndex,
        ]);
    }

    /**
     * 检测文件 MIME 类型。
     *
     * 优先从响应头 Content-Type 获取；
     * 缺失或为通用类型时，回退到用 finfo 从响应体内容检测。
     */
    private function detectMimeType(Response $response): string
    {
        $contentType = trim(explode(';', $response->header('Content-Type'))[0]);

        if ($contentType && $contentType !== 'application/octet-stream') {
            return $contentType;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $response->body());
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
    }
}
