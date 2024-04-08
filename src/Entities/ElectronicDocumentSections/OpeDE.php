<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Mcarral\Sifen\Entities\ElectronicDocument;

/**
 * Class CiODE
 * @property int iTipEmi
 * @property-read string dDesTipEmi
 * @property string dCodSeg
 * @property string dInfoEmi
 * @property string dInfoFisc
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class OpeDE extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iTipEmi' => null, 'dDesTipEmi' => null, 'dCodSeg' => null, 'dInfoEmi' => null, 'dInfoFisc' => null,
    ];

    protected $fillable = [
        'iTipEmi', 'dCodSeg', 'dInfoEmi'
    ];

    protected $secureCodeMaxRetry = 3;

    protected $iTipEmiOptions   = [1 => 'Normal', 2 => 'Contingencia'];

    public function defaults()
    {
        $this->iTipEmi = $this->ekuatia->config('operation.type-emission', 1);
    }

    public function rules()
    {
        return [
            'iTipEmi'       => ['required', 'integer', 'digits:1', Rule::in(array_keys($this->iTipEmiOptions))],        // Tipo de emisión
            'dDesTipEmi'    => ['required', 'string', 'between:6,12', Rule::in($this->iTipEmiOptions)],                 // Descripción del tipo de emisión
            'dCodSeg'       => ['required', ['regex', '/[0-9]+/'], 'digits:9'],                                         // Código de seguridad
            'dInfoEmi'      => ['nullable', 'string', 'between:1,3000'],                                                            // Información de interés del emisor respeto al DE
            'dInfoFisc'     => ['nullable', 'string', 'between:1,3000'],                                                            // Información de interés del Fisco respeto al DE
        ];
    }

    public function getDCodSegAttribute()
    {
        $value = Arr::get($this->attributes, 'dCodSeg');
        if ($value) return $value;

        // retry search unused secure code
        for ($i = 0; $i < $this->secureCodeMaxRetry; $i++) {
            $secCode = secure_code();
            if (ElectronicDocument::query()->where('sec_code', $value)->count()) continue;
            $value = $secCode;
        }
        if (! $value) throw new \LogicException("Electronic Document secure code unused not found. Retries {$this->secureCodeMaxRetry}");

        return ($this->attributes['dCodSeg'] = $value);
    }

    public function getDDesTipEmiAttribute()
    {
        if ( isset($this->attributes['dDesTipEmi'])) return $this->attributes['dDesTipEmi'];

        return (! is_null($this->iTipEmi)) ? Arr::get($this->iTipEmiOptions, $this->iTipEmi) : null;
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        if (! $this->dInfoFisc) $attributes = Arr::except($attributes, ['dInfoFisc']);

        return array_merge($attributes, [
            'dDesTipEmi'    => $this->dDesTipEmi,
        ]);
    }

}
