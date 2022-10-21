<?php namespace Waka\Worder\Classes;

use Waka\Utils\Classes\WakaDate;

class WordResolver 
{

    private $templateProcessor;

    public function __construct($templateProcessor)
    {
        $this->templateProcessor = $templateProcessor;
    }

    
    public function resolveFnc($tag, $data) {
            // trace_log($fncTag);
            // trace_log($fncDatas);
            $functionName = $tag->varName;
            //trace_log($functionName);
            $functionRows = $data['datas'];
            //trace_log('-- functionRows --');
            //trace_log($data);
            if(is_object($functionRows)) {
                throw new \SystemException('Attention ! verifiez votre module de fonction ||'.$tag['code']. '|| Il ne retourne pas un array');
            }
            $countFunctionRows = count($functionRows);
            $tagName = 'FNC.' . $functionName;
            $this->templateProcessor->cloneBlock($tagName, $countFunctionRows, true, true);
            $i = 1; //i permet de creer la cla #i lors du clone row
            foreach ($functionRows as $functionRow) {
                foreach ($tag->subTags as $subTag) {
                    $subTag->tagKey =  $subTag->tag . '#' . $i;
                    $fncData = $functionRow[$subTag->varName] ?? false;
                    if(!$fncData) {
                        $fncData = array_get($functionRow, $subTag->varName);
                    }
                    $this->findAndResolve($subTag, $fncData);

                }
                $i++;
            }
            //
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
        //trace_log($tagData);

        if ($wordTag->tagType != null) {
            $tagData = $this->transformValue($tagData, $wordTag->tagType);
        }
        $this->templateProcessor->setValue($wordTag->tagKey, $tagData);
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
        $path = $tagData['path'] ?? false;
        $width = $tagData['width'] ?? false;
        $height = $tagData['height'] ?? false;
        $title = $tagData['title'] ?? false;

        //trace_log(['path' => $path, 'width' => $width . 'px', 'height' => $height . 'px']);

        
        if ($path) {
            if (!$width or !$height) {
               $this->templateProcessor->setImageValue($wordTag->tagKey, $path);
            } else {
                $this->templateProcessor->setImageValue($wordTag->tagKey, ['path' => $path, 'width' => $width, 'height' => $height], 1);
            }
        } else {
            // trace_log('pas de path');
            //$this->getTemplateProcessor()->setValue($tag, Lang::get("waka.worder::lang.word.error.no_image"), 1);
            // trace_log($tag); // deleteblock ne fonctionne pas nlanc à la place
            // $this->getTemplateProcessor()->deleteBlock($tag);
            $this->templateProcessor->setValue($wordTag->tagKey, "", 1);
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
            return "error 146 wordresolver";
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
