<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('image_url')->nullable();
            $table->unsignedInteger('hours')->default(0);
            $table->unsignedDecimal('acars_base_pay_rate')->nullable()->default(0);
            $table->unsignedDecimal('manual_base_pay_rate')->nullable()->default(0);
            $table->boolean('auto_approve_acars')->nullable()->default(false);
            $table->boolean('auto_approve_manual')->nullable()->default(false);
            $table->boolean('auto_promote')->nullable()->default(true);
            $table->boolean('auto_approve_above_score')->nullable()->default(false);
            $table->smallInteger('auto_approve_score')->nullable();
            $table->timestamps();

            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ranks');
    }
}
