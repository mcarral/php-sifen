<?php


namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;


use Illuminate\Support\Arr;

/**
 * Class CamAE
 * @property int iNatVen
 * @property string dDesNatVen
 * @property int iTipIDVen
 * @property string dDTipIDVen
 * @property string dNumIDVen
 * @property string dNomVen
 * @property string dDirVen
 * @property int dNumCasVen
 * @property int cDepVen
 * @property string dDesDepVen
 * @property int cCiuVen
 * @property string dDesCiuVen
 * @property string dDirProv
 * @property int cDepProv
 * @property int cDisProv
 * @property int dDesDisProv
 * @property string dDesDepProv
 * @property int cCiuProv
 * @property string dDesCiuProv
 * @package Mcarral\Sifen\Entities\ElectronicDocumentSections
 */
class CamAE extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'iNatVen' => null, 'dDesNatVen' => null, 'iTipIDVen' => null, 'dDTipIDVen' => null,
        'dNumIDVen' => null, 'dNomVen' => null, 'dDirVen' => null, 'dNumCasVen' => null, 'cDepVen' => null,
        'dDesDepVen' => null, 'cCiuVen' => null, 'dDesCiuVen' => null, 'dDirProv' => null, 'cDepProv' => null,
        'dDesDepProv' => null, 'cDisProv' => null, 'dDesDisProv' => null, 'cCiuProv' => null, 'dDesCiuProv' => null,
    ];

    public function rules()
    {
        return array_merge(parent::rules(), [
            'iNatVen'       => ['required', 'integer', 'digits:1'],
            'dDesNatVen'    => ['required', 'string', 'between:10,16'],
            'iTipIDVen'     => ['required', 'integer', 'digits:1'],
            'dDTipIDVen'    => ['required', 'string', 'between:9,20'],
            'dNumIDVen'     => ['required', 'string', 'between:1,20'],
            'dNomVen'       => ['required', 'string', 'between:4,60'],
            'dDirVen'       => ['required', 'string', 'between:1,255'],
            'dNumCasVen'    => ['required', 'integer', 'digits_between:1,6'],
            'cDepVen'       => ['required', 'integer', 'digits_between:1,2'],
            'dDesDepVen'    => ['required', 'string', 'between:6,16'],
            'cDisVen'       => ['integer', 'digits_between:1,4'],
            'dDesDisVen'    => ['string', 'between:1,30'],
            'cCiuVen'       => ['required', 'integer', 'digits_between:1,5'],
            'dDesCiuVen'    => ['required', 'string', 'between:1,30'],
            'dDirProv'      => ['required', 'string', 'between:1,255'],
            'cDepProv'      => ['required', 'integer', 'digits_between:1,2'],
            'dDesDepProv'   => ['required', 'string', 'between:6,16'],
            'cDisProv'      => ['integer', 'digits_between:1,4'],
            'dDesDisProv'   => ['string', 'between:1,30'],
            'cCiuProv'      => ['required', 'integer', 'digits_between:1,5'],
            'dDesCiuProv'   => ['required', 'string', 'between:1,30'],
        ]);
    }

    public function rulesAttributes()
    {
        return array_merge(parent::rulesAttributes(), [
            'iNatVen'        => 'Naturaleza del vendedor',
            'dDesNatVen'     => 'Descripción de la naturaleza del vendedor',
            'iTipIDVen'      => 'Tipo de documento de identidad del vendedor',
            'dDTipIDVen'     => 'Descripción del tipo de documento de identidad del vendedor',
            'dNumIDVen'      => 'Número de documento de identidad del vendedor',
            'dNomVen'        => 'Nombre y apellido del vendedor',
            'dDirVen'        => 'Dirección del vendedor',
            'dNumCasVen'     => 'Número de casa del vendedor',
            'cDepVen'        => 'Código del departamento del vendedor',
            'dDesDepVen'     => 'Descripción del departamento del vendedor',
            'cDisVen'        => 'Código del distrito del vendedor',
            'dDesDisVen'     => 'Descripción del distrito del vendedor',
            'cCiuVen'        => 'Código de la ciudad del vendedor',
            'dDesCiuVen'     => 'Descripción de la ciudad del vendedor',
            'dDirProv'       => 'Lugar de la transacción',
            'cDepProv'       => 'Código del departamento donde se realiza la transacción',
            'dDesDepProv'    => 'Descripción del departamento donde se realiza la transacción',
            'cDisProv'       => 'Código del distrito donde se realiza la transacción',
            'dDesDisProv'    => 'Descripción del distrito donde se realiza la transacción',
            'cCiuProv'       => 'Código de la ciudad donde se realiza la transacción',
            'dDesCiuProv'    => 'Descripción de la ciudad donde se realiza la transacción',
        ]);
    }

    public function getDDesNatVenAttribute() {
        return Arr::get([1 => "No contribuyente", 2 => "Extranjero"], $this->iNatVen, null);
    }

    public function getDDTipIDVenAttribute()
    {
        if( isset($this->attributes['dDTipIDVen'])) return $this->attributes['dDTipIDVen'];

        return ( ! is_null($this->iTipIDVen)) ? Arr::get([
            1 => "Cédula paraguaya", 2 => "Pasaporte", 3 => "Cédula extranjera", 4 => "Carnet de residencia",
        ], $this->iTipIDVen) : null;
    }

    public function setCCiuVenAttribute($value)
    {
        if ( ! is_null($value)) $value = ekuatia()->equivalence('City', $value, $value)->code_converted;
        $this->attributes['cCiuVen'] = $value;
    }

    public function getDDesCiuVenAttribute()
    {
        if ( isset($this->attributes['dDesCiuVen'])) return $this->attributes['dDesCiuVen'];

        return ( ! is_null($this->cCiuVen)) ? Arr::get($this->ekuatia->config('encodings.locations'), $this->cCiuVen . ".name") : null;
    }

    public function setCDisVenAttribute($value)
    {
        if ( ! is_null($value)) $value = ekuatia()->equivalence('District', $value, $value)->code_converted;
        $this->attributes['cDisVen'] = $value;
    }

    public function getCDisVenAttribute()
    {
        $value = Arr::get($this->attributes, 'cDisVen');
        if (! is_null($value)) return $value;

        $city = $this->ekuatia->config('encodings.locations.' . $this->cCiuVen);
        return ( is_null($city)) ? $city : $city['district'];
    }

    public function getDDesDisVenAttribute()
    {
        if ( isset($this->attributes['dDesDisVen'])) return $this->attributes['dDesDisVen'];

        return ( ! is_null($this->cDisVen)) ? Arr::get($this->ekuatia->config('encodings.districts'), $this->cDisVen . '.name') : null;
    }

    public function setCDepVenAttribute($value)
    {
        if ( ! is_null($value)) $value = ekuatia()->equivalence('Province', $value, $value)->code_converted;
        $this->attributes['cDepVen'] = $value ?: '';
    }

    public function getCDepVenAttribute()
    {
        $value = Arr::get($this->attributes, 'cDepVen');
        if (! is_null($value)) return $value;

        $city = $this->ekuatia->config('encodings.locations.' . $this->cCiuVen);
        return ( is_null($city)) ? $city : $city['department'];
    }

    public function getDDesDepVenAttribute()
    {
        if ( isset($this->attributes['dDesDepVen'])) return $this->attributes['dDesDepVen'];

        return ( ! is_null($this->cDepVen)) ? Arr::get($this->ekuatia->config('encodings.departments'), $this->cDepVen . ".name") : '';
    }

    public function setCCiuProvAttribute($value)
    {
        if ( ! is_null($value)) $value = ekuatia()->equivalence('City', $value, $value)->code_converted;
        $this->attributes['cCiuProv'] = $value;
    }

    public function getDDesCiuProvAttribute()
    {
        if ( isset($this->attributes['dDesCiuProv'])) return $this->attributes['dDesCiuProv'];

        return ( ! is_null($this->cCiuProv)) ? Arr::get($this->ekuatia->config('encodings.locations'), $this->cCiuProv . ".name") : null;
    }

    public function setCDisProvAttribute($value)
    {
        if ( ! is_null($value)) $value = ekuatia()->equivalence('District', $value, $value)->code_converted;
        $this->attributes['cDisProv'] = $value;
    }

    public function getCDisProvAttribute()
    {
        $value = Arr::get($this->attributes, 'cDisProv');
        if (! is_null($value)) return $value;

        $city = $this->ekuatia->config('encodings.locations.' . $this->cCiuProv);
        return ( is_null($city)) ? $city : $city['district'];
    }

    public function getDDesDisProvAttribute()
    {
        if ( isset($this->attributes['dDesDisProv'])) return $this->attributes['dDesDisProv'];

        return ( ! is_null($this->cDisProv)) ? Arr::get($this->ekuatia->config('encodings.districts'), $this->cDisProv . '.name') : null;
    }

    public function setCDepProvAttribute($value)
    {
        if ( ! is_null($value)) $value = ekuatia()->equivalence('Province', $value, $value)->code_converted;
        $this->attributes['cDepProv'] = $value ?: '';
    }

    public function getCDepProvAttribute()
    {
        $value = Arr::get($this->attributes, 'cDepProv');
        if (! is_null($value)) return $value;

        $city = $this->ekuatia->config('encodings.locations.' . $this->cCiuProv);
        return ( is_null($city)) ? $city : $city['department'];
    }

    public function getDDesDepProvAttribute()
    {
        if ( isset($this->attributes['dDesDepProv'])) return $this->attributes['dDesDepProv'];

        return ( ! is_null($this->cDepProv)) ? Arr::get($this->ekuatia->config('encodings.departments'), $this->cDepProv . ".name") : '';
    }
}
