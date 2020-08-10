<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDbipLookupTable2 extends Migration
{
    public function up()
    {
        Schema::create('ip_lookups', function (Blueprint $table) {
            $table->string('ip_start');
            $table->string('ip_end');
            $table->char('continent', 2);
            $table->char('country', 2);
            $table->string('stateprov', 80)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('city', 80);
            $table->string('zipcode', 20)->nullable();
            $table->float('latitude');
            $table->float('longitude');
            $table->integer('geoname_id')->unsigned()->nullable();
            $table->float('timezone_offset')->nullable();
            $table->string('timezone_name', 64);
            $table->string('weather_code', 10)->nullable();
            $table->string('isp_name', 128);
            $table->integer('autonomous_number')->unsigned()->nullable();
            $table->enum('connection_type', ['dialup', 'isdn', 'cable', 'dsl', 'fttx', 'wireless'])->nullable();
            $table->string('organization_name', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ip_lookups');
    }
}