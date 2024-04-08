<?php


namespace Mcarral\Sifen\Sifen;


use Carbon\Carbon;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Mcarral\Sifen\Entities\ElectronicDocumentSections\ElectronicDocumentSectionBase;
use Mcarral\Sifen\Exceptions\EkuatiaBatchInProgressException;
use Mcarral\Sifen\Jobs\ElectronicDocumentConsumerDelivery as DeliveryCustomerJob;
use Mcarral\Sifen\Entities\ElectronicDocumentDelivery;

class ResultLoteDE extends SifenBase {

    public function toSifen()
    {
        if ($this->delivery->ed and $this->delivery->ed->approved) return;

        $this->request('rEnviConsLoteDe', $this->ekuatia->config('soap.wsdls.de.query-lote'), [
            'dId' => $this->delivery->id,
            'dProtConsLote' => $this->argument('lote'),
        ], function(Config $response) {
            if ($response->get('dCodResLot') === "0361") {
                if (
                    $this->delivery->ed_id and
                    $response->get('dFecProc') and
                    $this->ekuatia->config('ed.lote_result_check_query', 43200) and
                    Carbon::now()->diffInSeconds(Carbon::createFromFormat(Carbon::W3C, $response->get('dFecProc'))) > (int) $this->ekuatia->config('ed.lote_result_check_query', 43200)
                ) {
                    if ($this->queryEDAndDeliveryApprove(true)) return;
                }

                throw new EkuatiaBatchInProgressException(__("Batch in progress"));
            }

            if ($response->get('dCodResLot') === "0364") {
                $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $response, null, 'dCodResLot', 'dMsgResLot');
                $this->delivery->sifen_status = 0;
                $this->delivery->save();

                // re-send lote
                $ed = $this->delivery->ed;
                $ed->receiptDeliveries()->delete();
                $ed->unsetRelation('delivery');
                $ed->delivery->save();
                $ed->delivery->dispatchJob();
                return;
            }

            if (! in_array($response->get('dCodResLot'), ['0362'])) {
                $message = new MessageBag();
                $message = $this->responseToMessage($message, $response, null, 'dCodResLot', 'dMsgResLot');
                throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
            }

            $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $response, null, 'dCodResLot', 'dMsgResLot');

            $this->resProcessLote($response, function(Config $res, $countDocs) {
                if (!$this->delivery->ed or $this->delivery->ed->cdc !== $res->get('id')) {
                    $message = new MessageBag();
                    $message->add('99999', "El CDC en la respuesta del lote no corresponde.");
                    throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
                }

                if (Str::startsWith($res->get('dEstRes'), 'Aprobado')) {
                    $this->approveEd();
                } elseif($res->get('gResProc.dCodRes') == 1001) {
                    $this->approveEd();
                } else {
                    if ($countDocs <= 1) {
                        $message = $this->responseToMessage($this->delivery->sifen_message, $res, 'gResProc');
                        throw \Illuminate\Validation\ValidationException::withMessages($message->toArray());
                    }
                }

                $this->delivery->sifen_message = $this->responseToMessage($this->delivery->sifen_message, $res, 'gResProc');
            });

            $this->delivery->save();
        });
    }

    protected function approveEd()
    {
        $this->delivery->ed->approved = true;
        $this->delivery->ed->save();

        // send to customer
        try {
            $this->delivery->dispatchConsumerJob();
        } catch (\Exception $e) {
            Log::error($e);
        }
        /*$queue = app('ekuatia')->config('queue');
        $job = (new DeliveryCustomerJob($this->delivery))
            ->onConnection($queue['connection'])
            ->onQueue($queue['queue'])
            ->delay(Carbon::now()->addSeconds((app()->environment('production') ? 15 : 3)));
        dispatch($job);
        $this->delivery->consumer_status = ElectronicDocumentDelivery::STATUS_PENDING;*/
    }

    public function toConsumer()
    {
        $receiveED = new ReceiveED($this->delivery);
        $receiveED->toConsumer();
    }

    function resProcessLote($response, $callback) {
        $responses = $response->get('gResProcLote', []);
        $responses = (Arr::isAssoc($responses)) ? $responses = [$responses] : $responses;
        foreach ($responses as $res) {
            $callback(new Config($res), count($responses));
        }
    }

}
