<?php

namespace Mcarral\Sifen\Sifen;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mcarral\Sifen\Entities\ElectronicDocumentDelivery;
use Illuminate\Config\Repository as Config;
use Mcarral\Sifen\Exceptions\ValidationException;
use Mcarral\Sifen\Support\SimpleXMLElement;

/**
 * Class ReceiveBase
 * @method sendConsumer
 * @package Mcarral\Sifen\Sifen
 */
abstract class ReceiveBase extends SifenBase {

}