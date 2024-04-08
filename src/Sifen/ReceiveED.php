<?php

namespace Mcarral\Sifen\Sifen;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\MessageBag;
use Mcarral\Sifen\Entities\ElectronicDocumentDelivery;
use Mcarral\Sifen\Exceptions\ValidationException;
use Mcarral\Sifen\Mail\KuDE;
use Mcarral\Sifen\Traits\EdConsumer;
use Illuminate\Config\Repository as Config;

class ReceiveED extends ReceiveBase {

    public function toSifen()
    {
        if ($this->delivery->ed->approved) {
            throw \Mcarral\Sifen\Exceptions\ValidationException::withMessages([
                'global' => __('Electronic Document has approved')
            ]);
        }

        $this->request('rEnviDe', $this->ekuatia->config('soap.wsdls.de.receipt'), [
            'dId' => $this->delivery->id,
            'xDE' => new \SoapVar('<xsd:xDE>' . $this->delivery->ed->xml->asXmlWithoutHeader() . '</xsd:xDE>', \XSD_ANYXML)
        ], function(Config $response) {
            if (! in_array($response->get('rProtDe.gResProc.dCodRes'), ['0260', '1005'])) {
                $message = new MessageBag();
                $message = $this->responseToMessage($message, $response);
                throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
            }

            $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $response);
            $this->delivery->ed->approved = true;
            $this->delivery->ed->save();
        });
    }

    public function toConsumer()
    {
        Mail::to($emails)
            ->send(new KuDE($this->delivery->ed));
    }

}
