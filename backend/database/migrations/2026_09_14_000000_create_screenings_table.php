<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('screenings', function (Blueprint $table) {
            $table->id();
            // 删除影片时若存在排期则数据库层面阻止（应用层会给出友好提示）
            $table->foreignId('movie_id')->constrained()->restrictOnDelete();
            $table->string('location');                      // 放映地点
            $table->date('screening_date');                  // 放映日期
            $table->time('start_time');                      // 场次（开场时间）
            $table->string('contact_name');                  // 联系人
            $table->string('contact_phone', 50)->nullable(); // 联系电话
            $table->timestamps();

            $table->index(['movie_id', 'screening_date']);
            $table->index('screening_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screenings');
    }
};
