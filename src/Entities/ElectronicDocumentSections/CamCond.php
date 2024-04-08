<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property int iCondOpe
 * @property string dDCondOpe
 * @property SectionCollection|PaConEIni[] gPaConEIni
 * @property PagCred gPagCred
 * Class CamCond
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class CamCond extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iCondOpe' => null, 'dDCondOpe' => null,
        'gPaConEIni' => null, 'gPagCred' => null,
    ];

    const COND_COUNTED = 1;
    const COND_CREDIT = 2;

    protected $iCondOpeOptions = [CamCond::COND_COUNTED => "Contado", CamCond::COND_CREDIT => "Crédito"];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iCondOpe'  => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->iCondOpeOptions))],  // Condición de la operación
            'dDCondOpe' => ['required', 'string', 'size:7', Rule::in(array_values($this->iCondOpeOptions))],     // Descripción de la condición de operación
        ]);
    }

    public function getDDCondOpeAttribute()
    {
        if ( isset($this->attributes['dDCondOpe'])) return $this->attributes['dDCondOpe'];

        return Arr::get($this->iCondOpeOptions, $this->iCondOpe);
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        if ((int) $this->iCondOpe !== 1) {
            $attributes = Arr::except($attributes, ['gPaConEIni']);
        }
        if ((int) $this->iCondOpe !== 2) {
            $attributes = Arr::except($attributes, ['gPagCred']);
        }

        return $attributes;
    }
}