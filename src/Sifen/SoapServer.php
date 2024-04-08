<?php

namespace Mcarral\Sifen\Sifen;

use BadMethodCallException;
use soap_server;

/**
 * Class SoapServer
 * @package Mcarral\Sifen\Sifen
 * @mixin soap_server
 */
class SoapServer {

    /**
     * @var soap_server
     */
    protected $server;

    /**
     * @var string
     */
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = (request()) ? request()->url() : config('app.url');

        $this->registerServer();
    }

    public function registerServer()
    {
        $namespace = $this->baseUrl . '?wsdl';
        $this->server = new soap_server();
        $this->server->configureWSDL(ekuatia()->config('soap.name'), $namespace, $namespace);

        // set our namespace
        $this->server->wsdl->schemaTargetNamespace = $namespace;

        // set utf-8
        $this->server->soap_defencoding = 'UTF-8';
        $this->server->decode_utf8 = false;
        $this->server->encode_utf8 = true;

        \soap_server::$resolve_method = $this->resolveMethod();

        $this->register("siRecepDE", ReceiveED::structure($this->server));
        $this->register("siRecepEvento", ReceiveEvent::structure($this->server));

        /*$this->soapServer->wsdl->addComplexType('ErrorMessagesResp', 'complexType', 'struct', 'all', '', [
            'code'    => ['name' => 'code', 'type' => 'xsd:string'],
            'message' => ['name' => 'message', 'type' => 'xsd:string'],
        ]);*/
    }

    /**
     * @return \Closure
     */
    protected function resolveMethod()
    {
        return function(\soap_server $server) {
            $class = ''; $method = '';
            $routeClass = ekuatia()->config('soap.controller', "Mcarral\Http\Controllers");
            if ( class_exists($routeClass)) {
                $try_class          = $routeClass;
                $class              = new  $try_class;
                $method             = $server->methodname;
            } else {
                $try_class = '';
                $server->debug('in invoke_method, not set route class for laravel framework');
            }

            return [$class, $method, $try_class];
        };
    }

    /**
     * @param $name
     * @param $params
     * @return $this
     */
    protected function register($name, $params)
    {
        call_user_func_array([$this->server, 'register'], array_merge([$name], $params, [false, $this->baseUrl]));

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->server, $name)) return call_user_func_array([$this->server, $name], $arguments);

        throw new BadMethodCallException("Method [{$name}] does not exist.");
    }

}