<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property int iTipTra
 * @property string dDesTipTra
 * @property int iTImp
 * @property string dDesTImp
 * @property string cMoneOpe
 * @property string dDesMoneOpe
 * @property string dCondTiCam
 * @property string dTiCam
 * @property-read SectionCollection|OblAfe[] gOblAfe
 * Class OpeCom
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class OpeCom extends ElectronicDocumentSectionBase {

    protected $attributes = ['iTipTra' => null, 'dDesTipTra' => null, 'iTImp' => null, 'dDesTImp' => null,
                             'cMoneOpe' => null, 'dDesMoneOpe' => null, 'dCondTiCam' => null, 'dTiCam' => null,
                             'gOblAfe' => null];

    protected $fillable = ['iTipTra', 'dDesTipTra', 'cMoneOpe', 'dDesMoneOpe', 'dTiCam'];

    static public $transactionsDes;
    static public $taxesDes;

    public function defaults()
    {
        $this->iTipTra = $this->ekuatia->config('operation.transaction-type', 1);
        $this->iTImp = 1;
    }

    public function rules()
    {
        return [
            'iTipTra'       => ['required_if:gTimb.iTiDE,1,2,3,4', 'integer', 'digits:1', Rule::in(array_keys(static::getTransactionsDesc()))],                         // Tipo de transacción
            'dDesTipTra'    => ['required_with:iTipTra', 'string', 'between:5,60', Rule::in(static::getTransactionsDesc())],                                  // Descripción del tipo de transacción
            'cMoneOpe'      => ['required', 'string', 'size:3', Rule::in(array_keys($this->ekuatia->config('encodings.currencies')))],      // Moneda de la operación
            'dDesMoneOpe'   => ['required', 'string', 'between:3,20', Rule::in($this->ekuatia->config('encodings.currencies'))],            // Descripción de la moneda de operación
            'dTiCam'        => ['required_unless:cMoneOpe,PYG', 'numeric', 'ekuatia_long:1-5p0-4']                                               // Tipo de Cambio
        ];
    }

    public function validateAdditionalData()
    {
        return [
            'gTimb.iTiDE' => $this->root->gTimb->iTiDE,
        ];
    }

    public function getDDesTipTraAttribute()
    {
        if ( isset($this->attributes['dDesTipTra'])) return $this->attributes['dDesTipTra'];

        return ( ! is_null($this->iTipTra)) ? Arr::get(static::getTransactionsDesc(), $this->iTipTra) : null;
    }

    public function setITImpAttribute($value)
    {
        $this->attributes['iTImp'] = $value;
        $this->dDesTImp = Arr::get(static::getTaxesDes(), $value);
    }

    public function setCMoneOpeAttribute($value)
    {
        $this->attributes['cMoneOpe'] = ( ! is_null($value)) ? $this->ekuatia->equivalence('Currency', $value, $value)->code_converted : null;
    }

    public function getDDesMoneOpeAttribute()
    {
        if ( isset($this->attributes['dDesMoneOpe'])) return $this->attributes['dDesMoneOpe'];

        return $this->ekuatia->config('encodings.currencies.' . $this->cMoneOpe);
    }

    public function getDCondTiCamAttribute()
    {
        return ($this->cMoneOpe !== "PYG") ? 1 : null; // Global (un solo tipo de cambio para todo el DE)
    }

    public function getDTiCamAttribute()
    {
        if ($this->dCondTiCam !== 1) return null; // Solo si la condicion del tipo de cambio es Global

        return ekuatia_number_format($this->attributes['dTiCam'], 4);
    }

    static public function getTransactionsDesc() {
        if (static::$transactionsDes) return static::$transactionsDes;

        return static::$transactionsDes = [
            1 => 'Venta de mercadería', 2 => 'Prestación de servicios', 3 => 'Mixto (Venta de mercadería y servicios)',
            4 => 'Venta de activo fijo', 5 => 'Venta de divisas', 6 => 'Compra de divisas',
            7 => 'Promociones o entrega de muestras', 8 => 'Donación', 9 => 'Anticipo', 10 => 'Compra de productos',
            11 => 'Compra de servicios', 12 => 'Venta de crédito fiscal'
        ];
    }

    /**
     * @return array
     */
    public static function getTaxesDes()
    {
        if ( static::$taxesDes) return static::$taxesDes;

        return static::$taxesDes = [
            1 => "IVA", 2 => "ISC General", 3 => "ISC Importación", 4 => "ISC Combustible", 5 => "ISC Cigarrillo",
            6 => "Ninguno"
        ];
    }

    public function toArray()
    {
        $arr = parent::toArray();
        if (! in_array($this->root->gTimb->iTiDE, [1, 2, 3, 4])) {
            $arr = Arr::except($arr, ['iTipTra', 'dDesTipTra']);
        }

        if (! $this->gOblAfe->count()) $arr = Arr::except($arr, ['gOblAfe']);

        return $arr;
    }

}
