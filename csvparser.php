<?php

class CsvParser 
{
   var $headers = array();
   var $fields = array();
   var $numRecords = 0;

   function CsvParser($file, $readHeaders = true, $delim = ";")
   {
      if (file_exists($file)) {
         $f = fopen($file, "r");
         if (!$f) { echo "Cannot read CSV file"; error_log("Cannot read CSV file ".$file, 0); return; }

         if ($readHeaders === true) {
            $this->headers = $this->getcsv($f, $delim);
         }

         while ($data = $this->getcsv($f, $delim)) {
            $this->fields[] = $data;
            $this->numRecords++;
         }
         fclose($f);
      } else {
         echo "CSV file doesn't exist"; 
         error_log("CSV file ".$file." doesn't exist", 0);
         return;
      }
   }

   function getcsv($fp, $delim)
   {
      if ($f = fgets($fp, 4096))
      	return explode($delim, $f);
      else
      	return false;
   }

   function getRecord($num)
   {
      if ($num >= 0 && $num < $this->numRecords)
      	return $this->fields[$num];
      else
      	return array();
   }
};

?>