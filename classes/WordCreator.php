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
        self::$ds = new DataSource(self::$document->data_source);
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
        return ['FNC', 'IMG', 'info', 'ds'];
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
                    $tagType = null;
                    $tagWithoutType = $tag;
                    $tagTypeExist = str_contains($tag, '*');
                    if ($tagTypeExist) {
                        $checkTag = explode('*', $tag);
                        $tagType = array_pop($checkTag);
                        $tagWithoutType = $checkTag[0];
                    }
                    //trace_log("On est inside un bloc");
                    $subParts = explode('.', $tagWithoutType);
                    $fncName = array_shift($subParts);
                    $varName = implode('.', $subParts);

                    $subTag = [
                        'tagType' => $tagType,
                        'tag' => $tag,
                        'varName' => $varName,
                        'fncName' => $fncName,
                    ];
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
                    //trace_log('le tag commence par le nom de la source');
                    $tagWithoutType = $tag;
                    $tagType = null;
                    $tagTypeExist = str_contains($tag, '*');
                    if ($tagTypeExist) {
                        $checkTag = explode('*', $tag);
                        $tagType = array_pop($checkTag);
                        $tagWithoutType = $checkTag[0];
                    }
                    $tagOK = $this->checkInjection($tagWithoutType);
                    //trace_log("tagOk : ".$tagOK);
                    if ($tagOK) {
                        $tagObj = [
                            'tagType' => $tagType,
                            'varName' => $tagWithoutType,
                            'tag' => $tag,
                        ];
                        array_push($injections, $tagObj);
                    }
                    continue;
                }

                //si le tag commence par imagekey
                if ($fncFormat == 'IMG') {
                    array_push($imageKeys, $tag);
                    continue;
                }
                $fnc_code['code'] = array_shift($parts);
                //trace_log("nouvelle fonction : " . $fnc_code['code']);
                if (!$fnc_code) {
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
        return [
            'fncs' => $fncs,
            'injections' => $injections,
            'IMG' => $imageKeys,
        ];
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
        $docFncs = $this->getProductor()->model_functions;
        $docFncsCodes = [];
        //si il y a deja des fonctions, on va les checker et les mettre à jour
        if (is_countable($docFncs)) {
            foreach ($docFncs as $docFnc) {
                array_push($docFncsCodes, $docFnc['collectionCode']);
            }
        }
        //trace_log($docFncsCodes);
        $i = 1;
        foreach ($wordFncs as $wordFnc) {
            $fncCode = $wordFnc['code'] ?? false;
            if (!$fncCode) {
                $this->recordInform('problem', Lang::get("Une fonction n'a pas de code"));
            } elseif (!in_array($wordFnc['code'], $docFncsCodes)) {
                $txt = "La fonction " . $wordFnc['code'] . " du word n'est pas déclaré, veuillez la créer";
                $this->recordInform('problem', $txt);
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

    public function checkScopes()
    {
        //trace_log('checkScopes');
        if (!$this->modelId && !$this->getDs()->model) {
            //trace_log("modelId pas instancie");
            throw new \SystemException("Le modelId n a pas ete instancié");
        }
        $scope = new \Waka\Utils\Classes\Scopes($this->getProductor(), $this->getDs()->model);
        //trace_log('scope calcule');
        if ($scope->checkScopes()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function prepareCreatorVars()
    {
        //trace_log("Model ID dans prepareCreator var : ".$this->modelId);
        $this->values = $this->getDs()->getValues($this->modelId);
        $dotedValues = $this->getDs()->getDotedValues($this->modelId, 'ds');
        //trace_log($dotedValues);
        $listImages = $this->getDs()->wimages->getPicturesUrl($this->getProductor()->images);
        $fncs = $this->getDs()->getFunctionsCollections($this->modelId, $this->getProductor()->model_functions);

        $originalTags = $this->checkTags();
        //trace_log($originalTags);

        //Traitement des champs simples
        //trace_log("Traitement des champs simples");
        foreach ($originalTags['injections'] as $injection) {
            $value = $dotedValues[$injection['varName']];

            if ($injection['tagType'] == 'CB') {
                $ck;
                if ($value) {
                    $ck = '/waka/worder/assets/images/check.gif';
                } else {
                    $ck = '/waka/worder/assets/images/uncheck.gif';
                }
                //trace_log($ck);
                $checkBox = ['path' => plugins_path() . $ck, 'width' => '10px', 'height' => '10px'];
                $this->getTemplateProcessor()->setImageValue($injection['tag'], $checkBox);
            } elseif ($injection['tagType'] == 'HTM') {
                $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                //set html supporte une fonction clean qui permet de supprimer les paragraphes avec espaces. cas des listes UL/LI
                $this->getTemplateProcessor()->setHtmlValue($injection['tag'], $value, true);
            } elseif ($injection['tagType'] == 'MD') {
                $value = \Markdown::parse($value);
                $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                $this->getTemplateProcessor()->setHtmlValue($injection['tag'], $value, true);
            } else {
                if ($injection['tagType'] != null) {
                    $value = $this->transformValue($value, $injection['tagType']);
                }
                $this->getTemplateProcessor()->setValue($injection['tag'], $value);
            }
        }

        //Traitement des image
        //trace_log("Traitement des images");
        foreach ($originalTags['IMG'] as $imagekey) {
            $parts = explode(".", $imagekey);
            $key = array_pop($parts);
            $objImage = $listImages[$key] ?? null;

            if ($objImage) {
                $objWord = [
                    'path' => $objImage['path'],
                    'width' => $objImage['width'] . 'px',
                    'height' => $objImage['height'] . 'px',
                    'ratio' => true,
                ];
                //trace_log($imagekey);
                //trace_log($objWord);
                if ($objImage['path'] ?? false) {
                    $this->getTemplateProcessor()->setImageValue($imagekey, $objWord);
                } else {
                    $this->getTemplateProcessor()->setValue($imagekey, Lang::get("waka.worder::lang.word.error.no_image"));
                }
                $this->getTemplateProcessor()->setImageValue($imagekey, $objWord);
            }
        }

        //Préparation des resultat de toutes les fonbctions
        //trace_log('traitement des fncs');

        // Pour chazque fonctions dans le word
        foreach ($originalTags['fncs'] as $wordFnc) {
            //trace_log("-------------------------------");
            //trace_log($wordFnc);

            $functionName = $wordFnc['code'];
            //trace_log($functionName);

            $functionRows = $fncs[$functionName];
            //trace_log('-- functionRows --');
            //trace_log($functionRows);

            //Préparation du clone block
            $countFunctionRows = count($functionRows);
            $fncTag = 'FNC.' . $functionName;
            $this->getTemplateProcessor()->cloneBlock($fncTag, $countFunctionRows, true, true);
            $i = 1; //i permet de creer la cla #i lors du clone row

            //Parcours des lignes renvoyé par la fonctions
            foreach ($functionRows as $functionRow) {
                $functionRow = array_dot($functionRow);
                foreach ($wordFnc['subTags'] as $subTag) {
                    //trace_log('**subtag***');
                    //trace_log($subTag);
                    $tagType = $subTag['tagType'] ?? null;

                    $tag = $subTag['tag'] . '#' . $i;

                    if ($tagType == 'IMG') {
                        //trace_log("c'est une image tag : " . $tag);
                        // $path = $functionRow[$subTag['varName'] . '.path'];
                        $path = $functionRow[$subTag['varName'] . '.path'] ?? false;
                        $width = $functionRow[$subTag['varName'] . '.width'] ?? false;
                        $height = $functionRow[$subTag['varName'] . '.height'] ?? false;
                        if ($path) {
                            if (!$width && !$height) {
                                $this->getTemplateProcessor()->setImageValue($tag, $path);
                            } else {
                                $this->getTemplateProcessor()
                                    ->setImageValue($tag, ['path' => $path, 'width' => $width . 'px', 'height' => $height . 'px'], 1);
                            }
                        } else {
                            // trace_log('pas de path');
                            //$this->getTemplateProcessor()->setValue($tag, Lang::get("waka.worder::lang.word.error.no_image"), 1);
                            // trace_log($tag); // deleteblock ne fonctionne pas nlanc à la place
                            // $this->getTemplateProcessor()->deleteBlock($tag);
                            $this->getTemplateProcessor()->setValue($tag, "", 1);
                        }
                    } elseif ($tagType == 'HTM') {
                        $value = $functionRow[$subTag['varName']] ?? 'Inconnu';
                        $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                        $this->getTemplateProcessor()->setHtmlValue($tag, $value, 1);
                    } elseif ($tagType == 'MD') {
                        $value = $functionRow[$subTag['varName']] ?? 'Inconnu';
                        $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                        $value = \Markdown::parse($value);
                        $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                        $this->getTemplateProcessor()->setHtmlValue($tag, $value, 1);
                    } elseif ($tagType == 'TXT') {
                        $value = $functionRow[$subTag['varName']] ?? 'Inconnu';
                        $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                        $value = \Markdown::parse($value);
                        $value = strip_tags($value);
                        // $value = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $value), ENT_QUOTES, 'UTF-8');
                        $this->getTemplateProcessor()->setValue($tag, $value, 1);
                    } else {
                        //trace_log("c'est une value tag : " . $tag);
                        $value = $functionRow[$subTag['varName']] ?? 'Inconnu';
                        if ($tagType) {
                            $value = $this->transformValue($value, $tagType);
                        }
                        $this->getTemplateProcessor()->setValue($tag, $value, 1);
                    }
                }
                $i++;
            }
        }

        //trace_log($this->listImages);
    }
    public function createTwigStrName()
    {
        if (!$this->getProductor()->name_construction) {
            return str_slug($this->getProductor()->name . '-' . $this->getDsName());
        }
        $vars = [
            'ds' => $this->values,
        ];
        $nameConstruction = \Twig::parse($this->getProductor()->name_construction, $vars);
        return str_slug($nameConstruction);
    }

    public function transformValue($value, $type)
    {
        if ($value == 'Inconnu') {
            $value = 0;
        }

        if ($type == 'float') {
            return number_format($value, 2, ',', ' ');
        }

        if ($type == 'number' || $type == 'numercic') {
            return number_format($value, 0, ',', ' ');
        }
        if ($type == 'euro') {
            return number_format($value, 2, ',', ' ') . ' €';
        }
        if ($type == 'euro_int') {
            return number_format($value, 0, ',', ' ') . ' €';
        }
        if ($type == 'workflow') {
            return $this->$dataSource->getWorkflowState();
        }
        if (starts_with($type, 'percent') && $value) {
            $operators = explode("::", $type);
            $percent = $operators[1];
            $value = $value * $percent / 100;
            return number_format($value, 2, ',', ' ') . ' €';
        }
        if (starts_with($type, 'multiply') && $value) {
            $operators = explode("::", $type);
            $multiply = $operators[1];
            $value = $value * $multiply;
            return number_format($value, 2, ',', ' ') . ' €';
        }
        if (starts_with($type, 'date') && $value) {
            $date = new WakaDate();
            $value = DateTimeHelper::makeCarbon($value, false);
            return $date->localeDate($value, $type);
        } else {
            return 'Inconnu';
        }
    }
}
