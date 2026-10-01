<?php

//if (!defined('SC_PLUGIN_CURRENT_VERSION')):
//    define('SC_PLUGIN_CURRENT_VERSION', '1.2');
//endif;
final class scoc_lib {

    private $registry;

    public function __construct($registry)
    {
        $this->registry = $registry;
        //if (!defined('SC_PLUGIN_CURRENT_VERSION')) :
        //    define('SC_PLUGIN_CURRENT_VERSION', '1.4.3');
        //endif;
    }

    public function getPluginVersion()
    {
        return '4.0.2.1';
    }

    public function getVersionXml()
    {
        return "<channel>"
                . "<plugin_version>" . $this->getPluginVersion() . "</plugin_version>"
                . "<oc_version>" . VERSION . '</oc_version>'
                . "<php_version>" . PHP_VERSION . "</php_version>"
                . "<link>" . HTTP_SERVER . "</link>"
                . "</channel>";
    }

    public function httpHeaders()
    {
        $this->response->addHeader("HTTP/1.1 200 OK");
        $this->response->addHeader("Content-type: text/xml");
        $this->response->addHeader("Expires: on, 01 Jan 1970 00:00:00 GMT");
        $this->response->addHeader("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        $this->response->addHeader("Cache-Control: no-store, no-cache, must-revalidate");
        $this->response->addHeader("Cache-Control: post-check=0, pre-check=0", false);
        $this->response->addHeader("Pragma: no-cache");
    }

    public function __get($name)
    {
        return $this->registry->get($name);
    }

    public function importProducts($jobId, $verbose)
    {
        require_once DIR_SYSTEM . "library/scoc_importer.php";
        require_once DIR_SYSTEM . "library/scoc_xmllog.php";

        $job_row = $this->getJob($jobId);
        if ($job_row == null) {
            //$log->write('SCOC :  ' . "job# " . $jobId . " not found");
            return "job# " . $jobId . " not found";
        }

        //$localfilename = $this->getLogFolderPath() . (string) $job_row['localfilename'];
        //$logfilename = $this->getLogFolderPath() . (string) $job_row['logfilename'];
        $local_file_source = (string) $job_row["local_file_source"];

        //$t = new scoc_xmllog($logfilename);
        //var_dump($logfilename); return false;

        try {
            //$feed_xml = simplexml_load_file( $localfilename ); /* simplexml_load_string	 */
            $feed_xml = simplexml_load_string($local_file_source);

            if ($feed_xml == null) {
                $this->updateJobErrored($jobId);
                if ($verbose == 1) {
                    echo "job# " . $jobId . ", can not load localfilesource. Job status:error, rejected";
                }
                return "job# " . $jobId . ", can not load localfilesource. Job status:error, rejected";
            }

            $module = '';
            if (isset($feed_xml['module'])) {
                $module = (string) $feed_xml['module'];
            }
            $this->updateJobSubmitted($jobId);


            $lid = (int) $this->config->get('config_language_id');

            $o = new scoc_importer($this, $this->log, $jobId, $verbose);

            if ($module == '') {
                $o->startImport($feed_xml, $lid);
            } elseif ($module == 'invprice') {
                $o->startMini($feed_xml, $lid);
            } elseif ($module == 'shipping') {
                //$o->importShipping($feed_xml);
            } elseif ($module == 'order_cancel') {
                $o->importOrderCancel($feed_xml);
            } elseif ($module == 'order_refund') {
                $o->importOrderRefund($feed_xml);
            }
            
            $o = null;

            $this->updateJobCompleted($jobId);

            if ($verbose == 1) {
                echo "job# " . $jobId . " updated as completed";
            }
            
            return ""; // executed successfully
        } catch (Exception $ex) {
            $this->updateJobErrored($jobId);
            if ($verbose == 1) {
                echo "job# " . $jobId . ", Job status:error " . $ex->getMessage();
            }
            return $ex->getMessage();
        }


        $data = array();
        $lid = (int) $this->config->get('config_language_id');
        echo "test page";
        if (true) {
            $sku = 'test51';
            $product['product_description'][$lid] = array();
            $product['product_description'][$lid]['name'] = 'test51 Product 003';
            $product['product_description'][$lid]['meta_description'] = 'Test Product 003 meta_description';
            $product['product_description'][$lid]['meta_keyword'] = 'Test Product 003 meta_keyword';
            $product['product_description'][$lid]['description'] = 'Test Product 003 description';
            $product['product_description'][$lid]['tag'] = 'Test,Product';


            $product["config_language_id"] = $lid;
            $product["sort_order"] = 1;
            $product["sku"] = $sku;
            $product["model"] = "model 003";
            $product["upc"] = "upc 003";
            $product["ean"] = "ean 003";
            $product["jan"] = "jan 003";
            $product["isbn"] = "isbn 003";
            $product["mpn"] = "mpn 003";
            $product["location"] = "location 003";
            $product["price"] = 33.00;
            $product["tax_class_id"] = $this->gettax_class_id('Taxable Goods');
            $product["quantity"] = "99";
            $product["minimum"] = "1";
            $product["subtract"] = "";
            $product["stock_status_id"] = "7";
            $product["shipping"] = "1"; // 1 or 0
            $product["keyword"] = "keyword 003";
            $product["image"] = "data/logo.png";
            $product["date_available"] = "2013-11-19";
            $product["length"] = "10";
            $product["width"] = "20";
            $product["height"] = "";
            $product["length_class_id"] = $this->getlength_class_id('mm', $lid);
            $product["weight"] = "1.00";
            $product["weight_class_id"] = $this->getweight_class_id('gm', $lid); // kg,g,lb,oz
            $product["status"] = "1";
            $product["manufacturer"] = "Apple";
            $product["manufacturer_id"] = $this->getManufacturerId('Apple');
            $product["points"] = 0;
            $product["download"] = '1000';

            $product["product_category"] = array();
            $product["product_category"][] = 33;
            $product["product_category"][] = 25;


            $product["product_store"] = array();
            $product["product_store"][] = 0;


            $product['product_attribute'] = array();
            $product['product_attribute'][0] = array();
            $product['product_attribute'][0]['name'] = 'No. of Cores';
            $product['product_attribute'][0]['attribute_id'] = 2;

            //$product['product_attribute'][0]['product_attribute_description'][]=array();
            //$product['product_attribute'][0]['product_attribute_description'][$lid]=array();
            $product['product_attribute'][0]['product_attribute_description'][$lid] = array(
                'text' => '4'
            );

            //var_dump($product['product_attribute'][0]);
            // Discount section starts
            //$product['product_discount'] = array();

            $product['product_discount'][0] = array();
            $product['product_discount'][0]['customer_group_id'] = $this->getCustomerGroupId(
                    'Default', $lid
            );
            $product['product_discount'][0]['quantity'] = 100;
            $product['product_discount'][0]['priority'] = 1;
            $product['product_discount'][0]['price'] = 1.5;
            $product['product_discount'][0]['date_start'] = '2013-11-20';
            $product['product_discount'][0]['date_end'] = '2013-12-31';

            $product['product_discount'][1] = array();
            $product['product_discount'][1]['customer_group_id'] = $this->getCustomerGroupId(
                    'Default', $lid
            );
            $product['product_discount'][1]['quantity'] = 200;
            $product['product_discount'][1]['priority'] = 1;
            $product['product_discount'][1]['price'] = 1.44;
            $product['product_discount'][1]['date_start'] = '2013-11-20';
            $product['product_discount'][1]['date_end'] = '2013-12-31';
            // Discount section ends...............
            // product_special section starts
            $product['product_special'] = array();
            $customer_group_id = 0;
            $product['product_special'][0] = array();
            $product['product_special'][0]['customer_group_id'] = $this->getCustomerGroupId(
                    'Default', $lid
            );
            $product['product_special'][0]['priority'] = 1;
            $product['product_special'][0]['price'] = 1.5;
            $product['product_special'][0]['date_start'] = '2013-11-20';
            $product['product_special'][0]['date_end'] = '2013-12-31';

            // product_image section starts
            $product['product_image'] = array();
            $product['product_image'][] = array();
            $product['product_image'][0]['image'] = 'data/logo.png';
            $product['product_image'][0]['sort_order'] = '1';
            $product['product_image'][1]['image'] = 'data/logo.png';
            $product['product_image'][1]['sort_order'] = '2';

            // product_image section ends..............
            // product_reward section starts
            $product['product_reward'] = array();
            $product['product_reward'][$this->getCustomerGroupId(
                            'Default', $lid
                    )] = array();
            $product['product_reward'][$this->getCustomerGroupId(
                            'Default', $lid
                    )]['points'] = '200';

            // product_reward section ends......
            $product['product_option'] = array();

            $option_id = $this->getproduct_option_id('Color', $lid);
            $po = array();
            $po["product_option_id"] = '0';
            $po["type"] = "select";
            $po["option_id"] = $option_id;
            $po["required"] = 1;
            $po["option_value"] = "direct value";
            $po["product_option_value"] = array();

            $pov = array();
            $pov["product_option_value_id"] = '0';
            $pov["option_value_id"] = $this->getproduct_option_value_id(
                    'Red', $option_id, $lid
            );
            $pov["quantity"] = 50;
            $pov["subtract"] = 1;
            $pov["price"] = 3.00;
            $pov["price_prefix"] = "+";
            $pov["points"] = 50;
            $pov["points_prefix"] = "+";
            $pov["weight"] = 1.1;
            $pov["weight_prefix"] = "+";

            $po["product_option_value"][] = $pov;

            $product['product_option'][] = $po;
        }

        //var_dump($product);
        $product_id = $this->getProductIdBySku($sku);
        $exist = $product_id == 0 ? false : true;
        if ($exist == false) {
            $product_id = $this->addProduct($product);
            echo "New Product created " . $product_id;
        }
        else {
            echo "Product exists. Updating";
        }
        //var_dump($product);
        $product_id = $this->updateProduct($product_id, $product);
    }

    /*
     * addonLoad (copy from ebay library)
     *
     * Loads a 3rd party module for OpenBay to use.
     * @param $addon
     * @return bool
     */

    public function addonLoad($addon)
    {
        $addon = (string) $addon; //ensure the addon name is a string value.


        if (file_exists(DIR_SYSTEM . "scoc/" . $addon . ".php")) {
            include_once(DIR_SYSTEM . "scoc/" . $addon . ".php");

            if (empty($this->addon) || !is_object($this->addon)) {
                $this->addon = new stdClass();
            }

            $this->addon->$addon = new $addon;
            return true;
        }
        else {
            return false;
        }
    }

    private function blankOrValue($array, $col)
    {
        return isset($array[$col]) ? $array[$col] : '';
    }

    public function getOrders(
    $currentPageIndex = 0, $rowsPerPage = 20, $fromDate = null, $toDate = null, $order_id = null
    )
    {

        //$this->load->model('sale/order');
        //$orders = $this->model_account_order->getOrders($currentPageIndex, $rowsPerPage);
        $start = $currentPageIndex;
        $limit = $rowsPerPage;
        if ($start < 0) {
            $start = 0;
        }
        $start = $currentPageIndex * $rowsPerPage;
        if ($limit < 1) {
            $limit = 1;
        }

        $sql = "SELECT o.order_id FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE ";
        $sql.= " os.language_id = '" . (int) $this->config->get('config_language_id') . "'";
        //var_dump($sql);
        if (!empty($fromDate)) {
            $sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape($fromDate) . "')";
        }
        if (!empty($toDate)) {
            $sql .= " AND DATE(o.date_added) <= DATE('" . $this->db->escape($toDate) . "')";
        }
        if (!empty($order_id)) {
            $sql .= " AND o.order_id='" . ($order_id) . "'";
        }
        $sql .= " ORDER BY o.order_id DESC LIMIT " . (int) $start . "," . (int) $limit;

        //echo $sql;

        $query = $this->db->query($sql);

        return $query->rows;

        return $orders;
    }

    public function getOrder($order_id)
    {
        $sql = "SELECT o.* ";
        $sql .= ", (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int) $this->config->get('config_language_id') . "') AS status ";
        $sql .= "FROM `" . DB_PREFIX . "order` o ";
        $sql .= "WHERE o.order_id = '" . (int) $order_id . "'";
        //echo $sql;

        $order_query = $this->db->query($sql);
        //$order_info['order']=$order_query; return $order_info;

        $order_info = array();
        $order_info["order"] = array();
        $order_info['order_products'] = array();
        $order_info['order_payments'] = array();

        if ($order_query->num_rows > 0) {
            //foreach ($order_query->rows as $row) {
            //	$order_info['order'][] = $row;
            //}
            if (true) {
                $order_info['order']['order_id'] = $order_query->row['order_id'];
                $order_info['order']['invoice_no'] = $order_query->row['invoice_no'];
                $order_info['order']['invoice_prefix'] = $order_query->row['invoice_prefix'];
                $order_info['order']['store_id'] = $order_query->row['store_id'];
                $order_info['order']['store_name'] = $order_query->row['store_name'];
                $order_info['order']['store_url'] = $order_query->row['store_url'];
                $order_info['order']['customer_id'] = $order_query->row['customer_id'];
                $order_info['order']['customer_group_id'] = $order_query->row['customer_group_id'];
                $order_info['order']['firstname'] = $order_query->row['firstname'];
                $order_info['order']['lastname'] = $order_query->row['lastname'];
                $order_info['order']['telephone'] = $order_query->row['telephone'];
                $order_info['order']['fax'] = $order_query->row['fax'];
                $order_info['order']['email'] = $order_query->row['email'];
                $order_info['order']['payment_firstname'] = $order_query->row['payment_firstname'];
                $order_info['order']['payment_lastname'] = $order_query->row['payment_lastname'];
                $order_info['order']['payment_company'] = $order_query->row['payment_company'];


                $order_info['order']['payment_company_id'] = isset($order_query->row['payment_company_id']) ? $order_query->row['payment_company_id'] : '';

                $order_info['order']['payment_tax_id'] = isset($order_query->row['payment_tax_id']) ? $order_query->row['payment_tax_id'] : '';

                $order_info['order']['payment_address_1'] = $order_query->row['payment_address_1'];
                $order_info['order']['payment_address_2'] = $order_query->row['payment_address_2'];
                $order_info['order']['payment_postcode'] = $order_query->row['payment_postcode'];
                $order_info['order']['payment_city'] = $order_query->row['payment_city'];
                $order_info['order']['payment_zone_id'] = $order_query->row['payment_zone_id'];
                $order_info['order']['payment_zone'] = $order_query->row['payment_zone'];

                $order_info['order']['payment_country_id'] = $order_query->row['payment_country_id'];
                $order_info['order']['payment_country'] = $order_query->row['payment_country'];

                $order_info['order']['payment_address_format'] = $order_query->row['payment_address_format'];
                $order_info['order']['payment_method'] = $order_query->row['payment_method'];
                $order_info['order']['payment_code'] = $order_query->row['payment_code'];
                $order_info['order']['shipping_firstname'] = $order_query->row['shipping_firstname'];
                $order_info['order']['shipping_lastname'] = $order_query->row['shipping_lastname'];
                $order_info['order']['shipping_company'] = $order_query->row['shipping_company'];
                $order_info['order']['shipping_address_1'] = $order_query->row['shipping_address_1'];
                $order_info['order']['shipping_address_2'] = $order_query->row['shipping_address_2'];
                $order_info['order']['shipping_postcode'] = $order_query->row['shipping_postcode'];
                $order_info['order']['shipping_city'] = $order_query->row['shipping_city'];
                $order_info['order']['shipping_zone_id'] = $order_query->row['shipping_zone_id'];
                $order_info['order']['shipping_zone'] = $order_query->row['shipping_zone'];

                $order_info['order']['shipping_country_id'] = $order_query->row['shipping_country_id'];
                $order_info['order']['shipping_country'] = $order_query->row['shipping_country'];
                $order_info['order']['shipping_address_format'] = $order_query->row['shipping_address_format'];
                $order_info['order']['shipping_method'] = $order_query->row['shipping_method'];
                $order_info['order']['shipping_code'] = $order_query->row['shipping_code'];
                $order_info['order']['comment'] = $order_query->row['comment'];
                $order_info['order']['total'] = $order_query->row['total'];

                $order_info['order']['order_status_id'] = $order_query->row['order_status_id'];
                $order_info['order']['affiliate_id'] = $order_query->row['affiliate_id'];

                $order_info['order']['commission'] = $order_query->row['commission'];
                $order_info['order']['language_id'] = $order_query->row['language_id'];

                $order_info['order']['currency_id'] = $order_query->row['currency_id'];
                $order_info['order']['currency_code'] = $order_query->row['currency_code'];
                $order_info['order']['currency_value'] = $order_query->row['currency_value'];
                $order_info['order']['ip'] = $order_query->row['ip'];
                $order_info['order']['forwarded_ip'] = $order_query->row['forwarded_ip'];
                $order_info['order']['user_agent'] = $order_query->row['user_agent'];
                $order_info['order']['accept_language'] = $order_query->row['accept_language'];
                $order_info['order']['date_added'] = $order_query->row['date_added'];
                $order_info['order']['date_modified'] = $order_query->row['date_modified'];
                $order_info["order"]["comment"] = nl2br($order_info["order"]["comment"]);
                //$order_info['order']["payment_address_format"] = "";
            }


            $reward = 0;
            $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int) $order_id . "'");

            foreach ($order_product_query->rows as $product) {
                $reward += $product['reward'];

                // Need to insert data for product option here, too
                $product['options'] = array();
                $order_product_option_query = $this->db->query("SELECT name, value FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int) $order_id . "' AND order_product_id='" . (int) $product["order_product_id"] . "'");
                if ($order_product_option_query->num_rows > 0):
                    foreach ($order_product_option_query->rows as $po):
                        $product['options'][] = array(
                            'option_name' => $po['name'],
                            'option_value' => $po['value']
                        );
                    endforeach;

                endif;

                $order_info['order_products'][] = $product;
            }
            $order_info['order']["reward"] = $reward;

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int) $order_query->row['payment_country_id'] . "'");
            $payment_iso_code_2 = '';
            $payment_iso_code_3 = '';
            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int) $order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            }
            else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int) $order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            }
            else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int) $order_query->row['shipping_zone_id'] . "'");
            $shipping_zone_code = '';
            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            }

            if ($order_query->row['affiliate_id']) {
                $affiliate_id = $order_query->row['affiliate_id'];
            }
            else {
                $affiliate_id = 0;
            }

            $affiliate_info = null;
            //$this->load->model('sale/affiliate');
            //$affiliate_info = $this->model_sale_affiliate->getAffiliate($affiliate_id);

            if ($affiliate_info) {
                $affiliate_firstname = $affiliate_info['firstname'];
                $affiliate_lastname = $affiliate_info['lastname'];
            }
            else {
                $affiliate_firstname = '';
                $affiliate_lastname = '';
            }

            $this->load->model('localisation/language');

            $language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

            if ($language_info) {
                $language_code = $language_info['code'];
                $language_filename = $this->blankOrValue(
                        $language_info, 'filename'
                );
                $language_directory = $this->blankOrValue(
                        $language_info, 'directory'
                );
            }
            else {
                $language_code = '';
                $language_filename = '';
                $language_directory = '';
            }

            if (true) {
                $order_info['order']['shipping_iso_code_2'] = $shipping_iso_code_2;
                $order_info['order']['shipping_iso_code_3'] = $shipping_iso_code_3;
                $order_info['order']['shipping_zone_code'] = $shipping_zone_code;
                $order_info['order']['language_code'] = $language_code;
                $order_info['order']['payment_iso_code_2'] = $payment_iso_code_2;
                $order_info['order']['payment_iso_code_3'] = $payment_iso_code_3;
                $order_info['order']['payment_zone_code'] = $payment_zone_code;
                $order_info['order']['language_filename'] = $language_filename;
                $order_info['order']['language_directory'] = $language_directory;
                $order_info['order']['affiliate_firstname'] = $affiliate_firstname;
                $order_info['order']['affiliate_lastname'] = $affiliate_lastname;
            }

            $order_info['order_vouchers'] = array();
            $voucher_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int) $order_id . "'");
            if ($voucher_query->num_rows > 0) {
                $ii = 0;
                foreach ($voucher_query->rows as $voucher) {
                    $order_info['order_vouchers'][$ii]['voucher_id'] = $voucher['voucher_id'];
                    $order_info['order_vouchers'][$ii]['code'] = $voucher['code'];
                    $order_info['order_vouchers'][$ii]['description'] = $voucher['description'];
                    $order_info['order_vouchers'][$ii]['amount'] = $voucher['amount'];
                }
            }
            $amazonOrderId = '';

            if ($this->config->get('amazon_status') == 1) {
                $amazon_query = $this->db->query("
                    SELECT `amazon_order_id`
                    FROM `" . DB_PREFIX . "amazon_order`
                    WHERE `order_id` = " . (int) $order_query->row['order_id'] . "
                    LIMIT 1")->row;

                if (isset($amazon_query['amazon_order_id']) && !empty($amazon_query['amazon_order_id'])) {
                    $amazonOrderId = $amazon_query['amazon_order_id'];
                }
            }

            if ($this->config->get('amazonus_status') == 1) {
                $amazon_query = $this->db->query("
                        SELECT `amazonus_order_id`
                        FROM `" . DB_PREFIX . "amazonus_order`
                        WHERE `order_id` = " . (int) $order_query->row['order_id'] . "
                        LIMIT 1")->row;

                if (isset($amazon_query['amazonus_order_id']) && !empty($amazon_query['amazonus_order_id'])) {
                    $amazonOrderId = $amazon_query['amazonus_order_id'];
                }
            }

            $order_info["order"]["amazon_order_id"] = $amazonOrderId;
            $order_info["order"]["payment_zone_code"] = $payment_zone_code;
            $order_info["order"]["payment_iso_code_2"] = $payment_iso_code_2;
            $order_info["order"]["payment_iso_code_3"] = $payment_iso_code_3;
            $order_info["order"]["shipping_zone_code"] = $shipping_zone_code;

            $order_info["order"]["shipping_iso_code_2"] = $shipping_iso_code_2;
            $order_info["order"]["shipping_iso_code_3"] = $shipping_iso_code_3;

            $order_info["order"]["language_code"] = $language_code;

            //'affiliate_firstname' => $affiliate_firstname,
            //'affiliate_lastname' => $affiliate_lastname,
            $order_info["order_payments"] = array();
            //var_dump($order_info["order"]["payment_code"]);
            if ($order_info["order"]["payment_code"] == "pp_express" || $order_info["order"]["payment_code"] == "paypal_advanced") {
                $paypal_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "paypal_order` WHERE order_id = '" . (int) $order_id . "'");
                //echo "*************************" . $paypal_query->num_rows ;
                if ($paypal_query->num_rows > 0) {
                    $i = 0;
                    foreach ($paypal_query->rows as $paypal) {
                        $paypal_order_id = (int) $paypal["paypal_order_id"];
                        $order_info["order_payments"][$i] = array(
                            'paypal_order_id' => $paypal_order_id,
                            'created' => $this->blankOrValue($paypal, "created"),
                            'modified' => $this->blankOrValue(
                                    $paypal, "modified"
                            ),
                            'capture_status' => $paypal["capture_status"],
                            'currency_code' => $paypal["currency_code"],
                            'authorization_id' => $paypal["authorization_id"],
                            'total' => $paypal["total"]
                        );
                        //$order_info["order_payments"][][] = $paypal["paypal_order_id"];
                        //$order_info["order_payments"][][""] = $paypal["modified"];
                        //$order_info["order_payments"][][""] = $paypal["capture_status"];
                        //$order_info["order_payments"][][""] = $paypal["currency_code"];
                        //$order_info["order_payments"][][""] = $paypal["authorization_id"];
                        //$order_info["order_payments"][]["total"] = $paypal["total"];

                        $paypal_trans_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "paypal_order_transaction` WHERE paypal_order_id = '" . $paypal_order_id . "'");
                        if ($paypal_trans_query->num_rows > 0) {
                            foreach ($paypal_trans_query->rows as $paypal_trans) {
                                $order_info["order_payments"][$i]["trans"][] = array(
                                    'paypal_order_id' => $paypal_trans["paypal_order_id"],
                                    'transaction_id' => $paypal_trans["transaction_id"],
                                    'parent_transaction_id' => $this->blankOrValue($paypal_trans, "parent_transaction_id"),
                                    'created' => $this->blankOrValue($paypal_trans, "created"),
                                    'note' => $paypal_trans["note"],
                                    'receipt_id' => $paypal_trans["receipt_id"],
                                    'payment_type' => $paypal_trans["payment_type"],
                                    'payment_status' => $paypal_trans["payment_status"],
                                    'pending_reason' => $paypal_trans["pending_reason"],
                                    'transaction_entity' => $paypal_trans["transaction_entity"],
                                    'amount' => $paypal_trans["amount"],
                                );
                            }
                        }
                    }
                }
                else {
                    $order_info["order_payments"] = false;
                }
            }

            if ($order_info["order"]["payment_code"] == "authorizenet_aim") {
                $authorizenet_aim_query = $this->db->query("SELECT comment FROM `" . DB_PREFIX . "order_history` WHERE comment<>'' and order_id = '" . (int) $order_id . "'");

                if ($authorizenet_aim_query->num_rows > 0) {
                    $order_info["order_payments"][0] = array(
                        'comment' => $authorizenet_aim_query->row["comment"],
                    );
                }
                else {
                    $order_info["order_payments"] = false;
                }
            }


            return $order_info;
        }
        else {
            return false;
        }
    }

    public function getOrderTotals($order_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int) $order_id . "' ORDER BY sort_order");

        return $query->rows;
    }

    public function getCategories($data)
    {
        //$sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR ' &gt; ') AS name, c.parent_id, c.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (c.category_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int) $this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int) $this->config->get('config_language_id') . "'";
        $sql = "SELECT cp.category_id AS category_id, cd2.name  AS name, c.parent_id, c.sort_order ";
        $sql .=" FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (c.category_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int) $this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int) $this->config->get('config_language_id') . "'";
        $sql .= " GROUP BY cp.category_id ORDER BY name";

        $sql = ("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE cd.language_id = '1' AND c2s.store_id = '" . (int) $this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");


        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProducts($currentPageIndex = null, $rowsPerPage = null, $sku = null)
    {
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        // 0 based page index............
        $data = array();
        if ($currentPageIndex !== null) {
            $data["start"] = $currentPageIndex * $rowsPerPage;
        }
        if ($rowsPerPage !== null) {
            $data["limit"] = $rowsPerPage;
        }
        //$data["filter_name"]="A";

        if ($sku !== null) {
            $data["filter_name"] = $sku;
        }

        $products = null;
        try {
            $products = $this->model_catalog_product->getProducts($data);
        } catch (Exception $ex) {
            return null;
        }

        return $products;
    }

    public function getOrderCount(
    $fromDate = null, $toDate = null, $order_id = 0
    )
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o ";
        $sql .= "LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE ";
        $sql .= " os.language_id = '" . (int) $this->config->get('config_language_id') . "'";
        //var_dump($sql);
        if (!empty($fromDate)) {
            $sql .= " AND DATE(o.date_added) >= DATE('" . $this->db->escape($fromDate) . "')";
        }
        if (!empty($toDate)) {
            $sql .= " AND DATE(o.date_added) <= DATE('" . $this->db->escape($toDate) . "')";
        }
        if (!empty($order_id)) {
            $sql .= " AND o.order_id='" . ($order_id) . "'";
        }
        //echo $sql;
        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getReturn($return_id)
    {
        $query = $this->db->query("SELECT r.return_id, r.order_id, r.firstname, r.lastname, r.email, r.telephone, r.product, r.model, r.quantity, r.opened, (SELECT rr.name FROM " . DB_PREFIX . "return_reason rr WHERE rr.return_reason_id = r.return_reason_id AND rr.language_id = '" . (int) $this->config->get('config_language_id') . "') AS reason, (SELECT ra.name FROM " . DB_PREFIX . "return_action ra WHERE ra.return_action_id = r.return_action_id AND ra.language_id = '" . (int) $this->config->get('config_language_id') . "') AS action, (SELECT rs.name FROM " . DB_PREFIX . "return_status rs WHERE rs.return_status_id = r.return_status_id AND rs.language_id = '" . (int) $this->config->get('config_language_id') . "') AS status, r.comment, r.date_ordered, r.date_added, r.date_modified FROM `" . DB_PREFIX . "return` r WHERE return_id = '" . (int) $return_id . "' ");
        return $query->row;
    }

    public function getReturns($order_id)
    {
        $sql = "";
        $sql .= "SELECT r.return_id, r.order_id, r.firstname, r.lastname, rs.name as status,r.date_added ";
        $sql .= " FROM `" . DB_PREFIX . "return` r ";
        $sql .= " LEFT JOIN " . DB_PREFIX . "return_status rs ON (r.return_status_id = rs.return_status_id) ";
        $sql .= " WHERE rs.language_id = '" . (int) $this->config->get('config_language_id') . "'";
        $sql .= " AND r.order_id = '" . $order_id . "'";
        $sql .= " ORDER BY r.return_id";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function test()
    {
        global $loader, $registry;
        $loader->model('catalog/product');
        $model = $registry->get('model_catalog_product');
        $result = $model->getProduct(123);
    }

    public function getOrderTransactionId($orderid)
    {
        $transaction_id = '';
        $query = $this->db->query("SELECT comment FROM `" . DB_PREFIX . "order_history` where order_id = '" . $orderid . "' order by order_history_id desc");
        //var_dump($query->rows);

        if ($query == null || $query->rows == null || $query->num_rows == 0) {
            return '';
        }
        $rows = $query->rows;
        foreach ($rows as $row):
            $str = $row['comment'];
            $str = str_replace("<br>", "", $str);
            if (strpos($str, "Transaction ID is") != false) {
                preg_match_all('!\d+!', $str, $matches);
                if (count($matches) > 0) {
                    //print_r($matches);
                    $transaction_id = $matches[0][0];
                    return $transaction_id;
                }
            }
            elseif (strpos($str, "TRANSACTIONID:") != false) {
                $pos = strpos($str, "TRANSACTIONID:");
                $substr = substr($str, $pos);
                $myArray = explode(':', $substr);
                $transaction_id = $myArray[1];
                if ($transaction_id != '') {
                    return $transaction_id;
                }
            }

        endforeach;
        /*
          $str = $query->row['comment'];
          if (strpos($str, "Transaction ID is") != FALSE) {
          preg_match_all('!\d+!', $str, $matches);
          if (count($matches) > 0) {
          //print_r($matches);
          $transaction_id = $matches[0][0];
          }
          }
         */
        return $transaction_id;
    }

    public function getCustomerGroupId($customer_group_name, $config_language_id)
    {
        $query = $this->db->query("SELECT customer_group_id FROM " . DB_PREFIX . "customer_group_description WHERE name = '" . $customer_group_name . "' AND language_id = '" . $config_language_id . "'");
        if ($query == null || $query->row == null || $query->row["customer_group_id"] == null) {
            return 1;
        }
        return $query->row["customer_group_id"];
    }

    public function getManufacturerId($name)
    {
        // kg,g,lb,oz
        $query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE name = '" . $name . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["manufacturer_id"];
    }

    public function getlength_class_id($unit, $lid)
    {
        // cm, mm, in
        $query = $this->db->query("SELECT length_class_id FROM " . DB_PREFIX . "length_class_description WHERE unit='" . $unit . "' AND language_id = '" . $lid . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["length_class_id"];
    }

    public function getweight_class_id($unit, $lid)
    {
        if ($unit == 'gm') {
            $unit = 'g';
        }
        $query = $this->db->query("SELECT weight_class_id FROM " . DB_PREFIX . "weight_class_description WHERE unit='" . $unit . "' AND language_id = '" . $lid . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["weight_class_id"];
    }

    public function gettax_class_id($title)
    {
        // cm, mm, in
        $query = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "tax_class WHERE title='" . $title . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["tax_class_id"];
    }

    public function getProductExist($product_id)
    {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE product_id='" . $product_id . "'");
        //var_dump($query);
        if ($query == null || $query->row == null || $query->row["product_id"] == null) {
            return false;
        }
        else {
            return true;
        }
    }

    public function addProduct($data)
    {
        $sql = "";
        //$sql .="INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int) $data['quantity'] . "', minimum = '" . (int) $data['minimum'] . "', subtract = '" . (int) $data['subtract'] . "', stock_status_id = '" . (int) $data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int) $data['manufacturer_id'] . "', shipping = '" . (int) $data['shipping'] . "', price = '" . (float) $data['price'] . "', points = '" . (int) $data['points'] . "', weight = '" . (float) $data['weight'] . "', weight_class_id = '" . (int) $data['weight_class_id'] . "', length = '" . (float) $data['length'] . "', width = '" . (float) $data['width'] . "', height = '" . (float) $data['height'] . "', length_class_id = '" . (int) $data['length_class_id'] . "', status = '" . (int) $data['status'] . "', tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "', sort_order = '" . (int) $data['sort_order'] . "', date_added = NOW()";
        $sql .="INSERT INTO " . DB_PREFIX . "product SET ";
        if (isset($data['model'])) {
            $sql .= "  model = '" . $this->db->escape($data['model']) . "'";
        }
        if (isset($data['sku'])) {
            $sql .= ", sku = '" . $this->db->escape($data['sku']) . "'";
        }
        if (isset($data['upc'])) {
            $sql .= ", upc = '" . $this->db->escape($data['upc']) . "'";
        }
        if (isset($data['ean'])) {
            $sql .= ", ean = '" . $this->db->escape($data['ean']) . "'";
        }
        if (isset($data['jan'])) {
            $sql .= ", jan = '" . $this->db->escape($data['jan']) . "'";
        }
        if (isset($data['isbn'])) {
            $sql .= ", isbn = '" . $this->db->escape($data['isbn']) . "'";
        }
        if (isset($data['mpn'])) {
            $sql .= ", mpn = '" . $this->db->escape($data['mpn']) . "'";
        }
        if (isset($data['location'])) {
            $sql .= ", location = '" . $this->db->escape($data['location']) . "'";
        }
        if (isset($data['quantity'])) {
            $sql .= ", quantity = '" . (int) $data['quantity'] . "'";
        }
        if (isset($data['minimum'])) {
            $sql .= ", minimum = '" . (int) $data['minimum'] . "'";
        }
        if (isset($data['subtract'])) {
            $sql .= ", subtract = '" . (int) $data['subtract'] . "'";
        }
        if (isset($data['stock_status_id'])) {
            $sql .= ", stock_status_id = '" . (int) $data['stock_status_id'] . "'";
        }
        if (isset($data['date_available'])) {
            $sql .= ", date_available = '" . $this->db->escape($data['date_available']) . "'";
        }
        if (isset($data['manufacturer_id'])) {
            $sql .= ", manufacturer_id = '" . (int) $data['manufacturer_id'] . "'";
        }
        if (isset($data['shipping'])) {
            $sql .= ", shipping = '" . (int) $data['shipping'] . "'";
        }
        if (isset($data['price'])) {
            $sql .= ", price = '" . (float) $data['price'] . "'";
        }
        if (isset($data['points'])) {
            $sql .= ", points = '" . (int) $data['points'] . "'";
        }
        if (isset($data['weight'])) {
            $sql .= ", weight = '" . (float) $data['weight'] . "'";
        }
        if (isset($data['weight_class_id'])) {
            $sql .= ", weight_class_id = '" . (int) $data['weight_class_id'] . "'";
        }
        if (isset($data['length'])) {
            $sql .= ", length = '" . (float) $data['length'] . "'";
        }
        if (isset($data['width'])) {
            $sql .= ", width = '" . (float) $data['width'] . "'";
        }
        if (isset($data['height'])) {
            $sql .= ", height = '" . (float) $data['height'] . "'";
        }
        if (isset($data['length_class_id'])) {
            $sql .= ", length_class_id = '" . (int) $data['length_class_id'] . "'";
        }
        if (isset($data['status'])) {
            $sql .= ", status = '" . (int) $data['status'] . "'";
        }
        if (isset($data['tax_class_id'])) {
            $sql .= ", tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "'";
        }
        if (isset($data['sort_order'])) {
            $sql .= ", sort_order = '" . (int) $data['sort_order'] . "'";
        }

        $sql .= ", date_added = NOW()";
        $this->db->query($sql);
        $product_id = $this->db->getLastId();
        return $product_id;
    }

    public function updateProduct($product_id, $data)
    {
        $sql = "";
        $sql .= "UPDATE " . DB_PREFIX . "product SET ";
        if (isset($data['model'])) {
            $sql .= "  model = '" . $this->db->escape($data['model']) . "'";
        }
        if (isset($data['sku'])) {
            $sql .= ", sku = '" . $this->db->escape($data['sku']) . "'";
        }
        if (isset($data['upc'])) {
            $sql .= ", upc = '" . $this->db->escape($data['upc']) . "'";
        }
        if (isset($data['ean'])) {
            $sql .= ", ean = '" . $this->db->escape($data['ean']) . "'";
        }
        if (isset($data['jan'])) {
            $sql .= ", jan = '" . $this->db->escape($data['jan']) . "'";
        }
        if (isset($data['isbn'])) {
            $sql .= ", isbn = '" . $this->db->escape($data['isbn']) . "'";
        }
        if (isset($data['mpn'])) {
            $sql .= ", mpn = '" . $this->db->escape($data['mpn']) . "'";
        }
        if (isset($data['location'])) {
            $sql .= ", location = '" . $this->db->escape($data['location']) . "'";
        }
        if (isset($data['quantity'])) {
            $sql .= ", quantity = '" . (int) $data['quantity'] . "'";
        }
        if (isset($data['minimum'])) {
            $sql .= ", minimum = '" . (int) $data['minimum'] . "'";
        }
        if (isset($data['subtract'])) {
            $sql .= ", subtract = '" . (int) $data['subtract'] . "'";
        }
        if (isset($data['stock_status_id'])) {
            $sql .= ", stock_status_id = '" . (int) $data['stock_status_id'] . "'";
        }
        if (isset($data['date_available'])) {
            $sql .= ", date_available = '" . $this->db->escape($data['date_available']) . "'";
        }
        if (isset($data['manufacturer_id'])) {
            $sql .= ", manufacturer_id = '" . (int) $data['manufacturer_id'] . "'";
        }
        if (isset($data['shipping'])) {
            $sql .= ", shipping = '" . (int) $data['shipping'] . "'";
        }
        if (isset($data['price'])) {
            $sql .= ", price = '" . (float) $data['price'] . "'";
        }
        if (isset($data['points'])) {
            $sql .= ", points = '" . (int) $data['points'] . "'";
        }
        if (isset($data['weight'])) {
            $sql .= ", weight = '" . (float) $data['weight'] . "'";
        }
        if (isset($data['weight_class_id'])) {
            $sql .= ", weight_class_id = '" . (int) $data['weight_class_id'] . "'";
        }
        if (isset($data['length'])) {
            $sql .= ", length = '" . (float) $data['length'] . "'";
        }
        if (isset($data['width'])) {
            $sql .= ", width = '" . (float) $data['width'] . "'";
        }
        if (isset($data['height'])) {
            $sql .= ", height = '" . (float) $data['height'] . "'";
        }
        if (isset($data['length_class_id'])) {
            $sql .= ", length_class_id = '" . (int) $data['length_class_id'] . "'";
        }
        if (isset($data['status'])) {
            $sql .= ", status = '" . (int) $data['status'] . "'";
        }
        if (isset($data['tax_class_id'])) {
            $sql .= ", tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "'";
        }
        if (isset($data['sort_order'])) {
            $sql .= ", sort_order = '" . (int) $data['sort_order'] . "'";
        }
        $sql .= ", date_modified = NOW() WHERE product_id = '" . (int) $product_id . "'";
        //echo $sql;
        $this->db->query($sql);

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape(html_entity_decode(
                                    $data['image'], ENT_QUOTES, 'UTF-8'
                    )) . "' WHERE product_id = '" . (int) $product_id . "'");
        }

        if (isset($data['product_description'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_description'] as $language_id => $value) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int) $product_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "'");
            }
        }


        if (isset($data['product_store'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_store'] as $store_id) {
                $sql = "INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "'";
                //echo $sql;
                $this->db->query($sql);
            }
        }


        if (!empty($data['product_attribute'])) {
            //$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $product_id . "', attribute_id = '" . (int) $product_attribute['attribute_id'] . "', language_id = '" . (int) $language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }



        if (isset($data['product_option'])) {
            //var_dump($data['product_option']);
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_option'] as $product_option) {
                //var_dump($product_option);
                //$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "'");
                //$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int) $product_id . "'");

                if (
                        $product_option['type'] == 'select' ||
                        $product_option['type'] == 'radio' ||
                        $product_option['type'] == 'checkbox' ||
                        $product_option['type'] == 'image') {
                    $sql = "INSERT INTO " . DB_PREFIX . "product_option";
                    $sql .= " SET product_option_id = '" . (int) $product_option['product_option_id'] . "'";
                    $sql .= ", product_id = '" . (int) $product_id . "'";
                    $sql .= ", option_id = '" . (int) $product_option['option_id'] . "'";
                    $sql .= ", required = '" . (int) $product_option['required'] . "'";
                    $this->db->query($sql);

                    $product_option_id = $this->db->getLastId();

                    if (isset($product_option['product_option_value']) && count($product_option['product_option_value']) > 0) {
                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query(
                                    "INSERT INTO " . DB_PREFIX . "product_option_value SET "
                                    . "product_option_value_id = '" . (int) $product_option_value['product_option_value_id'] . "', "
                                    . "product_option_id = '" . (int) $product_option_id . "', "
                                    . "product_id = '" . (int) $product_id . "',"
                                    . "option_id = '" . (int) $product_option['option_id'] . "',"
                                    . "option_value_id = '" . (int) $product_option_value['option_value_id'] . "',"
                                    . "quantity = '" . (int) $product_option_value['quantity'] . "',"
                                    . "subtract = '" . (int) $product_option_value['subtract'] . "', "
                                    . "price = '" . (float) $product_option_value['price'] . "',"
                                    . "price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "',"
                                    . "points = '" . (int) $product_option_value['points'] . "',"
                                    . "points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "',"
                                    . "weight = '" . (float) $product_option_value['weight'] . "', "
                                    . "weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'"
                            );
                        }
                    }
                    else {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_option_id = '" . $product_option_id . "'");
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" 
                    . (int) $product_option['product_option_id'] . "', product_id = '" 
                    . (int) $product_id . "', option_id = '" 
                    . (int) $product_option['option_id'] . "', option_value = '" 
                    . $this->db->escape($product_option['option_value']) . "', required = '" 
                    . (int) $product_option['required'] . "'");
                }
            }
        }

        if (isset($data['product_discount'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" 
                        . (int) $product_id . "', customer_group_id = '" 
                        . (int) $product_discount['customer_group_id'] . "', quantity = '" 
                        . (int) $product_discount['quantity'] . "', priority = '" 
                        . (int) $product_discount['priority'] . "', price = '" 
                        . (float) $product_discount['price'] . "', date_start = '" 
                        . $this->db->escape($product_discount['date_start']) . "', date_end = '" 
                        . $this->db->escape($product_discount['date_end']) . "'");
            }
        }


        if (isset($data['product_special'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_special'] as $product_special) {
                $this->db->query(
                    "INSERT INTO " . DB_PREFIX . "product_special SET product_id = '"
                    . (int) $product_id . "', customer_group_id = '"
                    . (int) $product_special['customer_group_id'] . "', priority = '"
                    . (int) $product_special['priority'] . "', price = '"
                    . (float) $product_special['price'] . "', date_start = '"
                    . $this->db->escape($product_special['date_start']) . "', date_end = '"
                    . $this->db->escape($product_special['date_end']) . "'");
            }
        }



        if (isset($data['product_image'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '"
                        . (int) $product_id . "', image = '"
                        . $this->db->escape(html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8'))
                        . "', sort_order = '" . (int) $product_image['sort_order'] . "'");
            }
        }


        if (isset($data['product_download'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '"
                    . (int) $product_id . "'");

            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '"
                        . (int) $product_id . "', download_id = '" . (int) $download_id . "'");
            }
        }


        if (isset($data['product_category'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_category'] as $category_id) {
                //echo $category_id;
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '"
                        . (int) $product_id . "', category_id = '" . (int) $category_id . "'");
            }
        }


        if (isset($data['product_filter'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" 
                    . (int) $product_id . "'");

            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" 
                        . (int) $product_id . "', filter_id = '" . (int) $filter_id . "'");
            }
        }

        /*
          $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "'");
          $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int) $product_id . "'");

          if (isset($data['product_related'])) {
          foreach ($data['product_related'] as $related_id) {
          $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "' AND related_id = '" . (int) $related_id . "'");
          $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $product_id . "', related_id = '" . (int) $related_id . "'");
          $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $related_id . "' AND related_id = '" . (int) $product_id . "'");
          $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $related_id . "', related_id = '" . (int) $product_id . "'");
          }
          }
         */

        if (isset($data['product_reward'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" 
                    . (int) $product_id . "'");

            foreach ($data['product_reward'] as $customer_group_id => $value) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" 
                        . (int) $product_id . "', customer_group_id = '" . (int) $customer_group_id . "', points = '" . (int) $value['points'] . "'");
            }
        }

        if (isset($data['product_layout'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout "
                    . "WHERE product_id = '" . (int) $product_id . "'");

            foreach ($data['product_layout'] as $store_id => $layout) {
                if ($layout['layout_id']) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" 
                            . (int) $product_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout['layout_id'] . "'");
                }
            }
        }


        if ($data['keyword']) {
            try{
                $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int) $product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" 
                    . (int) $product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
            } catch (Exception $ex) {

            }
            
        }


        if (isset($data['product_profiles'])) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "product_profile` WHERE product_id = " . (int) $product_id);
            foreach ($data['product_profiles'] as $profile) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "product_profile` SET `product_id` = " . (int) $product_id . ", customer_group_id = " . (int) $profile['customer_group_id'] . ", `profile_id` = " . (int) $profile['profile_id']);
            }
        }

        $this->cache->delete('product');

        return $product_id;
    }

    public function update_extension_extra_tab_in_products(
    $product_id, $language_id, $valueArray
    )
    {
        $sql = "UPDATE " . DB_PREFIX . "product_description SET ";
        $sql .= " extra_tab_title = '" . $this->db->escape($valueArray['extra_tab_title']) . "',";
        $sql .= " extra_tab_text = '" . $this->db->escape($valueArray['extra_tab_text']) . "'";
        $sql .= " WHERE product_id = '" . (int) $product_id . "' and language_id = '" . (int) $language_id . "'";

        $this->db->query($sql);
    }

    public function updateInventory($product_id, $data)
    {
        $sql = "";
        $sql .= "UPDATE " . DB_PREFIX . "product SET ";

        if (isset($data['qty'])) {
            $sql .= " quantity = '" . (int) $data['qty'] . "'";
        }
        else {
            $sql .= " quantity = quantity";
        }

        if (isset($data['price'])) {
            $sql .= ", price = '" . (float) $data['price'] . "'";
        }

        $sql .= ", date_modified = NOW() WHERE product_id = '" . $product_id . "'";
        //echo $sql;
        $this->db->query($sql);
        $this->cache->delete('product');

        return $product_id;
    }

    public function getProductIdBySku($sku)
    {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku='" . $sku . "'");
        //var_dump($query);
        if ($query == null || $query->row == null || empty($query->row["product_id"])) {
            return 0;
        }
        else {
            return $query->row["product_id"];
        }
    }

    public function getproduct_option_id($name, $lid)
    {
        // cm, mm, in
        $query = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_description WHERE name='" . $name . "' AND language_id = '" . $lid . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["option_id"];
    }

    public function create_product_option_id($poXml)
    {
        $language_id = (int) $this->config->get('config_language_id');
        $option_name = (string) $poXml->option_name;

        $this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET type = '"
                . $this->db->escape((string) $poXml->type) . "', sort_order = '0'");
        $option_id = $this->db->getLastId();

        $this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '"
                . (int) $option_id . "', language_id = '" . $language_id
                . "', name = '" . $this->db->escape($option_name) . "'");

        $sort_order = 0;
        if (isset($poXml->product_option_value)):
            foreach ($poXml->product_option_value as $povXml):
                // if image is sent, add to query
                //$image_data = html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8');
                $image_data = '';
                $this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '"
                        . (int) $option_id . "',  sort_order = '" . $sort_order . "'");

                $option_value_id = $this->db->getLastId();
                //foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '"
                        . (int) $option_value_id . "', language_id = '" . (int) $language_id
                        . "', option_id = '" . (int) $option_id . "', name = '"
                        . $this->db->escape((string) $povXml->option_value_name) . "'");
                //}
                $sort_order++;
            endforeach;
        endif;

        return $option_id;
    }

    public function getproduct_option_value_id($name, $product_option_id, $lid)
    {
        // cm, mm, in
        $query = $this->db->query("SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE name='" . $name . "' AND option_id='" . $product_option_id . "' AND language_id = '" . $lid . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["option_value_id"];
    }

    public function getproduct_option_value_row(
    $product_id, $option_id, $option_value_id
    )
    {
        // cm, mm, in
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value WHERE "
                . "option_value_id='" . $option_value_id . "' "
                . "AND option_id='" . $option_id . "' "
                . "AND product_id = '" . $product_id . "' ");
        if ($query == null || $query->row == null) {
            return null;
        }
        return $query->row;
    }

    public function create_product_option_value_id($name, $option_id)
    {
        $language_id = (int) $this->config->get('config_language_id');

        $this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '"
                . (int) $option_id . "',  sort_order = '0'");
        $option_value_id = $this->db->getLastId();

        $this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '"
                . (int) $option_value_id . "', language_id = '"
                . $language_id . "', option_id = '" . (int) $option_id . "', name = '"
                . $this->db->escape($name) . "'");
        return $option_value_id;
    }

    public function getstock_status_id($name, $lid)
    {
        // cm, mm, in
        $query = $this->db->query("SELECT stock_status_id FROM " . DB_PREFIX . "stock_status WHERE name='" . $name . "' AND language_id = '" . $lid . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["stock_status_id"];
    }

    public function getattribute_id($name, $lid)
    {
        // cm, mm, in
        $query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name='" . $name . "' AND language_id = '" . $lid . "'");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["attribute_id"];
    }

    public function updateOrderStatus($order_id, $order_status_id)
    {
        $sql = "";
        $sql .= "UPDATE " . DB_PREFIX . "order SET order_status_id=" . $order_status_id . " ";
        $sql .= "WHERE order_id = '" . $order_id . "'";
        //echo $sql;
        $this->db->query($sql);
        $this->cache->delete('order');

        return true;
    }

    public function getOrderStatuses()
    {
        //global $loader;
        //$loader->model('sale/order');
        //$loader->model('localisation/order_status');
        //$model = $this->registry->get('model_localisation_order_status');
        //return $model->getOrderStatuses();
        $query = $this->db->query("SELECT order_status_id, name FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int) $this->config->get('config_language_id') . "' ORDER BY name");
        $order_status_data = $query->rows;
        return $order_status_data;
    }

    //----------------------- HELPER METHODS -------------------------------//
    //=====================================================================//
    public function printLoginResultXml($qryStrArray)
    {
        //var_dump($qryStrArray);
        $msg = "<?xml version=\"1.0\"?><response><status>" . $qryStrArray["status"] . "</status>" .
                "<message>" . $qryStrArray["message"] . "</message></response>";
        $this->response->setOutput($msg);
    }

    public function createJobTable()
    {
        try {
            $this->db->query('DROP TABLE job');
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }


        $q = 'CREATE TABLE job (
                    id BIGINT NOT NULL  AUTO_INCREMENT,
                    submittedon DATETIME NOT NULL ,
                    processedon DATETIME NULL ,
                    localfilename VARCHAR( 100 ) NOT NULL ,
                    status TINYINT NOT NULL ,
                    logfilename VARCHAR( 100 ) NOT NULL ,
                    module varchar(50) NULL,
                    local_file_source TEXT NULL,
                    log_file_source TEXT NULL,
                    
            PRIMARY KEY (  id )
	)';

        try {
            $this->db->query($q);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    public function DeleteData()
    {
        $query = "delete from job where submittedon < CurDate() - 7";
        $this->db->query($query);
    }

    public function updateJobLogFileSource($id, $log_file_source)
    {
        $query = "UPDATE job SET log_file_source='" . $this->db->escape($log_file_source) . "' WHERE id ='" . $id . "'";

        try {
            $this->db->query($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobStatus($id, $status)
    {
        $query = "UPDATE job SET status=" . $status . " WHERE id =" . $id;
        try {
            $this->db->query($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobSubmitted($id)
    {
        $query = "UPDATE job SET status=1 WHERE id =" . $id;
        try {
            $this->db->query($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobCompleted($id)
    {
        $query = "UPDATE job SET processedon=NOW(), status=2 WHERE id = '$id'";
        try {
            $this->db->query($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function updateJobErrored($id)
    {
        $query = "UPDATE job SET processedon=NOW(), status=-1 WHERE id = '$id'";
        try {
            $this->db->query($query);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function insertJob($submittedon, $processedon, $status, $module, $local_file_source, $log_file_source)
    {
        // $this->db->escape
        $query = "INSERT INTO job (submittedon, processedon, status, module, local_file_source, log_file_source) ";
        $query .= " values (";
        $query .= "'" . $submittedon . "', ";
        $query .= "" . $processedon == null ? "NULL," : "'" . $processedon . "'," . ", ";
        $query .= "'" . $status . "', ";
        $query .= "'" . $module . "', ";
        $query .= "'" . $this->db->escape($local_file_source) . "', ";
        $query .= "'" . $this->db->escape($log_file_source) . "' ";
        $query .= ")";


        //echo $query;
        try {
            $this->db->query($query);
            //$sql = "SELECT MAX( id ) FROM  job ";
            $lastId = $this->db->getLastId();
            return $lastId;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return -1;
    }

    public function getOneSubmittedJob($module)
    {
        if ($module == null) {
            $jobRow = $this->db->query("SELECT * FROM job WHERE status in (0,1) ORDER BY submittedon ASC LIMIT 0,1");
        }
        elseif ($module == '') {
            $jobRow = $this->db->query("SELECT * FROM job WHERE status in (0,1) and module in('', 'invprice') ORDER BY submittedon ASC LIMIT 0,1");
        }
        else {
            $jobRow = $this->db->query("SELECT * FROM job WHERE status in (0,1) and module='" . $module . "' ORDER BY submittedon ASC LIMIT 0,1");
        }
        return $jobRow->row;
    }

    public function getJob($id)
    {
        $jobRow = $this->db->query("SELECT * FROM  job WHERE id=" . $id . "");

        if ($jobRow == null) {
            return null;
        }
        return $jobRow->row;
    }

    public function getJobs($count)
    {
        $jobRow = $this->db->query("SELECT * FROM  job order by id desc LIMIT 0," . $count . "");
        return $jobRow->row;
    }

    public function printJobXmlError($err)
    {
        $xml_output = "<?xml version=\"1.0\"?>";
        $xml_output .= "<jobresult><id>0</id><status>error</status><submittedon/>"
                . "<processedon/><localfilename/><logfilename/><products/><errors><error>" . $err . "</error></errors></jobresult>";
        echo $xml_output;
    }

    public function printXml($job_row)
    {
        $xml_output = "";
        $error_output = "<errors>";
        $xml_output .= "<?xml version=\"1.0\"?>\n";

        if ($job_row != null) {
            $xml_output .= "<jobresult>\n";

            $status = "";
            if ($job_row['status'] == -1) {
                $status = "error";
            }
            elseif ($job_row['status'] == 0) {
                $status = "submitted";
            }
            elseif ($job_row['status'] == 1) {
                $status = "processing";
            }
            elseif ($job_row['status'] == 2) {
                $status = "completed";
            }

            $xml_output .= "\t<id>" . $job_row['id'] . "</id>\n";
            $xml_output .= "\t<status>" . $status . "</status>\n";
            $xml_output .= "\t<submittedon>" . $job_row['submittedon'] . "</submittedon>\n";
            $xml_output .= "\t<processedon>" . $job_row['processedon'] . "</processedon>\n";
            $xml_output .= "\t<localfilename>" . $job_row['localfilename'] . "</localfilename>\n";
            $xml_output .= "\t<logfilename>" . $job_row['logfilename'] . "</logfilename>\n";
            $xml_output .= "\t<module>" . $job_row['module'] . "</module>\n";
            //$xml_output .= "<errors>\n";
            //$xmllog = new xmllog(dirname(__FILE__) ."\" . $job_row['logfilename']);
            $logfilepath = str_replace("\\", "/", dirname(__FILE__)) . '/' . $job_row['logfilename'];

            $dom = new DOMDocument();

            try {
                if (file_exists($logfilepath) == false) {
                    throw new Exception("LOG XML: '$logfilepath' does not exist");
                }

                $dom->load($logfilepath);
                if ($dom) {
                    $node = $dom->getElementsByTagName('products')->item(0);
                    if ($node) {
                        $nodeXml = $dom->saveXML($node);
                        $xml_output .="\n\t" . $nodeXml . "\n";
                    }
                    else {
                        $xml_output .= "<products></products>";
                    }
                }
                else {
                    $error_output .= "<error>$logfilepath could notbe loaded into DOM</error>";
                }
            } catch (Exception $ex) {
                $error_output .= "<error>" . htmlentities($ex->getMessage()) . "</error>";
            }

            //$xmllog=null;

            $error_output .= "</errors>\n";
            $xml_output .= $error_output;
            $xml_output .= "</jobresult>";
        }
        else {
            $xml_output .= "<jobresult><id>0</id><status></status><submittedon/><processedon/><localfilename/><logfilename/><products/><errors></errors></jobresult>";
        }

        echo $xml_output;
    }

    public function getLogFolderPath()
    {
        //global $xcart_dir;

        $_filePath = DIR_LOGS;

        if (!is_dir($_filePath)) {
            mkdir($_filePath);
        }

        if (substr($_filePath, -1) != "/") {
            $_filePath = $_filePath . "/";
        }

        //if (file_exists($_filePath . ".htaccess") == false) {
        //    $fp = fopen($_filePath . ".htaccess", "w");
        //    fwrite($fp, "Allow from all");
        //    fclose($fp);
        //}
        $_filePath = str_replace("\\", "/", $_filePath);
        return $_filePath;
    }

    public function getJobXml2($job_row)
    {
        $xml_output = "<?xml version=\"1.0\"?>";
        $error_output = '';

        //var_dump($jobresult);
        //var_dump($job);
        $id = 0;
        $status = '';
        $submittedon = '';
        $processedon = '';

        $module = '';
        $local_file_source = '';
        $log_file_source = '';

        if ($job_row && $job_row["id"] != null) {
            if ($job_row['status'] == -1) {
                $status = "error";
            }
            elseif ($job_row['status'] == 0) {
                $status = "submitted";
            }
            elseif ($job_row['status'] == 1) {
                $status = "processing";
            }
            elseif ($job_row['status'] == 2) {
                $status = "completed";
            }

            $id = $job_row['id'];
            $submittedon = $job_row['submittedon'];
            $processedon = $job_row['processedon'];
            $localfilename = $job_row['localfilename'];
            $logfilename = $job_row['logfilename'];
            $module = $job_row['module'];
            $local_file_source = $job_row["local_file_source"];
            $log_file_source = $job_row["log_file_source"];

            //$logfilepath = str_replace( "\\", "/", $this->getLogFolderPath() ) . $job_row['logfilename'];
            $localfilename = HTTP_SERVER . 'index.php?route=scoc/job/localfilename&id=' . $id;
            $logfilename = HTTP_SERVER . 'index.php?route=scoc/job/logfilename&id=' . $id;

            $xml_output .= "<jobresult>\n";
            $xml_output .= $this->getVersionXml();
            $xml_output .= "<id>" . $id . "</id>\n";
            $xml_output .= "<status>" . $status . "</status>\n";
            $xml_output .= "<submittedon>" . $submittedon . "</submittedon>\n";
            $xml_output .= "<processedon>" . $processedon . "</processedon>\n";
            $xml_output .= "<localfilename><![CDATA[" . $localfilename . "]]></localfilename>\n";
            $xml_output .= "<logfilename><![CDATA[" . $logfilename . "]]></logfilename>\n";
            $xml_output .= "<module>" . $module . "</module>\n";

            $dom = new DOMDocument();

            if ($job_row["status"] != 0) {
                //var_dump($log_file_source);
                if (empty($log_file_source)) {
                    throw new Exception("LOG XML does not exist");
                }
            }

            //var_dump($log_file_source);
            $dom->loadXML($log_file_source);

            if ($dom) {
                $node = $dom->getElementsByTagName('products')->item(0);
                //var_dump($node);
                if ($node) {
                    $nodeXml = $dom->saveXML($node);

                    $xml_output .="\n\t" . $nodeXml . "\n";
                }
                else {
                    $xml_output .= "<products></products>";
                }
            }
            else {
                $error_output .= "<error>Log Xml could not be loaded into DOM</error>";
            }



            $xml_output .= $error_output;
            $xml_output .= "</jobresult>";
        }
        else {
            $xml_output .= "<jobresult>";
            $xml_output .= $this->getVersionXml();
            $xml_output .= "<id>0</id><status></status><submittedon/><processedon/><localfilename/>"
                    . "<logfilename/><products/><errors></errors></jobresult>";
        }

        $this->response->setOutput($xml_output);
    }

    public function saveImageFromUrl($imageUrl)
    {
        $info = pathinfo($imageUrl);
        $extension = $info['extension'];

        $folderOption = 1;
        //var_dump($info);
        if (file_exists(str_replace("\\", "/", DIR_IMAGE . 'data/')) == true):
            $importDir = str_replace("\\", "/", DIR_IMAGE . 'data/');
            $folderOption = 1;
        else:
            $importDir = str_replace("\\", "/", DIR_IMAGE . '');
            $folderOption = 2;
        endif;

        if ($info['basename'] != null) {
            $newFileName = $info['basename'];
        }
        else {
            $newFileName = uniqid(rand(), true) . "." . $extension;
        }

        $ch = curl_init($imageUrl);
        try {
            $fp = fopen($importDir . $newFileName, "w");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

            $rawdata = curl_exec($ch);
            curl_close($ch);


            fwrite($fp, $rawdata);
            fclose($fp);
        } catch (Exception $x) {
            
        }
        //echo $rawdata;
        if ($folderOption == 1):
            return "data/" . $newFileName;
        else:
            return $newFileName;
        endif;
    }

    // V 2.0
    public function getJobLogFileSource($id)
    {
        $query = $this->db->query("SELECT log_file_source FROM `job` WHERE id=" . $id . "");
        //var_dump($query);exit;

        if ($query == null || $query->row == null) {
            return "";
        }
        return $query->row["log_file_source"];
    }

    public function getJobFileSource($id)
    {
        $query = $this->db->query("SELECT local_file_source FROM `job` WHERE id=" . $id . "");
        if ($query == null || $query->row == null) {
            return 0;
        }
        return $query->row["local_file_source"];
    }

}

class SimpleXMLExtended extends SimpleXMLElement {

    public function addCData($cdata_text)
    {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }

}
