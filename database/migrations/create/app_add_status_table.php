<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->comment('1=启用 0=禁用')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('app', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
