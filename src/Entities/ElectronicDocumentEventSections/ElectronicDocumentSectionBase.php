<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentEventSections;

use Mcarral\Sifen\Entities\ElectronicDocumentSections\ElectronicDocumentSectionBase as BaseElectronicDocumentSection;

abstract class ElectronicDocumentSectionBase extends BaseElectronicDocumentSection {

    /**
     * @var EventRoot
     */
    protected $root;

    /**
     * @return string
     */
    protected function getSectionNameSpace()
    {
        return __NAMESPACE__;
    }

    /**
     * @return EventRoot
     */
    public function root()
    {
        return parent::root();
    }

}