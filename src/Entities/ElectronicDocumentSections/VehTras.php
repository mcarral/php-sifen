<?php


namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;


use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property string dTiVehTras
 * @property string dMarVeh
 * @property integer dTipIdenVeh
 * @property string dNroIDVeh
 * @property string dNroMatVeh
 * Class VehTras
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class VehTras extends ElectronicDocumentSectionBase {
    protected $attributes = [
        'dTiVehTras' => null, 'dMarVeh' => null, 'dTipIdenVeh' => null, 'dNroIDVeh' => null, 'dAdicVeh' => null,
        'dNroMatVeh' => null, 'dNroVuelo' => null,
    ];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'dTiVehTras'     => ['required', 'string', 'between:4,10'],
            'dMarVeh'        => ['required', 'string', 'between:1,10'],
            'dTipIdenVeh'    => ['required', 'integer', 'digits:1', Rule::in(array_keys([
                1 => 'Número de identificación del vehículo',
                2 => 'Número de matrícula del vehículo'
            ]))],
            'dNroIDVeh'      => ['required_if:dTipIdenVeh,1', 'string', 'between:1,20'],
            'dNroMatVeh'     => ['required_if:dTipIdenVeh,2', 'string', 'between:6,7'],
        ]);
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'dTiVehTras'    => 'Tipo de vehículo',
            'dMarVeh'       => 'Marca',
            'dTipIdenVeh'   => 'Tipo de identificación del vehículo',
            'dNroIDVeh'     => 'Número de identificación del vehículo',
            'dNroMatVeh'    => 'Número de matrícula del vehículo',
        ]); // TODO: Change the autogenerated stub
    }

    public function getDTiVehTrasAttribute()
    {
        return $this->root->gDtipDE->gTransp->dDesModTrans;
    }

    public function setDMarVehAttribute($value)
    {
        $value = substr($value, 0, 10);
        $this->attributes['dMarVeh'] = $value;

        return $value;
    }

}