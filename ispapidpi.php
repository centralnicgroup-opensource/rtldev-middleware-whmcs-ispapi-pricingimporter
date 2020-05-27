<?php
use WHMCS\Database\Capsule;

use WHMCS\Module\Registrar\Ispapi\Ispapi;
use WHMCS\Module\Registrar\Ispapi\LoadRegistrars;
use WHMCS\Module\Registrar\Ispapi\Helper;

session_start();

function ispapidpi_config()
{
    return [
        "name" => "ISPAPI Pricing Importer",
        "description" => "Quickly update your WHMCS domain pricing list.",
        "author" => "HEXONET",
        "language" => "english",
        "fields" => [
            "username" => [
                "FriendlyName" => "Admin username",
                "Type" => "text",
                "Size" => "30",
                "Description" => "[REQUIRED]",
                "Default" => "admin"
            ]
        ],
        "version" => "5.0.1"
    ];
}

function ispapidpi_output($vars)
{
    //load all the ISPAPI registrars
    $ispapi_registrars = new LoadRegistrars();
    $_SESSION["ispapi_registrar"] = $ispapi_registrars->getLoadedRegistars();

    if (empty($_SESSION["ispapi_registrar"])) {
        die("The ispapi registrar authentication failed! Please verify your registrar credentials and try again.");
    }

    //include file for TLDCLASS to TLD Label mapping
    include(dirname(__FILE__)."/tldlib_array.php");

    //download a sample csv file
    if (isset($_POST['download-sample-csv'])) {
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
    $command = array(
        "command" => "queryuserclasslist"
    );
    $queryuserclasslist = Ispapi::call($command);
    $smarty->assign('queryuserclasslist', $queryuserclasslist);

    //warning message to increase max_input_vars
    if (ini_get("max_input_vars") <= count($_POST, COUNT_RECURSIVE)) {
        $smarty->display(dirname(__FILE__).'/templates/maxinputvarserr.tpl');
    } else {
        if (isset($_POST['checkbox-tld']) || (isset($_SESSION["checkbox-tld"]) && isset($_POST['multiplier'])) || (isset($_SESSION["checkbox-tld"]) && isset($_POST['addfixedamount'])) || isset($_POST['import'])) {
            //step 3
            if (isset($_POST['checkbox-tld'])) {
                $_SESSION["checkbox-tld"] = $_POST["checkbox-tld"];
            }

            if (isset($_POST['multiplier'])) {
                $smarty->assign('post-multiplier', $_POST['multiplier']);
            } else {
                $_POST['multiplier'] = 1.00;
                $smarty->assign('post-multiplier', $_POST['multiplier']);
            }

            if (isset($_POST['addfixedamount'])) {
                $smarty->assign('post-addfixedamount', $_POST['addfixedamount']);
            } else {
                $_POST['add-fixed-amount'] = 1.00;
                $smarty->assign('post-addfixedamount', $_POST['addfixedamount']);
            }
            $smarty->assign('session-price-class', $_SESSION["price_class"]);

            //get checked TLD then get register,renew and transfer prices for that TLD
            $get_tld = [];
            foreach ($_SESSION["checkbox-tld"] as $checkbox_tld) {
                array_push($get_tld, $checkbox_tld);
            }
            $get_tld = array_flip($get_tld);

            $get_checked_tld_data = [];
            foreach ($get_tld as $key => $value) {
                if (array_key_exists($key, $_SESSION["tld-register-renew-transfer-currency-filter"])) {
                    $tld_prices = $_SESSION["tld-register-renew-transfer-currency-filter"][$key];
                    $get_checked_tld_data[$key]=$tld_prices;
                }
            }
            //csv file array
            foreach ($get_tld as $key => $value) {
                if (array_key_exists($key, $_SESSION["csv-as-new-array"])) {
                    $tld_prices = $_SESSION["csv-as-new-array"][$key];
                    $get_checked_tld_data[$key]=$tld_prices;
                }
            }

            $_SESSION["checked_tld_data"] = $get_checked_tld_data;
            $_SESSION["checked_tld_data"] = array_change_key_case($_SESSION["checked_tld_data"], CASE_LOWER);

            foreach ($_SESSION["checked_tld_data"] as $key => $subArr) {
                //unset($subArr['currency']);
                $_SESSION["checked_tld_data"][$key] = $subArr;
            }
            $currency_data = [];
            try {
                $pdo = Capsule::connection()->getPdo();
                $request = $pdo->prepare("SELECT * FROM tblcurrencies");
                $request->execute();
                $currencies = $request->fetchAll(PDO::FETCH_ASSOC);
                foreach ($currencies as $key => $value) {
                    $currency_data[$value["id"]] = $value["code"];
                }
            } catch (Exception $e) {
                die($e->getMessage());
            }
            
            $smarty->assign('idnmap', $idnmap);
            if (isset($_POST['add-fixed-amount'])) {
                $add_fixed_amount = $_POST['add-fixed-amount'];
                $smarty->assign('add_fixed_amount', $add_fixed_amount);
                $smarty->assign('session-checked-tld-data', $_SESSION["checked_tld_data"]);
            } elseif (isset($_POST['multiplier'])) {
                $smarty->assign('session-checked-tld-data', $_SESSION["checked_tld_data"]);
            } else {
                $smarty->assign('session-checked-tld-data', $_SESSION["checked_tld_data"]);
            }
            $smarty->assign('currency_data', $currency_data);
            $smarty->display(dirname(__FILE__).'/templates/step3.tpl');
        } elseif (isset($_POST['price_class'])) {
            //step 2
            $_SESSION["price_class"] = $_POST['price_class'];
            if ($_POST['price_class'] == "DEFAULT_PRICE_CLASS") {
                $command =  $command = array(
                    "command" => "StatusUser"
                );
                $default_costs = Ispapi::call($command);
                collect_tld_register_transfer_renew_currency($default_costs, $tldlib, $idnmap, $dontofferpattern);
            } elseif ($_POST['price_class'] == "CSV-FILE") {
                //when csv file is slected also in STEP 2
                //to check if the file is csv
                $type_of_uploaded_file = array('text/csv', 'application/vnd.ms-excel', 'text/plain');//linux vs. windows vs. just text
                if (isset($_FILES["file"])) {
                    if (in_array($_FILES["file"]["type"], $type_of_uploaded_file)) {
                        $smarty->assign('post-file', $_FILES["file"]);
                        if ($_FILES["file"]["name"] != "") {
                            $smarty->assign('post-file-name', $_FILES["file"]["name"]);
                            $tmpName = $_FILES['file']['tmp_name'];

                            $csvAsArray = array();
                            //if the delimiter is ; then continue else print an error message
                            if (checkDelimiterCount($tmpName)) {
                                //handling comma and semicolon with csv files
                                $csvAsArray = array_map(function ($d) {
                                        return str_getcsv($d, ",");
                                }, file($tmpName));

                                $csvAsArray = array_map(function ($d) {
                                    return str_getcsv($d, ";");
                                }, file($tmpName));

                                //remove first element (header part of the csv file)
                                array_shift($csvAsArray);

                                $csv_as_new_array = [];
                                foreach ($csvAsArray as $key => $value) {
                                        $newKey = "";
                                    foreach ($value as $ky => $val) {
                                        if ($ky == 0) {
                                            //first element to take as new key
                                            $newKey = $val;
                                            $csv_as_new_array[$newKey] = [];
                                        } else {
                                            $csv_as_new_array[$newKey][] = $val;
                                        }
                                    }
                                }

                                //to change keys of above array to strings
                                $keynames = array('register', 'renew', 'transfer');
                                foreach ($csv_as_new_array as $key => $value) {
                                    $csv_as_new_array[$key] = array_combine($keynames, array_values($csv_as_new_array[$key]));
                                }
                                $add_currency_to_array = array('currency'=>'USD');
                                foreach ($csv_as_new_array as $key => $value) {
                                    $csv_as_new_array[$key] = $csv_as_new_array[$key]+$add_currency_to_array;
                                }
                                $csv_as_new_array = array_change_key_case($csv_as_new_array, CASE_LOWER);

                                $_SESSION["csv-as-new-array"] = $csv_as_new_array;

                                $smarty->assign('csv_as_new_array', $csv_as_new_array);
                                $smarty->display(dirname(__FILE__).'/templates/step2.tpl');
                            } else {
                                echo "<div class='errorbox'><strong><span class='title'>File error!</span></strong><br>CSV file should use \";\" as separator.</div>";
                                // echo "<div class='errorbox'><strong><span class='title'>ERROR!</span></strong><br>No CSV file has been selected.</div><br>";
                                $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
                            }
                        } else {
                            // end of if $_FILES["file"]["name"] is not empty
                            echo "<div class='errorbox'><strong><span class='title'>ERROR!</span></strong><br>No CSV file has been selected.</div><br>";
                            $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
                        }
                    } else {
                        echo "<div class='errorbox'><strong><span class='title'>ERROR!</span></strong><br>Please upload only a CSV file.</div><br>";
                        $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
                    }
                } elseif (isset($_SESSION["csv-as-new-array"])) {
                    //else for isset($_FILES["file"]), i.e. there is no file, but a session
                    $csv_as_new_array = $_SESSION["csv-as-new-array"];
                    $smarty->assign('csv_as_new_array', $csv_as_new_array);
                    $smarty->display(dirname(__FILE__).'/templates/step2.tpl');
                }
            } else {
                $command = array(
                    "command" => "StatusUserClass",
                    "userclass"=> $_POST['price_class']
                );
                $getdata_of_priceclass = Ispapi::call($command);
                collect_tld_register_transfer_renew_currency($getdata_of_priceclass, $tldlib, $idnmap, $dontofferpattern);
            }
        } else {
            //step 1
            $smarty->assign('queryuserclasslist_PROPERTY_USERCLASS', $queryuserclasslist["PROPERTY"]["USERCLASS"]);
            $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
        }

        //import button clicked
        if (isset($_POST['import'])) {
            importButton();
            $smarty->assign('post-import', $_POST['import']);
        }
    }
}//end of ispapidpi_output()

//to check if each line with semicolon separated
function checkDelimiterCount($file)
{
    $file = new SplFileObject($file);
    $file->setFlags(SplFileObject::READ_CSV |
    SplFileObject::SKIP_EMPTY |
    SplFileObject::READ_AHEAD);
    $delimiter = ";";
    $count = 0;
    while (!$file->eof()) {
        $line = $file->fgets();
        if (!empty($line)) {
            if (substr_count($line, $delimiter) != 3) {
                $file = null;
                return 0;
            }
        }
        $count++;
    }
    $file = null;
    return 1;
}

/**
 * Convert Relation Prices into format that can be consumed by WHMCS
 * and output the result
 * @param array $r API response of StatusUser or StatusUserClass, providing relations
 * @param array $tldlib TLDCLASS to TLDLABEL mapping
 * @param array $idnmap TLDCLASS to Umlaut-Label mapping
 * @param string $dontofferpattern pattern filter for TLDCLASS to not offer
 */
function collect_tld_register_transfer_renew_currency($r, $tldlib, $idnmap, $dontofferpattern)
{
    //collect register, renew and transfer prices and currency for each tld in an array
    $relationprices = array();
    //relation type to value mapping for faster access
    $relations = array();
    foreach ($r["PROPERTY"]["RELATIONTYPE"] as $idx => &$type) {
        $relations[$type] = $r["PROPERTY"]["RELATIONVALUE"][$idx];
    }
    //tldclass pattern (we leave out tldclasses without currency for import)
    //this results in one match per tldclass, so we can drop array_unique later on
    $pattern_for_tldclass = "/^PRICE_CLASS_DOMAIN_([^_]+)_CURRENCY$/";
    foreach (preg_grep($pattern_for_tldclass, $r["PROPERTY"]["RELATIONTYPE"]) as $ctype) {
        $tldclass = preg_replace("/(^PRICE_CLASS_DOMAIN_|_CURRENCY$)/", "", $ctype);
        // if one of relation types SETUP, ANNUAL, TRANSFER exists
        if (preg_match($dontofferpattern, $tldclass) || (
            !isset($relations["PRICE_CLASS_DOMAIN_{$tldclass}_SETUP"]) &&
            !isset($relations["PRICE_CLASS_DOMAIN_{$tldclass}_ANNUAL"]) &&
            !isset($relations["PRICE_CLASS_DOMAIN_{$tldclass}_TRANSFER"]))
        ) {
            continue;
        }
        //get currency
        $currency = $relations[$ctype];
        //get tld label; check if tldclass exeption is defined
        $tld = isset($tldlib[$tldclass]) ? $tldlib[$tldclass] : strtolower($tldclass);
        //define pattern
        $pattern ="/^PRICE_CLASS_DOMAIN_{$tldclass}_(SETUP|ANNUAL|TRANSFER)$/i";
        $types = preg_grep($pattern, $r["PROPERTY"]["RELATIONTYPE"]);
        if (!empty($types)) {
            $prices = array(
                'register' => '',
                'renew' => '',
                'transfer' => '',
                'currency' => $currency
            );
            foreach ($types as $type) {
                //for now we only care about these ones! -> 1Y term
                //* basically (SETUP|ANNUAL|TRANSFER|...)[0-9]* could also appear to provide term specific prices
                //* also handling PROMO relations is not yet covered
                switch (preg_replace("/^.+_/", "", $type)) {
                    case 'SETUP':
                        $prices['register'] = $relations[$type];
                        break;
                    case 'ANNUAL':
                        $prices['renew'] = $relations[$type];
                        break;
                    default: //TRANSFER
                        $prices['transfer'] = $relations[$type];
                        break;
                }
            }
            if ($prices['register'] != '' || $prices['renew'] != '') {
                $prices['register'] = sprintf("%.2f", (floatval($prices['register']) + floatval($prices['renew'])));
            }
            $relationprices[$tld] = $prices;
        }
    }

    $_SESSION["tld-register-renew-transfer-currency-filter"] = $relationprices; //session variable for tld data (tld, prices, currency)
    //smarty template for table in step 2
    $smarty = new Smarty;
    $smarty->compile_dir = $GLOBALS['templates_compiledir'];
    $smarty->caching = false;
    $smarty->assign('tld_register_renew_transfer_currency_filter', $relationprices);
    $smarty->assign('idnmap', $idnmap);
    $smarty->display(dirname(__FILE__).'/templates/step2.tpl');
}

//Import button clicked
//It collects the tlds and the updated prices by user. calls the startimport()
function importButton()
{
    $prices_match_pattern = "/PRICE_(.*)_(.*)/";

    $tld_match = []; //has all the tld names which have new prices
    foreach ($_POST as $key => $value) {
        if (preg_match($prices_match_pattern, $key, $match)) {
            $tld_match[] = $match[1];
        }
    }
    //replace underscores with dots in the tlds
    foreach ($tld_match as $key => $value) {
        $tld_match[$key] = strtolower(str_replace('_', '.', $value));
    }
    //for prices renew, register, transfer
    $price_name_match = []; //has all new prices (strings)
    foreach ($_POST as $key => $value) {
        if (preg_match($prices_match_pattern, $key, $match)) {
            $price_name_match[] = $match[2];
        }
    }
    $tld_new_price = [];
    foreach ($_POST as $key => $value) {
        if (preg_match($prices_match_pattern, $key)) {
            $tld_new_price[] = $value;
        }
    }
    $new_prices_for_whmcs = array_combine_($tld_match, $tld_new_price);

    //for checked items -DNS Management, email Forwarding, id Protection, epp code
    $domain_addons = [];

    $dns_pattern = "/dns_management/";
    foreach ($_POST as $key => $value) {
        if (preg_match($dns_pattern, $key)) {
            $domain_addons['dns-management'] = $value;
        }
    }
    $emailforwarding_pattern = "/email_forwarding/";
    foreach ($_POST as $key => $value) {
        if (preg_match($emailforwarding_pattern, $key)) {
            $domain_addons['email-forwarding'] = $value;
        }
    }
    $idprotection_pattern = "/id_protection/";
    foreach ($_POST as $key => $value) {
        if (preg_match($idprotection_pattern, $key)) {
            $domain_addons['id-protection'] = $value;
        }
    }
    $eppcode_pattern = "/epp_code/";
    foreach ($_POST as $key => $value) {
        if (preg_match($eppcode_pattern, $key)) {
            $domain_addons['epp-code'] = $value;
        }
    }
    //for currency
    $currencies = [];
    $currency_pattern = "/currency/";
    foreach ($_POST as $key => $value) {
        if (preg_match($currency_pattern, $key)) {
            $currencies['currency'] = $value;
        }
    }
    // values in $domain_addons array are -  [dns-management] => checked='checked' !!!
    foreach ($domain_addons as $key => $value) {
        $domain_addons[$key] = 1;
    }

    foreach ($new_prices_for_whmcs as $key => $value) {
        array_push($new_prices_for_whmcs[$key], $domain_addons);
    }

    //to merge each curreny value from currencies array new_prices_for_whmcs
    $i = -1;
    foreach ($new_prices_for_whmcs as $key => $value) {
        $i++;
        $new_prices_for_whmcs[$key]['currency'] = $currencies['currency'][$i];
    }
    //import the data
    startimport($new_prices_for_whmcs);
}

//loop through array and insert or update the tld and prices for whmcs to DB
function startimport($prices_for_whmcs)
{
    $registrars = (new LoadRegistrars())->getLoadedRegistars();
    $registrar = $registrars[0];

    try {
        $pdo = Capsule::connection()->getPdo();

        $prices_for_whmcs = array_change_key_case($prices_for_whmcs, CASE_LOWER);
        
        // Convert TLDs to IDN (as having punycode in DB is not desired)
        $idns = array_keys($prices_for_whmcs);
        $r = Ispapi::call(array(
            "COMMAND"   => "ConvertIDN",
            "DOMAIN"    => $idns
        ));
        if ($r["CODE"] == 200) {
            $idns = $r["PROPERTY"]["IDN"];
        }
        $idx = 0;
        foreach ($prices_for_whmcs as $key => $value) {
            $tld_idn = "." . $idns[$idx];
            $idx++;
            //with TLD/extension
            $stmt = $pdo->prepare("SELECT * FROM tbldomainpricing WHERE extension=?");
            $stmt->execute(array($tld_idn));
            $tbldomainpricing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($tbldomainpricing)) {
                $update_stmt = $pdo->prepare("UPDATE tbldomainpricing SET dnsmanagement=?, emailforwarding=?, idprotection=?, eppcode=? WHERE extension=?");
                $update_stmt->execute(array($prices_for_whmcs[$key][3]['dns-management'], $prices_for_whmcs[$key][3]['email-forwarding'], $prices_for_whmcs[$key][3]['id-protection'], $prices_for_whmcs[$key][3]['epp-code'], $tld_idn));
            } else {
                if (!$prices_for_whmcs[$key][3]['dns-management']) {
                    $prices_for_whmcs[$key][3]['dns-management'] = '';
                }
                if (!$prices_for_whmcs[$key][3]['email-forwarding']) {
                    $prices_for_whmcs[$key][3]['email-forwarding'] = '';
                }
                if (!$prices_for_whmcs[$key][3]['id-protection']) {
                    $prices_for_whmcs[$key][3]['id-protection'] = '';
                }
                if (!$prices_for_whmcs[$key][3]['epp-code']) {
                    $prices_for_whmcs[$key][3]['epp-code'] = '';
                }

                $insert_stmt = $pdo->prepare("INSERT INTO tbldomainpricing ( extension, dnsmanagement, emailforwarding, idprotection, eppcode, autoreg) VALUES ( ?, ?, ?, ?, ?, 'ispapi')");
                $insert_stmt->execute(array($tld_idn, $prices_for_whmcs[$key][3]['dns-management'], $prices_for_whmcs[$key][3]['email-forwarding'], $prices_for_whmcs[$key][3]['id-protection'], $prices_for_whmcs[$key][3]['epp-code']));
                if ($insert_stmt->rowCount() != 0) {
                    $stmt = $pdo->prepare("SELECT id FROM tbldomainpricing WHERE extension=?");
                    $stmt->execute(array($tld_idn));
                    $tbldomainpricing = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            //replace or add pricing for domainregister
            $stmt = $pdo->prepare("SELECT * FROM tblpricing WHERE type='domainregister' AND currency=? AND relid=? ORDER BY id DESC LIMIT 1");
            $stmt->execute(array($prices_for_whmcs[$key]['currency'], $tbldomainpricing['id']));
            $tblpricing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($tblpricing)) {
                $update_stmt=$pdo->prepare("UPDATE tblpricing SET msetupfee=? WHERE id=?");
                $update_stmt->execute(array($prices_for_whmcs[$key][0], $tblpricing["id"]));
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, monthly, quarterly, semiannually, annually, biennially) VALUES ('domainregister', ?, ?, ?, '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1')");
                $insert_stmt->execute(array($prices_for_whmcs[$key]['currency'], $tbldomainpricing['id'], $prices_for_whmcs[$key][0]));
            }

            //replace or add pricing for domainrenew
            $stmt = $pdo->prepare("SELECT * FROM tblpricing WHERE type='domainrenew' AND currency=? AND relid=? ORDER BY id DESC LIMIT 1");
            $stmt->execute(array($prices_for_whmcs[$key]['currency'], $tbldomainpricing['id']));
            $tblpricing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($tblpricing)) {
                $update_stmt=$pdo->prepare("UPDATE tblpricing SET msetupfee=? WHERE id=?");
                $update_stmt->execute(array($prices_for_whmcs[$key][1], $tblpricing["id"]));
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, monthly, quarterly, semiannually, annually, biennially) VALUES ('domainrenew', ?, ?, ?, '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1')");
                $insert_stmt->execute(array($prices_for_whmcs[$key]['currency'], $tbldomainpricing['id'], $prices_for_whmcs[$key][1]));
            }

            //replace or add pricing for domaintransfer
            $stmt = $pdo->prepare("SELECT * FROM tblpricing WHERE type='domaintransfer' AND currency=? AND relid=? ORDER BY id DESC LIMIT 1");
            $stmt->execute(array($prices_for_whmcs[$key]['currency'], $tbldomainpricing['id']));
            $tblpricing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($tblpricing)) {
                $update_stmt=$pdo->prepare("UPDATE tblpricing SET msetupfee=? WHERE id=?");
                $update_stmt->execute(array($prices_for_whmcs[$key][2], $tblpricing["id"]));
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO tblpricing (type, currency, relid, msetupfee, qsetupfee, ssetupfee, asetupfee, bsetupfee, monthly, quarterly, semiannually, annually, biennially) VALUES ('domaintransfer', ?, ?, ?, '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1', '-1')");
                $insert_stmt->execute(array($prices_for_whmcs[$key]['currency'], $tbldomainpricing['id'], $prices_for_whmcs[$key][2]));
            }
        }
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

//download a sample csv file
function download_csv_sample_file()
{
    // output headers so that the file is downloaded rather than displayed
    header('Content-type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=yourpricinglist.csv');
    // do not cache the file
    header('Pragma: no-cache');
    header('Expires: 0');
    //create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    fputcsv($output, array('TLD','REGISTER_PRICE_USD','TRANSFER_PRICE_USD','RENEW_PRICE_USD'), ";");
    fputcsv($output, array('com', '10.99', '10.99', '11.59'), ";");
    fputcsv($output, array('com.au', '12.99', '10.45', '15.59'), ";");
    exit(0);
}

//###### Helper functions ######
function array_combine_($keys, $values)
{
    $result = array();
    foreach ($keys as $i => $k) {
        $result[$k][] = $values[$i];
    }
    array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
    return $result;
}

//filter tld data array for only tlds with usd currency
function filter_array($array, $term)
{
    $matches = array();
    foreach ($array as $key => $value) {
        if ($value['currency'] == $term) {
            $matches[$key]=$value;
        }
    }
    return $matches;
}
