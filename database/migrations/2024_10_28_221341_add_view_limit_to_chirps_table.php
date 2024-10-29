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
        $table->integer('view_limit')->default(1); // Default to 1 view limit
    });
}

public function down()
{
    Schema::table('chirps', function (Blueprint $table) {
        $table->dropColumn('view_limit');
    });
}
};