<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

class ActEco extends ElectronicDocumentSectionBase {

    protected $attributes = ['cActEco' => null, 'dDesActEco' => null];

    protected $fillable = ['cActEco', 'dDesActEco'];

    protected $occurrences = 9;

}