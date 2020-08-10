<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationsTable extends Migration
{
    public function up()
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->string("file_name");
            $table->unsignedBigInteger("rows");
            $table->string("md5sum");
            $table->date("downloaded_at")->nullable();
            $table->date("unzipped_at")->nullable();
            $table->date("inserted_at")->nullable();
            $table->enum("status", [-1, 1, 2, 3])->comment("status of this file,[
            '-1' => 'failed',
            '1' => 'downloaded',
            '2' => 'unzipped',
            '3' => 'completed',
        ]")->index();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('operations');
    }
}