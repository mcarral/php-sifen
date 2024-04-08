<?php

namespace Mcarral\Sifen\Sifen;

use Illuminate\Support\MessageBag;
use Illuminate\Config\Repository as Config;

class QueryED extends ReceiveBase {

    protected function responseToMessage(\Illuminate\Contracts\Support\MessageBag $message, Config $response, $baseKey = '', $codeRes = 'dCodRes', $msgRes = 'dMsgRes')
    {
        return parent::responseToMessage($message, $response, $baseKey, $codeRes, $msgRes);
    }

    public function toSifen(\Closure $callback = null)
    {
        $this->request('rEnviConsDe', $this->ekuatia->config('soap.wsdls.de.query-cdc'), [
            'dId'   => $this->delivery->id,
            'dCDC'  => $this->argument('cdc', ($this->delivery->ed ? $this->delivery->ed->cdc : null))
        ], function(Config $response, $client) use ($callback) {
            if ($response->get('dCodRes') != '0422') {
                $message = new MessageBag();
                $message = $this->responseToMessage($message, $response);
                throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
            }
            if ($callback) $callback($response, $client);

            $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $response);
        });
    }

}