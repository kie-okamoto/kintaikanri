<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->text('reason');
            $table->text('note')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('submitted_at');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('attendance_id')
                ->references('id')
                ->on('attendances')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
}
