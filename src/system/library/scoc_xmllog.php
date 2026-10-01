<?php

class scoc_xmllog {

    private $log_file_source = null;
    private $verbose = 0;
    private $globalHelper;
    private $JobId = 0;

    public function __construct($scoclib, $p_job_id, $p_verbose = 0)
    {
        $this->verbose = $p_verbose;
        $this->JobId = $p_job_id;
        $this->globalHelper = $scoclib;
        $this->loadXmlIntoVariable();
    }

    public function __destruct()
    {
        
    }

    private function loadXmlIntoVariable()
    {
        $this->log_file_source = $this->globalHelper->getJobLogFileSource($this->JobId);
    }

    private function saveXml()
    {
        $products->asXml($this->_filename);
    }

    private function get($sku)
    {
        //$products = simplexml_load_string(trim($this->log_file_source));
        $products = simplexml_load_string(
            $this->log_file_source,
            null,
            LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );
        
        if (!$products) {
            return false;
        } //version 10.7

        $p = $products->xpath('/products/product[sku="' . $sku . '"]');

        if ($p) {
            return true;
        }

        return false;
    }

    public function append($p_sku, $p_status, $p_error)
    {
        if ($this->get($p_sku)) {
            return false;
        }
        //return true;
        $products = simplexml_load_string(
            $this->log_file_source,
            null,
            LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );

        $p = $products->addChild('product');
        $p->addChild('sku', $p_sku);
        $p->addChild('status', $p_status);

        $errors = $p->addChild('errors');
        if ($p_error != "") {
            /* $p_error = cleanString($p_error);
              $p_error = preg_replace("/[<]/u", " ", $p_error);
              $p_error = preg_replace("/[>]/u", " ", $p_error);
              $error = $errors->addChild("error", $p_error);
             */
            $errors->error = null;
            $this->addCData($errors->error, $p_error);
        }
        //echo $products->asXML($this->_filename);
        return true;
    }

    public function appendError($p_sku, $p_error)
    {
        if ($this->get($p_sku) == false) {
            return false;
        }

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->recover = true;
        libxml_use_internal_errors(true);
        $dom->validateOnParse = false;

        $this->loadXmlIntoVariable();
        //var_dump("XXXXXX appendError XXXXXXXXXXxxx-" . strlen($this->log_file_source));
        $dom->loadXML($this->log_file_source, LIBXML_COMPACT | LIBXML_PARSEHUGE);

        foreach ($dom->getElementsByTagName('product') as $p) {
            //$p->setIdAttribute('id', true);
            //var_dump($p);
        }
        $elements = $dom->getElementsByTagName('product');
        foreach ($elements as $node) {
            foreach ($node->childNodes as $child) {
                //$data[] = array($child->nodeName => $child->nodeValue);
                if ($child->nodeName == 'sku' && $child->nodeValue == $p_sku) {
                    //echo $child->nodeValue;
                    $p = $node;
                    break;
                }
            }
        }

        foreach ($p->getElementsByTagName('errors') as $errors) {
            $cdata = $dom->createCDATASection($p_error);
            //$errors->getElementsByTagName('error')->item(0)->appendChild($cdata);
            $error = $dom->createElement("error");
            $errors->appendChild($error)->appendChild($cdata);
        }
        //var_dump( $p[0]->sku );
        //return;

        $this->globalHelper->updateJobLogFileSource(
            $this->JobId,
            $dom->saveXML()
        );
        return true;
    }

    public function log($p_sku, $p_status, $p_error)
    {
        $this->loadXmlIntoVariable();

        if ($this->get($p_sku)) {
            if ($this->verbose == 1) {
                echo 'product sku found';
            }
            return $this->appendError($p_sku, $p_error);
        }

        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->recover = true;
        libxml_use_internal_errors(true);
        $dom->validateOnParse = false;
        //var_dump("XXXXXX log XXXXXXXXXXxxx-" . strlen($this->log_file_source));
        $dom->loadXML($this->log_file_source, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        //$libxmlErrors = libxml_get_errors();
        //print_r($libxmlErrors);

        $p = $dom->createElement("product");

        $sku = $dom->createElement('sku', $p_sku);
        $p->appendChild($sku);

        $status = $dom->createElement('status', $p_status);
        $p->appendChild($status);

        $errors = $dom->createElement('errors');

        if ($p_error != "") {
            $cdata = $dom->createCDATASection($p_error);
            $error = $dom->createElement("error");
            $errors->appendChild($error)->appendChild($cdata);
        }
        $p->appendChild($errors);

        if ($dom->documentElement != null) {
            $dom->documentElement->appendChild($p);
        } else {
            $dom->appendChild($p);
        }

        $this->globalHelper->updateJobLogFileSource(
            $this->JobId,
            $dom->saveXML()
        );
        return true;
    }

    public function addCData($e, $cdata_text)
    {
        $node = dom_import_simplexml($e);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }
}