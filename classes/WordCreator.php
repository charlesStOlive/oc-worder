<?php namespace Waka\Worder\Classes;

use ApplicationException;
use Flash;
use Lang;
use Redirect;
use Storage;
use System\Helpers\DateTime as DateTimeHelper;
use Waka\Utils\Classes\DataSource;
use Waka\Utils\Classes\WakaDate;
use Waka\Worder\Models\Document;
use \PhpOffice\PhpWord\TemplateProcessor;
use Waka\Utils\Classes\TmpFiles;
use Waka\Utils\Classes\ProductorCreator;
class WordCreator extends ProductorCreator
{

    
    public static $templateProcessor;
    public $increment;
    public $fncFormatAccepted;
    public $apiBlocs;
    public $originalTags;
    public $nbErrors;

    public function __construct()
    {
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
    }

    public static function find($document_id)
    {
        $document = Document::find($document_id);
        if (!$document) {
            throw new ApplicationException(Lang::get('waka.worder::lang.word.processor.id_not_exist'));
        }
        self::$productor = Document::find($document_id);
        self::$ds = \DataSources::find(self::$productor->data_source);

        return new self;
    }
    public static function setTemplateProcessor()
    {
        $existe = Storage::exists('media' . self::$productor->path);
        if (!$existe) {
            throw new ApplicationException(Lang::get('waka.worder::lang.word.processor.document_not_exist'));
        }

        $document_path = storage_path('app/media' . self::$productor->path);
        self::$templateProcessor = new TemplateProcessor($document_path);
        //trace_log(self::$templateProcessor);
    }
    public function getTemplateProcessor()
    {
        return self::$templateProcessor;
    }
    

    public function getFncAccepted()
    {
        return ['info', 'ds', 'asks', 'FNC', 'FNC_M', 'IS_FNC', 'IS_DS'];
    }
    

    public function checkTags()
    {
        $this->nbErrors = 0;
        $allTags = $this->filterTags($this->getTemplateProcessor()->getVariables());
        //trace_log($allTags);
        //$this->checkFunctions($allTags['fncs']);
        //$this->checkAsks($allTags['asks']);
        return $allTags;
    }
    /**
     *
     */
    public function filterTags($tags)
    {
        $this->deleteInform();
        //tablaux de tags pour les blocs, les injections et les rows
        $allTags = [];
        //Utilisé pour savoir si dans un ensemble de bloc ou non
        $insideBlock = false;
        $insideIs = false;
        
        //Instanciation du premier FNC TAG
        $fncTag = new WordTag('FNC');
        $fncIs = null;
        $subTags = [];
        //trace_log($tags);
        foreach ($tags as $tag) {
            // Si un / est détécté c'est une fin de bloc. on enregistre les données du bloc mais pas le tag
            //trace_log("Nouveau tag analysé : " . $tag);
            if (starts_with($tag, '/FNC.')) {
                $fncTag->addSubTags($subTags);
                array_push($allTags, $fncTag);
                $insideBlock = false;
                //trace_log("---------------------FIN----Inside bloc-------------------");
                //reinitialisation du fnc_code et des subtags
                $fncTag = new WordTag('FNC');
                $subTags = [];
                //passage au tag suivant
                continue;
            } else if (starts_with($tag, '/IS_')) {
                $insideIs = false;
                //trace_log("---------------------FIN----Inside bloc-------------------");
                //passage au tag suivant
                continue;
            } else {
                // si on est dans un bloc on enregistre les subpart dans le bloc.
                if ($insideBlock) {
                    $subTag = new WordTag('FNC_child');
                    $subTag->decryptTag($tag);
                    array_push($subTags, $subTag);
                    continue;
                }
                $parts = explode('.', $tag);
                if (count($parts) <= 1) {
                    $error = Lang::get('waka.worder::lang.word.processor.bad_format') . ' : ' . $tag;
                    $this->recordInform('problem', $error);
                    continue;
                }
                //trace_log($tag);
                $fncFormat = array_shift($parts);

                if (!in_array($fncFormat, $this->getFncAccepted())) {
                    $frAccepted = implode(", ", $this->getFncAccepted());
                    $error = Lang::get('waka.worder::lang.word.processor.bad_tag') . ' : ' . $frAccepted . ' => ' . $tag;
                    $this->recordInform('problem', $error);
                    continue;
                }
                // si le tag commence par le nom de la source

                if ($fncFormat == 'ds' || $fncFormat == 'info') {
                    $tagObj = new WordTag('ds');
                    $tagObj->decryptTag($tag);
                    array_push($allTags, $tagObj);
                    continue;
                }
                
                if ($fncFormat == 'asks') {
                    $tagObj = new WordTag('asks');
                    $tagObj->decryptTag($tag);
                    array_push($allTags, $tagObj);
                    continue;
                }

                if($fncFormat == 'FNC_M') {
                    $tagObj = new WordTag('FNC_M');
                    $tagObj->decryptTag($tag);
                    array_push($allTags, $tagObj);
                    continue;
                }
                if($fncFormat == 'IS_FNC') {
                    $tagObj = new WordTag('IS_FNC');
                    $tagObj->decryptTag($tag);
                    array_push($allTags, $tagObj);
                    continue;
                }
                if($fncFormat == 'IS_DS') {
                    $tagObj = new WordTag('IS_DS');
                    $tagObj->decryptTag($tag);
                    array_push($allTags, $tagObj);
                    continue;
                }
                $fncTag->varName = array_shift($parts);
                //trace_log("nouvelle fonction : " . $fncTag['code']);
                if (!$fncTag) {
                    $txt = Lang::get('waka.worder::lang.word.processor.bad_format') . ' : ' . $tag;
                    $this->recordInform('warning', $txt);
                    continue;
                } else {
                    // on commence un bloc
                    
                    $insideBlock = true;
                    //trace_log("-------------------------Inside bloc-------------------");
                }
            }
        }
        return $allTags;
    }
    /**
     * TODO corriger
     */
    public function checkInjection($tag)
    {
        // $modelVarArray = $this->getDs()->getDotedValues(null, 'ds');
        // //trace_log($modelVarArray);
        // if (!array_key_exists($tag, $modelVarArray)) {
        //     $txt = Lang::get('waka.worder::lang.word.processor.field_not_existe') . ' : ' . $tag;
        //     $this->recordInform('problem', $txt);
        //     return false;
        // } else {
        //     return true;
        // }
        return true;
    }

    

