<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_record', function (Blueprint $table) {
            $table->comment('任务记录信息');
            $table->ulid('id')->primary()->comment('ID');
            $table->ulid('app_id')->index()->comment('应用ID');
            $table->string('custom_id')->comment('业务侧任务ID（应用内唯一）');
            $table->string('model')->index()->comment('模型');
            $table->string('status')->index()->comment('状态');
            $table->string('webhook_url')->comment('回调地址');
            $table->jsonb('provider_outputs')->default('[]')->comment('服务商输出');
            $table->string('requested_provider')->index()->comment('首次请求服务商');
            $table->string('requested_provider_task_id')->nullable()->index()->comment('首次请求服务商任务ID');
            $table->string('fallback_provider')->nullable()->index()->comment('回退服务商');
            $table->string('fallback_provider_task_id')->nullable()->index()->comment('回退服务商任务ID');
            $table->boolean('fallback_used')->default(false)->index()->comment('是否触发回退');
            $table->string('final_provider')->nullable()->index()->comment('最终成功/失败落定的服务商');
            $table->jsonb('parameters')->comment('输入参数');
            $table->jsonb('metadata')->nullable()->comment('元数据');
            $table->jsonb('primary_error_payload')->nullable()->comment('首次请求错误信息');
            $table->jsonb('final_error_payload')->nullable()->comment('最终错误信息（含回退失败）');
            $table->unsignedInteger('duration_ms')->default(0)->comment('任务耗时');
            $table->unsignedBigInteger('started_at')->nullable()->comment('任务开始时间');
            $table->unsignedBigInteger('completed_at')->nullable()->comment('任务完成时间');
            $table->unsignedBigInteger('created_at')->comment('创建时间');
            $table->unsignedBigInteger('updated_at')->comment('修改时间');

            $table->unique(['app_id', 'custom_id']);

            // 应用删除时，对应任务记录一并删除
            $table->foreign('app_id')
                ->references('id')
                ->on('app')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_record');
    }
};
