<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentEventSections;

use Mcarral\Sifen\Support\SimpleXMLElement;
use RobRichards\XMLSecLibs\XMLSecurityDSig;

/**
 * Class GesEve
 * @property Eve rEve
 * @package Mcarral\Sifen\Entities\ElectronicDocumentEventSections
 */
class GesEve extends ElectronicDocumentSectionBase {

    protected $attributes = ['rEve' => null];

    protected $occurrences = 15;

    protected $signId = null;

    protected $sectionPrefix = 'r';

    protected function afterSigned(SimpleXMLElement $xml, $objDSig)
    {
        // append date signed
        //$xml->appendXml((new SimpleXMLElement("<dFecFirma>" . Carbon::now()->format(Carbon::W3C) . "</dFecFirma>")));

        return $xml;
    }

    public function signId()
    {
        return $this->signId ?: XMLSecurityDSig::generateGUID('');
    }

    public function setSignId($id)
    {
        $this->signId = $id;
    }

    protected function afterAppendSection($key, SimpleXMLElement $xml)
    {
        if ($key != "rEve") return;
        $this->sign($xml, null, 'rEve');
    }

    /**
     * @return \Mcarral\Sifen\Support\SimpleXMLElement
     */
    protected function xmlElementInstance()
    {
        $schema = '';
        foreach (array_keys($this->rEve->gGroupTiEvt->toArray()) as $attr) {
            $event = $this->rEve->gGroupTiEvt->$attr;
            $schema = $event->schema();
            break;
        }

        return new SimpleXMLElement(
            '<' . $this->sectionName() . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://ekuatia.set.gov.py/sifen/xsd" xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd/ ' . basename(ekuatia()->schema($schema)) . '"/>'
        );
    }

}