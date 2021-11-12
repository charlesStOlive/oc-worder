<?php namespace Waka\Worder\Classes;



class WordTag 
{
    public $resolver;
    public $tagType;
    public $tagKey;
    public $tag;
    public $varName;
    public $parent;
    public $fncName;
    //
    public $subTags;

    public function __construct($resolver) {
        $this->resolver = $resolver;
        $this->subTags = [];
    }

    public function addSubTags($newSubTag) {
        $this->subTags = array_merge($this->subTags, $newSubTag);
    }

    public function decryptTag($tag) {
        //
        $tagWithoutType = $tag;
        $tagType = null;
        $tagTypeExist = str_contains($tag, '*');
        if ($tagTypeExist) {
            $checkTag = explode('*', $tag);
            $tagType = array_pop($checkTag);
            $tagWithoutType = $checkTag[0];
        }
        if($this->resolver == 'FNC' or $this->resolver == 'FNC_child' or $this->resolver == 'FNC_M' or $this->resolver == 'FNC_IS') {
            $subParts = explode('.', $tagWithoutType);
            $fncName = array_shift($subParts);
            $this->fncName = $data['fncName'] ?? null;
            $this->varName = implode('.', $subParts);
        } else if($this->resolver == 'asks') {
            $parent = null;
            $explodedTag = explode('.', $tagWithoutType);
            //On supprimer le nom du ask
            array_shift($explodedTag);
            if(count($explodedTag) > 1) {
                $this->parent = $explodedTag[0];
            }
            $this->varName = implode('.', $explodedTag);
        }
        else {
            $this->varName = $tagWithoutType;
        }
        //
        $this->tagType = $tagType;
        $this->tagKey = $tag;
        $this->tag = $tag;
        
        
       
        
        
        
    }
}
