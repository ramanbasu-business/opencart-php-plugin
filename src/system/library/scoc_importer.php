<?php

/* * **********************************************************
 *
 * *********************************************************** */

//require_once "KLogger.php";
/* * **********************************************************************
  class to handle product import from given XML file stored in Web server.
 * **********************************************************************
 * global $var_dirs;
 * $var_dirs['log']
 */

class scoc_importer
{
    private $verbose = 0;
    private $xmllog;
    private $log;
    private $globalHelper;

    public function __construct($scoclib, $log, $jobid, $_verbose)
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }
        $this->globalHelper = $scoclib;
        $this->verbose = $_verbose;
        $this->log = $log;
        $this->xmllog = new scoc_xmllog($scoclib, $jobid, $_verbose);
        //var_dump($logfilename);
    }

    public function __destruct()
    {
        unset($this->xmllog);
    }

    public function getModule($feed_xml)
    {
        if (isset($feed_xml['module'])) {
            return $feed_xml['module'];
        }
        return '';

        // foreach ($feed_xml->xpath("/products/product") as $child)
        // {
        // $nodes = $child->children();
        // $nodeCount = count($nodes);
        // echo $nodeCount;
        // $cnt_product++;
        // }
    }

    private function _getLangs()
    {
        global $all_languages;
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }
        $all_languages = func_data_cache_get('languages', array($shop_language));

        if (empty($all_languages)) {
            $def_language = @$current_area == 'C' ? $config['default_customer_language'] : $config['default_admin_language'];

            $all_languages = func_data_cache_get('languages', array($def_language));

            if (
                    empty($all_languages) && !empty($e_langs) && is_array($e_langs)
            ) {
                $all_languages = func_data_cache_get('languages', array(key($e_langs)));

                reset($e_langs);
            }
        }
        return true;
    }

    public function startMini($feed_xml, $lid)
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
            //echo $key;
        }

        $this->_print("---------------------------------------");
        $this->_print("-------- import process starts --------");

        foreach ($feed_xml->product as $productXML) {
            $sku = (string) $productXML->sku;
            $product_id = $this->globalHelper->getProductIdBySku($sku);
            if ($product_id === 0) {
                $this->xmllog->log($sku, "error", 'Product id for SKU: ' . $sku . ' not found');
                $this->_print('SKU: ' . $sku . ' Product id not found');
                continue;
            }
            try {
                $data = array();
                if (isset($productXML->qty)) {
                    $data['qty'] = (int) $productXML->qty;
                }
                if (isset($productXML->price)) {
                    $data['price'] = (float) $productXML->price;
                }

                $this->globalHelper->updateInventory($product_id, $data);

                $this->_print("SKU: " . $sku . " ProductId:" . $product_id . " updated.");
                $this->xmllog->log($sku, "success", "");

                unset($data);
            } catch (Exception $ex) {
                $this->xmllog->log($sku, "error", $ex->getMessage());
                continue;
            }
        }

        $this->_print("-------- import process ends --------");
    }

    public function startImport($feed_xml, $lid)
    {
        foreach ($GLOBALS as $key => $val) {
            global $$key;
            //echo $key;
        }

        $this->_print("---------------------------------------");
        $this->_print("-------- import process starts --------");

        foreach ($feed_xml->product as $productXML) {
            if (isset($product)) {
                unset($product);
            }

            if (isset($arrCategoryIds)) {
                unset($arrCategoryIds);
            }
            $sku = (string) $productXML->sku;
            $this->_print("======== importing SKU '$sku' starts ========");

            $product_id = $this->globalHelper->getProductIdBySku($sku);
            $is_new = ($product_id == 0 ? true : false);
                
            foreach ($productXML->children() as $a => $b) {
                if ($a == 'product_stores' || $a == 'categories' || $a == 'attributes' || $a == 'product_images' || $a == 'attributes' || $a == 'discounts' || $a == 'specials') {
                } else {
                    $product[$a] = (string) $b;
                }
            } // @End

            if (isset($product['price'])) {
                $product['price'] = abs(doubleval($product['price']));
            }


            $product["config_language_id"] = $lid;
            $product['product_description'][$lid] = array();
            $product['product_description'][$lid]['name'] = (string) $productXML->name;
            $product['product_description'][$lid]['description'] = (string) $productXML->description;
            $product['product_description'][$lid]['meta_description'] = (string) $productXML->meta_description;
            $product['product_description'][$lid]['meta_keyword'] = (string) $productXML->meta_keyword;
            $product['product_description'][$lid]['tag'] = (string) $productXML->tag;

            $product["tax_class_id"] = isset($productXML->tax_class) ? $this->globalHelper->gettax_class_id((string) $productXML->tax_class) : $this->globalHelper->gettax_class_id("Default");
            $product["length_class_id"] = isset($productXML->length_class) ? $this->globalHelper->getlength_class_id((string) $productXML->length_class, $lid) : $this->globalHelper->getlength_class_id("mm", $lid);
            $product["height_class_id"] = (int) $product["length_class_id"];
            $product["weight_class_id"] = isset($productXML->weight_class) ? $this->globalHelper->getweight_class_id((string) $productXML->weight_class, $lid) : $this->globalHelper->getlength_class_id("gm", $lid);
            $product["manufacturer_id"] = isset($productXML->manufacturer) ? $this->globalHelper->getManufacturerId((string) $productXML->manufacturer) : null;
            $product["stock_status_id"] = isset($productXML->stock_status) ? $this->globalHelper->getstock_status_id((string) $productXML->stock_status, $lid) : $this->globalHelper->getstock_status_id('In Stock', $lid);

            if (isset($productXML->image)) {
                try {
                    $url = (string) $productXML->image;
                    if ($url != ''):
                        $this->_print($sku . ' primary image ' . $url);
                    $product["image"] = $this->globalHelper->saveImageFromUrl($url);
                    endif;
                } catch (Exception $exx) {
                    $this->_print($sku . ' image error ' + $exx->getMessage());
                }
            } //@ primary image
            //
            // Import categories
            if (isset($productXML->categories)) {
                foreach ($productXML->categories->categoryid as $p_categoryID) {
                    $product["product_category"][(int) $p_categoryID] = (int) $p_categoryID;
                }
                //var_dump($product["product_category"]);
            }

            if (isset($productXML->product_stores)) {
                foreach ($productXML->product_stores->product_store as $p_storeID) {
                    $product["product_store"][(int) $p_storeID] = (int) $p_storeID;
                }
                //var_dump($product["product_store"]);
            }

            if (isset($productXML->attributes)) {
                $product['product_attribute'] = array();

                foreach ($productXML->attributes->attribute as $attribute) {
                    $field = (string) $attribute->field;
                    $value = (string) $attribute->value;
                    //var_dump( $field);
                    $pa = array();
                    $pa['name'] = $field;
                    $pa['attribute_id'] = $this->globalHelper->getattribute_id($field, $lid);
                    //$product["store"][]  = (int) $p_storeID;

                    $pa['product_attribute_description'][$lid] = array(
                        'text' => $value
                    );

                    $product['product_attribute'][] = $pa;
                }
            }

            if (isset($productXML->discounts)) {
                $product['discounts'] = array();

                foreach ($productXML->discounts->discount as $attribute) {
                    $customer_group = (string) $attribute->customer_group;
                    $quantity = doubleval($attribute->quantity);

                    //var_dump( $field);
                    $pa = array();
                    $pa['customer_group_id'] = $this->globalHelper->getCustomerGroupId($customer_group, $lid);
                    $pa['quantity'] = $quantity;
                    $pa['priority'] = (int) $attribute->priority;
                    $pa['price'] = doubleval($attribute->price);
                    $pa['date_start'] = (string) $attribute->date_start;
                    $pa['date_end'] = (string) $attribute->date_end;
                    //$product["store"][]  = (int) $p_storeID;

                    $product['product_discount'][] = $pa;
                    unset($pa);
                }
            }

            if (isset($productXML->specials)) {
                $product['product_special'] = array();

                foreach ($productXML->specials->special as $s) {
                    $customer_group = (string) $s->customer_group;
                    $quantity = doubleval($s->quantity);

                    $pa = array();
                    $pa['customer_group_id'] = $this->globalHelper->getCustomerGroupId($customer_group, $lid);
                    $pa['priority'] = (int) $s->priority;
                    $pa['price'] = doubleval($s->price);
                    $pa['date_start'] = (string) $s->date_start;
                    $pa['date_end'] = (string) $s->date_end;

                    if ($this->verbose==1) {
                        $this->_print('Customer Group ID: ' . $pa['customer_group_id']);
                    }
                    $product['product_special'][] = $pa;
                    unset($pa);
                }
            }

            if (isset($productXML->product_images)) {
                $product['product_images'] = array();

                foreach ($productXML->product_images->product_image as $img) {
                    $url = (string) $img->image;
                    if ($url != '') :
                        $this->_print($sku . ' secondary image ' . $url);

                        $pa = array();
                        $pa["image"] = $this->globalHelper->saveImageFromUrl($url);
                        $pa['sort_order'] = (int) $img->sort_order;

                        $product['product_image'][] = $pa;
                        unset($pa);
                    endif;
                }
            }

            if (isset($productXML->product_rewards)) {
                $product['product_reward'] = array();

                foreach ($productXML->product_rewards->product_reward as $r) {
                    $customer_group = (string) $r->customer_group;
                    $customer_group_id = $this->globalHelper->getCustomerGroupId($customer_group, $lid);
                    $points = (int) $r->points;

                    $product['product_reward'][$customer_group_id] = array();
                    $product['product_reward'][$customer_group_id]['points'] = $points;
                }
            }

            if (isset($productXML->product_options)) {
                $product['product_option'] = array();
                $this->_print("Updating product options");
                foreach ($productXML->product_options->product_option as $poXml) {
                    $option_name = (string) $poXml->option_name;
                    // Color / 13
                    $O_Id = $this->globalHelper->getproduct_option_id($option_name, $lid);
                    if (!$O_Id || $O_Id==null || $O_Id==0):
                        $this->_print("Option " . $option_name . " missing. Creating product option/values");
                    $O_Id = $this->globalHelper->create_product_option_id($poXml);
                    $this->_print("New Option ID " . $O_Id);
                    endif;
                    
                    $po = array();
                    $po["product_option_id"] = '0';
                    $po["type"] = (string) $poXml->type;
                    $po["option_id"] = $O_Id;
                    $po["required"] = (int) $poXml->required;

                    //if (isset($poXml->option_value))
                    //    $po["option_value"] = (string) $poXml->option_value;

                    if (isset($poXml->product_option_value)) {
                        $po["product_option_value"] = array();

                        foreach ($poXml->product_option_value as $povXml) {
                            $option_value_name = (string) $povXml->option_value_name;
                            $option_val_id = $this->globalHelper->getproduct_option_value_id($option_value_name, $O_Id, $lid);
                            if (!$option_val_id || $option_val_id==null || $option_val_id==0):
                                // create missing option value
                                $option_val_id = $this->globalHelper->create_product_option_value_id($option_value_name, $O_Id);
                            endif;
                            
                            $pov = array();
                            $pov["product_option_value_id"] = '0';
                            $pov["option_value_id"] = $option_val_id;
                            
                            // Assign from XML
                            $pov["quantity"] =(int) $povXml->quantity;
                            $pov["subtract"] = (int) $povXml->subtract; // 1 or 0
                            $pov["price"] = doubleval((string) $povXml->price);
                            $pov["price_prefix"] = (string) $povXml->price_prefix;
                            $pov["points"] = (int) $povXml->points;
                            $pov["points_prefix"] = (string) $povXml->points_prefix;
                            $pov["weight"] = doubleval((string) $povXml->weight);
                            $pov["weight_prefix"] = (string) $povXml->weight_prefix;
                            
                            $option_value_row = null;
                            // For existing product, get existing qty, price for option value
                            if ($is_new==false && $product_id >0) :
                                $option_value_row = $this->globalHelper->getproduct_option_value_row($product_id, $O_Id, $option_val_id);
                                //var_dump($option_value_row);
                                if ($option_value_row!=null) :
                                    // Overwrite values from existing product
                                    $pov["quantity"] = $option_value_row["quantity"];
                                    $pov["subtract"] = $option_value_row["subtract"]; // 1 or 0
                                    $pov["price"] = $option_value_row["price"];
                                    $pov["price_prefix"] = $option_value_row["price_prefix"];
                                    $pov["points"] = $option_value_row["points"];
                                    $pov["points_prefix"] = $option_value_row["points_prefix"];
                                    $pov["weight"] = $option_value_row["weight"];
                                    $pov["weight_prefix"] = $option_value_row["weight_prefix"];
                                endif;
                            endif;
                            
                            

                            $po["product_option_value"][] = $pov;
                        }
                    }
                    $product['product_option'][] = $po;
                }
                //var_dump($product['product_option']);
            }

            //var_dump($product); return;
            // Direct import
            if (!empty($product)) {
                //$product_id = $this->globalHelper->getProductIdBySku($sku);
                //$is_new = ($product_id == 0 ? TRUE : FALSE );

                if ($is_new) {
                    $this->_print($sku . ' adding new product.');

                    $product_id = $this->globalHelper->addProduct($product);
                    $this->_print("New product id '$product_id'");
                    $product_id = $this->globalHelper->updateProduct($product_id, $product);
                    $this->_print($sku . " added");
                } else {
                    $this->_print($sku . ' updating product.');
                    //func_array2update('products', $data, "productid = '$_productid'");

                    $product_id = $this->globalHelper->updateProduct($product_id, $product);

                    $this->_print($sku . " updated.");
                    $this->_print("Existing product id '$sku'");
                }

                // update data for extra_tab_in_products
                foreach ($productXML->extension as $extnXml) {
                    //var_dump((string)$extnXml['name']);
                    if (isset($extnXml) &&
                            ((string) $extnXml['name'] == 'custom_product_tab' || (string) $extnXml['name'] == 'extra_tab_in_products')) {
                        // actually "extra_tab_in_products"
                        $valueArray = array();
                        $valueArray['extra_tab_title'] = (string) $extnXml['field'];
                        $valueArray['extra_tab_text'] = (string) $extnXml;
                        $this->globalHelper->update_extension_extra_tab_in_products($product_id, 1, $valueArray);
                        unset($valueArray);
                    }
                }

                //echo $config['default_admin_language'];
                //var_dump($all_languages);
                // If image was posted
                /*
                  if (isset($productXML->image)) {
                  try {
                  $this->_print($_productcode . ' saving primary image ' . $product['image']);
                  //func_import_save_image_data('P', $_productid, $product['image']);
                  $this->saveImageFromUrl($_productid, $product['image'], 'P', $generateThumbnail);
                  } catch (Exception $exx) {
                  $this->_print($_productcode . ' image error ' + $exx->getMessage());
                  }
                  } //@ primary image
                 */
            } //@ End of data[] !empty

            if (empty($sku)) {
                $this->_print($sku . ' productid not found.');
                $this->xmllog->log($sku, "error", "productid not found");
                continue;
            }


            //******************** @@ Productwise PROCESS IS COMPLETE ********************

            $this->xmllog->log($sku, "success", "");
        } //@ Each product in Xml

        $this->_print("-------- import process ends --------");
    }

    public function importOrderCancel($feed_xml)
    {
        global $loader, $registry;
        //$loader->model('localisation/order_status');
        //$model_localisation_order_status = $registry->get('model_localisation_order_status');
                
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }

        $this->_print("---------------------------------------");
        $this->_print("-------- order cancel import process starts --------");
        
        $order_increment_id = trim((string) $feed_xml->order_increment_id);
                
        try {
            $order_info = $this->globalHelper->getOrder($order_increment_id);
            if ($order_info==null) :
                $this->xmllog->log($order_increment_id, "error", 'Order id : ' . $order_increment_id . ' not found');
                $this->_print('Order: ' . $order_increment_id . ' order id not found');
            endif;
            
            //$this->load->model('localisation/order_status');
            //$order_statuses = $this->globalHelper->getOrderStatuses();
            //var_dump($order_statuses);
            
            if ($this->globalHelper->updateOrderStatus($order_increment_id, 7)) :
                $this->xmllog->log($order_increment_id, "success", "");
            else :
                $this->xmllog->log($order_increment_id, "error", "Order could not be updated");
            endif;
        } catch (Exception $ex) {
            $this->xmllog->log($order_increment_id, "error", $ex->getMessage());
        }
    }
    
    public function importOrderRefund($feed_xml)
    {
        global $loader, $registry;
        //$loader->model('localisation/order_status');
        //$model_localisation_order_status = $registry->get('model_localisation_order_status');
                
        foreach ($GLOBALS as $key => $val) {
            global $$key;
        }

        $this->_print("---------------------------------------");
        $this->_print("-------- order refund import process starts --------");
        
        $order_increment_id = trim((string) $feed_xml->order_increment_id);
                
        try {
            $order_info = $this->globalHelper->getOrder($order_increment_id);
            if ($order_info==null) :
                $this->xmllog->log($order_increment_id, "error", 'Order id : ' . $order_increment_id . ' not found');
                $this->_print('Order: ' . $order_increment_id . ' order id not found');
            endif;
            
            //$this->load->model('localisation/order_status');
            //$order_statuses = $this->globalHelper->getOrderStatuses();
            //var_dump($order_statuses);
            
            if ($this->globalHelper->updateOrderStatus($order_increment_id, 11)) :
                $this->xmllog->log($order_increment_id, "success", "");
            else :
                $this->xmllog->log($order_increment_id, "error", "Order could not be updated");
            endif;
        } catch (Exception $ex) {
            $this->xmllog->log($order_increment_id, "error", $ex->getMessage());
        }
    }
    
    private function _print($s)
    {
        if ($this->verbose == 1) {
            echo '<li style="color:darkgreen;font-family:serif,tahoma,verdana,arial;font-size:0.85em">' . $s . '</li>';
        }
        $this->log->write($s);
    }

    private function _printError($s)
    {
        if ($this->verbose == 1) {
            echo '<li style="color:red;font-family:serif,tahoma,verdana,arial;font-size:0.85em">' . $s . '</li>';
        }
        $this->log->write($s);
    }

    protected function userCSVDataAsArray($data)
    {
        //return explode( ',', str_replace( " ", "", $data ) );
        return explode(',', trim($data));
    }
}
