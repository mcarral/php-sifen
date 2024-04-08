<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Class OpeCred
 * @property integer iCondCred
 * @property string dDCondCred
 * @property string dPlazoCre
 * @property integer dCuotas
 * @property integer dMonEnt
 * @property SectionCollection|Cuotas[] gCuotas
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class PagCred extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iCondCred' => null, 'dDCondCred' => null, 'dPlazoCre' => null, 'dCuotas' => null, 'dMonEnt' => null,
        'gCuotas' => null,
    ];

    const COND_CRED_DEADLINE = 1;
    const COND_CRED_INSTALLS = 2;

    static public $condCredOptions = [PagCred::COND_CRED_DEADLINE => 'Plazo', PagCred::COND_CRED_INSTALLS => 'Cuota'];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iCondCred'     => ['required', 'integer', 'digits:1', Rule::in(array_keys(PagCred::$condCredOptions))],
            'dDCondCred'    => ['required', 'string', 'between:5,6', Rule::in(array_values(PagCred::$condCredOptions))],

            'dPlazoCre'     => ['required_if:iCondCred,' . PagCred::COND_CRED_DEADLINE, 'string', 'between:2,15'],
            'dCuotas'       => ['required_if:iCondCred,' . PagCred::COND_CRED_INSTALLS, 'numeric', 'digits_between:1,3'],
        ]);
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'iCondCred'      => 'Condición de la operación a crédito',
            'dDCondCred'     => 'Descripción de la condición de la operación a crédito',
            'dPlazoCre'      => 'Plazo del crédito',
            'dCuotas'        => 'Cantidad de cuotas',
            'dMonEnt'        => 'Monto de la entrega inicial',
        ]);
    }

    public function getDDCondCredAttribute()
    {
        return ($this->iCondCred) ? Arr::get(static::$condCredOptions, $this->iCondCred, null) : null;
    }

}