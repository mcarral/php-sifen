<?php

namespace Mcarral\Sifen\Sifen;

use Carbon\Carbon;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mcarral\Sifen\Contracts\EkuatiaContract;
use Mcarral\Sifen\Entities\EkuatiaSetting;
use Mcarral\Sifen\Entities\ElectronicDocumentDelivery;
use Illuminate\Config\Repository as Config;
use Mcarral\Sifen\Exceptions\ValidationException;
use Mcarral\Sifen\Support\SimpleXMLElement;
use Illuminate\Contracts\Events\Dispatcher;

abstract class SifenBase {

    static public $eventsDispatcher = null;

    public $delivery;

    /**
     * @var EkuatiaContract
     */
    protected $ekuatia;

    protected $client;

    protected $tmpCert = null;

    public function __construct(ElectronicDocumentDelivery $delivery)
    {
        $this->delivery = $delivery;
        $this->ekuatia = ekuatia();

        register_shutdown_function([$this, 'removeTmpCertificate']);
    }

    public function __destruct()
    {
        $this->removeTmpCertificate();
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function argument($key, $default = null)
    {
        return Arr::get($this->delivery->args, $key, $default);
    }

    /**
     * Create TMP file for certificate key: local_cert or stream_context_create receive path to cert not content
     *
     * @return string
     * @throws \Exception
     */
    public function createTmpCertificate()
    {
        if ($this->tmpCert) return $this->tmpCert;
        $ekuatiaSetting = (new EkuatiaSetting())->find();
        $certFileName = (! empty($ekuatiaSetting->CertPem)) ? $ekuatiaSetting->CertPem : $ekuatiaSetting->CertPrivate;

        $disk = config('filesystems.disks.ekuatia-cert');
        if ($disk['driver'] == "local") {
            $certPath = $disk['root'] . (Str::endsWith($disk['root'], DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR) . $certFileName;
            if (file_exists($certPath)) return $certPath;
        }

        $tmpf = @tempnam(sys_get_temp_dir(), "ekuatia-pem");
        if (! $tmpf) throw new \Exception("Error create tmp for certificate.");
        file_put_contents($tmpf, ekuatia()->storageCert($certFileName));

        return ($this->tmpCert = $tmpf);
    }

    public function removeTmpCertificate()
    {
        if (! $this->tmpCert) return;
        if (! file_exists($this->tmpCert)) return;
        unlink($this->tmpCert);
        $this->tmpCert = null;
    }

    /**
     * Soap client connect to SIFEN
     * Observation:
     *      if error connect check openssl security level in server, refs:
     *      Centos: https://stackoverflow.com/questions/60676042/ssl-error-dh-key-is-too-small-when-connecting-to-sql-server-using-odbc-17-and
     *      Ubuntu: https://askubuntu.com/questions/1233186/ubuntu-20-04-how-to-set-lower-ssl-security-level
     *
     * @param $wsdl
     * @param array $options
     * @return SoapClient
     * @throws \Exception
     */
    public function soapClient($wsdl, $options = [])
    {
        $ekuatiaSetting = (new EkuatiaSetting())->find();

        ini_set('soap.wsdl_cache_enabled', $this->ekuatia->config('soap.cache', true) ? '1' : '0');
        ini_set('soap.wsdl_cache_ttl', (string) $this->ekuatia->config('soap.cache_ttl', 86400));

//        if (! array_key_exists('local_cert', $options)) $options['local_cert'] = $this->createTmpCertificate();

        return new SoapClient($wsdl, array_merge([
            'encoding'          => 'UTF-8',
            'cache_wsdl'        => ($this->ekuatia->config('soap.cache', true) ? WSDL_CACHE_BOTH : WSDL_CACHE_NONE),
            'soap_version'      => SOAP_1_2,
            'keepalive'         => false,

//            'local_cert'        => $tmpf, // move to request
            'passphrase'        => (! empty($ekuatiaSetting->CertPassphrase)) ? $ekuatiaSetting->CertPassphrase : null,

            /*'stream_context'    => stream_context_create([
                'ssl' => [
                    //'cafile'            => $this->ekuatia->storageCert($certificate['public']),
                ]
            ])*/
        ], $options));
    }

    /**
     * @param MessageBag $message
     * @param Config $response
     * @param string|array $baseKey
     * @param string|array $codeRes
     * @param string|array $msgRes
     * @return MessageBag
     */
    protected function responseToMessage(MessageBag $message, Config $response, $baseKey = 'rProtDe.gResProc', $codeRes = 'dCodRes', $msgRes = 'dMsgRes')
    {
        $baseKeys = array_unique(array_merge((is_array($baseKey) ? $baseKey : [$baseKey]), [
            'rProtDe.gResProc'
        ]));
        $codeRess = array_unique(array_merge((is_array($codeRes) ? $codeRes : [$codeRes]), [
            'dCodRes'
        ]));
        $msgRess = array_unique(array_merge((is_array($msgRes) ? $msgRes : [$msgRes]), [
            'dMsgRes'
        ]));
        if (count($codeRess) !== count($msgRess)) throw new \Exception("Ekuatia exception parse response diff count codeRes !== msgRes");
        foreach ($baseKeys as $baseKey) {
            $responses = ($baseKey) ? $response->get($baseKey, []) : $response->all();
            $responses = (Arr::isAssoc($responses)) ? $responses = [$responses] : $responses;
            foreach ($responses as $resp) {
                foreach ($codeRess as $codeKey => $codeRes) {
                    if (! array_key_exists($codeRes, $resp)) continue;
                    $message->add($resp[$codeRes], $resp[$msgRess[$codeKey]]);
                }
            }
        }

        return $message;
    }

    /**
     * @param $method
     * @param $wsdl
     * @param array $params
     * @param \Closure $callback
     * @param array $options
     * @param string $responseField
     * @return mixed|null|Config
     * @throws \SoapFault
     */
    public function request($method, $wsdl, $params = [], \Closure $callback = null, $options = [], $responseField = 'sifen_response')
    {
        $res = null;
        $client = $this->soapClient($wsdl, array_merge(['local_cert' => $this->createTmpCertificate()], $options));
        try {
            static::fireEvent('sending', [$method, $params]);
            $response = ($callback) ? $client->{$method}($params) : null;
            static::fireEvent('send', [$method, $params, $response]);
            $res = $callback($response, $client);
        } catch (\SoapFault $e) {
            static::fireEvent('connection.failed', [$method, $params]);
            if ($e->getMessage() == 'Function ("' . $method . '") is not a valid method for this service') {
                throw new \SoapFault(
                    $e->faultcode,
                    $e->faultstring . '. Enable methods [' . implode(', ', $client->getFunctions()) . ']'
                );
            } elseif(strpos($e->getMessage(), 'SOAP-ERROR: Parsing WSDL: Couldn\'t load from') !== false) {
                throw new \SoapFault(
                    $e->faultcode,
                    __("WebService Ekuatia not working, contact with a administrator.")
                );
            } elseif(strpos($e->getMessage(), 'SOAP-ERROR: Parsing Schema: can\'t import schema') !== false) {
                throw new \SoapFault(
                    $e->faultcode,
                    __("WebService Ekuatia not working, contact with a administrator.") . PHP_EOL .
                    PHP_EOL . $e->getMessage()
                );
            } elseif(strpos($e->getMessage(), 'Error Fetching http headers') !== false) {
                throw new \SoapFault(
                    $e->faultcode,
                    __("WebService Ekuatia connection timeout [:timeout], contact with a administrator.", ['timeout' => $client->getSocketTimeout()])
                );
            }
            throw $e;
        } finally {
            $this->delivery->request = $client->getLastRequest();
            if ($response = $client->getLastResponse()) $this->delivery->{$responseField} = $response;
            $this->removeTmpCertificate();
        }

        return $res;
    }

    /**
     * @param $method
     * @param $wsdl
     * @param array $params
     * @param \Closure|null $callback
     * @param array $options
     * @param string $responseField
     * @return Config|mixed|null
     * @throws \SoapFault
     */
    public function requestConsumer($method, $wsdl, $params = [], \Closure $callback = null, $options = [], $responseField = 'consumer_response')
    {
        return $this->request($method, $wsdl, $params, $callback, $options, $responseField);
    }

    static public function getEventsDispatcher() {
        if (static::$eventsDispatcher) return static::$eventsDispatcher;

        return (static::$eventsDispatcher = app(Dispatcher::class));
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string $event
     * @param array $data
     * @param  bool $halt
     * @return mixed
     */
    static public function fireEvent($event, array $data = [], $halt = true)
    {
        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "ekuatia.request.{$event}";

        $method = $halt ? 'until' : 'fire';

        return static::getEventsDispatcher()->$method($event, $data);
    }

    /**
     * Register a database query listener with the connection.
     *
     * @param  \Closure  $callback
     * @return void
     */
    static public function listen(\Closure $callback)
    {
        $eventsDispatcher = static::getEventsDispatcher();
        if ( isset($eventsDispatcher)) {
            $eventsDispatcher->listen('ekuatia.request.*', $callback);
        }
    }

    /**
     * @param false $force
     * @return false|Config|null
     * @throws ValidationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function queryED($force=false)
    {
        $queryStatusCodeOnly = [
            'global', // General errors, connection, timeout, etc
            '0100',   // Error inesperado (PKI)
            '0141',   // Error de PKI
            '0160',   // XML Mal Formado
            '0161',   // Servidor de procesamiento momentáneamente sin respuesta
            '0162',   // Servidor de procesamiento paralizado, sin tiempo de  regreso
        ];
        $edDelayCheckQuery = $this->ekuatia->config('ed.delay_check_query');
        if (
            ($this->delivery->created_at
                and (!$edDelayCheckQuery or Carbon::now()->diffInSeconds($this->delivery->created_at) > $edDelayCheckQuery)
                and $this->delivery->sifen_message->hasAny($queryStatusCodeOnly))
            or $force
        ) {
            $deliveryQuery = (new ElectronicDocumentDelivery());
            $deliveryQuery->id = 0;
            $deliveryQuery->args = ['cdc' => $this->delivery->ed->cdc];
            $query = (new QueryED($deliveryQuery));

            try {
                $doc = null;
                $query->toSifen(function(Config $response, $client) use (&$doc) {
                    $rDE = "<rDE " . explode('rDE>', explode("<rDE", $response->get('xContenDE'))[1])[0] . "rDE>";
                    $doc = new SimpleXMLElement(
                        '<?xml version="1.0" encoding="UTF-8"?>
                              <rContDe>' . $rDE . '</rContDe>'
                    );
                    $doc = (new Config($doc->toArray()));
                    $xml = (new Config($this->delivery->ed->xml->toArray()));
                    if ((string) $this->delivery->ed->xml->Signature->SignatureValue !== $doc->get('SignatureValue', null)) {
                        throw ValidationException::withMessages([
                            'global' => 'El CDC existe en SIFEN pero existen alteraciones.'
                        ]);
                    }
                });

                return $doc;
            } catch (\Illuminate\Validation\ValidationException $e) {
                if ($e->validator->errors()->has("0420")) {
                    // check document is same
                    return false;
                }

                throw $e;
            } catch (\Exception $e) {
//                dd($e);
                throw $e;
            }
        }

        return false;
    }

}
