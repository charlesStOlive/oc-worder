<?php namespace Waka\Worder\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->text('scope_type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('waka_worder_documents', function (Blueprint $table) {
            $table->dropColumn('scope_type');
        });
    }
}
