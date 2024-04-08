<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentEventSections;

use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * @property string dFecFirma
 * @property string dVerFor
 * @property GroupTiEvt gGroupTiEvt
 * Class Eve
 * @package Mcarral\Sifen\Entities\ElectronicDocumentEventSections
 */
class Eve extends ElectronicDocumentSectionBase {

    protected $attributes = [
        'dFecFirma' => null, 'dVerFor' => null, 'gGroupTiEvt' => null,
    ];

    protected $fillable = ['dVerFor'];

    protected $sectionPrefix = 'r';

    public function defaults()
    {
        $this->dFecFirma = Carbon::now();
        $this->dVerFor = $this->ekuatia->versionInt();

        return parent::defaults();
    }

    public function getDFecFirmaAttribute()
    {
        return $this->dateTimeToString(Arr::get($this->attributes, 'dFecFirma'));
    }

}