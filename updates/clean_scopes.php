<?php namespace Waka\Worder\Updates;

//use Excel;
use Seeder;
use Waka\Worder\Models\Document;

//use System\Models\File;
//use Waka\Worder\Models\BlocType;

// use Waka\Crsm\Classes\CountryImport;

class CleanScopes extends Seeder
{
    public function run()
    {
        //$this->call('Waka\Crsm\Updates\Seeders\SeedWorder');
        Document::where('scopes', '<>', null)->update(['scopes' => null]);

    }
}
