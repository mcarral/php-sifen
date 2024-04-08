<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Class CamDEAsoc
 * @property int iTipDocAso
 * @property string dDesTipDocAso
 * @property string dCdCDERef
 * @property int dNTimDI
 * @property string dEstDocAso
 * @property string dPExpDocAso
 * @property string dNumDocAso
 * @property int iTipoDocAso
 * @property string dDTipoDocAso
 * @property string dFecEmiDI
 * @property string dNumComRet
 * @property string dNumResCF
 * @property integer iTipCons
 * @property string dDesTipCons
 * @property integer dNumCons
 * @property string dNumControl
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class CamDEAsoc extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iTipDocAso' => null, 'dDesTipDocAso' => null, 'dCdCDERef' => null, 'dNTimDI' => null, 'dEstDocAso' => null,
        'dPExpDocAso' => null, 'dNumDocAso' => null, 'iTipoDocAso' => null, 'dDTipoDocAso' => null, 'dFecEmiDI' => null,
        'dNumComRet' => null, 'dNumResCF' => null, 'iTipCons' => null, 'dDesTipCons' => null, 'dNumCons' => null, 'dNumControl' => null,
    ];

    const TIP_DOC_ASO_ELECTRONIC = 1;
    const TIP_DOC_ASO_PRINTED = 2;
    const TIP_DOC_ASO_ELECTRONIC_PROOF = 3;

    const PROOF_NOT_PAYABLE = 1;
    const PROOF_MICRO_BUSINESS = 2;

    static public $iTipDocAsoOptions = [
        CamDEAsoc::TIP_DOC_ASO_ELECTRONIC => 'Electrónico',
        CamDEAsoc::TIP_DOC_ASO_PRINTED => 'Impreso',
        CamDEAsoc::TIP_DOC_ASO_ELECTRONIC_PROOF => 'Constancia Electrónica',
    ];

    static public $iTipConsOptions = [
        CamDEAsoc::PROOF_NOT_PAYABLE => 'Constancia de no ser contribuyente',
        CamDEAsoc::PROOF_MICRO_BUSINESS => 'Constancia de microproductores'
    ];

    static public $iTipoDocAsoOptions = [
        1 => 'Factura', 2 => 'Nota de crédito', 3 => 'Nota de débito', 4 => 'Nota de remisión',
        5 => 'Comprobante de retención'
    ];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iTipDocAso'    => ['required', 'integer', 'digits:1', Rule::in(array_keys(static::$iTipDocAsoOptions))],
            'dCdCDERef'     => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_ELECTRONIC, 'string', 'size:44'],

            'dNTimDI'       => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_PRINTED, 'integer', 'digits:8'],
            'dEstDocAso'    => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_PRINTED, 'numeric', 'digits:3'],
            'dPExpDocAso'   => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_PRINTED, 'numeric', 'digits:3'],
            'dNumDocAso'    => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_PRINTED, 'numeric', 'digits:7'],
            'iTipoDocAso'   => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_PRINTED, 'integer', Rule::in(array_keys(static::$iTipoDocAsoOptions))],
            'dFecEmiDI'     => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_PRINTED, 'string', 'date_format:' . CamDEAsoc::DATE_FORMAT],

            'iTipCons'     => ['required_if:iTipDocAso,' . static::TIP_DOC_ASO_ELECTRONIC_PROOF, 'integer', 'digits:1', Rule::in(array_keys(static::$iTipDocAsoOptions))],
            'dNumCons'     => ['numeric', 'digits:11'],
            'dNumControl'  => ['string', 'size:8'],
        ]); // TODO: Change the autogenerated stub
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'iTipDocAso'    => 'Tipo de documento asociado',
            'dCdCDERef'     => 'CDC del DTE referenciado',
            'dNTimDI'       => 'Nro. timbrado documento impreso de referencia',
            'dEstDocAso'    => 'Establecimiento',
            'dPExpDocAso'   => 'Punto de expedición',
            'dNumDocAso'    => 'Número del documento',
            'iTipoDocAso'   => 'Tipo de documento impreso',
            'dFecEmiDI'     => 'Fecha de emisión del documento impreso de referencia',
            'iTipCons'      => 'Tipo de constancia',
            'dDesTipCons'   => 'Descripción del tipo de constancia',
            'dNumCons'      => 'Número de constancia',
            'dNumControl'   => 'Número de control de la constancia',
        ]); // TODO: Change the autogenerated stub
    }

    public function getDDesTipDocAsoAttribute()
    {
        return (! is_null($this->iTipDocAso)) ? Arr::get(static::$iTipDocAsoOptions, $this->iTipDocAso, '') : null;
    }

    public function setDEstDocAsoAttribute($value)
    {
        if ($value) $value = str_pad($value, 3, '0', STR_PAD_LEFT);
        $this->attributes['dEstDocAso'] = $value;

        return $value;
    }

    public function setDPExpDocAsoAttribute($value)
    {
        if ($value) $value = str_pad($value, 3, '0', STR_PAD_LEFT);
        $this->attributes['dPExpDocAso'] = $value;

        return $value;
    }

    public function setDNumDocAsoAttribute($value)
    {
        if ( strlen($value) > 7) $value = substr($value, strlen($value)-7, strlen($value));
        if ( strlen($value) < 7) $value = str_pad($value, 7, '0', STR_PAD_LEFT);

        $this->attributes['dNumDocAso'] = $value;

        return $value;
    }

    public function getDDTipoDocAsoAttribute()
    {
        return (! is_null($this->iTipoDocAso)) ? Arr::get(static::$iTipoDocAsoOptions, $this->iTipoDocAso, '') : null;
    }

    public function getDFecEmiDIAttribute()
    {
        return (isset($this->attributes['dFecEmiDI'])) ? $this->dateToString($this->attributes['dFecEmiDI']) : null;
    }

    public function getDDesTipConsAttribute()
    {
        return (! is_null($this->iTipCons)) ? Arr::get(static::$iTipConsOptions, $this->iTipCons, '') : null;
    }

    public function setDNumConsAttribute($value)
    {
        if ( strlen($value) > 11) $value = substr($value, strlen($value)-11, strlen($value));
        if ( strlen($value) < 11) $value = str_pad($value, 11, '0', STR_PAD_LEFT);

        $this->attributes['dNumCons'] = $value;

        return $value;
    }

    public function setDNumControlAttribute($value)
    {
        if ( strlen($value) > 8) $value = substr($value, strlen($value)-8, strlen($value));
        if ( strlen($value) < 8) $value = str_pad($value, 8, '0', STR_PAD_LEFT);

        $this->attributes['dNumControl'] = $value;

        return $value;
    }

    public function toArray()
    {
        if ($this->iTipDocAso !== CamDEAsoc::TIP_DOC_ASO_ELECTRONIC) return parent::toArray();

        return Arr::only(parent::toArray(), ['iTipDocAso', 'dDesTipDocAso', 'dCdCDERef']);
    }

}
