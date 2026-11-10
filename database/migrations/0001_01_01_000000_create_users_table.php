<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Thay thế hoàn toàn file migration mặc định của Laravel (bảng users -> nguoi_dung).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nguoi_dung', function (Blueprint $t) {
            $t->increments('id');
            $t->string('ho_ten', 100);
            $t->string('email', 150)->unique();
            $t->string('sdt', 15);
            $t->string('cccd', 20)->nullable()->unique();
            $t->string('mat_khau')->comment('Lưu mã băm (bcrypt/argon2), không lưu mật khẩu thô');
            $t->enum('vai_tro', ['chu_tro', 'khach'])->default('khach');
            $t->dateTime('created_at')->useCurrent();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('nguoi_dung');
    }
};
