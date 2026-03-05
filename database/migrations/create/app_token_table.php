<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_token', function (Blueprint $table) {
            $table->comment('应用令牌');
            $table->ulid('id')->primary()->comment('ID');
            $table->ulid('app_id')->index();
            $table->string('value')->comment('token 值');
            $table->unsignedBigInteger('expired_at')->default(0)->nullable()->comment('过期时间');
            $table->unsignedBigInteger('created_at')->comment('创建时间');
            $table->unsignedBigInteger('updated_at')->comment('修改时间');

            // 应用删除时，对应令牌一并删除
            $table->foreign('app_id')
                ->references('id')
                ->on('app')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_token');
    }
};
