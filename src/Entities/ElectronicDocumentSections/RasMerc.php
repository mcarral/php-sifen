<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

/**
 * Class RasMerc
 * @property int dNumLote
 * @property string fVenc
 * @property int dCntProd
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class RasMerc extends ElectronicDocumentSectionBase {

    protected $fillable = ['dNumLote', 'fVenc', 'dCntProd'];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'dNumLote'  => 'required,integer,digits_between:1,80',              // Número de lote
            'fVenc'     => 'required,string,size:25,date_format:Y-m-dH:i:s',    // Fecha de vencimiento
            'dCntProd'  => 'required,numeric,ekuatia_long:1-11p2',              // Cantidad de productos
        ]); // TODO: Change the autogenerated stub
    }

}