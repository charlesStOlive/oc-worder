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
            $table->string('slug');

            $table->string('path');
            $table->string('data_source')->nullable();

            $table->text('scopes')->nullable();
            $table->text('model_functions')->nullable();
            $table->text('images')->nullable();

            $table->integer('sort_order')->default(0);

            $table->softDeletes();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waka_worder_documents');
    }
}
