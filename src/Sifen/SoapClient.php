<?php

namespace Mcarral\Sifen\Sifen;

use Illuminate\Support\Str;
use Mcarral\Sifen\Exceptions\ValidationException;
use Mcarral\Sifen\Support\SimpleXMLElement;
use Laminas\Soap\Client;
use Illuminate\Config\Repository as Config;

/**
 * Class SoapClient
 * @method Config rEnviDe(array $parameters) request soap for receive DE
 * @package Mcarral\Sifen\Sifen
 */
class SoapClient extends Client {

    protected $socketTimeout = null;

    protected $connectionTimeout = 15;

    /**
     * @var null|MockSoapHandler
     */
    static $stackHandler = null;

    /**
     * Return array of options suitable for using with SoapClient constructor
     *
     * @return array
     */
    public function getOptions()
    {
        return array_merge(parent::getOptions(), [
            'exceptions'    =>  true,
        ]);
    }

    /**
     * @param Client\Common $client
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param null $oneWay
     * @return mixed
     */
    public function _doRequest(Client\Common $client, $request, $location, $action, $version, $oneWay = null)
    {
        // replace prefix ns1 by xsd but sifen refused request
        $request = SoapClient::replacePrefixNamespace($request);

        if (static::$stackHandler) {
            $handler = static::$stackHandler;

            return $handler($this);
        }

        return parent::_doRequest($client, $request, $location, $action, $version, $oneWay);
    }

    /**
     * @param $ns
     * @param $nsreplace
     * @param $xml
     * @return mixed
     */
    public static function replacePrefixNamespace($xml, $ns = 'ns1', $nsreplace = 'xsd')
    {
        $xml = str_replace('<' . $ns . ':', '<' . $nsreplace . ':', $xml);
        $xml = str_replace('</' . $ns . ':', '</' . $nsreplace . ':', $xml);
        $xml = str_replace(' xmlns:' . $ns . '="', ' xmlns:' . $nsreplace . '="', $xml);

        return $xml;
    }

    /**
     * @return mixed|string
     */
    public function getLastRequest()
    {
        $request = parent::getLastRequest();
        if ($request) $request = static::replacePrefixNamespace($request);

        return $request;
    }

    public function getSocketTimeout() {
        if ($this->socketTimeout) return $this->socketTimeout;

        $socketTimeoutConfKey = 'connection.socket_timeout' . (app()->runningInConsole() ? '_queue' : '');

        return ekuatia()->config($socketTimeoutConfKey, 15);
    }

    public function setSocketTimeout($timeout)
    {
        $this->socketTimeout = $timeout;

        return $this;
    }

    public function socketTimeout(\Closure $callback)
    {
        $defaultSocketTimeout = ini_get("default_socket_timeout");
        ini_set("default_socket_timeout", $this->getSocketTimeout());
        try {
            $res = $callback();
        } finally {
            ini_set("default_socket_timeout", $defaultSocketTimeout);
        }

        return $res;
    }

    public function __call($name, $arguments)
    {
        // SoapClient exceptions not working with xdebug enable
        // https://bugs.php.net/bug.php?id=47584
        if( function_exists('xdebug_disable')) xdebug_disable();
        $response = $this->socketTimeout(function() use ($name, $arguments) {
            try {
                $response = parent::__call($name, $arguments);
                // fixed if soap client not parse xml response
                if (is_null($response)) {
                    $xml = simplexml_load_string($this->getLastResponse(), SimpleXMLElement::class, LIBXML_NOCDATA);
                    if ($xml === false) {
                        throw ValidationException::withMessages(['global' => 'Error procesando respuesta xml: ' . $this->getLastResponse()]);
                    }
                    $response = $xml->toArray();
                }
            } finally {
                if (app()->environment(['local', 'testing'])) {
                    $urls = explode('?', $this->getWSDL())[0];
                    $urls = explode('/', $urls);
                    $basename = Str::replaceLast('wsdl', 'xml', end($urls));
                    $basename2 = Str::replaceLast('wsdl', 'txt', end($urls));
                    file_put_contents(__DIR__ . '/../../tests/data/ws_request_' . $basename, $this->getLastRequest());
                    file_put_contents(__DIR__ . '/../../tests/data/ws_request_header_' . $basename2, $this->getLastRequestHeaders());
                    file_put_contents(__DIR__ . '/../../tests/data/ws_response_' . $basename, $this->getLastResponse());
                    file_put_contents(__DIR__ . '/../../tests/data/ws_response_header_' . $basename2, $this->getLastResponseHeaders());
                }
            }

            return $response;
        });
        if( function_exists('xdebug_enable')) xdebug_enable();
        $response = json_decode(json_encode($response), true);

        return new Config($response ? $response : []);
    }

}