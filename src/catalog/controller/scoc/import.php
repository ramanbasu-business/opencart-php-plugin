<?php

class ControllerScocImport extends Controller
{
    private $scoc_lib;
    private $scoc_encoder;
    
    public function index()
    {
        $this->scoc_lib=new scoc_lib($this->registry);
        $this->scoc_encoder = new scoc_encoder($this->registry);
        
        $queue = 0;
        $verbose = 0;
        
        if (isset($_GET['queue']) && $_GET['queue'] != "") {
            $queue = $_GET['queue'] == "1" ? 1 : 0;
        }
        
        if (isset($_GET['verbose']) && $_GET['verbose'] != "") {
            $verbose = $_GET['verbose'] == "1" ? 1 : 0;
        }
        
        if ($verbose == 0) {
            $this->scoc_lib->httpHeaders();
        }
        
        $qryStrArray = $this->scoc_encoder->authenticate();
        if ($qryStrArray == null) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') :
            $xml = file_get_contents('php://input');
            $this->_processPostedData($queue, $verbose, $xml);
        endif;
    }

    protected function getPath($parent_id, $current_path = '')
    {
        $category_info = $this->model_catalog_category->getCategory($parent_id);

        if ($category_info) {
            if (!$current_path) {
                $new_path = $category_info['category_id'];
            } else {
                $new_path = $category_info['category_id'] . '_' . $current_path;
            }

            $path = $this->getPath($category_info['parent_id'], $new_path);

            if ($path) {
                return $path;
            } else {
                return $new_path;
            }
        }
    }

    private function _get($key)
    {
        return $this->request->post($key);
    }

    private function _processPostedData($queue, $verbose, $local_file_source)
    {
        $globalHelper = $this->scoc_lib;
        $globalHelper->DeleteData();
        
        $output = '';
        if ($verbose == 0) {
            //header("Content-type: text/xml");
        }

        $feed_xml = simplexml_load_string($local_file_source);
        
        if (empty($feed_xml)) :
            $globalHelper->printJobXmlError("XML content could not be loaded into simple xml object");
            return;
        endif;

        $module = '';
        if (isset($feed_xml['module'])) {
            $module = $feed_xml['module'];
        }

        if ($module == '') :
            $module = "import";
        endif;
        
        $log_file_source = "<?xml version=\"1.0\"?>\n";
        $log_file_source .= "<products></products>";
    
        $today = date("Y-m-d H:i:s", strtotime(date("d.m.Y H:i:s")));
        $newId = $globalHelper->insertJob($today, null, 0, $module, $local_file_source, $log_file_source);
        
        if ($verbose == 1) {
            echo '[New ID# ' . $newId . ']';
        }
        
        
        if ($queue == 0) {
            $recordCount = 0;

            switch ($module) {
                case 'import':
                    foreach ($feed_xml->xpath("/products/product") as $child) {
                        $recordCount++;
                    }
                    if ($recordCount <= 50) :
                        $output = $globalHelper->importProducts($newId, $verbose);
                    endif;
                    break;
                case 'invprice':
                    foreach ($feed_xml->xpath("/products/product") as $child) {
                        $recordCount++;
                    }
                    if ($recordCount > 0 && $recordCount <= 1000) :
                        $output = $globalHelper->importProducts($newId, $verbose);
                    endif;
                    break;
                case 'shipping':
                    foreach ($feed_xml->xpath("/orders/order") as $child) {
                        $recordCount++;
                    }
                    if ($recordCount > 0 && $recordCount <= 500) :
                        $output = $globalHelper->importProducts($newId, $verbose);
                    endif;
                    break;
                case 'order_cancel':
                    $output = $globalHelper->importProducts($newId, $verbose);
                    break;
                
                case 'order_refund':
                    $output = $globalHelper->importProducts($newId, $verbose);
                    break;
                
                default:
                    break;
            }
            //$recordCount = count($feel_xml->children());
            if ($verbose == 1) {
                echo 'record count = ' . $recordCount;
            }
        }

        // print job xml now
        $job_row = $globalHelper->getJob($newId);
        
        if ($job_row) {
            $globalHelper->getJobXml2($job_row);
        } elseif ($output != "") {
            $globalHelper->printJobXmlError(htmlentities($output));
        }
    }

    public function test()
    {
        //$exist = $this->scoc_lib->getProductExist(42);
        //var_dump($exist);
        ///image\data
        //\image\cache\data
        echo DIR_IMAGE;
        $filename = basename(html_entity_decode(
            'data/logo.png',
            ENT_QUOTES,
                                                  'UTF-8'
        ));
        echo $filename;

        /*
          $image = new Image(DIR_IMAGE . 'data/5.jpg');
          $image->resize(100, 100);
          $image->save(DIR_IMAGE . 'data/5_copy.jpg');
         * */
        //echo $this->scoc_lib->saveImageFromUrl("http://blogs.bu.edu/rspooner/files/2013/04/cell-phone-300x300.jpg");
        $p = $this->scoc_lib->getProductIdBySku('asdas');
        var_dump($p);
    }
}
