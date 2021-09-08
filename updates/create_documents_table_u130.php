<?php namespace Waka\Worder\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;

class CreateDocumentsTableU130 extends Migration
{
    public function up()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->boolean('is_lot')->nullable()->default(true);
        });
    }

    public function down()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->dropColumn('is_lot');
        });
    }
}