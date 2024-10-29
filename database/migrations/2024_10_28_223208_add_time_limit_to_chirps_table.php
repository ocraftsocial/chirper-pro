<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */public function up()
{
    Schema::table('chirps', function (Blueprint $table) {
        $table->integer('time_limit')->nullable()->after('view_limit'); // Add the time limit column
    });
}

public function down()
{
    Schema::table('chirps', function (Blueprint $table) {
        $table->dropColumn('time_limit'); // Drop the column if needed
    });
}

};
