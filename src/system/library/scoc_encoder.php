<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of scoc_encoder
 *
 * @author raman
 */
class scoc_encoder
{
    public $enabled = true;
    public $encryption = 0;
    public $key;
    private $tripleDes;
    private $scoc_lib;
    private $verbose = 0;

    public function __construct($registry)
    {
        $this->registry = $registry;

        require_once DIR_SYSTEM . "library/scoc_TripleDES.php";

        $this->tripleDes = new scoc_TripleDES();
        $this->scoc_lib=new scoc_lib($this->registry);
        
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] != "") :
            $this->key = $_SERVER['HTTP_ORIGIN'];
        endif;
    }

    public function __get($name)
    {
        return $this->registry->get($name);
    }

    public function authenticate($enabled = true, $encryption = false)
    {
        /*         * *******************************************************
         *  Query string validation
         * ******************************************************* */
        
        $this->encryption = 0;
        $qryStrArray = array();
        $q = $_SERVER["QUERY_STRING"];
        
        parse_str($q, $qryStrArray);
        //var_dump($qryStrArray);
        
        if (count($qryStrArray)==1) :
            reset($qryStrArray);
            $first_key = key($qryStrArray);
            if ($first_key !== "" && $qryStrArray[$first_key]=="") :
                $this->encryption = 1;
            endif;
        endif;
        
        //var_dump($this->encryption);
        $this->enabled = $enabled;
        $this->verbose = 0;
        $qryStrArray2 = $this->validateLogin();
        //var_dump($qryStrArray);

        if ($qryStrArray2 == null) {
            return null;
        } else {
            return $qryStrArray2;
        }
        /*         * ******************************************************** */
        //* @ End of Query string validation
    }
    
    public function validateLogin()
    {
        $msg = "<response>"
                . $this->scoc_lib->getVersionXml()
                . "<status>failure</status><message>%s</message></response>";
        try {
            $qryStrArray2 = $this->getQueryStringArrayByDecryption();
            //if ($this->verbose == 1) {
            //    var_dump($qryStrArray2);
            //}
            //var_dump($qryStrArray2);
            if ($this->enabled == false) {
                return $qryStrArray2;
            }

            if ($qryStrArray2 == null || empty($qryStrArray2)) {
                $this->response->setOutput(sprintf($msg, "Authentication failed"));
                return null;
            }

            if (!isset($qryStrArray2["u"]) && !isset($qryStrArray2["p"])) {
                $this->response->setOutput(sprintf($msg, "Invalid Parameters"));
                return null;
            }

            if (!isset($qryStrArray2["u"])) {
                $this->response->setOutput(sprintf($msg, "Invalid User"));
                return null;
            }
            if (!isset($qryStrArray2["p"])) {
                $this->response->setOutput(sprintf($msg, "Invalid Password"));
                return null;
            }

            if (empty($qryStrArray2["u"])) {
                $this->response->setOutput(sprintf($msg, 'Required parameter "u" is missing'));
                return null;
            }
            if (empty($qryStrArray2["p"])) {
                $this->response->setOutput(sprintf($msg, 'Required parameter "p" is missing'));
                return null;
            }
            if ($qryStrArray2["u"] == "") {
                $this->response->setOutput(sprintf($msg, 'Required parameter "u" is missing'));
                return null;
            }
            if ($qryStrArray2["p"] == "") {
                $this->response->setOutput(sprintf($msg, 'Required parameter "p" is missing'));
                return null;
            }
            
            
            //call login
            $adminPassword = trim((string)$qryStrArray2["p"]);
            
            // 22.4.0  added Raman Oct 30, 2017.
            // If key is passed in host key "HTTP_ORIGIN", but Url is not fully encrypted,
            // we should decrypt the "p" or password only.
            if ($this->key != "" && $this->encryption==0) :
                $adminPassword = $this->decrypt($adminPassword);
            //echo $adminPassword;
            endif;
            //echo $this->key;
            
            $adminPassword = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $adminPassword);
            //iconv(mb_detect_encoding($adminPassword, mb_detect_order(), true), "UTF-8", $adminPassword);
            //$adminPassword = iconv('utf-16', 'utf-8', $adminPassword);
            
            if ($this->dbLogin($qryStrArray2["u"], $adminPassword)) {
                return $qryStrArray2;
            } else {
                $this->response->setOutput(sprintf($msg, "Authentications failed"));
                return null;
            }
        } catch (Exception $ex) {
            echo sprintf($msg, $ex->getMessage());
            return null;
        }
    }
    
    public function getQueryStringArrayByDecryption()
    {
        $qryStrArray = array();
        $q = $_SERVER["QUERY_STRING"];
        

        if (empty($q) && $this->enabled == true) {
            return null;
        }

        //if ($this->verbose == 1) {
        //    echo '<br>q before url decode ' . $q;
        //}
        
        // cannot urldecode. each query string param can contain ? and & characters. If
        // we decode now, additional element may be created in the array.
        //$q = urldecode($q);
        
        //if ($this->verbose == 1) {
        //    echo '<br>q after url decode ' . $q;
        //}

        // proceed with decryption of query string
        try {
            // Decide of we need encryption
            if ($this->encryption == 1) :
                $strDecrypted = $this->decrypt(urldecode($q)); //echo $qryString;
            //echo ' decrypted string ' . $strDecrypted;
            //if ($this->verbose == 1) {
            //    echo ' decrypted string ' . $strDecrypted;
            //}
                
                if (!empty($strDecrypted)) :
                    $qryString = "".$strDecrypted;
                    
                // removed Nov 13, 2017
                //$qryString = urldecode($qryString);
                endif;
                $qryString = $this->cleanString($qryString);
                parse_str($qryString, $qryStrArray);
            else :
                parse_str($q, $qryStrArray);
            //var_dump($qryStrArray);
            endif;

            //var_dump($qryStrArray);
            // added Nov 13, 2017
            $qryStrArray = array_map(function ($val) {
                return urldecode($val);
            }, $qryStrArray);
            $qryStrArray = $this->makeParamLower($qryStrArray);
            
            return $qryStrArray;
        } catch (Exception $ex) {
            echo "<response><status>failure</status><message>" . $ex->getMessage() . "</message></response>";
            return $qryStrArray;
        }
    }
    
    public function encrypt($string)
    {
        $phpEncrypted = $this->tripleDes->Encrypt($string, $this->key);
        return $phpEncrypted;
        /*
          //Encryption
          $cipher_alg = MCRYPT_TRIPLEDES;

          $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher_alg, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
          $iv = "45287112549354892144548565456541";

          $encrypted_string = mcrypt_encrypt($cipher_alg, $this->key, $string, MCRYPT_MODE_CBC, $iv);
          return base64_encode($encrypted_string);
          //return $encrypted_string;
         *
         */
    }

    public function decrypt($vbEncrypted)
    {
        $phpDecrypted = $this->tripleDes->Decrypt($vbEncrypted, $this->key);
        return $phpDecrypted;


        /*
          try {
          $string = base64_decode($original_string);
          $cipher_alg = MCRYPT_TRIPLEDES;
          $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher_alg, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
          $decrypted_string = mcrypt_decrypt($cipher_alg, $this->key, $string, MCRYPT_MODE_CBC, $iv);
          $decrypted_string = trim($decrypted_string);

          // We should get the original string here
          if( $this->encrypt($decrypted_string)== $original_string):
          //This string
          return $decrypted_string;
          else:
          return NULL;
          endif;
          //return trim($decrypted_string);


          } catch (Exception $ex) {
          return NULL;
          }
         */
    }

    private function makeParamLower($qryStrArray)
    {
        return array_change_key_case($qryStrArray, CASE_LOWER);
    }

    /*     * *******************************************************
     *  Query string validation
     * ******************************************************* */

    public function isValid($str)
    {
        return !preg_match('/[^A-Za-z0-9.#\\-$]/', $str);
    }

    public function dbLogin($username, $password)
    {
        $salt = '';

        if (true) :
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $this->db->escape($username)
                    . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape($password) . "'))))) "
                    . " OR password = '" . $this->db->escape(md5($password)) . "')"
                    . " AND status = '1'");

        //var_dump($this->db->escape(md5($password)) );
        //var_dump($user_query);
            if ($user_query->num_rows) :
                return true;
            else :
                    return false;
            endif;
        endif;
    }
}
