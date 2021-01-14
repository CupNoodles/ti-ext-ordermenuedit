<?php

namespace CupNoodles\OrderMenuEdit\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Schema;

/**
 * 
 */
class AddOrderMenuEdit extends Migration
{
    public function up()
    {

        Schema::table('order_menus', function (Blueprint $table) {
            $table->decimal('actual_amt', '15', '4');
        });

    }

    public function down()
    {
        Schema::table('order_menus', function (Blueprint $table) {
            $table->dropColumn(['actual_amt']);
        });

    }
}
