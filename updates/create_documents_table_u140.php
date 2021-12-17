<?php namespace Waka\Worder\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateDocumentsTableU140 extends Migration
{
    public function up()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->string('state')->default('Actif');
            $table->dropColumn('model_functions');
            $table->dropColumn('images');
            $table->dropColumn('has_asks');
            $table->dropColumn('asks');
            $table->dropColumn('is_scope');
            $table->dropColumn('scopes');
        });
    }

    public function down()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->text('model_functions')->nullable();
            $table->text('images')->nullable();
            $table->boolean('has_asks')->nullable();
            $table->text('asks')->nullable();
            $table->boolean('is_scope')->nullable();
            $table->text('scopes')->nullable();
        });
    }
}