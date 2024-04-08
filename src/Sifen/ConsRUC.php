<?php


namespace Mcarral\Sifen\Sifen;


use Illuminate\Config\Repository as Config;
use Illuminate\Support\MessageBag;

class ConsRUC extends ReceiveBase  {

    public function toSifen()
    {
        $this->request('rEnviConsRUC', $this->ekuatia->config('soap.wsdls.ruc'), [
            'dId'   => $this->delivery->id,
            'dRUCCons'  => $this->argument('ruc')
        ], function(Config $response) {
            if ($response->get('dCodRes') != '0502') {
                $message = new MessageBag();
                $message = $this->responseToMessage($message, $response, null);
                throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
            }

            $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $response);
        });
    }

}