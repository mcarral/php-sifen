<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Class CIVAopeVta
 * @property int iAfecIVA
 * @property string dDesAfecIVA
 * @property integer dPropIVA
 * @property int dTasaIVA
 * @property int dBasGravIVA
 * @property int dBasExe
 * @property int dLiqIVAItem
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class CamIVA extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iAfecIVA' => null, 'dDesAfecIVA' => null, 'dPropIVA' => null, 'dTasaIVA' => null,
        'dBasGravIVA' => null, 'dLiqIVAItem' => null,
        'dBasExe' => null,
    ];

    protected $fillable = ['iAfecIVA', 'dDesAfecIVA', 'dPropIVA', 'dTasaIVA', 'dLiqIVAItem'];

    protected $iAfecIVAOptions = [
        1 => 'Gravado IVA', 2 => 'Exonerado (Art. 83- Ley 125/91)', 3 => 'Exento', 4 => 'Gravado parcial (Grav- Exento)'
    ];

    public function rules()
    {
        $rules = array_merge(parent::rules(), [
            'iAfecIVA'          => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->iAfecIVAOptions))],                                                           // Forma de afectación tributaria del IVA
            'dDesAfecIVA'       => ['required_with:iAfecIVA', 'string', Rule::in($this->iAfecIVAOptions)],                                                      // Descripción de la forma de afectación tributaria del IVA
            'dPropIVA'          => [],
            'dTasaIVA'          => ['required', 'numeric', 'ekuatia_long:1-2'],                                                                                                 // Distinción de la tasa IVA
            'dBasGravIVA'       => ['required', 'numeric', 'ekuatia_long:1-23p0-8'],                                                                                              // Base imponible del IVA por ítem                                                                                              // Base imponible del IVA por ítem
            'dLiqIVAItem'       => ['required', 'numeric', 'ekuatia_long:1-23p0-8'],                                                                                              // Liquidación del IVA por ítem
        ]); // TODO: Change the autogenerated stub
        if (! ekuatia()->technicNoteVersion(14)) return $rules;

        return array_merge($rules, [
            'dBasExe'           => ['required', 'numeric', 'ekuatia_long:1-15p0-8'],
        ]);
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'iAfecIVA'      => 'Forma de afectación tributaria del IVA',
            'dDesAfecIVA'   => 'Descripción de la forma de afectación tributaria del IVA',
            'dPropIVA'      => 'Proporción gravada de IVA',
            'dTasaIVA'      => 'Tasa del IVA',
            'dBasGravIVA'   => 'Base gravada del IVA por ítem',
            'dBasExe'       => 'Base Exenta por ítem',
            'dLiqIVAItem'   => 'Liquidación del IVA por ítem',
        ]); // TODO: Change the autogenerated stub
    }

    protected function validating(Validator $validator)
    {
        // validate dTasaIVA
        if ( in_array($this->iAfecIVA, [2, 3])) {
            if ($this->dTasaIVA != 0) $validator->getMessageBag()->add('dTasaIVA', 'El valor debe ser 0');
        } elseif( in_array($this->iAfecIVA, [1, 4])) {
            if (! in_array($this->dTasaIVA, [5, 10])) $validator->getMessageBag()->add('dTasaIVA', 'El valor debe ser 5');
        }

        if (in_array($this->iAfecIVA, [1, 4])) {
            $dBasGravIVA = (100 * $this->parent->gValorItem->gValorRestaItem->dTotOpeItem * $this->dPropIVA) / (10000 + ($this->dTasaIVA * $this->dPropIVA));
//            $dBasGravIVA = ($this->parent->gValorItem->gValorRestaItem->dTotOpeItem * ($this->dPropIVA / 100)) / (($this->dTasaIVA === 10) ? 1.1 : 1.05);
            if (! rounded_equal($dBasGravIVA, $this->dBasGravIVA)) {
                $validator->getMessageBag()->add('dBasGravIVA', 'Debe ser igual al resultado del calculo (dCantProSer * dPUniProSer) / 1.1');
            }
        } elseif( in_array($this->iAfecIVA, [2, 3])) {
            if ( $this->dBasGravIVA != 0) $validator->getMessageBag()->add('dBasGravIVA', 'El valor debe ser 0');
            if ( $this->dLiqIVAItem != 0) $validator->getMessageBag()->add('dLiqIVAItem', 'El valor debe ser 0');
        }

        if (ekuatia()->technicNoteVersion(14)) {
            if (in_array($this->iAfecIVA, [4])) {
                $dBasExe =
                    (100 * $this->parent->gValorItem->gValorRestaItem->dTotOpeItem * (100 - $this->dPropIVA))
                    /
                    (10000 + ($this->dTasaIVA * $this->dPropIVA));
                if (! rounded_equal($dBasExe, $this->dBasExe)) {
                    $validator->getMessageBag()->add('dBasExe', 'Debe ser igual al resultado del calculo [100 * dTotOpeItem * (100 - dPropIVA)] / [10000 + (dTasaIVA * dPropIVA)]');
                }
            } elseif(in_array($this->iAfecIVA, [1, 2, 3])) {
                if ($this->dBasExe != 0) $validator->getMessageBag()->add('dBasExe', 'El valor debe ser 0');
            }
        }
    }

    public function getDDesAfecIVAAttribute()
    {
        if ( isset($this->attributes['dDesAfecIVA'])) return $this->attributes['dDesAfecIVA'];

        return Arr::get($this->iAfecIVAOptions, $this->iAfecIVA);
    }

    public function getDPropIVAAttribute()
    {
        return ! is_null($this->attributes['dPropIVA']) ? ekuatia_number_format($this->attributes['dPropIVA'], 0) : null;
    }

    public function getDBasGravIVAAttribute()
    {
        $value = 0;
        if ( in_array($this->iAfecIVA, [2, 3])) return 0; // Return 0 if Excento o Exonerado
        if (! is_null($this->attributes['dBasGravIVA'])) return ekuatia_number_format($this->attributes['dBasGravIVA'], 8);
        if ( in_array($this->iAfecIVA, [1, 4])) {
            $value = (100 * $this->parent->gValorItem->gValorRestaItem->dTotOpeItem * $this->dPropIVA) / (10000 + ($this->dTasaIVA * $this->dPropIVA));
//            $value = ($this->parent->gValorItem->gValorRestaItem->dTotOpeItem * ($this->dPropIVA/100)) / ($this->dTasaIVA == 10 ? 1.1 : 1.05);
        }

        return ekuatia_number_format($value, 8);
    }

    public function getDBasExeAttribute()
    {
        $value = 0;
        if ( !in_array($this->iAfecIVA, [4])) return 0; // Return 0 if not partial
        if (! is_null($this->attributes['dBasExe'])) return ekuatia_number_format($this->attributes['dBasExe'], 8);
        $value =
            (100 * $this->parent->gValorItem->gValorRestaItem->dTotOpeItem * (100 - $this->dPropIVA))
            /
            (10000 + ($this->dTasaIVA * $this->dPropIVA));

        return ekuatia_number_format($value, 8);
    }

    public function getDLiqIVAItemAttribute()
    {
        $value = 0;
        if (! is_null($this->attributes['dLiqIVAItem'])) return ekuatia_number_format($this->attributes['dLiqIVAItem'], 8);
        if ( in_array($this->iAfecIVA, [1, 4])) $value = ($this->dBasGravIVA * ($this->dTasaIVA/100));

        return ekuatia_number_format($value, 8);
    }

    public function toArray()
    {
        $attributes = array_merge(parent::toArray(), [
            'dDesAfecIVA'       => $this->dDesAfecIVA,
        ]); // TODO: Change the autogenerated stub
        if (! ekuatia()->technicNoteVersion(14)) $attributes = Arr::except($attributes, ['dBasExe']);

        return $attributes;
    }

}