    public function checkAsks($tags)
    {
        // trace_log('checkAsks');
        // trace_log($tags);
        if(!$tags) {
            return false;
        }
        //Recherche des asks recuperation du code et transformation en array avec uniquement le code.
        $docAsksCode = self::$productor->rule_asks->pluck('code')->toArray();
        //
        //trace_log($docAsksCode);
        foreach($tags as $tag) {
            $tagName = $tag['varName'];
            if(!in_array($tagName, $docAsksCode)) {
                $txt = "Le champs ask dans le document n'existe pas dans le modèle. Nom du tag : " . $tagName;
                $this->recordInform('problem', $txt);
                return true;
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
        self::$productor->record_inform($type, $message);
    }
    public function errors()
    {
        return self::$productor->has_informs();
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
        self::$productor->delete_informs();
    }

    /**
     * ********************************Partie liée à la création de document**********************************
     */
    

    public function renderWord()
    {
        //Préparation du fichier et template processor
        $this->prepareCreatorVars();
        $name = $this->createTwigStrName();
        $this->getTemplateProcessor()->saveAs($name . '.docx');
        return response()->download($name . '.docx')->deleteFileAfterSend(true);
    }

    public function renderTemp()
    {
        // reinitialisation du template processor si il y a une boucle !
        $this->prepareCreatorVars();
        $name = $this->createTwigStrName();
        $filePath = $this->getTemplateProcessor()->save();
        $output = \File::get($filePath);
        return TmpFiles::createDirectory()->putFile($name . '.docx', $output);
    }

    public function renderCloud($lot = false)
    {
        // reinitialisation du template process
        $this->prepareCreatorVars();
        $name = $this->createTwigStrName();

        $filePath = $this->getTemplateProcessor()->save($name);
        //trace_log($filePath);
        $output = \File::get($filePath);
        //trace_log("ok apres output");
        $cloudSystem = \App::make('cloudSystem');

        $path = [];
        if ($lot) {
            $path = 'lots';
        } else {
            $folderOrg = new \Waka\Cloud\Classes\FolderOrganisation();
            $path = $folderOrg->getPath($this->getDs()->model);
        }
        //trace_log($path.'/'.$name.'.docx');
        $cloudSystem->put($path.'/'.$name.'.docx', $output);
    }

    /**
     *
     */
    public function prepareCreatorVars()
    {
        
        $this->setTemplateProcessor();
        $allOriginalTags = $this->checkTags();
        $wordResolver = new WordResolver($this->getTemplateProcessor());
        $model = $this->getProductorVars();
        //
        foreach($allOriginalTags as $tag) {
            // trace_log("Objet tag");
            // trace_log($tag);
            //trace_log("Tag->tagKey : ".$tag->tagKey." tag->resolver: ".$tag->resolver." tag->varName: ".$tag->varName);
            //TODO NE MARCHE PAS --------------------------
            if($tag->resolver == 'IS_DS') {
                $data = array_get($model, $tag->varName);
                // trace_log('IS_DS---------');
                // trace_log($tag);
                // trace_log($data);
                // trace_log('--------------FIN IS_DS');
                if(!empty($data)) {
                    //trace_log('je trouve une data');
                    $this->getTemplateProcessor()->cloneBlock($tag->tag);
                } else {
                    //trace_log("je ne trouve pas de data : ".$tag->tag);
                    array_forget($model, $tag->varName);
                    $this->getTemplateProcessor()->deleteBlock($tag->tag);
                }
            }
            if($tag->resolver == 'IS_FNC') {
                $data = array_get($model, 'fncs.'.$tag->varName);
                if($data['show']) {
                    $this->getTemplateProcessor()->cloneBlock($tag->tag);
                } else {
                    array_forget($model, $tag->varName);
                    $this->getTemplateProcessor()->deleteBlock($tag->tag);
                }
            }

            if($tag->resolver == 'ds' || $tag->resolver == 'asks') {
                $data = array_get($model, $tag->varName);
                $wordResolver->findAndResolve($tag, $data);
            }
            
            

            if($tag->resolver == 'FNC') {
                $data = array_get($model, 'fncs.'.$tag->varName);
                if($data) {
                    $wordResolver->resolveFnc($tag, $data);
                }
            }
            
            if($tag->resolver == 'FNC_M') {
                // trace_log("FNC_M");
                // trace_log($tag->varName);
                $data = array_get($model, 'fncs.'.$tag->varName);
                if($data) {
                    $wordResolver->findAndResolve($tag, $data);
                }
                
            }
        }   
    }
    
}
