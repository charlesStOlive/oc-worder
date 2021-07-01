<?php namespace Waka\Worder\Classes\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Waka\Worder\Models\Document;

class DocumentsImport implements ToCollection, WithHeadingRow, WithCalculatedFormulas
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $document = null;
            $id = $row['id'] ?? null;
            if($id) {
                $document = Document::find($id);
            }
            if(!$document) {
                $document = new Document();
            }
            $document->id = $row['id'] ?? null;
            $document->save();
        }
    }
}
