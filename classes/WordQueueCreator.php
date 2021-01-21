<?php namespace Waka\Worder\Classes;

use Event;

class WordQueueCreator
{
    public function fire($job, $data)
    {
        if ($job) {
            Event::fire('job.start.word', [$job, 'Création de doc Words ']);
        }

        //trace_log($data);

        $listIds = $data['listIds'];
        $productorId = $data['productorId'];
        $lot = $data['lot'] ?? false;

        foreach ($listIds as $modelId) {
            //trace_log($lot);
            $word = new WordCreator2($productorId);
            $word->renderCloud($modelId, $lot);
        }

        if ($job) {
            Event::fire('job.end.email', [$job]);
            $job->delete();
        }

    }
}
