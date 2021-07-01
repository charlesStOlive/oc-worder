<?php namespace Waka\Worder\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('waka_worder_documents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('path')->nullable();
            $table->string('data_source')->nullable();
            $table->text('model_functions')->nullable();
            $table->text('images')->nullable();
            $table->text('scopes')->nullable();
            //reorder
            $table->integer('sort_order')->default(0);
            //softDelete
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_worder_documents');
    }
}