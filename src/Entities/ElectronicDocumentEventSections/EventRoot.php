<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentEventSections;

use Mcarral\Sifen\Support\SimpleXMLElement;
use Mcarral\Sifen\Entities\ElectronicDocumentEvent as Event;
use Mcarral\Sifen\Entities\ElectronicDocumentSections\SectionCollection;

/**
 * Class EventRoot
 * @property SectionCollection|GesEve[] rGesEve
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class EventRoot extends ElectronicDocumentSectionBase {

    static public $events = [
        // emisor
        Event::EVENT_CANCELED => [1, 'rGeVeCan'],
        Event::EVENT_DISABLED => [2, 'rGeVeInu'],
        Event::EVENT_ENDORSEMENT => [3, 'Endoso (futuro)'],
        Event::EVENT_NOMINATION => [4, 'rGEveNom'],

        // receptor
        Event::EVENT_COMPLIANCE => [10, 'rGeVeNotRec'],
        Event::EVENT_ACCORDANCE => [11, 'rGeVeConf'],
        Event::EVENT_DISAGREEMENT => [12, 'rGeVeDisconf'],
        Event::EVENT_UNKNOWN => [13, 'rGeVeDescon']
    ];

    protected $attributes = ['rGesEve' => null];

    protected $ed;

    public function __construct($ed)
    {
        parent::__construct($this);

        $this->ed = $ed;
    }

    public function ed()
    {
        return $this->ed;
    }

    public function sectionName()
    {
        return 'gGroupGesEve';
    }

    /**
     * @param null $content
     * @return \Mcarral\Sifen\Support\SimpleXMLElement
     */
    protected function xmlElementInstance($content = null)
    {
        return new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <' . $this->sectionName() . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://ekuatia.set.gov.py/sifen/xsd" xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd/ ' . basename(ekuatia()->schema('siRecepEvento')) . '">' . $content . '</' . $this->sectionName() . '>'
        );
    }

    public function toXml()
    {
        $content = '';
        foreach ($this->rGesEve as $eve) {
            $content .= xml_pretty($eve->toXml(), true, false);
        }

        return $this->xmlElementInstance($content); // TODO: Change the autogenerated stub
    }

}
