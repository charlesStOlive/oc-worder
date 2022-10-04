<?php namespace Waka\Worder\Updates;

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Schema;
use Waka\Worder\Models\Document;
use Waka\Session\Models\WakaSession;

class ChangeDocumentsTableU160 extends Migration
{
    public function up()
    {
        $documents = Document::get();
        foreach($documents as $document) {
            $ds = $document->data_source;
            $testId = $document->test_id;
            if($ds) {
                trace_log('il y a du ds');
                $wakaSession = new WakaSession();
                $wakaSession->data_source = $ds;
                $wakaSession->ds_id_test = $testId;
                $wakaSession->name = 'word_'.$document->slug;
                $wakaSession->has_ds = true;
                $wakaSession->embed_all_ds = true;
                $wakaSession->key_duration = '1y';
                $wakaSession->save();
                $document->waka_session()->add($wakaSession);
            }
        }
    }

    public function down()
    {

    }
}