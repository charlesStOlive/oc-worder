<?php namespace Waka\Worder\Classes;

use ApplicationException;
use Flash;
use Lang;
use Redirect;
use Storage;
use Waka\Worder\Models\Document;
use \PhpOffice\PhpWord\TemplateProcessor;

class WordProcessor2
{

    public $document_id;
    public $document;
    public $templateProcessor;
    //public $bloc_types;
    //public $AllBlocs;
    public $increment;
    public $fncFormatAccepted;
    public $dataSourceName;
    public $sector;
    public $apiBlocs;
    public $dotedValues;
    public $originalTags;
    public $nbErrors;

    public function __construct($document_id)
    {
        $this->prepareVars($document_id);
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
    }
    public function prepareVars($document_id)
    {
        $this->increment = 1;
        $this->nbErrors = 0;
        $this->document_id = $document_id;
        //$this->bloc_types = BlocType::get(['id', 'code']);
        //$this->AllBlocs = Bloc::get(['id', 'document_id', 'code', 'name']);
        //
        $document = Document::find($document_id);

        //trace_log($document_id);
        $this->document = $document;
        //
        $document_path = $this->getPath($this->document);

        $this->templateProcessor = new TemplateProcessor($document_path);
        // tous les champs qui ne sont pas des blocs ou des fonctions devront avoir le deatasourceName
        $this->dataSourceName = snake_case($document->data_source->model);
        $this->fncFormatAccepted = ['fnc', 'IMAGE', $this->dataSourceName];
    }
    /**
     *
     */
    public function checkTags()
    {
        $allTags = $this->filterTags($this->templateProcessor->getVariables());
        //trace_log($allTags);
        $create = $this->checkFunctions($allTags['fncs']);
        return $allTags;
    }
    /**
     *
     */
    public function filterTags($tags)
    {
        $this->deleteInform();
        //tablaux de tags pour les blocs, les injections et les rows
        $fncs = [];
        $injections = [];
        $imageKeys = [];
        $insideBlock = false;

        $fnc_code = [];
        $subTags = [];
        //trace_log($tags);
        foreach ($tags as $tag) {
            // Si un / est détécté c'est une fin de bloc. on enregistre les données du bloc mais pas le tag
            //trace_log("Nouveau tag analysé : " . $tag);
            if (starts_with($tag, '/')) {
                //trace_log("Fin de tag fnc_code");
                $fnc_code['subTags'] = $subTags;
                //trace_log($fnc_code);

                array_push($fncs, $fnc_code);
                $insideBlock = false;
                //trace_log("---------------------FIN----Inside bloc-------------------");
                //reinitialisation du fnc_code et des subtags
                $fnc_code = [];
                $subTags = [];
                //passage au tag suivant
                continue;
            } else {
                // si on est dans un bloc on enregistre les subpart dans le bloc.
                if ($insideBlock) {
                    //trace_log("On est inside un bloc");
                    $subParts = explode('.', $tag);
                    $fncName = array_shift($subParts);
                    $varName = array_shift($subParts);
                    $image = array_shift($subParts);

                    $subBlocType = array_shift($subParts);
                    $tagType = $image == 'IMAGE' ? true : false;
                    $subBlocTag = implode('.', $subParts);
                    $subTag = [
                        'image' => $tagType,
                        'tag' => $tag,
                        'varName' => $varName,
                        'fncName' => $fncName,
                    ];
                    array_push($subTags, $subTag);
                    continue;
                }
                $parts = explode('.', $tag);
                if (count($parts) <= 1) {
                    $this->recordInform('problem', Lang::get('waka.worder::lang.word.processor.bad_format') . ' : ' . $tag);
                    continue;
                }
                //trace_log($tag);
                $fncFormat = array_shift($parts);
                //trace_log("blocformat : " . $fncFormat);

                if (!in_array($fncFormat, $this->fncFormatAccepted)) {
                    $this->recordInform('problem', Lang::get('waka.worder::lang.word.processor.bad_tag') . ' : ' . implode(", ", $this->fncFormatAccepted) . ' => ' . $tag);
                    continue;
                }
                // si le tag commence par le nom de la source
                if ($fncFormat == $this->dataSourceName) {
                    $tagOK = $this->checkInjection($tag);
                    if ($tagOK) {
                        array_push($injections, $tag);
                    }
                    continue;
                }

                //si le tag commence par imagekey
                if ($fncFormat == 'IMAGE') {
                    array_push($imageKeys, $tag);
                    continue;
                }
                $fnc_code['code'] = array_shift($parts);
                //trace_log("nouvelle fonction : " . $fnc_code['code']);
                if (!$fnc_code) {
                    $this->recordInform('warning', Lang::get('waka.worder::lang.word.processor.bad_format') . ' : ' . $tag);
                    continue;
                } else {
                    // on commence un bloc
                    $insideBlock = true;
                    //trace_log("-------------------------Inside bloc-------------------");
                }

            }
        }
        return [
            'fncs' => $fncs,
            'injections' => $injections,
            'IMAGE' => $imageKeys,
        ];
    }
    /**
     *
     */
    public function checkInjection($tag)
    {
        $ModelVarArray = $this->document->data_source->getDotedValues();
        if (!array_key_exists($tag, $ModelVarArray)) {
            $this->recordInform('problem', Lang::get('waka.worder::lang.word.processor.field_not_existe') . ' : ' . $tag);
            return false;
        } else {
            return true;
        }
    }
    /**
     *
     */
    public function checkFunctions($wordFncs)
    {
        if (!$wordFncs) {
            return;
        }
        //trace_log($wordFncs);
        //trace_log("check function");
        $docFncs = $this->document->model_functions;
        //trace_log($docFncs);
        $docFncsCodes = [];
        //si il y a deja des fonctions, on va les checker et les mettre à jour
        if (is_countable($docFncs)) {
            foreach ($docFncs as $docFnc) {
                array_push($docFncsCodes, $docFnc['collectionCode']);
            }
        }
        $i = 1;
        foreach ($wordFncs as $wordFnc) {
            $fncCode = $wordFnc['code'] ?? false;
            if (!$fncCode) {
                $this->recordInform('problem', Lang::get("Une fonction n'a pas de code"));
            } else if (!in_array($wordFnc['code'], $docFncsCodes)) {
                //array_push($docFncs, ['functionCode' => $wordFnc, 'ready' => false, 'name' => "auto " . $wordFnc . " 1"]);
                $this->recordInform('problem', "La fonction " . $wordFnc['code'] . " dans le document word n'est pas déclaré, veuillez la créer");
                $i++;
            }
        }
    }
    /**
     *
     */
    public function getPath($document)
    {
        if (!isset($document)) {
            throw new ApplicationException(Lang::get('waka.worder::lang.word.processor.id_not_exist'));
        }

        $existe = Storage::exists('media' . $document->path);
        if (!$existe) {
            throw new ApplicationException(Lang::get('waka.worder::lang.word.processor.document_not_exist'));
        }

        return storage_path('app/media' . $document->path);
    }

    public function recordInform($type, $message)
    {
        $this->nbErrors++;
        $this->document->record_inform($type, $message);
    }
    public function errors()
    {
        return $this->document->has_informs();
    }
    public function checkDocument()
    {
        $this->checkTags();
        if ($this->nbErrors > 0) {
            Flash::error(Lang::get('waka.worder::lang.word.processor.errors'));
        } else {
            Flash::success(Lang::get('waka.worder::lang.word.processor.success'));
        }
        return Redirect::refresh();
    }
    public function deleteInform()
    {
        $this->document->delete_informs();
    }
}
