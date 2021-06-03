<?php
/* set ts=8 sw=8 enc=utf-8: -*- Mode: php; tab-width: 8; coding: utf-8 -*- */
/**
 * @file   mm.php
 * @brief  Magic Mirror script
 * @date   3 June 2021
 * @author cwyang
 * @copyright Apache
 */

// Object Generating Magic Mirror

/* Usage: 
   mm.php?HTTP_HEADER=HTTP_VALUE&...&TYPE=text|mp4&LEN=num&RATE=rate&SEED=num
   HTTP_HEADER should be prefixed with 'xx'; e.x: xxLast-Modified-Time
*/

global $objlen, $objtype, $otype, $delay, $seed;

$otype=array('text' => 1,
             'mp4' => 2);
$objtype=$otype['text'];
$objlen=1200;
$delay = 0;
$seed = 'ara';
$status_code = 200;
$dump_reqhdr_prefix = 'X-REQ-';

//------------------------------
function get_client_ip() 
{
        return $_SERVER['REMOTE_ADDR'];
}

function generate_obj($type, $len, $delay, $seed) 
{
	$pattern=$seed.str_repeat("#",1010-strlen($seed));

        if ($delay)
//                ob_implicit_flush(true);
                ob_end_flush();
	
	
        while ($len >= 1024) {
		$off_str=$len.str_repeat("#",13-strlen($len));
                printf("%s%s\n",$pattern,$off_str);
                $len -= 1024;
                usleep($delay);
        }
        print(substr($pattern.str_repeat("#",13),0,$len));

}

function generate_hdr($get) 
{
        global $objlen, $objtype, $otype, $delay, $seed, $status_code, $dump_reqhdr_prefix;
        
        $now = time();
        
        foreach ($get as $name => $value) {
                switch (strtolower($name)) {
                case "len":
                        $objlen = intval($value);
                        header("Content-Length: $objlen");
                        break;
                case "type":
                        $objtype = $otype[$value] ? $otype[$value] : $otype['text'];
                        
                        switch ($objtype) {
                        case $otype['text']:
                                header("Content-Type: text/html");
                                break;
                        case $otype['mp4']:
                                header("Content-Type: video/mp4");
                                break;
                        }
                        break;
		case "rate":
			header("X-ARA-QOS: $value");
			break;
		case "seed":
                        switch ($value) {
                        case "rand":
                                $seed = $value;
                                break;
                        default:
                                $seed = rand();
                        }
			break;
                case "delay":
                        $delay = intval($value) * 1000; // msec
                        break;
                case "dump_reqhdr":
                        foreach($_SERVER as $h=>$v)
                                if (substr($h, 0, 5) == "HTTP_")
                                        header("$dump_reqhdr_prefix$h: $v");
                        break;
                case "status":
                        $status_code = intval($value);
                        break;
                default:
                        if (strtolower(substr($name,0,2)) != "xx")
                                break;
                        switch ($value) {
                        case "now":
                                $value=gmdate("D, d M Y H:i:s",$now) . " GMT";
                                break;
                        case "client_ip_addr":
                                $value=get_client_ip();
                                break;
                        }
                        header(substr($name,2) . ": $value");
                }
        }
}

//------------------------------
generate_hdr($_GET);
if ($status_code == 304)
        header("HTTP/1.1 304 Not Modified");
else
        generate_obj($objtype, $objlen, $delay, $seed);

//print_r($_GET);
//extract($_GET, EXTR_PREFIX_ALL, 'x');



?>
