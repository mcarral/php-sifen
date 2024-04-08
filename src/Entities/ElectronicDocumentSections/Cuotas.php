<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Validation\Rule;

/**
 * Class Cuotas
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class Cuotas extends ElectronicDocumentSectionBase {

    protected $attributes = ['cMoneCuo' => null, 'dDMoneCuo' => null, 'dMonCuota' => null, 'dVencCuo' => null];

    protected $occurrences = 999;

    public function rules()
    {
        return array_merge(parent::rules(), [
            'cMoneCuo'      => ['required', 'string', 'size:3', Rule::in(array_keys($this->ekuatia->config('encodings.currencies')))],
            'dDMoneCuo'     => ['required', 'string', 'between:3,20', Rule::in($this->ekuatia->config('encodings.currencies'))],

            'dMonCuota'     => ['required', 'numeric', 'ekuatia_long:1-15p0-4'],
            'dVencCuo'      => ['string', 'date_format:' . Cuotas::DATE_FORMAT],
        ]);
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'cMoneCuo'      => 'Moneda de las cuotas',
            'dDCondCred'    => 'Descripción de la moneda de las cuotas',
            'dMonCuota'     => 'Monto de cada cuota',
            'dVencCuo'      => 'Fecha de vencimiento de cada cuota',
        ]);
    }

    public function setCMoneCuoAttribute($value)
    {
        $this->attributes['cMoneCuo'] = ( ! is_null($value)) ? $this->ekuatia->equivalence('Currency', $value, $value)->code_converted : null;
    }

    public function getDDMoneCuoAttribute()
    {
        if ( isset($this->attributes['dDMoneCuo'])) return $this->attributes['dDMoneCuo'];

        return $this->ekuatia->config('encodings.currencies.' . $this->cMoneCuo);
    }

    protected function getDVencCuoAttribute()
    {
        if ( ! isset($this->attributes['dVencCuo'])) return null;

        return $this->dateToString($this->attributes['dVencCuo']);
    }

    protected function getDMonCuotaAttribute()
    {
        return ! is_null($this->attributes['dMonCuota']) ? ekuatia_number_format($this->attributes['dMonCuota'], 2) : null;
    }

}