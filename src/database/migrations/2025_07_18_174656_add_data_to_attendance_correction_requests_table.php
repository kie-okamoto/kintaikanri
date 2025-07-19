<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDataToAttendanceCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_correction_requests', function (Blueprint $table) {
            $table->json('data')->nullable()->after('reason');
        });
    }

    public function down()
    {
        Schema::table('attendance_correction_requests', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
}
