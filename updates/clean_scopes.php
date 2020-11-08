<?php namespace Waka\Worder\Updates;

//use Excel;
use Seeder;
use Waka\Worder\Models\Document;

class CleanScopes extends Seeder
{
    public function run()
    {
        Document::where('scopes', '<>', null)->update(['scopes' => null]);

    }
}
