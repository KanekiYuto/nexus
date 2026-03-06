<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('task_result', function (Blueprint $table) {
            $table->comment('任务生成结果表');
            $table->ulid('id')->primary()->comment('ID');
            $table->ulid('task_record_id')->index()->comment('所属任务ID');
            $table->text('key')->comment('S3 存储路径（key）');
            $table->text('original_url')->comment('服务商原始输出 URL');
            $table->unsignedInteger('order_index')->comment('在输出列表中的位置（0-based）');
            $table->unsignedBigInteger('created_at')->comment('创建时间');

            $table->index(['task_record_id', 'order_index']);

            // 任务删除时，对应的生成结果一并删除，避免孤儿数据
            $table->foreign('task_record_id')
                ->references('id')
                ->on('task_record')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_result');
    }
};
