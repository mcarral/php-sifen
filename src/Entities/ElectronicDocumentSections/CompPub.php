<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

class CompPub extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'dModCont' => null, 'dEntCont' => null, 'dAnoCont' => null, 'dSecCont' => null, 'dFeCodCont' => null,
    ];

    protected $fillable = [
        'dModCont', 'dEntCont', 'dAnoCont', 'dSecCont', 'dFeCodCont',
    ];

}