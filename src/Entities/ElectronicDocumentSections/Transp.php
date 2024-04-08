<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property int iTipTrans
 * @property string dDesTipTrans
 * @property int iModTrans
 * @property string dDesModTrans
 * @property int iRespFlete
 * @property string dIniTras
 * @property string dFinTras
 * @property CamSal gCamSal
 * @property CamEnt gCamEnt
 * @property VehTras gVehTras
 * @property CamTrans gCamTrans
 * Class Transp
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class Transp extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iTipTrans' => null, 'dDesTipTrans' => null, 'iModTrans' => null, 'dDesModTrans' => null, 'iRespFlete' => null,
        'cCondNeg' => null, 'dNuManif' => null, 'dNuDespImp' => null, 'dIniTras' => null, 'dFinTras' => null, 'cPaisDest' => null,
        'dDesPaisDest' => null,
        // groups
        'gCamSal' => null, 'gCamEnt' => null, 'gVehTras' => null, 'gCamTrans' => null,
    ];

    protected $tipTrans = [
        1 => 'Propio', 2 => 'Tercero',
    ];

    protected $modTrans = [
        1 => 'Terrestre', 2 => 'Fluvial', 3 => 'Aéreo', 4 => 'Multimodal'
    ];

    public static $respFlete = [
        1 => 'Emisor de la Factura Electrónica', 2 => 'Emisor de la Factura Electrónica', 3 => 'Tercero',
        4 => 'Agente intermediario del transporte (cuando intervenga)', 5 => 'Transporte propio',
    ];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iTipTrans'     => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->tipTrans))],       // Tipo de transporte
            'dDesTipTrans'  => ['required', 'string', 'between:6,7', Rule::in($this->tipTrans)],                 // Descripción de Tipo de transporte
            'iModTrans'     => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->modTrans))],       // Modalidad del transporte
            'dDesModTrans'  => ['required', 'string', 'between:5,10', Rule::in($this->modTrans)],                // Descripción de Modalidad del transporte
            'iRespFlete'    => ['required', 'integer', 'digits:1', Rule::in(array_keys(Transp::$respFlete))],      // Responsable del costo del flete
            'dIniTras'      => ['required', 'string', 'date_format:' . Transp::DATE_FORMAT],                     // Fecha estimada de inicio de traslado
            'dFinTras'      => ['required', 'string', 'date_format:' . Transp::DATE_FORMAT],                     // Fecha estimada de fin de traslado
        ]);
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'iTipTrans'     => 'Tipo de transporte',
            'dDesMotEmiNR'  => 'Descripción del tipo de transporte',
            'iModTrans'     => 'Modalidad del transporte',
            'dDesModTrans'  => 'Descripción de la modalidad del transporte',
            'iRespFlete'    => 'Responsable del costo del flete',
            'dIniTras'      => 'Fecha estimada de inicio de traslado',
            'dFinTras'      => 'Fecha estimada de fin de traslado',
        ]); // TODO: Change the autogenerated stub
    }

    public function getDDesTipTransAttribute()
    {
        if ( isset($this->attributes['dDesTipTrans'])) return $this->attributes['dDesTipTrans'];

        return Arr::get($this->tipTrans, $this->iTipTrans);
    }

    public function getDDesModTransAttribute()
    {
        if ( isset($this->attributes['dDesModTrans'])) return $this->attributes['dDesModTrans'];

        return Arr::get($this->modTrans, $this->iModTrans);
    }

    public function getDIniTrasAttribute()
    {
        return (isset($this->attributes['dIniTras'])) ? $this->dateToString($this->attributes['dIniTras']) : null;
    }

    public function getDFinTrasAttribute()
    {
        return (isset($this->attributes['dFinTras'])) ? $this->dateToString($this->attributes['dFinTras']) : null;
    }
}