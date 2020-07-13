<?php namespace Waka\Worder\Classes;

use System\Helpers\DateTime as DateTimeHelper;

class WordCreator2 extends WordProcessor2
{
    use \Waka\Utils\Classes\Traits\ConvertPx;

    private $dataSourceModel;
    private $dataSourceId;
    private $listImages;
    //private $additionalParams;
    //private $dataSourceAdditionalParams;

    use \Waka\Cloudis\Classes\Traits\CloudisKey;

    public function prepareCreatorVars($dataSourceId)
    {
        //trace_log("prepareCreatorVars");
        $this->dataSourceModel = $this->linkModelSource($dataSourceId);
        $this->dotedValues = $this->document->data_source->getDotedValues($dataSourceId);
        $this->listImages = $this->document->data_source->getPicturesUrl($dataSourceId, $this->document->images);
        $this->fncs = $this->document->data_source->getFunctionsCollections($this->dataSourceId, $this->document->model_functions);

        //trace_log($this->listImages);
    }
    // }
    private function linkModelSource($dataSourceId)
    {
        $this->dataSourceId = $dataSourceId;
        // si vide on puise dans le test
        if (!$this->dataSourceId) {
            $this->dataSourceId = $this->document->data_source->test_id;
        }
        //on enregistre le modèle
        return $this->document->data_source->modelClass::find($this->dataSourceId);
    }
    public function renderWord($dataSourceId)
    {
        $this->prepareCreatorVars($dataSourceId);
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
                trace_log($ck);
                $checkBox = ['path' => plugins_path() . $ck, 'width' => '10px', 'height' => '10px'];
                $this->templateProcessor->setImageValue($injection['tag'], $checkBox);
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
                //trace_log($functionRow);
                foreach ($wordFnc['subTags'] as $subTag) {
                    //trace_log('**subtag***');
                    //trace_log($subTag);
                    $tagType = $subTag['tagType'] ?? null;

                    $tag = $subTag['tag'] . '#' . $i;

                    if ($tagType == 'IMG') {

                        //trace_log("c'est une image tag : " . $tag);
                        $path = $functionRow[$subTag['varName'] . '.path'];
                        $width = $functionRow[$subTag['varName'] . '.width'];
                        $height = $functionRow[$subTag['varName'] . '.height'];
                        $this->templateProcessor->setImageValue($tag, ['path' => $path, 'width' => $width . 'px', 'height' => $height . 'px'], 1);

                    } else {
                        //trace_log("c'est une value tag : " . $tag);
                        $value = $functionRow[$subTag['varName']];
                        if ($tagType) {
                            $value = $this->transformValue($value, $tagType);
                        }
                        $this->templateProcessor->setValue($tag, $value, 1);
                    }
                }
                $i++;

            }
        }
        //trace_log("tout est pret");
        $name = str_slug($this->document->name . '-' . $this->dataSourceModel->name);
        $coin = $this->templateProcessor->saveAs($name . '.docx');
        return response()->download($name . '.docx')->deleteFileAfterSend(true);
    }

    public function getUrlFromImageKey($imageKey)
    {
        $imageKey_array = explode(':', $imageKey);
        $idAndCrop = $imageKey_array[0];

        $idAndCrop_array = explode('**', $idAndCrop);
        $id = $idAndCrop_array[0];
        $crop = $idAndCrop_array[1] ?? 'fill';

        $nameOrId = $this->listImages[$id] ?? null;

        if (!$nameOrId) {
            return null;
        }

        $width = $imageKey_array[1] ?? '165mm';
        $height = $imageKey_array[2] ?? '165mm';

        $is_montage = is_numeric($nameOrId);

        $width = $this->convertStringToPx($width);
        $height = $this->convertStringToPx($height);

        //trace_log("width : " . $width);
        //trace_log("height : " . $height);

        $options = ['width' => $width, 'height' => $height, 'crop' => $crop];

        if ($is_montage) {
            return \Waka\Cloudis\Models\Montage::find($nameOrId)->getCloudiUrl($this->dataSourceId);
        } else {
            return \Cloudder::secureShow($nameOrId, $options);
        }

    }
    public function transformValue($value, $type)
    {
        switch ($type) {
            case 'numeric':
                return number_format($value, 0, ',', ' ');
                break;
            case 'euro':
                return number_format($value, 2, ',', ' ') . ' €';
                break;
            case 'euro_int':
                return number_format($value, 0, ',', ' ') . ' €';
                break;
            case 'date':
                $value = DateTimeHelper::makeCarbon($value, false);
                $backendTimeZone = \Backend\Models\Preference::get('timezone');
                $value->setTimezone($backendTimeZone);
                $value->setTime(0, 0, 0);
                $value->setTimezone(\Config::get('app.timezone'));
                return $value->format('d/m/Y');
                break;
        }

    }

    // }
}
