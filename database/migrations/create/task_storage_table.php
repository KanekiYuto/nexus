<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_storage', function (Blueprint $table) {
            $table->comment('任务存储表');
            $table->ulid('id')->primary()->comment('ID');
            $table->ulid('task_record_id')->index()->comment('任务ID');
            $table->ulid('storage_id')->index()->comment('存储资源ID');
            $table->string('type')->index()->comment('资源类型(parameter/result)');
            $table->unsignedInteger('order_index')->comment('排序序号');
            $table->unsignedBigInteger('created_at')->comment('创建时间');

            $table->index(['task_record_id', 'order_index']);

            // 任务删除时，对应的生成结果一并删除，避免孤儿数据
            $table->foreign('task_record_id')
                ->references('id')
                ->on('task_record')
                ->onDelete('cascade');

            // 原始结果资源删除时，对应结果记录一并删除（该记录已无有效资源）
            $table->foreign('storage_id')
                ->references('id')
                ->on('storage')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_storage');
    }
};
