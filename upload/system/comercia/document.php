<?php
namespace comercia;
class Document
{
    var $document;
    var $variables = array();

    function __construct()
    {
        $this->document = Util::registry()->get("document");
    }

    public function setTitle($title)
    {
        $this->document->setTitle($title);
    }

    public function setDescription($description)
    {
        $this->document->setDescription($description);
    }

    public function setKeywords($keywords)
    {
        $this->document->setKeywords($keywords);
    }

    public function addLink($href, $rel)
    {
        $this->document->addLink(Util::http()->getPathFor($href), $rel);
    }

    public function addStyle($href, $rel = 'stylesheet', $media = 'screen')
    {
        $this->document->addStyle(Util::http()->getPathFor($href), $rel, $media);
    }

    public function addScript($href, $position = 'header')
    {
        $this->document->addScript(Util::http()->getPathFor($href), $position);
    }

    public function addVariable($name, $value)
    {
        $this->variables[$name] = $value;
    }


    function addDependency($file){
        if(is_array($file)){
            $files=$file;
            foreach($files as $file){
                $this->addDependency($file);
            }
            return true;
        }

        $exp=explode(".",$file);
        $ext=$exp[count($exp)-1];
        if($ext=="css"){
            $this->addStyle($file);
        }
        if($ext=="js"){
            $this->addScript($file);
        }
        return true;
    }
}


?>