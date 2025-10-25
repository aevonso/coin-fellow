<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Проверяем существование полей перед добавлением
        if (!Schema::hasColumn('categories', 'is_default')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('color');
            });
        }
        
        if (!Schema::hasColumn('categories', 'user_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignUuid('user_id')
                      ->nullable()
                      ->after('is_default')
                      ->constrained()
                      ->onDelete('cascade');
            });
        }

        // Добавляем индекс для оптимизации
        if (!Schema::hasIndex('categories', ['is_default', 'user_id'])) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index(['is_default', 'user_id']);
            });
        }
    }

    public function down()
    {
        // Безопасный rollback - проверяем существование перед удалением
        if (Schema::hasColumn('categories', 'user_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
        
        if (Schema::hasColumn('categories', 'is_default')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }

        // Удаляем индекс если существует
        if (Schema::hasIndex('categories', ['is_default', 'user_id'])) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex(['is_default', 'user_id']);
            });
        }
    }
};