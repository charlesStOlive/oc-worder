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

class WordCreator extends \Winter\Storm\Extension\Extendable
{

    public static $document;
    public static $ds;
    public static $templateProcessor;

    public $values;
    public $modelId;
    //public $bloc_types;
    //public $AllBlocs;
    public $increment;
    public $fncFormatAccepted;
    public $dataSource;
    public $dataSourceName;
    public $sector;
    public $apiBlocs;
    public $originalTags;
    public $nbErrors;

    public $askResponse;

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
        self::$document = Document::find($document_id);
        self::$ds = \DataSources::find(self::$document->data_source);
        self::setTemplateProcessor();

        return new self;
    }
    public static function setTemplateProcessor()
    {
        $existe = Storage::exists('media' . self::$document->path);
        if (!$existe) {
            throw new ApplicationException(Lang::get('waka.worder::lang.word.processor.document_not_exist'));
        }

        $document_path = storage_path('app/media' . self::$document->path);
        self::$templateProcessor = new TemplateProcessor($document_path);
        //trace_log(self::$templateProcessor);
    }
    public function getProductor()
    {
        return self::$document;
    }
    public function getDs()
    {
        return self::$ds;
    }
    public function getDsName()
    {
        //trace_log($this->getDs());
        return $this->getDs()->code;
    }

    public function getFncAccepted()
    {
        return ['info', 'ds', 'asks', 'FNC', 'FNC_M', 'IS_FNC', 'IS_DS'];
    }
    public function getTemplateProcessor()
    {
        return self::$templateProcessor;
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
     *
     */
    public function checkInjection($tag)
    {
        $modelVarArray = $this->getDs()->getDotedValues(null, 'ds');
        //trace_log($modelVarArray);
        if (!array_key_exists($tag, $modelVarArray)) {
            $txt = Lang::get('waka.worder::lang.word.processor.field_not_existe') . ' : ' . $tag;
            $this->recordInform('problem', $txt);
            return false;
        } else {
            return true;
        }
    }

    public function getAsksByCode() {
        return $this->getProductor()->rule_asks->keyBy('code');

    }

    public function checkAsks($tags)
    {
        // trace_log('checkAsks');
        // trace_log($tags);
        if(!$tags) {
            return false;
        }
        //Recherche des asks recuperation du code et transformation en array avec uniquement le code.
        $docAsksCode = $this->getProductor()->rule_asks->pluck('code')->toArray();
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
    // public function checkFunctions($wordFncs)
    // {
    //     if (!$wordFncs) {
    //         return;
    //     }
    //     //trace_log($wordFncs);
    //     //trace_log("check function");
    //     $docFncs = $this->getProductor()->model_functions;
    //     $docFncsCodes = [];
    //     //si il y a deja des fonctions, on va les checker et les mettre à jour
    //     if (is_countable($docFncs)) {
    //         foreach ($docFncs as $docFnc) {
    //             array_push($docFncsCodes, $docFnc['collectionCode']);
    //         }
    //     }
    //     //trace_log($docFncsCodes);
    //     $i = 1;
    //     foreach ($wordFncs as $wordFnc) {
    //         $fncCode = $wordFnc['code'] ?? false;
    //         if (!$fncCode) {
    //             $this->recordInform('problem', Lang::get("Une fonction n'a pas de code"));
    //         } elseif (!in_array($wordFnc['code'], $docFncsCodes)) {
    //             $txt = "La fonction " . $wordFnc['code'] . " du word n'est pas déclaré, veuillez la créer";
    //             $this->recordInform('problem', $txt);
    //             $i++;
    //         }
    //     }
    // }
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
        $this->getProductor()->record_inform($type, $message);
    }
    public function errors()
    {
        return $this->getProductor()->has_informs();
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
        $this->getProductor()->delete_informs();
    }

    /**
     * ********************************Partie liée à la création de document**********************************
     */
    public function setModelId($modelId)
    {
        $this->modelId = $modelId;
        $this->getDs()->instanciateModel($modelId);
        return $this;
    }

    public function setModelTest()
    {
        $this->modelId = $this->getProductor()->test_id;
        $this->getDs()->instanciateModel($modelId);
        return $this;
    }

    public function setRuleAsksResponse($datas = [])
    {
        $askArray = [];
        $srcmodel = $this->getDs()->getModel($this->modelId);
        $asks = $this->getProductor()->rule_asks()->get();
        foreach($asks as $ask) {
            $key = $ask->getCode();
            //trace_log($key);
            $askResolved = $ask->resolve($srcmodel, 'twig', $datas);
            $askArray[$key] = $askResolved;
        }
        //trace_log($askArray); // les $this->askResponse sont prioritaire
        return array_replace($askArray,$this->askResponse ?? []);
        
    }

    //BEBAVIOR AJOUTE LES REPOSES ??
    public function setAsksResponse($datas = [])
    {
        $this->askResponse = $this->getDs()->getAsksFromData($datas, $this->getProductor()->asks);
        return $this;
    }

    public function setRuleFncsResponse()
    {
        $fncArray = [];
        $srcmodel = $this->getDs()->getModel($this->modelId);
        $fncs = $this->getProductor()->rule_fncs()->get();
        foreach($fncs as $fnc) {
            $key = $fnc->getCode();
            //trace_log('key of the function');
            $fncResolved = $fnc->resolve($srcmodel,$this->getDs()->code);
            $fncArray[$key] = $fncResolved;
        }
        //trace_log($fncArray);
        return $fncArray;
        
    }

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
        $this->setTemplateProcessor();
        $this->prepareCreatorVars();
        $name = $this->createTwigStrName();
        $filePath = $this->getTemplateProcessor()->save();
        $output = \File::get($filePath);
        return TmpFiles::createDirectory()->putFile($name . '.docx', $output);
    }

    public function renderCloud($lot = false)
    {
        // reinitialisation du template processor si il y a une boucle !
        $this->setTemplateProcessor();
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

    public function checkConditions()//Ancienement checkScopes
    {
        $conditions = new \Waka\Utils\Classes\Conditions($this->getProductor(), $this->getDs()->getmodel());
        return $conditions->checkConditions();
    }

    /**
     *
     */
    public function prepareCreatorVars()
    {
        //trace_log("Model ID dans prepareCreator var : ".$this->modelId);
        $model = $this->getDs()->getModel($this->modelId);
        $values = $this->getDs()->getValues();

        $model = [
            'ds' => $values,
        ];

        $allOriginalTags = $this->checkTags();

        //Nouveau bloc pour nouveaux asks
        if($this->getProductor()->rule_asks()->count()) {
           $this->askResponse = $this->setRuleAsksResponse($model);
        } else {
            //Injection des asks s'ils existent dans le model;
            if(!$this->askResponse) {
                $this->setAsksResponse($model);
            }
        }
        //Nouveau bloc pour les new Fncs
        if($this->getProductor()->rule_fncs()->count()) {
            $fncs = $this->setRuleFncsResponse($model);
            $model = array_merge($model, [ 'fncs' => $fncs]);
        }
        //$datas = $dotedValues;
        $model = array_merge($model, [ 'asks' => $this->askResponse]);

        $wordResolver = new WordResolver($this->getTemplateProcessor());
        //
        
        foreach($allOriginalTags as $tag) {
            trace_log("Tag : ".$tag->tagKey." : ".$tag->resolver);
            if($tag->resolver == 'IS_DS') {
                $data = $datas[$tag->varName] ?? null;
                trace_log('IS_DS');
                //trace_log($tag->tag);
                if($data) {
                    $this->getTemplateProcessor()->cloneBlock($tag->tag);
                } else {
                    unset($datas[$tag->varName]);
                    $this->getTemplateProcessor()->deleteBlock($tag->tag);
                }
            }
            if($tag->resolver == 'ds' || $tag->resolver == 'asks') {
                $data = array_get($model, $tag->varName);
                $wordResolver->findAndResolve($tag, $data);
            }
            
            if($tag->resolver == 'IS_FNC') {
                $data = $fncs[$tag->varName];
                if($data['show']) {
                    $this->getTemplateProcessor()->cloneBlock($tag->tag);
                } else {
                    unset($fncs[$tag->varName]);
                    $this->getTemplateProcessor()->deleteBlock($tag->tag);
                }
            }

            if($tag->resolver == 'FNC') {
                $data = $fncs[$tag->varName] ?? null;
                trace_log($data);
                if($data) {
                    $wordResolver->resolveFnc($tag, $data);
                }
                
            }
            
            if($tag->resolver == 'FNC_M') {
                trace_log("FNC_M");
                trace_log($fncs);
                //$dotedFncs = array_dot($fncs);
                //trace_log($dotedFncs);
                $data = $fncs[$tag->varName] ?? null;
                if($data) {
                    $wordResolver->findAndResolve($tag, $data);
                }
                
            }
        }   
    }
    public function createTwigStrName()
    {
        if (!$this->getProductor()->name_construction) {
            return str_slug($this->getProductor()->name . '-' . $this->getDsName());
        }
        $vars = [
            'ds' => $this->getDs()->getValues(),
        ];
        $nameConstruction = \Twig::parse($this->getProductor()->name_construction, $vars);
        return str_slug($nameConstruction);
    }
}
