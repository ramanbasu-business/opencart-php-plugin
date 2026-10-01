<?php

class ControllerScocExport extends Controller {

    private $scoc_encoder;
    private $scoc_lib;
    private $verbose = 0;

    public function index()
    {
        
    }

    public function auth()
    {
        $this->scoc_lib = new scoc_lib($this->registry);
        $this->scoc_encoder = new scoc_encoder($this->registry);


        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        //$this->scoc_lib->printLoginResultXml( $qryStrArray );

        if ($qryStrArray == null) :

        else:
            $msg = "<response>"
                    . $this->scoc_lib->getVersionXml()
                    . "<status>success</status>"
                    . "<message>User has been authenticated successfully</message>"
                    . "</response>";

            $this->response->setOutput($msg);
        endif;
    }

    public function product()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib = new scoc_lib($this->registry);

        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            return;
        }

        $currentPageIndex = isset($this->request->get['currentPageIndex']) ? $this->request->get['currentPageIndex'] : null;
        $rowsPerPage = isset($this->request->get['rowsPerPage']) ? $this->request->get['rowsPerPage'] : null;
        $sku = isset($this->request->get['sku']) ? $this->request->get['sku'] : null;

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<products>';
        $output .= $this->scoc_lib->getVersionXml();

        //$this->load->model('catalog/category');
        //$this->load->model('catalog/product');
        //$this->load->model('tool/image');
        //$orders = $this->_getProducts();
        //$this->addonLoad('attrib');
        //$this->load->library('scoc_lib');
        //$this->Scoclib=new Scoclib();
        //$this->scoc_lib = new scoc_lib();
        //$orders =$this->Scoclib->getProducts();
        $products = $this->scoc_lib->getProducts($currentPageIndex, $rowsPerPage, $sku);

        foreach ($products as $product) {
            //var_dump($product);exit;
            $output .= '<product>';

            $output .= '<product_id>' . $product['product_id'] . '</product_id>';
            $output .= '<sku>' . $product['sku'] . '</sku>';
            $output .= '<name><![CDATA[' . (utf8_encode($product['name'])) . ']]></name>';
            $output .= '<description lang="en"><![CDATA[' . (utf8_encode($product['description'])) . ']]></description>';
            $output .= '<meta_description lang="en"><![CDATA[' . utf8_encode($product['meta_description']) . ']]></meta_description>';
            $output .= '<meta_keyword lang="en"><![CDATA[' . $product['meta_keyword'] . ']]></meta_keyword>';

            $output .= '<tag>' . $product['tag'] . '</tag>'; // Can be used as MPN
            $output .= '<model><![CDATA[' . $product['model'] . ']]></model>'; // Can be used as MPN
            $output .= '<mpn>' . $product['mpn'] . '</mpn>';
            $output .= '<upc>' . $product['upc'] . '</upc>';
            $output .= '<isbn>' . $product['isbn'] . '</isbn>';
            $output .= '<location>' . $product['location'] . '</location>';
            $output .= '<quantity>' . $product['quantity'] . '</quantity>';
            $output .= '<stock_status>' . $product['stock_status'] . '</stock_status>';

            $output .= '<manufacturer lang="en"><![CDATA[' . html_entity_decode(
                            $product['manufacturer'], ENT_QUOTES, 'UTF-8'
                    ) . ']]></manufacturer>';
            $output .= '<manufacturer_id>' . $product['manufacturer_id'] . '</manufacturer_id>';

            $link = $this->url->link('product/product', 'product_id=' . $product['product_id']);
            $link = str_replace('&amp;', '&', $link);
            $output .= '<link><![CDATA[' . $link . ']]></link>';

            $cpt_status = $this->config->get('cpt_status');

            //$output .= '<condition>new</condition>';

            if ($product['image']) {
                $output .= '<image>' . $this->model_tool_image->resize(
                                $product['image'], 500, 500
                        ) . '</image>';
            }
            else {
                $output .= '<image>' . $this->model_tool_image->resize(
                                'no_image.jpg', 500, 500
                        ) . '</image>';
            }

            // D:\xampp\htdocs\opencart2302\catalog\controller\extension\feed\google_base.php
            $currencies = array('USD', 'EUR', 'GBP');
            if (in_array($this->session->data['currency'], $currencies)) {
                $currency_code = $this->session->data['currency'];
                $currency_value = $this->currency->getValue($this->session->data['currency']);
            }
            else {
                $currency_code = 'USD';
                $currency_value = $this->currency->getValue('USD');
            }

            $output .= '<special>' . $product['special'] . '</special>';
            $output .= '<price>' . $product['price'] . '</price>';
            $output .= '<currency_code>' . $product['price'] . '</currency_code>';

            if ((float) $product['special']) {
                $output .= '<price_formatted>' . $this->currency->format(
                                $this->tax->calculate(
                                        $product['special'], $product['tax_class_id']
                                ), $currency_code, $currency_value, false
                        ) . '</price_formatted>';
            }
            else {
                $output .= '<price_formatted>' . $this->currency->format(
                                $this->tax->calculate(
                                        $product['price'], $product['tax_class_id']
                                ), $currency_code, $currency_value, false
                        ) . '</price_formatted>';
            }

            $output .= '<availability>' . ($product['quantity'] ? 'in stock' : 'out of stock') . '</availability>';
            $output .= '<tax_class_id>' . $product['tax_class_id'] . '</tax_class_id>';

            $output .= '<date_available>' . $product['date_available'] . '</date_available>';

            //$output .= '<weight>' . $this->weight->format($product['weight'], $product['weight_class_id']) . '</weight>';
            $output .= '<weight>' . $product['weight'] . '</weight>';
            $output .= '<weight_class_id>' . $product['weight_class_id'] . '</weight_class_id>';
            $output .= '<weight_unit>' . $this->weight->getUnit($product['weight_class_id']) . '</weight_unit>';

            $output .= '<length>' . $product['length'] . '</length>';
            $output .= '<width>' . $product['width'] . '</width>';
            $output .= '<height>' . $product['height'] . '</height>';
            $output .= '<length_class_id>' . $product['length_class_id'] . '</length_class_id>';
            $output .= '<length_unit>' . $this->length->getUnit($product['length_class_id']) . '</length_unit>';

            $output .= '<minimum>' . $product['minimum'] . '</minimum>';
            $output .= '<status>' . $product['status'] . '</status>';
            $output .= '<date_added>' . $product['date_added'] . '</date_added>';
            $output .= '<date_modified>' . $product['date_modified'] . '</date_modified>';
            $output .= '<viewed>' . $product['viewed'] . '</viewed>';

            //$output .= '<custom_product_tab>';
            /*             * * version 1.1 **
             * Added support for custom_product_tab module extension
             */
            $cpt_status = $this->config->get('cpt_status');
            if ($cpt_status) {
                $tabs = $this->getProductTabs($product['product_id']);
                if ($tabs) {
                    foreach ($tabs as $tab) {
                        $output .= '<extension name="custom_product_tab" field="' . $tab['name'] . '"><![CDATA[' . $tab['content'] . ']]></extension>';
                    }
                }
            }
            //$output .= '</custom_product_tab>';

            /*             * * version 1.3 **
             * Added support for extra_tab_in_products module extension
             */
            if (array_key_exists('extra_tab_text', $product)) {
                $output .= '<extension name="extra_tab_in_products" field="What\'s In The Box"><![CDATA[' . utf8_encode(isset($product['extra_tab_text']) ? $product['extra_tab_text'] : '') . ']]></extension>';
            } //extra_tab_text

            $categories = $this->model_catalog_product->getCategories($product['product_id']);

            $output .= '<categories>';
            foreach ($categories as $category) {
                $path = $this->getPath($category['category_id']);

                if ($path) {
                    $string = '';
                    $output .= '<category>';
                    $output .= '<category_id>' . $category['category_id'] . '</category_id>';

                    /*
                      foreach (explode('_', $path) as $path_id) {
                      $category_info = $this->model_catalog_category->getCategory($path_id);

                      if ($category_info) {

                      if (!$string) {
                      $string = $category_info['name'];
                      } else {
                      $string .= ' &gt; ' . $category_info['name'];
                      }
                      }
                      } */
                    $category_info = $this->model_catalog_category->getCategory($category['category_id']);
                    $output .= '<category_name>' . $category_info['name'] . '</category_name>';
                    $output .= '</category>';
                }
            }
            $output .= '</categories>';

            /* PRINTING ADDITIONAL ATTRIBUTES */
            $output .= '<additional_attributes>';
            $attributes = $this->model_catalog_product->getProductAttributes($product['product_id']);
            //print_r($attributes);
            foreach ($attributes as $attribute_group) {
                //echo $attribute_group['name'];
                foreach ($attribute_group['attribute'] as $attribute) {
                    $output .= '<associativeentity>';

                    $output .= '<key>' . $attribute['name'] . '</key>';
                    $output .= '<value>' . $attribute['text'] . '</value>';

                    $output .= '</associativeentity>';
                }
            }
            $output .= '</additional_attributes>';

            $output .= '</product>';

            //var_dump($product);
            //return;
        }


        $output .= '</products>';
        $output = trim($output);
        //$this->response->addHeader('Content-Type:xml');
        $this->response->setOutput($output);
    }

    public function csv()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib = new scoc_lib($this->registry);

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            return;
        }

        header('HTTP/1.1 200 OK');
        header("Pragma: public");
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Last-Modified: " . date('r'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=data.csv');

        // create a file pointer connected to the output stream
        $fp = fopen('php://output', 'w');

        // output the column headings
        fputcsv($fp, $this->_getCsvHeaders());
        $this->GenerateCsvDataArray($fp);
        fclose($fp);
    }

    private function GenerateCsvDataArray($fp)
    {
        $this->scoc_lib = new scoc_lib($this->registry);

        //$products = $this->scoc_lib->getProducts(0, 200000 );
        $currentPageIndex = null;
        $rowsPerPage = null;
        $currentPageIndex = isset($this->request->get['currentPageIndex']) ? $this->request->get['currentPageIndex'] : null;
        $rowsPerPage = isset($this->request->get['rowsPerPage']) ? $this->request->get['rowsPerPage'] : null;

        $products = $this->scoc_lib->getProducts($currentPageIndex, $rowsPerPage);

        foreach ($products as $p) :

            if ($p['sku'] != "") :
                $product_id = $p["product_id"];
                $sku = $p['sku'];
                $price = $p['price'];
                $stock = $p['quantity'];

                $arr = array();
                $arr[] = $product_id;
                $arr[] = $sku;
                $arr[] = "";
                $arr[] = $price;
                $arr[] = $stock;

                fputcsv($fp, $arr);
            endif;

        endforeach;
        unset($products);
    }

    private function _getCsvHeaders()
    {
        $headers = array("id", "sku", "parent_sku", "price", "qty");
        return $headers;
    }

    public function productcsv()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib = new scoc_lib($this->registry);

        $this->scoc_lib->httpHeaders();
        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            return;
        }

        $csv = true;
        $headers = array("Productid", "Sku", "ProductName",
            "ShortDescription", "LongDescription",
            "SitePrice",
            "Weight",
            "Qty",
            "UPC",
            "Galleryimageurl",
            "SupplimentalImageURL1", "SupplimentalImageURL2", "SupplimentalImageURL3", "SupplimentalImageURL4", "SupplimentalImageURL5", "SupplimentalImageURL6", "SupplimentalImageURL7",
            "SupplimentalImageURL8", "SupplimentalImageURL9", "SupplimentalImageURL10", "SupplimentalImageURL11", "SupplimentalImageURL12", "SupplimentalImageURL13", "SupplimentalImageURL14"
        );

        if ($csv) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
        }

        $currentPageIndex = null;
        $rowsPerPage = null;
        $currentPageIndex = isset($this->request->get['currentPageIndex']) ? $this->request->get['currentPageIndex'] : null;
        $rowsPerPage = isset($this->request->get['rowsPerPage']) ? $this->request->get['rowsPerPage'] : null;

        $products = $this->scoc_lib->getProducts(
                $currentPageIndex, $rowsPerPage
        );

        //$ps = array();
        if ($csv) {
            header("Content-type: application/csv");
            //header("Content-Disposition: attachment; filename=\"Jobs_" . date('M.j.y', $from) . "-" . date('M.j.y', $to) . ".csv\"");
            header("Content-Disposition: attachment; filename=\"Jobs_" . date('M.j.y') . ".csv\"");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        foreach ($products as $product) {
            try {
                $arr = array(
                    'Productid' => $product['product_id'],
                    'Sku' => $product['sku'],
                    'ProductName' => $product['name'],
                    'ShortDescription' => $product['description'],
                    'LongDescription' => $product['description'],
                    'SitePrice' => $product['price'],
                    'Weight' => $product['weight'],
                    'Qty' => $product['quantity'],
                    'UPC' => $product['upc'],
                );

                try {
                    $arr["Galleryimageurl"] = '';
                    if (empty($product['image']) == false) {
                        //echo $product['image'];
                        $arr["Galleryimageurl"] = $this->model_tool_image->resize(
                                $product['image'], 500, 500
                        );
                    }
                    else {
                        $arr["Galleryimageurl"] = $this->model_tool_image->resize(
                                'no_image.jpg', 500, 500
                        );
                    }
                } catch (Exception $exImage) {
                    $arr["Galleryimageurl"] = null;
                }

                $productImages = $this->model_catalog_product->getProductImages($product['product_id']);
                $productImageIndex = 1;
                foreach ($productImages as $img) {
                    $arr["SupplimentalImageURL" . $productImageIndex] = $this->model_tool_image->resize(
                            $img["image"], 500, 500
                    );
                    $productImageIndex++;
                }
                //var_dump($arr);
                //$output .= '<link>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</link>';

                if ($csv) {
                    fputcsv($handle, $arr);
                }
            } catch (Exception $ex) {
                //echo $ex->getMessage();
            }
        }

        //var_dump($ps);
        //$this->response->addHeader('Content-Type:xml');
        //$this->response->setOutput($output);

        if ($csv) {
            fclose($handle);
        }
    }

    public function order()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib = new scoc_lib($this->registry);

        // http://www.localhost.com/opencart200/index.php?route=scoc/export/order&ignorelogin=1
        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            return;
        }

        if (isset($qryStrArray["verbose"]) && $qryStrArray["verbose"] == 1):
            $this->verbose = 1;
        endif;

        //var_dump($qryStrArray);
        //$this->load->model('sale/order');
        $order_id = isset($_GET['id']) ? $_GET['id'] : 0;
        $currentPageIndex = isset($qryStrArray['currentpageindex']) ? $qryStrArray['currentpageindex'] : 0;
        $rowsPerPage = isset($qryStrArray['rowsperpage']) ? $qryStrArray['rowsperpage'] : 10;
        $fromDate = isset($this->request->get['from']) ? $this->request->get['from'] : null;
        $toDate = isset($this->request->get['to']) ? $this->request->get['to'] : null;

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<orders>';
        $output .= $this->scoc_lib->getVersionXml();

        $recordCount = $this->scoc_lib->getOrderCount($fromDate, $toDate, $order_id);
        $totalPages = 0;

        $output .= '<recordcount>' . $recordCount . '</recordcount>';
        //var_dump($currentPageIndex);
        if ($rowsPerPage > 0) {
            $totalPages = (int) ($recordCount / $rowsPerPage) + ((int) ($recordCount % $rowsPerPage) > 0 ? 1 : 0);
            $output .= '<currentpageindex>' . $currentPageIndex . '</currentpageindex>';
            $output .= '<totalpages>' . $totalPages . '</totalpages>';
        }

        $orders = $this->scoc_lib->getOrders($currentPageIndex, $rowsPerPage, $fromDate, $toDate, $order_id);
        //var_dump($orders);

        foreach ($orders as $o) {
            $order_id = $o["order_id"];
            //$order_info = $this->model_sale_order->getOrder($order_id);
            $order_info = $this->scoc_lib->getOrder($order_id);
            //var_dump($order_info["order_vouchers"]); return;

            if ($this->verbose == 1) {
                echo "<br />----------- ORDER INFO -----------------<br />";
                var_dump($order_info);
            }

            $output .= '<order>';

            $total_data = $this->scoc_lib->getOrderTotals($order_id);
            if ($total_data != null) {
                foreach ($total_data as $k => $v) :

                    if (isset($v["value"])) :

                        $total_code_original = $v["code"];
                        $total_code_modified = $total_code_original;

                        if (strpos($total_code_original, 'tax') !== false):
                            $total_code_modified = "localtax";
                            // Display original tax code for debugging purpose
                            $output .= "<taxcode_actual><![CDATA[" . $total_code_original . "]]></taxcode_actual>";
                        elseif (strpos($total_code_original, 'discount') !== false):
                            $total_code_modified = "discount";
                            // Display original discount code for debugging purpose
                            $output .= "<discountcode_actual><![CDATA[" . $total_code_original . "]]></discountcode_actual>";
                        endif;

                        $output .= "<order_" . $total_code_modified . ">" . abs($v["value"]) . "</order_" . $total_code_modified . ">";

                    endif;

                endforeach;
            }
            unset($total_data);

            if ($order_info) {
                if (false) {
                    foreach ($order_info["order"] as $key => $value) {
                        echo "\$output .= '<" . $key . ">'" . " . " . "\$order_info['order']['" . $key . "']" . " . " . "'</" . $key . ">'" . ";";
                    }
                    exit;
                }

                if (true):
                    $output .= '<order_id>' . $order_info['order']['order_id'] . '</order_id>';
                    $output .= '<invoice_no>' . $order_info['order']['invoice_no'] . '</invoice_no>';
                    $output .= '<invoice_prefix>' . $order_info['order']['invoice_prefix'] . '</invoice_prefix>';
                    $output .= '<store_id>' . $order_info['order']['store_id'] . '</store_id>';
                    $output .= '<store_name>' . $order_info['order']['store_name'] . '</store_name>';
                    $output .= '<store_url>' . $order_info['order']['store_url'] . '</store_url>';
                    $output .= '<customer_id>' . $order_info['order']['customer_id'] . '</customer_id>';
                    $output .= '<customer_group_id>' . $order_info['order']['customer_group_id'] . '</customer_group_id>';
                    $output .= '<firstname>' . $order_info['order']['firstname'] . '</firstname>';
                    $output .= '<lastname>' . $order_info['order']['lastname'] . '</lastname>';
                    $output .= '<telephone>' . $order_info['order']['telephone'] . '</telephone>';
                    $output .= '<fax>' . $order_info['order']['fax'] . '</fax>';
                    $output .= '<email>' . $order_info['order']['email'] . '</email>';
                    $output .= '<payment_firstname><![CDATA[' . $order_info['order']['payment_firstname'] . ']]></payment_firstname>';
                    $output .= '<payment_lastname><![CDATA[' . $order_info['order']['payment_lastname'] . ']]></payment_lastname>';
                    $output .= '<payment_company><![CDATA[' . $order_info['order']['payment_company'] . ']]></payment_company>';
                    $output .= '<payment_company_id>' . $order_info['order']['payment_company_id'] . '</payment_company_id>';
                    $output .= '<payment_tax_id>' . $order_info['order']['payment_tax_id'] . '</payment_tax_id>';
                    $output .= '<payment_address_1><![CDATA[' . $order_info['order']['payment_address_1'] . ']]></payment_address_1>';
                    $output .= '<payment_address_2><![CDATA[' . $order_info['order']['payment_address_2'] . ']]></payment_address_2>';
                    $output .= '<payment_postcode><![CDATA[' . $order_info['order']['payment_postcode'] . ']]></payment_postcode>';
                    $output .= '<payment_city><![CDATA[' . $order_info['order']['payment_city'] . ']]></payment_city>';
                    $output .= '<payment_zone_id>' . $order_info['order']['payment_zone_id'] . '</payment_zone_id>';
                    $output .= '<payment_zone><![CDATA[' . $order_info['order']['payment_zone'] . ']]></payment_zone>';
                    $output .= '<payment_country_id>' . $order_info['order']['payment_country_id'] . '</payment_country_id>';
                    $output .= '<payment_country><![CDATA[' . $order_info['order']['payment_country'] . ']]></payment_country>';
                    $output .= '<payment_address_format>' . $order_info['order']['payment_address_format'] . '</payment_address_format>';
                    $output .= '<payment_method><![CDATA[' . $order_info['order']['payment_method'] . ']]></payment_method>';
                    $output .= '<payment_code>' . $order_info['order']['payment_code'] . '</payment_code>';
                    $output .= '<shipping_firstname><![CDATA[' . $order_info['order']['shipping_firstname'] . ']]></shipping_firstname>';
                    $output .= '<shipping_lastname><![CDATA[' . $order_info['order']['shipping_lastname'] . ']]></shipping_lastname>';
                    $output .= '<shipping_company><![CDATA[' . $order_info['order']['shipping_company'] . ']]></shipping_company>';
                    $output .= '<shipping_address_1><![CDATA[' . $order_info['order']['shipping_address_1'] . ']]></shipping_address_1>';
                    $output .= '<shipping_address_2><![CDATA[' . $order_info['order']['shipping_address_2'] . ']]></shipping_address_2>';
                    $output .= '<shipping_postcode>' . $order_info['order']['shipping_postcode'] . '</shipping_postcode>';
                    $output .= '<shipping_city><![CDATA[' . $order_info['order']['shipping_city'] . ']]></shipping_city>';
                    $output .= '<shipping_zone_id>' . $order_info['order']['shipping_zone_id'] . '</shipping_zone_id>';
                    $output .= '<shipping_zone><![CDATA[' . $order_info['order']['shipping_zone'] . ']]></shipping_zone>';
                    $output .= '<shipping_country_id>' . $order_info['order']['shipping_country_id'] . '</shipping_country_id>';
                    $output .= '<shipping_country><![CDATA[' . $order_info['order']['shipping_country'] . ']]></shipping_country>';
                    $output .= '<shipping_address_format>' . $order_info['order']['shipping_address_format'] . '</shipping_address_format>';
                    $output .= '<shipping_method><![CDATA[' . $order_info['order']['shipping_method'] . ']]></shipping_method>';
                    $output .= '<shipping_code>' . $order_info['order']['shipping_code'] . '</shipping_code>';
                    $output .= '<comment>' . $order_info['order']['comment'] . '</comment>';
                    $output .= '<total>' . $order_info['order']['total'] . '</total>';
                    $output .= '<order_status_id>' . $order_info['order']['order_status_id'] . '</order_status_id>';
                    $output .= '<affiliate_id>' . $order_info['order']['affiliate_id'] . '</affiliate_id>';
                    $output .= '<commission>' . $order_info['order']['commission'] . '</commission>';
                    $output .= '<language_id>' . $order_info['order']['language_id'] . '</language_id>';
                    $output .= '<currency_id>' . $order_info['order']['currency_id'] . '</currency_id>';
                    $output .= '<currency_code>' . $order_info['order']['currency_code'] . '</currency_code>';
                    $output .= '<currency_value>' . $order_info['order']['currency_value'] . '</currency_value>';
                    $output .= '<ip>' . $order_info['order']['ip'] . '</ip>';
                    $output .= '<forwarded_ip>' . $order_info['order']['forwarded_ip'] . '</forwarded_ip>';
                    $output .= '<user_agent>' . $order_info['order']['user_agent'] . '</user_agent>';
                    $output .= '<accept_language>' . $order_info['order']['accept_language'] . '</accept_language>';
                    $output .= '<date_added>' . $order_info['order']['date_added'] . '</date_added>';
                    $output .= '<date_modified>' . $order_info['order']['date_modified'] . '</date_modified>';
                    $output .= '<reward>' . $order_info['order']['reward'] . '</reward>';
                    $output .= '<shipping_iso_code_2>' . $order_info['order']['shipping_iso_code_2'] . '</shipping_iso_code_2>';
                    $output .= '<shipping_iso_code_3>' . $order_info['order']['shipping_iso_code_3'] . '</shipping_iso_code_3>';
                    $output .= '<shipping_zone_code>' . $order_info['order']['shipping_zone_code'] . '</shipping_zone_code>';
                    $output .= '<language_code>' . $order_info['order']['language_code'] . '</language_code>';
                    $output .= '<payment_iso_code_2>' . $order_info['order']['payment_iso_code_2'] . '</payment_iso_code_2>';
                    $output .= '<payment_iso_code_3>' . $order_info['order']['payment_iso_code_3'] . '</payment_iso_code_3>';
                    $output .= '<payment_zone_code>' . $order_info['order']['payment_zone_code'] . '</payment_zone_code>';
                    $output .= '<language_filename>' . $order_info['order']['language_filename'] . '</language_filename>';
                    $output .= '<language_directory>' . $order_info['order']['language_directory'] . '</language_directory>';
                    $output .= '<affiliate_firstname>' . $order_info['order']['affiliate_firstname'] . '</affiliate_firstname>';
                    $output .= '<affiliate_lastname>' . $order_info['order']['affiliate_lastname'] . '</affiliate_lastname>';
                    $output .= '<amazon_order_id>' . $order_info['order']['amazon_order_id'] . '</amazon_order_id>';
                endif;
            }
            //$categories = $this->model_catalog_product->getCategories($product['product_id']);

            $output .= '<orderitems>';
            try {
                //$orderProducts = $this->model_account_order->getOrderProducts($order_id);
                //$orderProducts = $this->model_sale_order->getOrderProducts($order_id);

                foreach ($order_info["order_products"] as $products) {
                    //var_dump($products);
                    $_productId = $products["order_product_id"];
                    $_productName = $products["name"];
                    $_model = $products["model"];
                    $_quantity = $products["quantity"];
                    $_price = $products["price"];
                    $_total = $products["total"];
                    $_tax = $products["tax"];
                    $_reward = $products["reward"];

                    $output .= '<orderitem>';
                    $output .= '<productid>' . $_productId . '</productid>';
                    $output .= '<name><![CDATA[' . $_productName . ']]></name>';
                    $output .= '<model>' . $_model . '</model>';
                    $output .= '<quantity>' . $_quantity . '</quantity>';
                    $output .= '<price>' . $_price . '</price>';
                    $output .= '<total>' . $_total . '</total>';
                    $output .= '<tax>' . $_tax . '</tax>';
                    $output .= '<reward>' . $_reward . '</reward>';

                    $output .= '<options>';
                    if (isset($products['options']) && count($products['options']) > 0):
                        foreach ($products['options'] as $po):
                            $output .= '<option>';
                            $output .= '<name>' . $po['option_name'] . '</name>';
                            $output .= '<value>' . $po['option_value'] . '</value>';
                            $output .= '</option>';
                        endforeach;
                    endif;
                    $output .= '</options>';

                    $output .= '</orderitem>';
                }
            } catch (Exception $ex) {
                
            }

            // including vouchers as order items too
            try {
                if ($order_info["order_vouchers"] && count($order_info["order_vouchers"]) > 0):
                    foreach ($order_info["order_vouchers"] as $voucher):
                        $output .= '<orderitem>';
                        $output .= '<productid>GIFTVOUCHER-' . $voucher['voucher_id'] . '</productid>';
                        $output .= '<name><![CDATA[' . $voucher['description'] . ']]></name>';
                        $output .= '<model>' . $voucher['code'] . '</model>';
                        $output .= '<quantity>1</quantity>';
                        $output .= '<price>' . $voucher['amount'] . '</price>';
                        $output .= '<total>' . $voucher['amount'] . '</total>';
                        $output .= '<tax>0.0</tax>';
                        $output .= '<reward>0</reward>';
                        $output .= '</orderitem>';
                    endforeach;
                endif;
            } catch (Exception $ex) {
                
            }
            $output .= '</orderitems>';

            // ------------------------------------------
            /*
              $output .= '<ordervouchers>';
              if ($order_info["order_vouchers"] && count($order_info["order_vouchers"]) > 0):
              foreach ($order_info["order_vouchers"] as $voucher):
              $output .= '<ordervoucher>';
              $output .= '<description>' . $voucher["description"] . '</description>';
              $output .= '<amount>' . $voucher["amount"] . '</amount>';
              $output .= '</ordervoucher>';
              endforeach;
              endif;

              $output .= '</ordervouchers>';
             */
            // ==========================================
            // ------------------------------------------
            $output .= '<orderpayments>';
            //var_dump($order_info["order_payments"][0]);
            $retrieved_trans_id = $this->scoc_lib->getOrderTransactionId($order_id);

            if ($retrieved_trans_id != '') :
                $output .= '<orderpayment>';
                $output .= '<transaction_id>' . $retrieved_trans_id . '</transaction_id>';
                $output .= '</orderpayment>';
            else :
                if ($this->verbose == 1) {
                    echo "<br />----------- ORDER PAYMENTS -----------------<br />";
                    var_dump($order_info["order_payments"]);
                }

                switch ($order_info["order"]["payment_code"]) {
                    case "pp_express":
                    case "paypal_advanced":
                        if ($order_info["order_payments"]) :
                            foreach ($order_info["order_payments"] as $op) {
                                $output .= '<orderpayment>';
                                /*
                                  $output .= '<paypal_order_id>' . $op["paypal_order_id"] . '</paypal_order_id>';
                                  $output .= '<created>' . $op["created"] . '</created>';
                                  $output .= '<modified>' . $op["modified"] . '</modified>';
                                  $output .= '<capture_status>' . $op["capture_status"] . '</capture_status>';
                                  $output .= '<currency_code>' . $op["currency_code"] . '</currency_code>';
                                  $output .= '<authorization_id>' . $op["authorization_id"] . '</authorization_id>';
                                  $output .= '<total>' . $op["total"] . '</total>';
                                 */
                                foreach ($op as $key => $value) :
                                    if ($key != "trans") {
                                        $output .= '<' . (string) $key . '><![CDATA[' . (string) $value . ']]></' . (string) $key . '>';
                                    }
                                endforeach;
                                if ($op["trans"]) {
                                    foreach ($op["trans"] as $pt) {
                                        $output .= '<transaction>';
                                        /*
                                          $output .= '<paypal_order_id>' . $pt["paypal_order_id"] . '</paypal_order_id>';
                                          $output .= '<transaction_id>' . $pt["transaction_id"] . '</transaction_id>';
                                          $output .= '<parent_transaction_id>' . $pt["parent_transaction_id"] . '</parent_transaction_id>';
                                          $output .= '<created>' . $pt["created"] . '</created>';
                                          $output .= '<note>' . $pt["note"] . '</note>';
                                          $output .= '<receipt_id>' . $pt["receipt_id"] . '</receipt_id>';
                                          $output .= '<payment_type>' . $pt["payment_type"] . '</payment_type>';
                                          $output .= '<payment_status>' . $pt["payment_status"] . '</payment_status>';
                                          $output .= '<pending_reason>' . $pt["pending_reason"] . '</pending_reason>';
                                          $output .= '<transaction_entity>' . $pt["transaction_entity"] . '</transaction_entity>';
                                          $output .= '<amount>' . $pt["amount"] . '</amount>';
                                         */

                                        foreach ($pt as $key => $value) :
                                            $output .= '<' . (string) $key . '><![CDATA[' . (string) $value . ']]></' . (string) $key . '>';
                                        endforeach;
                                        $output .= '</transaction>';
                                    }
                                }

                                $output .= '</orderpayment>';
                            }
                        endif;
                        break;
                    case "authorizenet_aim":
                        $output .= '<orderpayment>';
                        if ($order_info["order_payments"]):
                            foreach ($order_info["order_payments"][0] as $key => $value):
                                $output .= '<' . (string) $key . '><![CDATA[' . (string) $value . ']]></' . (string) $key . '>';
                            endforeach;
                        endif;
                        $output .= '</orderpayment>';
                        break;
                    case 'aznet_sim':
                        // mainly used in 1.5.5.1
                        $output .= '<orderpayment>';
                        $output .= '<transaction_id>' . $this->scoc_lib->getOrderTransactionId($order_id) . '</transaction_id>';
                        $output .= '</orderpayment>';
                        break;
                    default:

                        break;
                }
            endif;

            if ($this->verbose == 1) {
                echo "<br />========= ORDER PAYMENTS ==========<br />";
            }

            $output .= '</orderpayments>';
            // ==========================================
            $output .= $this->build_returns($order_id);

            $output .= '</order>';

            //var_dump($product);
            //return;
        }


        $output .= '</orders>';

        //$this->response->addHeader('Content-Type:xml');
        $this->response->setOutput($output);
    }

    public function category()
    {
        $this->scoc_lib = new scoc_lib($this->registry);
        $this->scoc_encoder = new scoc_encoder($this->registry);

        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            return;
        }

        //$this->load->model('catalog/category');
        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<categories>';
        $output .= $this->scoc_lib->getVersionXml();

        $scoc_utility = new scoc_utility();

        $data = array(
            'start' => 0,
            'limit' => 500
        );

        //$category_total = $this->model_catalog_category->getTotalCategories();

        $categories = $this->scoc_lib->getCategories($data);

        //var_dump($categories);
        foreach ($categories as $c) {
            if ($c['category_id'] != 0) {
                $output .= '<category>';
                $output .= '<id>' . $c["category_id"] . '</id>';
                $output .= '<name><![CDATA[' . $c["name"] . ']]></name>';
                $output .= '<parent_id>' . $c["parent_id"] . '</parent_id>';
                $output .= '</category>';
            }
        }

        $output .= '</categories>';

        //scoc_utility::printHeader();


        $this->response->setOutput($output);
        //echo $output;
    }

    private function getPath($parent_id, $current_path = '')
    {
        $this->load->model('catalog/category');


        $category_info = $this->model_catalog_category->getCategory($parent_id);

        if ($category_info) {
            if (!$current_path) {
                $new_path = $category_info['category_id'];
            }
            else {
                $new_path = $category_info['category_id'] . '_' . $current_path;
            }

            $path = $this->getPath($category_info['parent_id'], $new_path);

            if ($path) {
                return $path;
            }
            else {
                return $new_path;
            }
        }
    }

    private function build_returns($order_id)
    {
        $output = '<returns>';

        $rs = $this->scoc_lib->getReturns($order_id);

        foreach ($rs as $r):
            $return = $this->scoc_lib->getReturn($r["return_id"]);

            $output .= '<return>';
            foreach ($return as $key => $value) :
                $output .= '<' . (string) $key . '>' . (string) $value . '</' . (string) $key . '>';
            endforeach;
            $output .= '</return>';

        endforeach;

        $output .= '</returns>';
        return $output;
    }

    public function shipping()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib = new scoc_lib($this->registry);

        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            return;
        }

        $adminpath = str_replace("catalog", "admin", DIR_APPLICATION);
        //$shippath = str_replace( "\\", "/", $adminpath . 'controller/shipping/*.php' );
        $shippath = str_replace("\\", "/", $adminpath . 'controller/extension/shipping/*.php');

        //$shippath = str_replace("catalog", "admin", $shippath);
        //$this->language->load('extension/shipping');
        //$this->load->model('setting/extension');
        $extensions = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension WHERE `type` = 'shipping'");
        foreach ($query->rows as $result) {
            $extensions[] = $result['code'];
        }


        $files = glob($shippath);
        //var_dump($this->language);
        $data['extensions'] = array();

        if ($extensions) {
            foreach ($extensions as $extension) {
                
            }
        }

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                //require_once $adminpath . 'language/english/shipping/' . $extension . '.php';
                require_once $adminpath . 'language/en-gb/extension/shipping/' . $extension . '.php';

                $installed = 0;
                if (in_array($extension, $extensions)) {
                    $installed = 1;
                }

                $options = array();
                switch ($extension):
                    case "auspost":
                        $options['dhl_IE'] = 'International Express';

                    // no break
                    case "fedex":
                        $options['EUROPE_FIRST_INTERNATIONAL_PRIORITY'] = 'Europe First International Priority';
                        $options['FEDEX_1_DAY_FREIGHT'] = 'Fedex 1 Day Freight';
                        $options['FEDEX_2_DAY'] = 'Fedex 2 Day';
                        $options['FEDEX_2_DAY_AM'] = 'Fedex 2 Day AM';
                        $options['FEDEX_2_DAY_FREIGHT'] = 'Fedex 2 Day Freight';
                        $options['FEDEX_3_DAY_FREIGHT'] = 'Fedex 3 Day Freight';
                        $options['FEDEX_EXPRESS_SAVER'] = 'Fedex Express Saver';
                        $options['FEDEX_FIRST_FREIGHT'] = 'Fedex First Fright';
                        $options['FEDEX_FREIGHT_ECONOMY'] = 'Fedex Fright Economy';
                        $options['FEDEX_FREIGHT_PRIORITY'] = 'Fedex Fright Priority';
                        $options['FEDEX_GROUND'] = 'Fedex Ground';
                        $options['FIRST_OVERNIGHT'] = 'First Overnight';
                        $options['GROUND_HOME_DELIVERY'] = 'Ground Home Delivery';
                        $options['INTERNATIONAL_ECONOMY'] = 'International Economy';
                        $options['INTERNATIONAL_ECONOMY_FREIGHT'] = 'International Economy Freight';
                        $options['INTERNATIONAL_FIRST'] = 'International First';
                        $options['INTERNATIONAL_PRIORITY'] = 'International Priority';
                        $options['INTERNATIONAL_PRIORITY_FREIGHT'] = 'International Priority Freight';
                        $options['PRIORITY_OVERNIGHT'] = 'Priority Overnight';
                        $options['SMART_POST'] = 'Smart Post';
                        $options['STANDARD_OVERNIGHT'] = 'Standard Overnight';
                        break;

                        break;

                    case "ups":
                        $options['ups_us_01'] = 'UPS Next Day Air';
                        $options['ups_us_02'] = 'UPS Second Day Air';
                        $options['ups_us_03'] = 'UPS Ground';
                        $options['ups_us_07'] = 'UPS Worldwide Express';
                        $options['ups_us_08'] = 'UPS Worldwide Expedited';
                        $options['ups_us_11'] = 'UPS Standard';
                        $options['ups_us_12'] = 'UPS Three-Day Select';
                        $options['ups_us_13'] = 'UPS Next Day Air Saver';
                        $options['ups_us_14'] = 'UPS Next Day Air Early A.M.';
                        $options['ups_us_54'] = 'UPS Worldwide Express Plus';
                        $options['ups_us_59'] = 'UPS Second Day Air A.M.';
                        $options['ups_us_65'] = 'UPS Saver';

                        break;

                    case "usps":
                        $options['usps_domestic_00'] = 'First-Class Mail Parcel';
                        $options['usps_domestic_01'] = 'First-Class Mail Large Envelope';
                        $options['usps_domestic_02'] = 'First-Class Mail Letter';
                        $options['usps_domestic_03'] = 'First-Class Mail Postcards';
                        $options['usps_domestic_1'] = 'Priority Mail';
                        $options['usps_domestic_2'] = 'Express Mail Hold for Pickup';
                        $options['usps_domestic_3'] = 'Express Mail';
                        $options['usps_domestic_4'] = 'Parcel Post';
                        $options['usps_domestic_5'] = 'Bound Printed Matter';
                        $options['usps_domestic_6'] = 'Media Mail';
                        $options['usps_domestic_7'] = 'Library';
                        $options['usps_domestic_12'] = 'First-Class Postcard Stamped';
                        $options['usps_domestic_13'] = 'Express Mail Flat-Rate Envelope';
                        $options['usps_domestic_16'] = 'Priority Mail Flat-Rate Envelope';
                        $options['usps_domestic_17'] = 'Priority Mail Regular Flat-Rate Box ';
                        $options['usps_domestic_18'] = 'Priority Mail Keys and IDs';
                        $options['usps_domestic_19'] = 'First-Class Keys and IDs';
                        $options['usps_domestic_22'] = 'Priority Mail Flat-Rate Large Box';
                        $options['usps_domestic_23'] = 'Express Mail Sunday/Holiday';
                        $options['usps_domestic_25'] = 'Express Mail Flat-Rate Envelope Sunday/Holiday';
                        $options['usps_domestic_27'] = 'Express Mail Flat-Rate Envelope Hold For Pickup';
                        $options['usps_domestic_28'] = 'Priority Mail Small Flat-Rate Box ';

                        $options['usps_international_1'] = 'Express Mail International';
                        $options['usps_international_2'] = 'Priority Mail International';
                        $options['usps_international_4'] = 'Global Express Guaranteed (Document and Non-document)';
                        $options['usps_international_5'] = 'Global Express Guaranteed Document used';
                        $options['usps_international_6'] = 'Global Express Guaranteed Non-Document Rectangular shape';
                        $options['usps_international_7'] = 'Global Express Guaranteed Non-Document Non-Rectangular';
                        $options['usps_international_8'] = 'Priority Mail Flat Rate Envelope';
                        $options['usps_international_9'] = 'Priority Mail Flat Rate Box';
                        $options['usps_international_10'] = 'Express Mail International Flat Rate Envelope';
                        $options['usps_international_11'] = 'Priority Mail Flat Rate Large Box';
                        $options['usps_international_12'] = 'Global Express Guaranteed Envelope';
                        $options['usps_international_13'] = 'First Class Mail International Letters';
                        $options['usps_international_14'] = 'First Class Mail International Flats';
                        $options['usps_international_15'] = 'First Class Mail International Parcels';
                        $options['usps_international_16'] = 'Priority Mail Flat Rate Small Box';
                        $options['usps_international_21'] = 'Postcards';
                        break;
                endswitch;



                $data['extensions'][] = array(
                    'code' => $extension,
                    'name' => $_['heading_title'],
                    'options' => $options,
                    'installed' => $installed
                );
            }
        }

        //var_dump($this->data['extensions']);
        // generate xml here

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<carriers>';

        $output .= $this->scoc_lib->getVersionXml();


        foreach ($data['extensions'] as $carrier) {
            $output .= '<carrier>';
            $output .= '<code>' . $carrier["code"] . '</code>';
            $output .= '<name>' . $carrier["name"] . '</name>';
            $output .= '<installed>' . $carrier["installed"] . '</installed>';

            $output .= '<options>';
            foreach ($carrier["options"] as $option_code => $option_title) :
                $output .= '<option>';
                $output .= '<code>' . $option_code . '</code>';
                $output .= '<title>' . $option_title . '</title>';
                $output .= '</option>';
            endforeach;

            $output .= '</options>';

            $output .= '</carrier>';
        }


        $output .= '</carriers>';
        //$this->scoc_lib->httpHeaders();
        $this->response->setOutput($output);
    }

    private function getProductTabs($product_id)
    {
        $product_tabs = array();
        $query = $this->db->query("SELECT t.*, p2t.status AS product_tab_status, tn.name, tc.content FROM "
                . DB_PREFIX . "product_tab t JOIN " . DB_PREFIX . "product_to_tab p2t ON (t.tab_id = p2t.tab_id) LEFT JOIN "
                . DB_PREFIX . "product_tab_name tn ON (t.tab_id = tn.tab_id AND tn.language_id = '"
                . (int) $this->config->get('config_language_id') . "') LEFT JOIN "
                . DB_PREFIX . "product_tab_content tc ON (t.tab_id = tc.tab_id AND tc.language_id = '"
                . (int) $this->config->get('config_language_id') . "' AND tc.product_id = '"
                . (int) $product_id . "') WHERE p2t.product_id = '"
                . (int) $product_id . "' AND t.status = '1' AND p2t.status = '1' ORDER BY t.sort_order ASC");

        if ($query->num_rows) {
            $product_tabs = $query->rows;
        }
        $tabs = array();
        foreach ($product_tabs as $tab) {
            $tab['content'] = html_entity_decode(
                    $tab['content'], ENT_QUOTES, 'UTF-8'
            );
            $tabs[] = $tab;
            //$tab_ids[] = $tab['tab_id'];
        }
        return $tabs;
    }

    public function test()
    {
        
    }

    public function inventorydownload()
    {
        $this->scoc_encoder = new scoc_encoder($this->registry);
        $this->scoc_lib = new scoc_lib($this->registry);

        if (ob_get_length()) {
            //ob_clean();
        }
        //ob_start();
        error_reporting(1);

        $this->scoc_lib->httpHeaders();

        $qryStrArray = $this->scoc_encoder->authenticate(true, false);
        if ($qryStrArray == null) {
            if (ob_get_contents()) :
            //ob_end_clean();
            endif;
            return;
        }

        //$this->load->model('catalog/category');
        $this->load->model('catalog/product');

        //$currentPageIndex = isset($this->request->get['currentPageIndex']) ? $this->request->get['currentPageIndex'] : NULL;
        $currentPageIndex = isset($qryStrArray['currentpageindex']) ? $qryStrArray['currentpageindex'] : 0;

        //$rowsPerPage = isset($this->request->get['rowsPerPage']) ? $this->request->get['rowsPerPage'] : NULL;
        $rowsPerPage = isset($qryStrArray['rowsperpage']) ? $qryStrArray['rowsperpage'] : 100;

        $output = '<?xml version="1.0" encoding="UTF-8" ?>';
        $output .= '<products>';
        $output .= $this->scoc_lib->getVersionXml();

        $products = null;
        $recordCount = 0;

        try {
            $products = $this->scoc_lib->getProducts(
                    $currentPageIndex, $rowsPerPage
            );
            $recordCount = $this->model_catalog_product->getTotalProducts(null);
        } catch (Exception $ex) {
            
        }

        $output .= '<recordcount>' . $recordCount . '</recordcount>';

        //if (isset( $this->request->get['currentPageIndex'] ) && isset( $this->request->get['rowsPerPage'] )) {
        //}

        $totalPages = (int) ($recordCount / $rowsPerPage) + ((int) ($recordCount % $rowsPerPage) > 0 ? 1 : 0);
        $output .= '<currentpageindex>' . $currentPageIndex . '</currentpageindex>';
        $output .= '<totalpages>' . $totalPages . '</totalpages>';

        if ($products):

            foreach ($products as $product) {
                //var_dump($product);exit;
                //if( !isset($product['sku']) || empty($product['sku'])) continue;
                $output .= '<product>';
                $output .= '<id>' . $product['product_id'] . '</id>';
                $output .= '<sku><![CDATA[' . $product['sku'] . ']]></sku>';
                $output .= '<inventorylevel>' . $product['quantity'] . '</inventorylevel>';
                $output .= '<price>' . $product['price'] . '</price>';
                
                $link = $this->url->link('product/product', 'product_id=' . $product['product_id']);
                $link = str_replace('&amp;', '&', $link);
                $output .= '<link><![CDATA[' . $link . ']]></link>';
                $output .= '</product>';
            }

        endif;

        $output .= '</products>';
        $output = trim($output);
        //header_remove('Content-Type');



        if (ob_get_contents()):
        //ob_end_clean();
        endif;

        $this->response->setOutput($output);
        //echo $output;
    }

}
