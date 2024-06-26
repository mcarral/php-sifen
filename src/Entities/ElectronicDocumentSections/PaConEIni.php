<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property int iTiPago
 * @property string dDesTiPag
 * @property int dMonTiPag
 * @property string cMoneTiPag
 * @property string dDMoneTiPag
 * @property integer dTiCamTiPag
 * @property PagTarCD gPagTarCD
 * @property PagCheq gPagCheq
 * Class PaConEIni
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class PaConEIni extends ElectronicDocumentSectionBase {

    protected $attributes = ['iTiPago' => null, 'dDesTiPag' => null, 'dMonTiPag' => null, 'cMoneTiPag' => null,
                             'dDMoneTiPag' => null, 'dTiCamTiPag' => null, 'gPagTarCD' => null, 'gPagCheq' => null];

    protected $iTiPagoOptions = [
        1 => "Efectivo", 2 => "Cheque", 3 => "Tarjeta de crédito", 4 => "Tarjeta de débito",
        5 => "Transferencia", 6 => "Giro", 7 => "Billetera electrónica", 8 => "Tarjeta empresarial",
        9 => "Vale", 10 => "Retención", 11 => "Anticipo", 12 => "Valor fiscal", 13 => "Valor comercial",
        14 => "Compensación", 15 => "Permuta", 16 => "Pago bancario",
    ];

    protected $occurrences = 99;

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iTiPago'       => ['required', 'integer', 'digits_between:1,2', Rule::in(array_keys($this->iTiPagoOptions))],
            'dDesTiPag'     => ['required', 'string', 'between:2,50', Rule::in($this->iTiPagoOptions)],
            'dMonTiPag'     => ['required', 'integer', 'ekuatia_long:1-15p0-4'],
            'cMoneTiPag'    => ['required', 'string', 'size:3', Rule::in(array_keys($this->ekuatia->config('encodings.currencies')))],
            'dDMoneTiPag'   => ['required', 'string', 'between:3,20', Rule::in($this->ekuatia->config('encodings.currencies'))],
            'dTiCamTiPag'   => ['required_unless:cMoneTiPag,PYG', 'integer', 'ekuatia_long:1-5p0-4'],
        ]); // TODO: Change the autogenerated stub
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'iTiPago'       => 'Tipo de pago',
            'dDesTiPag'     => 'Descripción del tipo de pago',
            'dMonTiPag'     => 'Monto por tipo de pago',
            'cMoneTiPag'    => 'Moneda de la operación',
            'dDMoneTiPag'   => 'Descripción de la moneda de operación',
            'dTiCamTiPag'   => 'Tipo de cambio por tipo',
        ]);
    }

    protected function validating(Validator $validator)
    {
        //dd($this->toArray());
        parent::validating($validator); // TODO: Change the autogenerated stub
    }

    public function getDDesTiPagAttribute()
    {
        if ( isset($this->attributes['dDesTiPag'])) return $this->attributes['dDesTiPag'];

        return Arr::get($this->iTiPagoOptions, $this->iTiPago, 'Unknown');
    }

    public function setCMoneTiPagAttribute($value)
    {
        $this->attributes['cMoneTiPag'] = ( ! is_null($value)) ? $this->ekuatia->equivalence('Currency', $value, $value)->code_converted : null;
    }

    public function getDDMoneTiPagAttribute()
    {
        if ( isset($this->attributes['dDMoneTiPag'])) return $this->attributes['dDMoneTiPag'];

        return $this->ekuatia->config('encodings.currencies.' . $this->cMoneTiPag);
    }

    public function setDMonTiPagAttribute($value)
    {
        $this->attributes['dMonTiPag'] = $value ? $value : 0;
    }

    public function getDMonTiPagAttribute()
    {
        return ! is_null($this->attributes['dMonTiPag']) ? ekuatia_number_format($this->attributes['dMonTiPag'], 4) : null;
    }

    public function getDTiCamTiPagAttribute()
    {
        return ($this->cMoneTiPag !== 'PYG') ? Arr::get($this->attributes, 'dTiCamTiPag') : null;
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        if (! in_array((int) $this->iTiPago, [3, 4])) {
            $attributes = Arr::except($attributes, ['gPagTarCD']);
        }
        if (! in_array((int) $this->iTiPago, [2])) {
            $attributes = Arr::except($attributes, ['gPagCheq']);
        }

        return $attributes;
    }

}
