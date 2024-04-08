<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Class CamFE
 * @property int iIndPres
 * @property string dDesIndPres
 * @property-read CompPub gCompPub
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class CamFE extends ElectronicDocumentSectionBase {

    static public $operationsDes;

    protected $attributes = [
        'iIndPres' => null , 'dDesIndPres' => null, 'gCompPub' => null,
    ];

    protected $fillable = ['iIndPres', 'dDesIndPres'];

    public function defaults()
    {
        $this->iIndPres = $this->ekuatia->config('operation.operation-type', 1);
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iIndPres'      => ['required', 'integer', 'digits:1', Rule::in(array_keys(static::getOperationsDesc()))],  // Indicador de Presencia
            'dDesIndPres'   => array_merge(
                ['required', 'string', 'between:10,30', ],
                ($this->iIndPres !== 9) ? [Rule::in(static::getOperationsDesc())] : [] // validate in array if not other description
            ),
        ]); // TODO: Change the autogenerated stub
    }

    public function setIIndPresAttribute($value)
    {
        $this->attributes['iIndPres'] = $value ? $value : 1;
    }

    /*public function getIIndPresAttribute()
    {
        $value = $this->attributes['iIndPres'];
        $isBank = false;
        $this->root->gDtipDE->gCamCond->gPaConEIni->each(function(PaConEIni $row) use (&$isBank) {
            if (! in_array($row->iTiPago, [16])) return;
            $isBank = true;
        });
    }*/

    public function getDDesIndPresAttribute()
    {
        if ( isset($this->attributes['dDesIndPres'])) return $this->attributes['dDesIndPres'];

        return (! is_null($this->iIndPres)) ? Arr::get(static::getOperationsDesc(), $this->iIndPres) : null;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $attributes = array_merge(parent::toArray(), [
            'dDesIndPres'   => $this->dDesIndPres,
        ]);
        if ($this->root->gDatGralOpe->gDatRec->iTiOpe != 3) $attributes = Arr::except($attributes, ['gCompPub']);

        return $attributes;
    }

    /**
     * @return array
     */
    static public function getOperationsDesc()
    {
        if (static::$operationsDes) return static::$operationsDes;

        return static::$operationsDes = [
            1 => 'Operación presencial', 2 => 'Operación electrónica', 3 => 'Operación telemarketing',
            4 => 'Venta a domicilio', 5 => 'Operación bancaria',    9 => ekuatia()->config('operation.operation-other-name', '')
        ];
    }

}