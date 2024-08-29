<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table
                ->json('measurements')
                ->after('code')
                ->nullable()
                ->default(null)
            ;
            $table
                ->bigInteger('batch_id')
                ->after('measurements')
                ->nullable()
                ->default(null)
            ;
            $table
                ->bigInteger('batch_ref_track_id')
                ->after('batch_id')
                ->nullable()
                ->default(null)
                ->comment('This field holds batch referance id. Like package id in excel or csv file for further edit');
            ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table
                ->dropColumn('measurements')
            ;

            $table
                ->dropColumn('batch_id')
            ;

            $table
            ->dropColumn('batch_ref_track_id')
        ;

        });
    }
};
