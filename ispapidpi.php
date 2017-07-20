<?php
session_start();
$module_version = "2.0";

function ispapidpi_config($params) {
    global $module_version;
    $configarray = array(
        "name" => "ISPAPI Pricing Importer",
        "description" => "Quickly update your WHMCS domain pricing list.",
        "version" => $module_version,
        "author" => "HEXONET",
        "language" => "english",
        "fields" => array("username" => array ("FriendlyName" => "Admin username", "Type" => "text", "Size" => "30", "Description" => "[REQUIRED]", "Default" => "admin",))
    );
    return $configarray;
}

function ispapidpi_activate() {
	return array('status'=>'success','description'=>'Installed');
}

function ispapidpi_deactivate() {
	return array('status'=>'success','description'=>'Uninstalled');
}

function ispapidpi_output($vars){
    // echo "<pre>"; print_r($_POST); echo "</pre>";
    // echo "<pre>"; print_r($_SESSION); echo "</pre>";

    //load and check if registrar module is installed
    require_once(dirname(__FILE__)."/../../../includes/registrarfunctions.php");
    //Check if the registrar module exists
    $file = "ispapi";
    $error = false;
    if(file_exists(dirname(__FILE__)."/../../../modules/registrars/".$file."/".$file.".php")){
        require_once(dirname(__FILE__)."/../../../modules/registrars/".$file."/".$file.".php");
        $funcname = $file.'_GetISPAPIModuleVersion';
        if(function_exists($file.'_GetISPAPIModuleVersion')){
            $version = call_user_func($file.'_GetISPAPIModuleVersion');
            //check if version = 1.0.15 or higher
            if( version_compare($version, '1.0.15') >= 0 ){
                //check authentication
                $registrarconfigoptions = getregistrarconfigoptions($file);
                $ispapi_config = ispapi_config($registrarconfigoptions);
                $command =  $command = array(
                    "command" => "CheckAuthentication",
                    "subuser" => $ispapi_config["login"],
                    "password" => $ispapi_config["password"],
                );
                $checkAuthentication = ispapi_call($command, $ispapi_config);
                if($checkAuthentication["CODE"] != "200"){
                    die("The \"".$file."\" registrar authentication failed! Please verify your registrar credentials and try again.");
                }
            }else{
                $error = true;
            }
        }else{
            $error = true;
        }
    }else{
        $error = true;
    }
    if($error){
        die("The ISPAPI Pricing importer Module requires ISPAPI Registrar Module v1.0.15 or higher!");
    }

    //to download a sample csv file
    if(isset($_POST['download-sample-csv'])){
        download_csv_sample_file();
    }

    //smarty template
    $smarty = new Smarty;
    $smarty->compile_dir = $GLOBALS['templates_compiledir'];
    $smarty->caching = false;
    $entity = ($ispapi_config["entity"] == "54cd") ? "PRODUCTION Environment" : "OT&E Environment";
    $smarty->assign('user', $ispapi_config["login"]);
    $smarty->assign('entity', $entity);

    //get all user classes
    $registrarconfigoptions = getregistrarconfigoptions($file);
    $ispapi_config = ispapi_config($registrarconfigoptions);
    $command = array(
        "command" => "queryuserclasslist"
    );
    $queryuserclasslist = ispapi_call($command, $ispapi_config);
    $smarty->assign('queryuserclasslist', $queryuserclasslist);

    if(isset($_POST['checkbox-tld']) || (isset($_SESSION["checkbox-tld"]) && isset($_POST['multiplier'])) || (isset($_SESSION["checkbox-tld"]) && isset($_POST['addfixedamount'])) || isset($_POST['import'])){
        //step 3
        if(isset($_POST['checkbox-tld'])){
            $_SESSION["checkbox-tld"] = $_POST["checkbox-tld"];
        }

        if(isset($_POST['multiplier'])){
            $smarty->assign('post-multiplier', $_POST['multiplier']);
        }else{
            $_POST['multiplier'] = 1.00;
            $smarty->assign('post-multiplier', $_POST['multiplier']);
        }

        if(isset($_POST['addfixedamount'])){
            $smarty->assign('post-addfixedamount', $_POST['addfixedamount']);
        }else{
            $_POST['add-fixed-amount'] = 1.00;
            $smarty->assign('post-addfixedamount', $_POST['addfixedamount']);
        }
        $smarty->assign('session-price-class', $_SESSION["price_class"]);

        //get checked TLD then get register,renew and transfer prices for that TLD
        $get_tld = [];
        foreach($_SESSION["checkbox-tld"] as $checkbox_tld){
            array_push($get_tld, $checkbox_tld);
        }
        $get_tld = array_flip($get_tld);

        $get_checked_tld_data = [];
        foreach($get_tld as $key=>$value){
            if(array_key_exists($key, $_SESSION["tld-register-renew-transfer-currency-filter"])){
                $tld_prices = $_SESSION["tld-register-renew-transfer-currency-filter"][$key];
                $get_checked_tld_data[$key]=$tld_prices;
            }
        }
        //csv file array
        foreach($get_tld as $key=>$value){
            if(array_key_exists($key, $_SESSION["csv-as-new-array"])){
                $tld_prices = $_SESSION["csv-as-new-array"][$key];
                $get_checked_tld_data[$key]=$tld_prices;
            }
        }

        $_SESSION["checked_tld_data"] = $get_checked_tld_data;
        $_SESSION["checked_tld_data"] = array_change_key_case($_SESSION["checked_tld_data"], CASE_LOWER);


        foreach ($_SESSION["checked_tld_data"] as $key => $subArr){
            unset($subArr['currency']);
            $_SESSION["checked_tld_data"][$key] = $subArr;
        }
        $currency_data = [];
        $request = mysql_query("SELECT * FROM tblcurrencies");
        while ($currencies = mysql_fetch_array($request)) {
            $currency_data[$currencies["id"]] = $currencies["code"];
        }
        if(isset($_POST['add-fixed-amount'])){
            $add_fixed_amount = $_POST['add-fixed-amount'];
            $smarty->assign('add_fixed_amount', $add_fixed_amount);
            $smarty->assign('session-checked-tld-data', $_SESSION["checked_tld_data"]);
            $smarty->assign('currency_data', $currency_data);
            $smarty->display(dirname(__FILE__).'/templates/step3.tpl');
        }elseif(isset($_POST['multiplier'])){
            $smarty->assign('session-checked-tld-data', $_SESSION["checked_tld_data"]);
            $smarty->assign('currency_data', $currency_data);
            $smarty->display(dirname(__FILE__).'/templates/step3.tpl');
        }else{
            $smarty->assign('session-checked-tld-data', $_SESSION["checked_tld_data"]);
            $smarty->assign('currency_data', $currency_data);
            $smarty->display(dirname(__FILE__).'/templates/step3.tpl');
        }
    }
    elseif(isset($_POST['price_class'])){
        //step 2
        $_SESSION["price_class"] = $_POST['price_class'];
        if($_POST['price_class'] == "DEFAULT_PRICE_CLASS"){
            $command =  $command = array(
              "command" => "StatusUser"
            );
            $default_costs = ispapi_call($command, $ispapi_config);
            collect_tld_register_transfer_renew_currency($default_costs);
        }
        elseif($_POST['price_class'] == "CSV-FILE"){
            //when csv file is slected also in STEP 2
            //to check if the file is csv
            $type_of_uploaded_file = array('text/csv');
            if (isset($_FILES["file"])) {
                 if(in_array($_FILES["file"]["type"], $type_of_uploaded_file)){
                     $smarty->assign('post-file', $_FILES["file"]);
                     if($_FILES["file"]["name"] != "") {
                         $smarty->assign('post-file-name', $_FILES["file"]["name"]);
                         $tmpName = $_FILES['file']['tmp_name'];

                         $csvAsArray = array();
                         //if the delimiter is ; then continue else print an error message
                             if(checkDelimiterCount($tmpName)){
                             //handling comma and semicolon with csv files
                             $csvAsArray = array_map(function($d) {
                                 return str_getcsv($d, ",");
                             }, file($tmpName));

                             $csvAsArray = array_map(function($d) {
                                 return str_getcsv($d, ";");
                             }, file($tmpName));

                         //remove first element (header part of the csv file)
                         array_shift($csvAsArray);

                         $csv_as_new_array = [];
                         foreach($csvAsArray as $key=>$value){
                             $newKey = "";
                             foreach($value as $ky=>$val){
                                 if($ky == 0){
                                     //first element to take as new key
                                     $newKey = $val;
                                     $csv_as_new_array[$newKey] = [];
                                 }else{
                                     $csv_as_new_array[$newKey][] = $val;
                                 }
                             }
                         }

                         //to change keys of above array to strings
                         $keynames = array('register', 'renew', 'transfer');
                         foreach($csv_as_new_array as $key=>$value){
                             $csv_as_new_array[$key] = array_combine($keynames, array_values($csv_as_new_array[$key]));
                         }
                         $add_currency_to_array = array('currency'=>'USD');
                         foreach($csv_as_new_array as $key=>$value){
                             $csv_as_new_array[$key] = $csv_as_new_array[$key]+$add_currency_to_array;
                         }
                         $csv_as_new_array = array_change_key_case($csv_as_new_array, CASE_LOWER);

                         $_SESSION["csv-as-new-array"] = $csv_as_new_array;

                         $smarty->assign('csv_as_new_array', $csv_as_new_array);
                         $smarty->display(dirname(__FILE__).'/templates/step2.tpl');

                         }else{
                             echo "<div class='errorbox'><strong><span class='title'>File error!</span></strong><br>CSV file should use \";\" as separator.</div>";
                             // echo "<div class='errorbox'><strong><span class='title'>ERROR!</span></strong><br>No CSV file has been selected.</div><br>";
                             $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
                         }

                     }// end of if $_FILES["file"]["name"] is not empty
                     else{
                         echo "<div class='errorbox'><strong><span class='title'>ERROR!</span></strong><br>No CSV file has been selected.</div><br>";
                         $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
                     }
                 }else{
                     echo "<div class='errorbox'><strong><span class='title'>ERROR!</span></strong><br>Please upload only a CSV file.</div><br>";
                     $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
                 }

            }
            elseif(isset($_SESSION["csv-as-new-array"])){
                //else for isset($_FILES["file"]), i.e. there is no file, but a session
                $csv_as_new_array = $_SESSION["csv-as-new-array"];
                $smarty->assign('csv_as_new_array', $csv_as_new_array);
                $smarty->display(dirname(__FILE__).'/templates/step2.tpl');
            }
        }else{
            $command = array(
                "command" => "StatusUserClass",
                "userclass"=> $_POST['price_class']
            );
            $getdata_of_priceclass = ispapi_call($command, $ispapi_config);
            collect_tld_register_transfer_renew_currency($getdata_of_priceclass);
        }
    }else{
        //step 1
        $smarty->assign('queryuserclasslist_PROPERTY_USERCLASS', $queryuserclasslist["PROPERTY"]["USERCLASS"]);
        $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
    }


    //import button clicked
    if(isset($_POST['import'])){
        importButton();
        $smarty->assign('post-import', $_POST['import']);
        if($_POST['dns_management'] == 'on'){
            $smarty->assign('post-dns_management', $_POST['dns_management']);
        }
        if($_POST['email_forwarding'] == 'on'){
            $smarty->assign('post-email_forwarding', $_POST['email_forwarding']);
        }
        if($_POST['id_protection'] == 'on'){
            $smarty->assign('post-id_protection', $_POST['id_protection']);
        }
        if($_POST['epp_code'] == 'on'){
            $smarty->assign('post-epp_code', $_POST['epp_code']);
        }
    }
}//end of ispapidpi_output()

