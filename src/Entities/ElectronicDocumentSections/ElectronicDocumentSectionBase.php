<?php

namespace Mcarral\Sifen\Entities\ElectronicDocumentSections;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mcarral\Sifen\Entities\ElectronicDocumentEventSections\EventRoot;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Mcarral\Sifen\Support\SimpleXMLElement;

abstract class ElectronicDocumentSectionBase {

    const DATE_FORMAT = 'Y-m-d';
    const DATE_TIME_FORMAT = 'Y-m-d\TH:i:s';
    const BASE_AMOUNT_FORMAT = '1-23p0-8';

    protected $sectionName;

    protected $fillable = [];

    protected $attributes = [];

    protected $occurrences = 1;

    protected $groupPrefixes = ['g', 'r'];

    protected $sectionPrefix = 'g';

    protected $classmap = [];

    protected $redirectNode = null;

    /**
     * @var ElectronicDocumentSectionBase|Root
     */
    protected $root;

    /**
     * @var ElectronicDocumentSectionBase
     */
    protected $parent;

    public function __construct(ElectronicDocumentSectionBase $root, ElectronicDocumentSectionBase $parent = null)
    {
        $this->root = $root;
        $this->parent = $parent;
    }

    /**
     * @return ElectronicDocumentSectionBase|Root|EventRoot
     */
    public function root()
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function sectionName()
    {
        if ( ! isset($this->sectionName)) {
            return $this->sectionPrefix . str_replace('\\', '', class_basename($this));
        }

        return $this->sectionName;
    }

    /**
     * @param $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (! $this->isFillable($key)) continue;
            $this->{$key} = $value;
        }

        return $this;
    }

    public function defaults() { return $this; }

    /**
     * @return array
     */
    public function toArray()
    {
        $attributes = [];
        foreach(array_keys($this->attributes) as $key) {
            $attributes[$key] = $this->{$key};
        }

        return array_filter($attributes, function ($value) {
            if ( is_null($value)) return false;
            if ( is_array($value) and empty($value)) return false;
            if ($value instanceof SectionCollection and $value->isEmpty()) return false;

            return true;
        });
    }

    /**
     * @return SimpleXMLElement
     */
    protected function xmlElementInstance()
    {
        return new SimpleXMLElement("<" . $this->sectionName() . "/>");
    }

    /**
     * @return \SimpleXMLElement|null
     */
    public function toXml()
    {
        if ($this->occurrences <= 0 and ! array_filter($this->attributes, function($value){ return !is_null($value); })) return null;

        $xml = $this->xmlElementInstance();
        foreach ($this->toArray() as $key => $value) {
            if ($value instanceof ElectronicDocumentSectionBase) {
                $sectionXml = $value->toXml();
                if ( is_null($sectionXml)) continue;
                $xml->appendXml($sectionXml);
                if (method_exists($this, 'afterAppendSection')) $this->afterAppendSection($key, $xml);
                continue;
            } elseif ($value instanceof SectionCollection) {
                if ($value->isEmpty()) continue;
                $value->appendToXml($xml);
                if (method_exists($this, 'afterAppendSection')) $this->afterAppendSection($key, $xml);
                continue;
            }
            try {
                $values = ( is_array($value)) ? $value : [$value];
                foreach ($values as $v) {
                    $xml->addChild($key, htmlspecialchars($v));
                }
            } catch (\Exception $e) {
                throw new \Exception("Section invalid convert to xml attribute {$key} value not convert to string (" . print_r($value) . ")", $e->getCode(), $e);
            }
        }

        return $xml;
    }

    /**
     * @return int
     */
    public function getOccurrences()
    {
        return $this->occurrences;
    }

    /**
     * @return string
     */
    protected function getSectionNameSpace()
    {
        return __NAMESPACE__;
    }

    protected function getClassSection($name)
    {
        $class = null;
        foreach ($this->groupPrefixes as $prefix) {
            if ( array_key_exists($name, $this->classmap)) $name = $this->classmap[$name];
            if ((! class_exists($class = $this->getSectionNameSpace() . "\\" . Str::replaceFirst($prefix, '', $name)))) continue;
            break;
        }

        return $class;
    }

