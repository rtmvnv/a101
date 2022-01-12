<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accruals', function (Blueprint $table) {
            $table->string('payee')->comment('Получатель платежа по квитанции')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accruals', function (Blueprint $table) {
            $table->dropColumn('payee');
        });
    }
}
