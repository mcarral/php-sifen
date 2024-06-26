<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Support\Arr;

class PagCheq extends ElectronicDocumentSectionBase {

    protected $attributes = ['dNumCheq' => null, 'dBcoEmi' => null];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'dNumCheq'  => ['required', 'string', 'size:8', 'regex:/[0-9]{8}/i'],
            'dBcoEmi'   => ['required', 'string', 'between:4,20']
        ]); // TODO: Change the autogenerated stub
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'dNumCheq'  => 'Número de cheque',
            'dBcoEmi'   => 'Banco emisor'
        ]); // TODO: Change the autogenerated stub
    }

    public function getDNumCheqAttribute()
    {
        $value = Arr::get($this->attributes, 'dNumCheq');

        return ( is_null($value)) ? $value : str_pad(filter_var(str_replace('-', '', $value), FILTER_SANITIZE_NUMBER_INT), 8, '0', STR_PAD_LEFT);
    }

}