<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Class CISCope
 * @property int cCatISC
 * @property string dDesCatISC
 * @property int cTasaISC
 * @property int dBaseGravISCItem
 * @property int dlISCItem
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class CamISC extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'cCatISC' => null, 'dDesCatISC' => null, 'cTasaISC' => null, 'dBaseGravISCItem' => null, 'dlISCItem' => null
    ];

    protected $fillable = ['cCatISC', 'dDesCatISC', 'cTasaISC', 'dBaseGravISCItem', 'dlISCItem'];

    protected $occurrences = 0;

    public function rules()
    {
        return array_merge(parent::rules(), [
            'cCatISC'           => ['required', 'integer', 'digits:2', Rule::in(array_keys($this->ekuatia->config('encodings.isc_categories')))],   // Categoría de ISC
            'dDesCatISC'        => ['required', 'string', 'between:2,60', Rule::in($this->ekuatia->config('encodings.isc_categories'))],            // Descripción de la categoría del ISC
            'cTasaISC'          => ['required', 'numeric', 'ekuatia_long:1-2p2', Rule::in($this->ekuatia->config('encodings.isc_rates'))],          // Tasa del ISC
            'dBaseGravISC'      => ['required', 'numeric', 'ekuatia_long:1-11p2', 'same:gDtipDE.gValorItem.gValorItem.dTotOpeItem'],                       // Tasa del ISC
            'dlISCItem'         => ['required', 'numeric', 'ekuatia_long:1-11p2'],                                                                       // Liquidación del ISC por ítem
        ]); // TODO: Change the autogenerated stub
    }

    protected function validateAdditionalData()
    {
        return array_merge(parent::validateAdditionalData(), [
            'gDtipDE.gValorItem.gValorItem.dTotOpeItem' => $this->parent->dTotOpeItem,
        ]); // TODO: Change the autogenerated stub
    }

    protected function validating(Validator $validator)
    {
        if ($this->dlISCItem !== ($this->dBaseGravISC * $this->cTasaISC)) {
            $validator->getMessageBag()->add('dlISCItem', 'El valor tiene que coincidir con la multiplicacion de dBaseGravISC y cTasaISC');
        }
    }
}