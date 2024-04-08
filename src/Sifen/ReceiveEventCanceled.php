<?php

namespace Mcarral\Sifen\Sifen;

class ReceiveEventCanceled extends ReceiveEvent {

    public function getSuccessCodes()
    {
        return array_merge(static::$SUCCESS_CODES, [
            '2414', // El CDC informado corresponde a un DTE anulado
            '4003', // CDC ya se encuentra con el mismo evento solicitado
        ]);
    }

}