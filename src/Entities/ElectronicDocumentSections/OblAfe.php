<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;


/**
 * @property int cOblAfe
 * @property string dDesOblAfe
 * Class OblAfe
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class OblAfe extends ElectronicDocumentSectionBase {

    protected $attributes = ['cOblAfe' => null, 'dDesOblAfe' => null];

    protected $fillable = ['cOblAfe', 'dDesOblAfe'];

    protected $occurrences = 99;

}
