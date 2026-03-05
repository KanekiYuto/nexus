<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('storage', function (Blueprint $table) {
            $table->comment('文件存储信息');
            $table->ulid('id')->primary()->comment('ID');
            $table->text('key')->comment('S3 key (存储路径)');
            $table->text('url')->comment('S3 URL');
            $table->string('filename')->comment('文件名称');
            $table->string('original_filename')->comment('原始文件名称');
            $table->string('type')->index()->comment('文件类型');
            $table->string('mime_type')->index()->comment('MIME 类型');
            $table->unsignedBigInteger('size')->comment('文件大小(字节)');
            $table->unsignedBigInteger('created_at')->comment('创建时间');
            $table->unsignedBigInteger('updated_at')->comment('修改时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage');
    }
};
