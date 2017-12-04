<?php  
require_once __DIR__ . '/../vendor/autoload.php';  
use HolidayOrWorkday\holidayOrWorkday;  

//ceshi
var_dump( (new holidayOrWorkday('d6fe2c62790e47db6f904e409483dc72'))->isWorkday('2017-10-1'));