<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Class DetVehN
 * @property int iTipOpVN
 * @property string dDesTipOpVN
 * @property string dChasis
 * @property string dColorCod
 * @property string dDesColor
 * @property int dPotVeh
 * @property int dCapMot
 * @property int dPNet
 * @property int dPBruto
 * @property string dNSerie
 * @property int iTipCom
 * @property string dDesTipCom
 * @property string dNroMotor
 * @property int dCapTracc
 * @property int dDisEj
 * @property int dAnoMod
 * @property int dAnoFab
 * @property int iTP
 * @property string dDesTP
 * @property int cTipVeh
 * @property int cEspVeh
 * @property int iCondVeh
 * @property string dDesCondVeh
 * @property int dLotac
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class DetVehN extends ElectronicDocumentSectionBase {

    protected $fillable = [
        'iTipOpVN', 'dDesTipOpVN', 'dChasis', 'dColorCod', 'dDesColor', 'dPotVeh', 'dCapMot', 'dPNet', 'dPBruto',
        'dNSerie', 'iTipCom', 'dDesTipCom', 'dNroMotor', 'dCapTracc', 'dDisEj', 'dAnoMod', 'dAnoFab', 'iTP', 'dDesTP',
        'cTipVeh', 'cEspVeh', 'iCondVeh', 'dDesCondVeh', 'dLotac',
    ];

    protected $iTipOpVNOptions = [
        1 => 'Venta a representante', 2 => 'Venta al consumidor final', 3 => 'Venta a gobierno',
        4 => 'Venta a flota de vehículos', 9 => null
    ];

    protected $iTipComOptions = [
        1 => 'Gasolina', 2 => 'Diésel', 3 => 'Etanol', 4 => 'GNV', 5 => 'Gasolina/GNV', 6 => ' Gasolina/Etanol',
        7 => 'Gasolina/Etanol/GNV', 8 => 'Gasolina/Eléctrico', 9 => null,
    ];

    protected $iTPOptions = [
        1 => 'Sólida', 2 => 'Metálica', 3 => 'Perla', 4 => 'Fosca', 9 => null
    ];

    protected $iCondVehOptions = [
        1 => 'acabado', 2 => 'semi-acabado', 3 => 'inacabado'
    ];

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'iTipOpVN'      => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->iTipOpVNOptions))], // Tipo de operación de venta de vehículos
            'dDesTipOpVN'   => ['required', 'integer', 'digits:1', Rule::in($this->iTipOpVNOptions)],             // Descripción del tipo de operación de venta de vehículos
            'dChasis'       => ['required', 'string', 'size:17'],                                                 // Chasis del vehículo
            'dColorCod'     => ['required', 'string', 'between:1,20'],                                            // Color del vehículo
            'dDesColor'     => ['required', 'string', 'between:1,60'],                                            // Descripción del color del vehículo
            'dPotVeh'       => ['required', 'integer', 'digits_between:1,4'],                                     // Potencia del motor (CV)
            'dCapMot'       => ['required', 'integer', 'digits_between:1,4'],                                     // Capacidad del motor
            'dPNet'         => ['required', 'integer', 'ekuatia_long:1-11p0-4'],                                  // Peso Neto
            'dPBruto'       => ['required', 'integer', 'ekuatia_long:1-11p0-4'],                                  // Peso Bruto
            'dNSerie'       => ['required', 'string', 'between:1,9'],                                             // Número serial
            'iTipCom'       => ['required', 'integer', 'digists:2', Rule::in(array_keys($this->iTipComOptions))], // Tipo de combustible
            'dDesTipCom'    => ['required', 'string', 'between:10,50', Rule::in($this->iTipComOptions)],          // Descripción del tipo de combustible
            'dNroMotor'     => ['required', 'integer', 'digists_between:1,21'],                                   // Número del motor
            'dCapTracc'     => ['required', 'integer', 'ekuatia_long:1-11p0-4'],                                  // Capacidad máxima de tracción
            'dDisEj'        => ['required', 'integer', 'ekuatia_long:1-11p0-4'],                                  // Distancia entre ejes
            'dAnoMod'       => ['required', 'integer', 'digits:4'],                                               // Año del modelo de fabricación
            'dAnoFab'       => ['required', 'integer', 'digits:4'],                                               // Año de fabricación
            'iTP'           => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->iTPOptions))],      // Tipo de pintura
            'dDesTP'        => ['required', 'string', 'between:10,50', Rule::in($this->iTPOptions)],              // Descripción del tipo de pintura

            'cTipVeh'       => ['required', 'integer', 'digits_between:1,2', Rule::in(array_keys($this->ekuatia->config('encodings.vehicles_type')))],  // Tipo del vehículo
            'cEspVeh'       => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->ekuatia->config('encodings.vehicles_type')))],            // Especie del vehículo

            'iCondVeh'      => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->iCondVehOptions))], // Condición del vehículo
            'dDesCondVeh'   => ['required', 'string', 'size:15', Rule::in($this->iCondVehOptions)],               // Descripción de la condición de vehículo
            'dLotac'        => ['required', 'integer', 'digits_between:1,3'],                                     // Capacidad máxima de pasajeros
        ]); // TODO: Change the autogenerated stub
    }

    /**
     * @return string
     */
    public function getDDesTipOpVNAttribute()
    {
        if ( isset($this->attributes['dDesTipOpVN'])) return $this->attributes['dDesTipOpVN'];

        return Arr::get($this->iTipOpVNOptions, $this->iTipOpVN);
    }

    /**
     * @return string
     */
    public function getDDesTipComAttribute()
    {
        if ( isset($this->attributes['dDesTipCom'])) return $this->attributes['dDesTipCom'];

        return Arr::get($this->iTipComOptions, $this->iTipCom);
    }

    /**
     * @return string
     */
    public function getDDesTPAttribute()
    {
        if ( isset($this->attributes['dDesTP'])) return $this->attributes['dDesTP'];

        return Arr::get($this->iTPOptions, $this->iTP);
    }

    /**
     * @return string
     */
    public function getDDesCondVehAttribute()
    {
        if ( isset($this->attributes['dDesCondVeh'])) return $this->attributes['dDesCondVeh'];

        return Arr::get($this->iCondVehOptions, $this->iCondVeh);
    }
}