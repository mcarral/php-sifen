<?php


namespace Mcarral\Sifen\Entities\ElectronicDocumentEventSections;


/**
 * Class EventCompliance
 * @package Mcarral\Sifen\Entities\ElectronicDocumentEventSections
 * @property string Id
 * @property string dFecEmi
 * @property string dFecRecep
 * @property integer iTipRec
 * @property string dNomRec
 * @property string dRucRec
 * @property integer dDVRec
 * @property integer dTipIDRec
 * @property string dNumID
 * @property integer dTotalGs
 */
class EventCompliance extends EventBase {

    protected $attributes = [
        'Id' => null, 'dFecEmi' => null, 'dFecRecep' => null, 'iTipRec' => null, 'dNomRec' => null, 'dRucRec' => null,
        'dDVRec' => null, 'dTipIDRec' => null, 'dNumID' => null, 'dTotalGs' => null,
    ];

    public function schema()
    {
        return "";
    }

    public function sectionName()
    {
        return 'rGeVeNotRec';
    }

}