//to check if each line with semicolon separated
function checkDelimiterCount($file){
    $file = new SplFileObject($file);
    $file->setFlags(SplFileObject::READ_CSV |
    SplFileObject::SKIP_EMPTY |
    SplFileObject::READ_AHEAD);
    $delimiter = ";";
    $count = 0;
    while (!$file->eof()) {
        $line = $file->fgets();
        if(!empty($line)){
            // echo "This is the line: " . $line . "<br>";
            if(substr_count($line, $delimiter) != 3){
                // echo "Error found in line " . $count . ". found " . substr_count($line, $delimiter) . " semicolons.<br>";
                $file = null;
                return 0;
            }
        }
        //$file->next();
        $count++;
    }
    $file = null;
    return 1;
}

//this function is called based on the user selection --> selection of price class or to use default hexonet costs and creates an array
function collect_tld_register_transfer_renew_currency($priceclass_or_defaultcost){

//include file for second or third level domain name
    include("tldlib_array.php");

    //smarty template for table in step 2
    $smarty = new Smarty;
    $smarty->compile_dir = $GLOBALS['templates_compiledir'];
    $smarty->caching = false;

    $pattern_for_tld = "/PRICE_CLASS_DOMAIN_([^_]+)_/";
    $tlds = [];
    foreach($priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"] as $key => $value){
        if(preg_match($pattern_for_tld,$value,$match)){
          $tlds[] = $match[1];
        }
    }

    //remove duplicates of tlds
    $tlds = array_unique($tlds);

    //collect register, renew and transfer prices and currency for each tld in an array
    $tld_register_renew_transfer_currency = array();
    foreach ($tlds as $key => $tld){
        //register
        $pattern_for_registerprice ="/PRICE_CLASS_DOMAIN_".$tld."_ANNUAL$/";
        if(preg_grep($pattern_for_registerprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"])){
            $register_match = preg_grep($pattern_for_registerprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
            $register_match_keys = array_keys($register_match);
            // $tld_data[] = $tld;
            foreach ($register_match_keys as $key){
                if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
                    //values of the keys
                    //register and renew
                    $register_price = $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
                    $tld_register_renew_transfer_currency[$tld]['register'] = $register_price;
                    // $tld_register_renew_transfer_currency[$tld]['renew']= $register_price;
                }
            }
        }else{
            $tld_register_renew_transfer_currency[$tld]['register']='';
        }

        //renew
        $pattern_for_renewprice = "/PRICE_CLASS_DOMAIN_".$tld."_RENEW$/";
        if(preg_grep($pattern_for_renewprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"])){
            $renew_match = preg_grep($pattern_for_renewprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
            $renew_match_keys = array_keys($renew_match);
            foreach ($renew_match_keys as $key){
                if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
                    //values of the keys
                    $renew_price = $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
                    $tld_register_renew_transfer_currency[$tld]['renew'] = $renew_price;
                }
            }
        }else{
            $tld_register_renew_transfer_currency[$tld]['renew'] = $register_price;
        }

        //Transfer
        $pattern_for_transferprice = "/PRICE_CLASS_DOMAIN_".$tld."_TRANSFER$/";
        if(preg_grep($pattern_for_transferprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"])){
            $transfer_match = preg_grep($pattern_for_transferprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
            $transfer_match_keys = array_keys($transfer_match);
            foreach ($transfer_match_keys as $key){
                if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
                    //values of the keys
                    $transfer_price = $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
                    $tld_register_renew_transfer_currency[$tld]['transfer'] = $transfer_price;
                }
            }
        }else{
            $tld_register_renew_transfer_currency[$tld]['transfer']= '';
        }

        //get tld currency
        $pattern_for_currency = "/PRICE_CLASS_DOMAIN_".$tld."_CURRENCY$/";
        $currency_match = preg_grep($pattern_for_currency, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
        $currency_match_keys= array_keys($currency_match);
        foreach($currency_match_keys as $key){
            if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
                $tld_currency = $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
                $tld_register_renew_transfer_currency[$tld]['currency'] = $tld_currency;
            }
        }
    }
    removeEmpty($tld_register_renew_transfer_currency);

    //filter tlds that are with currency USD
    $tld_register_renew_transfer_currency_filter = filter_array($tld_register_renew_transfer_currency,'USD');

    $tld_register_renew_transfer_currency_filter =  array_change_key_case($tld_register_renew_transfer_currency_filter, CASE_LOWER);

//check for second or third level domain names and replace
    $tldlib =  array_change_key_case($tldlib, CASE_LOWER);

    $tld_register_renew_transfer_currency_filter1 = array();
    foreach($tld_register_renew_transfer_currency_filter as $key => $value){

        if(array_key_exists($key, $tldlib)){
            $tld_register_renew_transfer_currency_filter1[$tldlib[$key]['tld']] = $tld_register_renew_transfer_currency_filter[$key];
        }else{
            $tld_register_renew_transfer_currency_filter1[$key] = $tld_register_renew_transfer_currency_filter[$key];
        }
    }

    $_SESSION["tld-register-renew-transfer-currency-filter"] = $tld_register_renew_transfer_currency_filter1; //session variable for tld data (tld and prices ,currency)

    $smarty->assign('tld_register_renew_transfer_currency_filter', $tld_register_renew_transfer_currency_filter1);
    $smarty->display(dirname(__FILE__).'/templates/step2.tpl');
 }

//Import button clicked
//It collects the tlds and the updated prices by user. calls the startimport()
function importButton(){
    $prices_match_pattern = "/PRICE_(.*)_(.*)/";

    $tld_match = []; //has all the tld names which have new prices
    foreach($_POST as $key=>$value){
        if(preg_match($prices_match_pattern,$key,$match)){
          $tld_match[] = $match[1];
        }
    }
//replace underscores with dots in the tlds
    foreach ($tld_match as $key => $value){
        $tld_match[$key] = strtolower(str_replace('_', '.', $value));
    }
    //for prices renew, register, transfer
    $price_name_match = []; //has all new prices (strings)
    foreach($_POST as $key=>$value){
        if(preg_match($prices_match_pattern,$key,$match)){
          $price_name_match[] = $match[2];
        }
    }
    $tld_new_price = [];
    foreach($_POST as $key=>$value){
        if(preg_match($prices_match_pattern, $key)){
          $tld_new_price[] = $value;
        }
    }
    $new_prices_for_whmcs = array_combine_($tld_match, $tld_new_price);

    //for checked items -DNS Management, email Forwarding, id Protection, epp code
    $domain_addons = [];
    $dns_pattern = "/dns_management/";
    foreach($_POST as $key=>$value){
        if(preg_match($dns_pattern, $key)){
          $domain_addons['dns-management'] = $value;
        }
    }
    $emailforwarding_pattern = "/email_forwarding/";
    foreach($_POST as $key=>$value){
        if(preg_match($emailforwarding_pattern, $key)){
          $domain_addons['email-forwarding'] = $value;
        }
    }
    $idprotection_pattern = "/id_protection/";
    foreach($_POST as $key=>$value){
        if(preg_match($idprotection_pattern, $key)){
          $domain_addons['id-protection'] = $value;
        }
    }
    $eppcode_pattern = "/epp_code/";
    foreach($_POST as $key=>$value){
        if(preg_match($eppcode_pattern, $key)){
          $domain_addons['epp-code'] = $value;
        }
    }
    //for currency
    $currencies = [];
    $currency_pattern = "/currency/";
    foreach($_POST as $key=>$value){
        if(preg_match($currency_pattern, $key)){
          $currencies['currency'] = $value;
        }
    }
    foreach($new_prices_for_whmcs as $key=>$value){
        array_push($new_prices_for_whmcs[$key], $domain_addons);
    }

    //to merge each curreny value from currencies array new_prices_for_whmcs
    $i = -1;
    foreach($new_prices_for_whmcs as $key=>$value){
        $i++;
        $new_prices_for_whmcs[$key]['currency'] = $currencies['currency'][$i];
    }
    //import the data
    startimport($new_prices_for_whmcs);
}

//loop through array and insert or update the tld and prices for whmcs to DB
function startimport($prices_for_whmcs){
    $prices_for_whmcs = array_change_key_case($prices_for_whmcs, CASE_LOWER);
    foreach($prices_for_whmcs as $key=>$value){
        //with TLD/extension
        $result = mysql_query("SELECT * FROM tbldomainpricing WHERE extension='".'.'.$key."'");
        $tbldomainpricing = mysql_fetch_array($result);
        if(!empty($tbldomainpricing)){
            update_query("tbldomainpricing",array("dnsmanagement"=> $prices_for_whmcs[$key][3]['dns-management'], "emailforwarding"=> $prices_for_whmcs[$key][3]['email-forwarding'], "idprotection"=> $prices_for_whmcs[$key][3]['id-protection'], "eppcode"=> $prices_for_whmcs[$key][3]['epp-code'], "autoreg"=> "ispapi"),array("extension" => '.'.$key));
        }else{
            $tbldomainpricing["id"] = insert_query("tbldomainpricing",array("extension" => '.'.$key, "dnsmanagement"=> $prices_for_whmcs[$key][3]['dns-management'], "emailforwarding"=> $prices_for_whmcs[$key][3]['email-forwarding'], "idprotection"=> $prices_for_whmcs[$key][3]['id-protection'], "eppcode"=>$prices_for_whmcs[$key][3]['epp-code'], "autoreg"=>"ispapi"));
        }

        //replace or add pricing for domainregister
        $result = mysql_query("SELECT * FROM tblpricing WHERE type='domainregister' AND currency=".$prices_for_whmcs[$key]['currency']." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
        $tblpricing = mysql_fetch_array($result);
        if(!empty($tblpricing)){
          update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][0]), array("id" => $tblpricing["id"]));
        }else{
          insert_query("tblpricing",array("type" => "domainregister", "currency" => $prices_for_whmcs[$key]['currency'], relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][0], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
        }

        //replace or add pricing for domaintransfer
		$result = mysql_query("SELECT * FROM tblpricing WHERE type='domaintransfer' AND currency=".$prices_for_whmcs[$key]['currency']." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
		$tblpricing = mysql_fetch_array($result);
		if(!empty($tblpricing)){
			update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][1]), array("id" => $tblpricing["id"]));
		}else{
			insert_query("tblpricing",array("type" => "domaintransfer", "currency" => $prices_for_whmcs[$key]['currency'], relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][1], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
		}

        //replace or add pricing for domainrenew
		$result = mysql_query("SELECT * FROM tblpricing WHERE type='domainrenew' AND currency=".$prices_for_whmcs[$key]['currency']." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
		$tblpricing = mysql_fetch_array($result);
		if(!empty($tblpricing)){
			update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][2]), array("id" => $tblpricing["id"]));
		}else{
			insert_query("tblpricing",array("type" => "domainrenew", "currency" => $prices_for_whmcs[$key]['currency'], relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][2], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
		}
    }
}

