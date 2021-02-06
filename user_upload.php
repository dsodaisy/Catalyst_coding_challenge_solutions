<?php

/*Create a PHP script, that is executed from the command line, which accepts a CSV file as input
(see command line directives below) and processes the CSV file. The parsed file data is to be
inserted into a MySQL database. A CSV file is provided as part of this task that contains test
data, the script must be able to process this file appropriately.*/

//obtaining and storing CLI arguments passed to the php script
require_once 'PHPExcel/Classes/PHPExcel.php';
//require_once(BASE_PATH . '/PHPExcel/Classes/PHPExcel.php');
$shortopts="";
$shortopts.="u:";//Required value - username
$shortopts.="p:";//Required value - password
$shortopts.="h:";//Required value - hostname
$longopts=array("file:","create_table","dry_run");//File name is a required option with value, while creat_table & dry_run do not require values
$options=getopt($shortopts,$longopts);//stores CLI arguments passed to the script

//begin validating arguments passed
if(!isset($options['file']))
{
	echo "Please provide the CSV file that you want to parse by using the --file option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
	return false;
}
elseif(!isset($options['create_table']))
{
	echo "Please indicate whether you want to create the MySQL table by using the --create_table option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
	return false;
}
elseif(!isset($options['u']))
{
	echo "Please provide the MySQL username with the -u option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
	return false;
}
elseif(!isset($options['p']))
{
	echo "Please provide the MySQL password with the -p option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
	return false;
}
elseif(!isset($options['h']))
{
	echo "Please provide the MySQL hostname with the -h option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
	return false;
}


print_r($options); //******
if($argc==1)
	echo "***********************************************\nPlease enter the csv file to be parsed along with the database credentials and the create table option.\n  An example is as follows:\n > php user_upload.php --file <xyz.csv> --create_table -u <MySQL username> -p <MySQL password> -h <MySQL host>\n  Alternatively type the below command to get a list of command line options you can use:\n > php user_upload.php --help\n***********************************************";
elseif($argc==2 && $argv[1]=='--help')
	echo file_get_contents("help.txt");

//***
//other conditions of options combinations need to be included here
//***


else //if all the parameters are entered as expected, proceed with validating the parameters/arguments provided
{
	$excelReader = PHPExcel_IOFactory::createReaderForFile($options['file']);
	$excelObj = $excelReader->load($options['file']);
	$worksheet = $excelObj->getSheet(0);
	$lastRow = $worksheet->getHighestRow(); //to get the last row in the sheet
	$all_Rows=array();
	for($row=2;$row<($lastRow+1);$row++)
	{
		$rowdata=array();
		for($col=0;$col<3;$col++)
		{
			$cell_value=trim(strtolower($worksheet->getCellByColumnAndRow($col,$row)->getValue()));
			if($col!=2)
				$cell_value=ucfirst($cell_value);
			array_push($rowdata,$cell_value);
		}
		array_push($all_Rows,$rowdata);
	}
	print_r($all_Rows);//*****
	
}
?>

