<?php namespace Waka\Worder\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateDocumentsTableU110 extends Migration
{
    public function up()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->boolean('is_scope')->nullable();
        });
    }

    public function down()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->dropColumn('is_scope');
        });
    }
}