<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('free_quality_level')->nullable();
            $table->string('pro_quality_level')->nullable();
            $table->string('free_upload_count')->nullable();
            $table->string('pro_upload_count')->nullable();
            $table->string('free_upload_size_limit')->nullable();
            $table->string('pro_upload_size_limit')->nullable();
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
        Schema::dropIfExists('settings');
    }
}
