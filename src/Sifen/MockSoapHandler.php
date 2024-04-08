<?php

namespace Mcarral\Sifen\Sifen;

use Countable;
use Illuminate\Support\Arr;
use Mcarral\Sifen\Support\SimpleXMLElement;

class MockSoapHandler implements Countable {

    private $queue = [];
    private $lastRequest;
    private $lastOptions;

    public function __construct(array $queue = null)
    {
        if ($queue) {
            call_user_func_array([$this, 'append'], $queue);
        }
    }

    public function count()
    {
        return count($this->queue);
    }

    protected function describeType($input)
    {
        switch (gettype($input)) {
            case 'object':
                return 'object(' . get_class($input) . ')';
            case 'array':
                return 'array(' . count($input) . ')';
            default:
                ob_start();
                var_dump($input);
                // normalize float vs double
                return str_replace('double(', 'float(', rtrim(ob_get_clean()));
        }
    }

    public function append()
    {
        foreach (func_get_args() as $value) {
            if ($value instanceof \Exception
                || is_string($value)
                || is_array($value)
                || is_callable($value)
            ) {
                $this->queue[] = $value;
            } else {
                throw new \InvalidArgumentException('Expected a stdClass, string or '
                    . 'exception. Found ' . $this->describeType($value));
            }
        }
    }

    /**
     * Get the last received request.
     *
     * @return SoapClient
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * Get the last received request options.
     *
     * @return array
     */
    public function getLastOptions()
    {
        return $this->lastOptions;
    }

    /**
     * @param array $values
     * @param $prefix
     * @return string
     */
    protected function arrayToXml(array $values, $prefix)
    {
        $xml = '';
        foreach ($values as $key => $value) {
            if ( is_array($value) and Arr::isAssoc($value)) $value = $this->arrayToXml($value, $prefix);
            if ( ! is_array($value)) $value = [$value];
            foreach ($value as $v) {
                $xml .=
                    '<' . $prefix . ':' . $key . '>'
                    . ( is_array($v) ? $this->arrayToXml($v, $prefix) : $v) .
                    '</' . $prefix . ':' . $key . '>';
            }
        }

        return $xml;
    }

    public function __invoke(SoapClient $request)
    {
        if (! $this->queue) {
            throw new \OutOfBoundsException('Mock queue is empty');
        }

        $this->lastRequest = $request;
        $this->lastOptions = $request->getOptions();
        $response = array_shift($this->queue);

        if (is_callable($response)) {
            $response = call_user_func($response, $request, $this->lastOptions);
        }

        if ($response instanceof \Exception) {
            throw $response;
        } elseif( is_array($response)) {
            $xml = '';
            $nsnr = 2;
            foreach ($response as $key => $value) {
                $prefix = 'ns' . $nsnr;
                $xml .= '<' . $prefix . ':' . $key . ' xmlns:' . $prefix . '="' . $request->getWSDL() . '">'
                    . $this->arrayToXml($value, $prefix) .
                    '</' . $prefix . ':' . $key . '>';
                $nsnr++;
            }

            $xml = new SimpleXMLElement('
                <env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
                  <env:Header/>
                  <env:Body>
                  ' . $xml . '
                  </env:Body>
                </env:Envelope>
            ');
            $response = xml_pretty($xml);
        }

        return $response;
    }
}