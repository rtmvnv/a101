<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditUserTable1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // We can drop only one column in a migration
        // https://laravel.com/docs/8.x/migrations#dropping-columns
        // "Dropping or modifying multiple columns within a single
        // migration while using a SQLite database is not supported."
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('username')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->dropColumn('username');
        });
    }
}
