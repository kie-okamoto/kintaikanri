<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id'); // 勤怠レコードとの紐付け
            $table->text('reason');                      // 修正理由
            $table->json('data')->nullable();            // 修正内容（出退勤・休憩・備考など）
            $table->timestamp('submitted_at');           // 申請日時
            $table->string('status')->default('承認待ち'); // 承認状態：承認待ち／承認済み／却下
            $table->timestamps();

            // 外部キー制約
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
}
