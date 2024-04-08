<?php

namespace Mcarral\Sifen\Support;

class DOMValidator {

    /**
     * @var \DOMDocument
     */
    protected $handler;

    /**
     * @var string
     */
    protected $feedSchema;

    /**
     * @var int
     */
    protected $feedErrors = 0;

    /**
     * Formatted libxml Error details
     *
     * @var array
     */
    protected $errorDetails;

    /**
     * Validation Class constructor Instantiating DOMDocument
     * @param $feedSchema
     * @param string $version
     */
    public function __construct($feedSchema, $version = '1.0')
    {
        $this->handler = new \DOMDocument($version, 'utf-8');
        $this->feedSchema = $feedSchema;
    }

    /**
     * @param \libXMLError object $error
     *
     * @return string
     */
    private function libxmlDisplayError($error)
    {
        /*$errorString = "Error $error->code in $error->file (Line:{$error->line}):";
        $errorString .= trim($error->message);*/

        return "(Line:{$error->line}): \t" . trim($error->message);
    }

    /**
     * @return array
     */
    private function libxmlErrors() {
        $errors = libxml_get_errors();
        $result    = [];
        foreach ($errors as $error) {
            $result[] = $error;
        }
        libxml_clear_errors();

        return $result;
    }

    /**
     * Validate Incoming Feeds against Listing Schema
     *
     * @param resource $feeds
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function validateXMLFile($feeds)
    {
        if (!($fp = fopen($feeds, "r"))) {
            throw new \Exception("could not open XML input");
        }

        $xml = fread($fp, filesize($feeds));
        fclose($fp);

        return $this->validateXML($xml);
    }

    /**
     * @param $feedSchema
     * @return bool
     */
    protected function schemaValidate($feedSchema)
    {
        try {
            return $this->handler->schemaValidate($feedSchema);
        } catch (\ErrorException $e) { return false; }
    }

    /**
     * @param string|\SimpleXMLElement|\DOMDocument $xml
     * @return bool
     * @throws \DOMException
     */
    public function validateXML($xml)
    {
        if (!class_exists('DOMDocument')) {
            throw new \DOMException("'DOMDocument' class not found!");
        }
        if (!file_exists($this->feedSchema)) {
            throw new \Exception('Schema is Missing, Please add schema to feedSchema property');
        }
        libxml_use_internal_errors(true);
        if ($xml instanceof \DOMDocument) {
            $this->handler = $xml;
        } else {
            if ($xml instanceof \SimpleXMLElement) $xml = xml_pretty($xml);
            $this->handler->loadXML($xml, LIBXML_NOBLANKS);
        }
        if (! $this->schemaValidate($this->feedSchema)) {
            $this->errorDetails = $this->libxmlErrors();
            $this->feedErrors   = 1;
        } else {
            //The xml is valid
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errorDetails;
    }

    /**
     * Display Error if Resource is not validated
     *
     * @return array
     */
    public function displayErrors()
    {
        $result = [];
        foreach ($this->errorDetails as $error) {
            $result[] = $this->libxmlDisplayError($error);
        }

        return $result;
    }
}