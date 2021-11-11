<?php namespace Waka\Worder\Classes;

use Waka\Utils\Classes\WakaDate;

class WordResolver 
{

    private $templateProcessor;

    public function __construct($templateProcessor)
    {
        $this->templateProcessor = $templateProcessor;
    }

    public function resolveRows($wordTags, $tagDatas) {
        foreach ($wordTags as $tag) {
            $wordTag = (object) [
                'tagName' => $tag['varName'],
                'tagType' => $tag['tagType'],
                'tagKey' => $tag['tag'],
            ];
            $tagData = $tagDatas[$wordTag->tagName];
            $this->findAndResolve($wordTag, $tagData);
        }
    }

    public function resolveAsks($wordAskTags, $askDatas) {
        foreach ($wordAskTags as $tag) {

            $wordTag = (object) [
                'tagName' => $tag['varName'],
                'tagType' => $tag['tagType'],
                'tagKey' => $tag['tag'],
            ];
            $askData = $askDatas[$wordTag->tagName] ?? null;
            if(!$askData) {
                //que fait on
                trace_log("Pas trouvé : ".$wordTag->tagName);
            } else {
                 trace_log("trouvé : ".$wordTag->tagName);
                 $this->findAndResolve($wordTag, $askData);
            }
           
        } 
    }

    public function resolveFncs($wordFncTags, $fncDatas) {
        foreach ($wordFncTags as $fncTag) {
            // trace_log($fncTag);
            // trace_log($fncDatas);
            $functionName = $fncTag['code'];
            //trace_log($functionName);
            $functionRows = $fncDatas[$functionName];
            // trace_log('-- functionRows --');
            if(is_object($functionRows)) {
                throw new \SystemException('Attention ! verifiez votre module de fonction ||'.$fncTag['code']. '|| Il ne retourne pas un array');
            }
            $countFunctionRows = count($functionRows);
            $fncTagName = 'FNC.' . $functionName;
            $this->templateProcessor->cloneBlock($fncTagName, $countFunctionRows, true, true);
            $i = 1; //i permet de creer la cla #i lors du clone row
            foreach ($functionRows as $functionRow) {
                //$functionRow = array_dot($functionRow);
                foreach ($fncTag['subTags'] as $subTag) {
                    //trace_log($subTag);
                    $finalSubTag = (object) [
                        'tagName' => $subTag['varName'],
                        'tagType' => $subTag['tagType'] ?? null,
                        'tagKey' =>  $subTag['tag'] . '#' . $i,
                    ];

                    $fncData = $functionRow[$finalSubTag->tagName] ?? false;
                    if(!$fncData) {
                        $fncData = array_get($functionRow, $finalSubTag->tagName);
                    }
                    $this->findAndResolve($finalSubTag, $fncData);

                }
                $i++;
            }
            
            //
            // $askData = $askDatas[$wordTag->tagName];
            // $this->findAndResolve($wordTag, $askData);
        } 
    }





    public function findAndResolve($wordTag, $tagData) {
        $tagType = $wordTag->tagType;
        switch ($tagType) {
            case 'HTM':
                $this->resolveHtmRow($wordTag, $tagData);
                break;
            case 'MD':
                $this->resolveMdRow($wordTag, $tagData);
                break;
            case 'TXT':
                $this->resolveHtmToTxtRow($wordTag, $tagData);
                break;
            case 'IMG':
                $this->resolveImgRow($wordTag, $tagData);
                break;
            default:
                $this->resolveBasicRow($wordTag, $tagData);
        }

    }

    public function resolveBasicRow($wordTag, $tagData) {
        $tagType = $wordTag->tagType;
        $tagKey = $wordTag->tagKey;
        //trace_log($tagData);

        if ($tagType != null) {
            $tagData = $this->transformValue($tagData, $tagType);
        }
        $this->templateProcessor->setValue($tagKey, $tagData);
    }

    public function resolveHtmRow($wordTag, $tagData) {
        //trace_log('resoudre un htm------------------------');
        $tagData = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $tagData), ENT_QUOTES, 'UTF-8');
        $this->templateProcessor->setHtmlValue($wordTag->tagKey, $tagData, true);
    }

    public function resolveMdRow($wordTag, $tagData) {
        $tagKey = $wordTag->tagType;
        $tagData = \Markdown::parse($tagData);
        $tagData = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $tagData), ENT_QUOTES, 'UTF-8');
        $this->templateProcessor->setHtmlValue($wordTag->tagKey, $tagData, true);

    }

    public function resolveHtmToTxtRow($wordTag, $tagData) {
        $tagKey = $wordTag->tagType;
        $tagData = \Markdown::parse($tagData);
        $tagData = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $tagData), ENT_QUOTES, 'UTF-8');
        $tagData = strip_tags($tagData);
        // $tagData = html_entity_decode(preg_replace("/[\r\n]{2,}/", "\n", $tagData), ENT_QUOTES, 'UTF-8');
        $this->templateProcessor->setValue($wordTag->tagKey, $tagData, true);

    }

    public function resolveImgRow($wordTag, $tagData) {
        //trace_log('resoudre une image------------------------');
        $tagName = $wordTag->tagName;
        $tagKey = $wordTag->tagKey;
        //trace_log($tagData);
        // $path = $functionRow[$subTag['varName'] . '.path'] ?? false;
        // $width = $functionRow[$subTag['varName'] . '.width'] ?? false;
        // $height = $functionRow[$subTag['varName'] . '.height'] ?? false;
        //
        $path = $tagData['path'] ?? false;
        $width = $tagData['width'] ?? false;
        $height = $tagData['height'] ?? false;
        $title = $tagData['title'] ?? false;

        //trace_log(['path' => $path, 'width' => $width . 'px', 'height' => $height . 'px']);

        
        if ($path) {
            if (!$width or !$height) {
               $this->templateProcessor->setImageValue($tagKey, $path);
            } else {
                $this->templateProcessor->setImageValue($tagKey, ['path' => $path, 'width' => $width, 'height' => $height], 1);
            }
        } else {
            // trace_log('pas de path');
            //$this->getTemplateProcessor()->setValue($tag, Lang::get("waka.worder::lang.word.error.no_image"), 1);
            // trace_log($tag); // deleteblock ne fonctionne pas nlanc à la place
            // $this->getTemplateProcessor()->deleteBlock($tag);
            $this->templateProcessor->setValue($tagKey, "", 1);
        }

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
            //return $this->$dataSource->getWorkflowState();
            return "error 194 wordresolver";
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
            //trace_log($type);
            //trace_log($value);
            $dateFinal = $date->localeDate($value, $type);
            //trace_log($dateFinal);
            return $dateFinal;
        } else {
            return 'Inconnu';
        }
    }

    
}
