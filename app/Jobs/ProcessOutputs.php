<?php

namespace App\Jobs;

use App\Models\Storage as StorageModel;
use App\Models\TaskResult;
use App\Support\WebhookNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

/**
 * 转存服务商输出资源并推送最终 webhook 通知。
 *
 * 职责边界：
 * 1) 逐个下载服务商输出的资源 URL
 * 2) 上传至自有对象存储，写入 storage 表，再写入 task_result 表
 * 3) 以转存后的 URL 调用业务侧 webhook_url
 *
 * 任意资源转存失败时抛出异常，Job 进入重试队列，不推送 webhook。
 */
class ProcessOutputs implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param string $taskRecordId 任务记录主键
     * @param string $customId     业务侧 ID
     * @param string $webhookUrl   业务侧回调地址
     * @param int    $completedAt  任务完成时间（秒级时间戳）
     * @param int    $durationMs   任务耗时（毫秒）
     * @param array  $outputs      服务商原始输出 URL 列表
     */
    public function __construct(
        public readonly string $taskRecordId,
        public readonly string $customId,
        public readonly string $webhookUrl,
        public readonly int    $completedAt,
        public readonly int    $durationMs,
        public readonly array  $outputs,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $storedUrls = [];

        foreach ($this->outputs as $index => $url) {
            $storedUrls[] = $this->transferOne((string)$url, $index);
        }

        WebhookNotifier::completed(
            $this->webhookUrl,
            $this->taskRecordId,
            $this->customId,
            $this->completedAt,
            $this->durationMs,
            $storedUrls,
        );
    }

    /**
     * 下载单个资源，上传至 S3，写入 storage 表和 task_result 表。
     *
     * @param string $url 服务商资源 URL
     * @param int $orderIndex 在输出列表中的位置（0-based）
     * @return string 转存后的可访问 URL
     * @throws ConnectionException
     */
    private function transferOne(string $url, int $orderIndex): string
    {
        $response  = Http::timeout(30)->get($url);
        $content   = $response->body();
        $mimeType  = $this->detectMimeType($content, $response);
        $extension = MimeTypes::getDefault()->getExtensions($mimeType)[0] ?? '';
        $filename  = Str::ulid() . ($extension ? '.' . $extension : '');
        $key       = 'outputs/' . $this->taskRecordId . '/' . $filename;

        Storage::put($key, $content);
        $storedUrl = Storage::url($key);

        $storageRecord = StorageModel::query()->create([
            'key'               => $key,
            'url'               => $storedUrl,
            'filename'          => $filename,
            'original_filename' => basename(parse_url($url, PHP_URL_PATH)) ?: $filename,
            'type'              => $this->resolveType($mimeType),
            'mime_type'         => $mimeType,
            'size'              => strlen($content),
        ]);

        TaskResult::query()->create([
            'task_record_id' => $this->taskRecordId,
            'storage_id'     => $storageRecord->id,
            'order_index'    => $orderIndex,
        ]);

        return $storedUrl;
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

    /**
     * 将 MIME 类型归类为 image / video / file。
     */
    private function resolveType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) return 'image';
        if (str_starts_with($mimeType, 'video/')) return 'video';
        return 'file';
    }
}
