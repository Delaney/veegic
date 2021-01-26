<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDimensionsToVideosAndEditLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('dimensions')->nullable();
        });

        Schema::table('edit_logs', function (Blueprint $table) {
            $table->string('dimensions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('dimensions');
        });

        Schema::table('edit_logs', function (Blueprint $table) {
            $table->dropColumn('dimensions');
        });
    }
}
