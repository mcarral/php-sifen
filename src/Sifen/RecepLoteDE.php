<?php


namespace Mcarral\Sifen\Sifen;

use Carbon\Carbon;
use Illuminate\Support\MessageBag;
use Mcarral\Sifen\Entities\ElectronicDocumentDelivery;
use Mcarral\Sifen\Jobs\ElectronicDocumentDelivery as DeliveryJob;
use Mcarral\Sifen\Support\SimpleXMLElement;
use Illuminate\Config\Repository as Config;
use Mcarral\Sifen\Support\ZipFile;
use ZipArchive;

class RecepLoteDE extends ReceiveBase {

    public function getZipBase64()
    {
        $zip = new ZipFile();
        $batchSimpleXml = simplexml_load_string("<rLoteDE>" . $this->delivery->ed->xml->asXmlWithoutHeader() . "</rLoteDE>", SimpleXMLElement::class);
        $zip->addFile(xml_pretty($batchSimpleXml, true, true), 'DE');
        $zipFileString = base64_encode($zip->file());

        return $zipFileString;
    }

    public function toSifen()
    {
        if ($this->delivery->ed->approved) {
            throw \Mcarral\Sifen\Exceptions\ValidationException::withMessages([
                'global' => __('Electronic Document has approved')
            ]);
        }

        if ($this->queryEDAndDeliveryApprove()) return;

        $this->request('rEnvioLote', $this->ekuatia->config('soap.wsdls.de.receipt-lote'), [
            'dId' => $this->delivery->id,
            'xDE' => new \SoapVar('<xsd:xDE>' . $this->getZipBase64() . '</xsd:xDE>', \XSD_ANYXML)
        ], function(Config $response) {
            if (! in_array($response->get('dCodRes'), ['0300'])) {
                $message = new MessageBag();
                $message = $this->responseToMessage($message, $response, null);
                throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
            }

            $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $response, null);

            $delivery = new ElectronicDocumentDelivery([
                'ed_id' => $this->delivery->ed->id,
                'type' => ResultLoteDE::class,
                'args' => ['lote' => $response->get('dProtConsLote'), 'delay' => $response->get('dTpoProces')]]
            );
            $delivery->sifen_status = ElectronicDocumentDelivery::STATUS_PENDING;
            $delivery->save();

            // send worker job approved ed
            $minDelay = ekuatia()->config('lote.min-delay', 30);
            $queue = app('ekuatia')->config('queue');
            $job = (new DeliveryJob($delivery))
                ->onConnection($queue['connection'])
                ->onQueue($queue['queue']);
            if ($minDelay > 0) {
                $delay = (int) $response->get('dTpoProces');
                if ($delay < $minDelay) $delay = $minDelay;
                $delay = $delay + ceil(($delay * 30) / 100);
                $job->delay(Carbon::now()->addSeconds($delay));
            }
            dispatch($job);
        });
    }

}
