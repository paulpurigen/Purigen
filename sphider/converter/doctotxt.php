<?php

function doctotxt($file){

    $string_ascii	= '';
    $fp 			= @fopen($file,'rb');

    while (($fp != false) && !feof($fp)){

        $string = bin2hex(fread($fp,filesize($file)));

echo "\r\n\r\n<br /> string0: '$string'<br />\r\n";        

        // Remove unreadable data at the start of the doc and remove useless blanks
        $string = substr($string, strpos($string,'00d9000000')+10);
		$string = preg_replace("/00000000000000/", "", $string);
echo "\r\n\r\n<br /> string1: '$string'<br />\r\n";        

        // Transform hexa to ascii
        for ($i=0; $i<strlen($string); $i+=2){

            // 2 hexa chars
            $car=substr($string,$i,2);

            // Remove NUL and replace line
            if ($car!='00')

                if ($car!='0d')
                    $car=chr(hexdec($car));
                else $car = "\r\n";

            else $car = '';
            $string_ascii.=$car;

        }
    }

    fclose($fp);
    return $string_ascii;

}

 

?>