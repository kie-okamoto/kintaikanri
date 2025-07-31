<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // 勤怠日（検索・一覧表示に必須）
            $table->date('date');

            // 打刻タイムスタンプ
            $table->timestamp('clock_in')->nullable();      // 出勤
            $table->timestamp('break_start')->nullable();   // 休憩開始
            $table->timestamp('break_end')->nullable();     // 休憩終了
            $table->timestamp('clock_out')->nullable();     // 退勤

            $table->time('break_duration')->nullable();     // 休憩時間
            $table->time('total_duration')->nullable();     // 勤務合計時間


            $table->text('note')->nullable();               // 備考

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
