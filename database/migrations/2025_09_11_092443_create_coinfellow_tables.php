<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Пользователи (обновленная версия)
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('telegram_user_id')->unique()->nullable();
            $table->string('username')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('language_code')->nullable();
            
            // JWT токены
            $table->text('refresh_token')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            
            // Аватар
            $table->string('avatar_url')->nullable();
            $table->string('avatar_telegram_file_id')->nullable();
            
            // Верификация
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('telegram_verified_at')->nullable();
            
            // Статус
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Типы подписок
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Базовый", "Премиум", "Бизнес"
            $table->string('slug')->unique(); // basic, premium, business
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('RUB');
            $table->integer('duration_days'); // Длительность подписки в днях
            $table->json('features')->nullable(); // JSON с доступными фичами
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Подписки пользователей
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('status')->default('active'); // active, expired, cancelled, pending
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('payment_method')->nullable(); // telegram, card, etc
            $table->string('payment_id')->nullable(); // ID платежа в платежной системе
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency')->default('RUB');
            $table->boolean('is_auto_renewal')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });

        // 4. Категории расходов
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->default('#3498db');
            $table->timestamps();
        });

        // 5. Группы
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('currency')->default('RUB');
            $table->string('invite_code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 6. Участники групп
        Schema::create('group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('member'); // owner, admin, member
            $table->timestamps();
            
            $table->unique(['group_id', 'user_id']);
        });

        // 7. Расходы
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('payer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->timestamps();
        });

        // 8. Участники расходов
        Schema::create('expense_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->decimal('share', 10, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['expense_id', 'user_id']);
        });

        // 9. Балансы
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('to_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            
            $table->unique(['group_id', 'from_user_id', 'to_user_id']);
        });

        // 10. Платежи
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('to_user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. Бюджеты
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('period')->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 12. Повторяющиеся расходы
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('payer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('frequency');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('day_of_month')->nullable();
            $table->string('weekdays')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 13. Уведомления
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
        });

        // 14. Приглашения
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('inviter_id')->constrained('users')->onDelete('cascade');
            $table->string('invitee_telegram_id')->nullable();
            $table->string('invitee_email')->nullable();
            $table->string('token')->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // 15. Комментарии
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->morphs('commentable');
            $table->text('content');
            $table->timestamps();
        });

        // 16. Курсы валют
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('target_currency', 3);
            $table->decimal('rate', 10, 6);
            $table->date('date');
            $table->timestamps();
            
            $table->unique(['base_currency', 'target_currency', 'date']);
        });

        // 17. Настройки пользователя
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('default_currency')->default('RUB');
            $table->boolean('notify_new_expenses')->default(true);
            $table->boolean('notify_payments')->default(true);
            $table->boolean('notify_budget_alerts')->default(true);
            $table->string('language')->default('ru');
            $table->timestamps();
            
            $table->unique(['user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('recurring_expenses');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('balances');
        Schema::dropIfExists('expense_user');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('users');
    }
};