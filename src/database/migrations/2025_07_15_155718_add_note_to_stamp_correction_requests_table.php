<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteToStampCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->text('note')->nullable()->after('reason');
        });
    }

    public function down()
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
}
