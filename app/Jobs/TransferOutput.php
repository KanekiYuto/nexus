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
 * - 下载一个服务商输出 URL
 * - 上传至自有 S3 存储
 * - 写入 task_result 记录
 *
 * 由 ProcessOutputs 通过 Bus::batch() 批量派发，
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
     * @param string $originalUrl 服务商原始输出 URL
     * @param int $orderIndex 在输出列表中的位置（0-based）
     */
    public function __construct(
        public readonly string $taskRecordId,
        public readonly string $originalUrl,
        public readonly int    $orderIndex,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $response = Http::timeout(30)->get($this->originalUrl);
        $content = $response->body();
        $mimeType = $this->detectMimeType($content, $response);
        $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? '';
        $key = 'outputs/' . $this->taskRecordId . '/' . Str::ulid() . ($extension ? '.' . $extension : '');

        Storage::put($key, $content);

        TaskResult::query()->create([
            'task_record_id' => $this->taskRecordId,
            'key' => $key,
            'original_url' => $this->originalUrl,
            'order_index' => $this->orderIndex,
        ]);
    }

    /**
     * 检测文件 MIME 类型。
     *
     * 优先用 finfo 从文件内容本身检测（最可靠）；
     * 若 finfo 返回通用类型（application/octet-stream）则回退到响应头。
     */
    private function detectMimeType(string $content, Response $response): string
    {
        $fromContent = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);

        if ($fromContent && $fromContent !== 'application/octet-stream') {
            return $fromContent;
        }

        $contentType = $response->header('Content-Type');
        return trim(explode(';', $contentType)[0]);
    }
}
