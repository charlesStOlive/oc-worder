<?php namespace Waka\Worder\Classes;

use App;
use Lang;
use System\Helpers\DateTime as DateTimeHelper;
use Waka\Utils\Classes\WakaDate;

class WordCreator2 extends WordProcessor2
{
    use \Waka\Utils\Classes\Traits\ConvertPx;

    private $modelId;
    private $listImages;

    use \Waka\Cloudis\Classes\Traits\CloudisKey;

    public function prepareCreatorVars($modelId)
    {
        $this->dotedValues = $this->dataSource->getDotedValues($modelId);
        $this->listImages = $this->dataSource->wimages->getPicturesUrl($modelId, $this->document->images);
        $this->fncs = $this->dataSource->getFunctionsCollections($modelId, $this->document->model_functions);

        $originalTags = $this->checkTags();
        //trace_log($originalTags);

        //Traitement des champs simples
        //trace_log("Traitement des champs simples");
        foreach ($originalTags['injections'] as $injection) {

            $value = $this->dotedValues[$injection['varName']];

            if ($injection['tagType'] == 'CB') {
                $ck;
                if ($value) {
                    $ck = '/waka/worder/assets/images/check.gif';
                } else {
                    $ck = '/waka/worder/assets/images/uncheck.gif';
                }
                //trace_log($ck);
                $checkBox = ['path' => plugins_path() . $ck, 'width' => '10px', 'height' => '10px'];
                $this->templateProcessor->setImageValue($injection['tag'], $checkBox);

            } else if ($injection['tagType'] == 'HTM') {
                $this->templateProcessor->setHtmlValue($injection['tag'], $value);
            } else {
                if ($injection['tagType'] != null) {
                    $value = $this->transformValue($value, $injection['tagType']);

                }
                $this->templateProcessor->setValue($injection['tag'], $value);
            }

        }

        //Traitement des image
        //trace_log("Traitement des images");
        foreach ($originalTags['IMG'] as $imagekey) {
            $parts = explode(".", $imagekey);
            $key = array_pop($parts);
            $objImage = $this->listImages[$key] ?? null;

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
                    $this->templateProcessor->setImageValue($imagekey, $objWord);
                } else {
                    $this->templateProcessor->setValue($imagekey, Lang::get("waka.worder::lang.word.error.no_image"), 1);
                }
                $this->templateProcessor->setImageValue($imagekey, $objWord);
            }
        }

        //Préparation des resultat de toutes les fonbctions
        //trace_log('traitement des fncs');

        $data = $this->fncs;
        // Pour chazque fonctions dans le word
        foreach ($originalTags['fncs'] as $wordFnc) {
            //trace_log("-------------------------------");
            //trace_log($wordFnc);

            $functionName = $wordFnc['code'];
            // trace_log($functionName);

            $functionRows = $data[$functionName];
            // trace_log('-- functionRows --');
            // trace_log($functionRows);

            //Préparation du clone block
            $countFunctionRows = count($functionRows);
            $fncTag = 'FNC.' . $functionName;
            $this->templateProcessor->cloneBlock($fncTag, $countFunctionRows, true, true);
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
                        $path = $functionRow[$subTag['varName'] . '.path'];
                        $path = $functionRow[$subTag['varName'] . '.path'] ?? false;
                        $width = $functionRow[$subTag['varName'] . '.width'] ?? false;
                        $height = $functionRow[$subTag['varName'] . '.height'] ?? false;
                        if ($path) {
                            $this->templateProcessor->setImageValue($tag, ['path' => $path, 'width' => $width . 'px', 'height' => $height . 'px'], 1);
                        } else {
                            $this->templateProcessor->setValue($tag, Lang::get("waka.worder::lang.word.error.no_image"), 1);
                        }

                    } else {
                        //trace_log("c'est une value tag : " . $tag);
                        $value = $functionRow[$subTag['varName']] ?? 'Inconnu';
                        if ($tagType) {
                            $value = $this->transformValue($value, $tagType);
                        }
                        $this->templateProcessor->setValue($tag, $value, 1);
                    }
                }
                $i++;

            }
        }

        //trace_log($this->listImages);
    }
    public function renderWord($modelId)
    {
        $this->prepareCreatorVars($modelId);

        //trace_log("tout est pret");
        $name = str_slug($this->document->name . '-' . $this->dataSource->name);
        $this->templateProcessor->saveAs($name . '.docx');
        //trace_log(get_class($coin));
        return response()->download($name . '.docx')->deleteFileAfterSend(true);
    }

    public function renderCloud($modelId)
    {
        $this->prepareCreatorVars($modelId);

        //trace_log("tout est pret");
        $name = str_slug($this->document->name . '-' . $this->dataSource->modelName);
        $filePath = $this->templateProcessor->save();
        $output = \File::get($filePath);

        $folderOrg = new \Waka\Cloud\Classes\FolderOrganisation();
        $folders = $folderOrg->getFolder($this->dataSource->model);

        $cloudSystem = App::make('cloudSystem');
        $lastFolderDir = $cloudSystem->createDirFromArray($folders);

        \Storage::cloud()->put($lastFolderDir['path'] . '/' . $name . '.docx', $output);

    }

    public function transformValue($value, $type)
    {
        if ($value == 'Inconnu') {
            $value = 0;
        }

        if ($type == 'numeric') {
            return number_format($value, 0, ',', ' ');
        }
        if ($type == 'euro') {
            return number_format($value, 2, ',', ' ') . ' €';
        }
        if ($type == 'euro_int') {
            return number_format($value, 0, ',', ' ') . ' €';
        }
        if (starts_with($type, 'date') && $value) {
            $date = new WakaDate();
            $value = DateTimeHelper::makeCarbon($value, false);
            return $date->localeDate($value, $type);
        } else {
            return 'Inconnu';
        }

    }

    // }
}