    /**
     * @param $name
     * @return null|string
     */
    protected function isSection($name)
    {
        if (! Str::startsWith($name, $this->groupPrefixes)) return null;

        return $this->getClassSection($name);
    }

    /**
     * @param $className
     * @return ElectronicDocumentSectionBase
     */
    protected function createSectionInstance($className)
    {
        return new $className($this->root, $this);
    }

    /**
     * @param $name
     * @param bool $defaults
     * @return mixed|null
     */
    public function getSection($name, $defaults = true)
    {
        if ( ! ($className = $this->isSection($name))) return null;
        if ( isset($this->attributes[$name])) return $this->attributes[$name];
        $section = $this->createSectionInstance($className);
        if ($section->getOccurrences() > 1) {
            $collection = (new SectionCollection())
                ->setSectionClass(get_class($section))
                ->setRoot($this->root);
            return ($this->attributes[$name] = $collection);
        }
        if ( $defaults and method_exists($section, 'defaults')) $section->defaults();

        return ($this->attributes[$name] = $section);
    }

    /**
     * @return array
     */
    public function rules() { return []; }

    /**
     * @return array
     */
    public function rulesMessages() { return []; }

    /**
     * @return array
     */
    public function rulesAttributes() {
        $keys = array_merge(
            array_keys($this->rules()),
            array_keys($this->validateAdditionalData())
        );

        return array_combine($keys, $keys);
    }

    /**
     * @param Validator $validator
     */
    protected function validating(Validator $validator) { }

    /**
     * @return array
     */
    protected function validateAdditionalData() { return []; }

    /**
     * @return Validator
     */
    public function createValidatorInstance()
    {
        $validator = validator(array_merge($this->validateAdditionalData(), $this->toArray()), $this->rules(), $this->rulesMessages(), $this->rulesAttributes());
        $validator->after(function(Validator $validator) {
            $this->validating($validator);
        });

        return $validator;
    }

    /**
     * @param string $middleFix
     * @return Validator|null
     */
    public function validate($middleFix = null)
    {
        $validator = validator([]);

        $rootValid = $this->createValidatorInstance();
        if ($rootValid->fails()) {
            $this->validatorMerger($validator, $rootValid, $middleFix);

            return $validator;
        }

        foreach ($this->toArray() as $section) {
            if (! ($section instanceof ElectronicDocumentSectionBase) and ! ($section instanceof SectionCollection)) continue;
            if (! ($validate = $section->validate($middleFix))) continue;
            $this->validatorMerger($validator, $validate, $middleFix);
            return $validator;
        }

        return null;
    }