//download a sample csv file
function download_csv_sample_file(){
  // output headers so that the file is downloaded rather than displayed
  header('Content-type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=yourpricinglist.csv');
  // do not cache the file
  header('Pragma: no-cache');
  header('Expires: 0');
  //create a file pointer connected to the output stream
  $output = fopen('php://output', 'w');
  fputcsv($output, array('TLD','REGISTER_PRICE_USD','RENEW_PRICE_USD','TRANSFER_PRICE_USD'),";");
  fputcsv($output, array('com', '10.99', '10.99', '11.59'),";");
  fputcsv($output, array('com.au', '12.99', '10.45', '15.59'),";");
  exit(0);
}

//###### Helper functions ######
function array_combine_($keys, $values){
    $result = array();
    foreach ($keys as $i => $k) {
        $result[$k][] = $values[$i];
    }
    array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
    return $result;
}

//filter tld data array for only tlds with usd currency
function filter_array($array,$term){
    $matches = array();
    foreach($array as $key=>$value){
        if($value['currency'] == $term)
            $matches[$key]=$value;
    }
    return $matches;
}

//function to remove if any of the prices are empty/not listed
function removeEmpty(&$arr) {
    foreach ($arr as $index => $person) {
        if (count($person) != count(array_filter($person, function($value) { return !!$value; }))) {
            unset($arr[$index]);
        }
    }
}
?>
