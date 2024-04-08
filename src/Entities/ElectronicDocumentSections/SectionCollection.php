<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Collection;
use Mcarral\Sifen\Support\SimpleXMLElement;

class SectionCollection extends Collection {

    /**
     * @var string
     */
    protected $sectionClass;

    /**
     * @var Root
     */
    protected $root;

    /**
     * @param $className
     * @return $this
     */
    public function setSectionClass($className)
    {
        $this->sectionClass = $className;

        return $this;
    }

    /**
     * @param ElectronicDocumentSectionBase $root
     * @return $this
     */
    public function setRoot(ElectronicDocumentSectionBase $root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @param bool $defaults
     * @return ElectronicDocumentSectionBase
     */
    public function createInstance($defaults = true)
    {
        $className = $this->sectionClass;
        $section = new $className($this->root);
        if ($defaults and method_exists($section, 'defaults')) $section->defaults();

        return $section;
    }

    /**
     * @param bool $defaults
     * @return ElectronicDocumentSectionBase
     */
    public function pushNewInstance($defaults = true)
    {
        $instance = $this->createInstance($defaults);
        $this->push($instance);

        return $instance;
    }

    /**
     * @return string
     */
    public function toXml()
    {
        $xml = "";
        $this->each(function(ElectronicDocumentSectionBase $section) use (&$xml, &$parentXml) {
            $dom = dom_import_simplexml($section->toXml());
            $xml .= $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
        });

        return "<root>" . $xml . "<root/>";
    }

    /**
     * @param SimpleXMLElement $xml
     * @return $this
     */
    public function appendToXml(SimpleXMLElement $xml)
    {
        $this->each(function(ElectronicDocumentSectionBase $section) use (&$xml) {
            $xml->appendXml($section->toXml());
        });

        return $this;
    }

    /**
     * @return MessageBag|null
     */
    public function validate()
    {
        foreach ($this as $rowNr => $item) {
            if (! ($validate = $item->validate($rowNr))) continue;
            return $validate;
        }

        return null;
    }

}