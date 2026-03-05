<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app', function (Blueprint $table) {
            $table->comment('应用信息');
            $table->ulid('id')->primary()->comment('ID');
            $table->string('name')->comment('应用名称');
            $table->unsignedBigInteger('created_at')->comment('创建时间');
            $table->unsignedBigInteger('updated_at')->comment('修改时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app');
    }
};