    /**
     * @param Validator $validator
     * @param Validator $validator2
     * @param string $middleFix
     * @return $this
     */
    protected function validatorMerger(Validator $validator, Validator $validator2, $middleFix = null)
    {
        foreach($validator2->getMessageBag()->toArray() as $key => $messages) {
            foreach($messages as $message) {
                //$validator->getMessageBag()->add($this->sectionName() . (!is_null($middleFix) ? '.' . $middleFix : '') . '.' . $key, $message);
                $key2 = Str::contains($key, '.') ? $key : $this->sectionName() . (!is_null($middleFix) ? '.' . $middleFix : '') . '.' . $key;
                if ( array_key_exists($key, $validator2->customAttributes)) {
                    $name = ($validator2->customAttributes[$key] !== $key) ? $validator2->customAttributes[$key] : $key2;
                    $message = (Str::contains($message, $validator2->customAttributes[$key])) ?
                        $message : '"' . $name . '" - ' . $message;
                }
                $validator->getMessageBag()->add($key2, $message);
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws ValidationException
     */
    public function validateOrFail()
    {
        if (($validator = $this->validate())) throw new ValidationException($validator);

        return $this;
    }

    /**
     * @param Carbon $date
     * @param string $format
     * @return string
     */
    public function dateToString(Carbon $date, $format = ElectronicDocumentSectionBase::DATE_FORMAT)
    {
        return $date->format($format);
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public function dateToUTC(Carbon $date)
    {
        return $date->toW3cString();
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public function dateTimeToString(Carbon $date)
    {
        return $this->dateToString($date, ElectronicDocumentSectionBase::DATE_TIME_FORMAT);
    }

    /**
     * @param Carbon $date
     * @return string
     */
    public function dateToInt(Carbon $date = null)
    {
        if ( ! $date) return $date;
        $timezone = str_replace(':', '', $date->format('P'));

        return $date->format('YmdHis') . substr($timezone, 1, strlen($timezone));
    }

    /**
     * @return Carbon
     */
    public function createDate()
    {
        return Carbon::now();
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getOriginal($name)
    {
        if (! array_key_exists($name, $this->attributes)) return null;

        return $this->attributes[$name];
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getAttribute($name)
    {
        if ($this->redirectNode and $name !== $this->redirectNode and ! $this->isFillable($name)) {
            return $this->{$this->redirectNode}->{$name};
        }
        $mutator = 'get' . Str::studly($name) . 'Attribute';
        if ( method_exists($this, $mutator)) return $this->{$mutator}();
        if ( $section = $this->getSection($name)) return $section;

        return Arr::get($this->attributes, $name);
    }

    /**
     * @param $key
     * @return bool
     */
    public function isFillable($key)
    {
        return in_array($key, $this->fillable);
    }

    /**
     * @param SimpleXMLElement $xml
     * @param string $tagContent
     * @param string $tagSign
     * @param string $attrRefName
     * @return SimpleXMLElement
     * @throws \Exception
     */
    public function sign(SimpleXMLElement $xml, $tagContent = null, $tagSign = 'DE', $attrRefName = 'Id')
    {
        // append Signature tag
        $signatureEl = dom_import_simplexml($xml);
        if ($tagContent) {
            $xml->appendXml((new SimpleXMLElement("<" . $tagContent . "/>")));
            $signatureEl = dom_import_simplexml($xml->{$tagContent});
        }

        $objDSig = new XMLSecurityDSig('');
        $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
        $elSigned = $signatureEl->ownerDocument->getElementsByTagName($tagSign)[0];
        if (($signId = $this->signId())) $elSigned->setAttribute($attrRefName, $signId);
        $objDSig->addReference(
            $elSigned, XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', 'http://www.w3.org/2001/10/xml-exc-c14n#'],
            ['id_name' => 'Id', 'overwrite' => ($signId) ? false : true]
        );

        $ekuatiaSetting = (new EkuatiaSetting())->find();
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);

        if (! empty($ekuatiaSetting->CertPassphrase)) $objKey->passphrase = $ekuatiaSetting->CertPassphrase;

        $objKey->loadKey(ekuatia()->storageCert((! empty($ekuatiaSetting->CertPem)) ? $ekuatiaSetting->CertPem : $ekuatiaSetting->CertPrivate));
        $objDSig->sign($objKey, $signatureEl);
        $objDSig->add509Cert(ekuatia()->storageCert((! empty($ekuatiaSetting->CertPem)) ? $ekuatiaSetting->CertPem : $ekuatiaSetting->CertPublic));

        if (method_exists($this, 'afterSigned')) $xml = $this->afterSigned($xml, $objDSig);

        return $xml;
    }

    protected function signId()
    {
        return null;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        if ($this->redirectNode and $name !== $this->redirectNode and ! $this->isFillable($name)) {
            return ($this->{$this->redirectNode}->{$name} = $value);
        }
        $mutator = 'set' . Str::studly($name) . 'Attribute';
        if ( method_exists($this, $mutator)) return $this->{$mutator}($value);

        return ($this->attributes[$name] = $value);
    }

    public function extractPhoneNumber($value)
    {
        $value = ( ! is_null($value)) ? filter_var(str_replace('-', '', $value), FILTER_SANITIZE_NUMBER_INT) : null;

        return (! empty($value)) ? $value : null;
    }

}
