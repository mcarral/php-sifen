<?php

namespace Mcarral\Sifen\Support;

use DOMXPath;
use SimpleXMLElement as SimpleXMLElementBase;

class SimpleXMLElement extends SimpleXMLElementBase {

    /**
     * @param $name
     * @param string $value [optional]
     * @return BaseSimpleXMLElement
     */
    public function prependChild($name, $value = null)
    {
        $dom = dom_import_simplexml($this);

        $new = $dom->insertBefore(
            $dom->ownerDocument->createElement($name, $value),
            $dom->firstChild
        );

        return simplexml_import_dom($new, get_class($this));
    }

    /**
     * @param SimpleXMLElement $element
     * @param SimpleXMLElement|null $structure
     * @param array $excludeNamespaces
     * @return BaseSimpleXMLElement
     */
    public function appendXml(SimpleXMLElement $element, SimpleXMLElement $structure = null, $excludeNamespaces = ['default'])
    {
        $domSelf = dom_import_simplexml(($structure ?: $this));
        $domImport = dom_import_simplexml($element);

        $domImport = $domSelf->ownerDocument->importNode($domImport, true);
        $new = $domSelf->appendChild($domImport);
        $snew = simplexml_import_dom($new, get_class($this));

        // remove namespaces after append
        /*$finder = new DOMXPath($new->ownerDocument);
        foreach ($snew->getNamespaces(true) as $prefix => $ns) {
            if (! in_array($prefix, $excludeNamespaces)) continue;
            $finder->registerNamespace($prefix, $ns);
            $nodes = $finder->query("//*[namespace::{$prefix} and not(../namespace::{$prefix})]");
            foreach ($nodes as $n) {
                $n->removeAttributeNS($ns, $prefix);
            }
        }*/

        return simplexml_import_dom($new, get_class($this));
    }

    /**
     * @param $xmlStr
     * @param SimpleXMLElement|null $structure
     * @return BaseSimpleXMLElement
     */
    public function appendXmlFromString($xmlStr, SimpleXMLElement $structure = null)
    {
        return $this->appendXml((new SimpleXMLElement($xmlStr)), $structure);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $arr = [];
        $queries = (func_get_args()) ? func_get_args() : ['/*/*/*'];
        $queries = ( $queries and is_array($queries[0])) ? $queries[0] : $queries;

        // register namespaces
        $namespaces = $this->getDocNamespaces(true);
        foreach ($namespaces as $prefix => $namespace) {
            $this->registerXPathNamespace($prefix, $namespace);
        }

        // extract queries
        foreach ($queries as $query) {
            if (! ($search = $this->xpath($query))) continue;
            foreach ($search as $element) {
                foreach (array_keys($namespaces) as $prefix) {
                    $arr = array_merge_recursive($arr, (array) $element->children($prefix, true));
                }
            }
        }

        return $arr;
    }

    public function asXmlWithoutHeader()
    {
        return xml_pretty($this, true);
    }
}