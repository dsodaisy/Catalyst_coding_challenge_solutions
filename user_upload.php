<?php

/*Create a PHP script, that is executed from the command line, which accepts a CSV file as input
(see command line directives below) and processes the CSV file. The parsed file data is to be
inserted into a MySQL database. A CSV file is provided as part of this task that contains test
data, the script must be able to process this file appropriately.*/

//obtaining and storing CLI arguments passed to the php script
require_once 'PHPExcel/Classes/PHPExcel.php';
require_once 'Console/Table.php';
$shortopts="";
$shortopts.="u:";//Required value - username
$shortopts.="p:";//Required value - password
$shortopts.="h:";//Required value - hostname
$longopts=array("file:","create_table","dry_run");//File name is a required option with value, while creat_table & dry_run do not require values
$arg_options=getopt($shortopts,$longopts);//stores CLI arguments passed to the script

//begin validating arguments passed
if($argc==1) //if only php file name is provided
	echo "***********************************************\nPlease enter the csv file to be parsed. Please also provide the database credentials and the create table option if you would like to enter the csv file's data into the DB.\n  An example is as follows:\n > php user_upload.php --file <xyz.csv> --create_table -u <MySQL username> -p <MySQL password> -h <MySQL host>\n  Alternatively type the below command to get a list of command line options you can use:\n > php user_upload.php --help\n***********************************************";
elseif($argc==2 && $argv[1]=='--help')//if only php file name and --help is provided
	echo file_get_contents("help.txt");
elseif(!isset($arg_options['file']))//if csv file name is not provided
{
	echo "---\nPlease provide the CSV file that you want to parse by using the --file option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
	exit();
}
else //if all the parameters are entered as expected, proceed with validating the parameters/arguments provided
{
	$excelReader = PHPExcel_IOFactory::createReaderForFile($arg_options['file']);
	$excelObj = $excelReader->load($arg_options['file']);
	$worksheet = $excelObj->getSheet(0);
	$lastRow = $worksheet->getHighestRow(); //to get the last row in the sheet
	$all_Rows=array();
	$one_element=0; //flag to indicate if at least 1 record is successfully read from the csv file
	for($row=2;$row<($lastRow+1);$row++)//iterate through each row
	{
		$rowdata=array();
		for($col=0;$col<3;$col++)//iterate through each column
		{
			$cell_value=trim(strtolower($worksheet->getCellByColumnAndRow($col,$row)->getValue()));//get cell value
			if($col!=2)
			{
				$cell_value=ucfirst($cell_value);
				$cell_value=preg_replace("/[^a-zA-Z-' ]*$/","",$cell_value);//remove anything other than alphabets, -, & ' from names
			}
			else //need to validate names as well
			{
				if(!filter_var($cell_value,FILTER_VALIDATE_EMAIL))
					array_push($rowdata,'NULL'); //invalid email address indicated by 'NULL'
				elseif($one_element!=0)
				{
					for($i=0;$i<count($all_Rows);$i++)
					{
						if($cell_value==end($all_Rows[$i]))
							array_push($rowdata, ''); //duplicate email address indicated by ''
					}
				}
			}
			array_push($rowdata,$cell_value);
			$one_element=1; //indicates at least 1 entry read from csv file successfully.
		}
		array_push($all_Rows,$rowdata);//$all_Rows contains an array of all csv file data
	}
	if(isset($arg_options['dry_run']))//if dry_run is set
	{
		echo "---\nSince the 'dry_run' flag is set, the data has not been entered into the MySQL table.\nBelow is a preview of the data (first three columns) that would be entered if dry_run was not enabled.\n";
		$table=new Console_Table();//to display in a clear table format via the CLI
		$table->setHeaders(array('Name','Surname','Email'));
		$j=0;
		for($i=0;$i<count($all_Rows);$i++)//iterate through $all_Rows
		{
			if($all_Rows[$i][2]=='NULL')
			{
				$table->addRow(array($all_Rows[$i][0],$all_Rows[$i][1],$all_Rows[$i][3],"This record will not be entered into the DB as the email address format is invalid!"));
			}
			elseif($all_Rows[$i][2]=='')
			{
				$table->addRow(array($all_Rows[$i][0],$all_Rows[$i][1],$all_Rows[$i][3],"This record will not be entered into the DB as this email address is already assigned to another user!"));
			}
			else
			{
				$table->addRow(array($all_Rows[$i][0],$all_Rows[$i][1],$all_Rows[$i][2]));
			}
		}
		echo $table->getTable();//display table
		if(isset($arg_options['create_table'])) //if create_table is set
		{
			if(!isset($arg_options['u']))//if DB username not provided
			{
				echo "---\nUnfortunately, we cannot create the table 'users' as the MySQL username has not been provided.\nPlease provide the MySQL username with the -u option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
				exit();
			}
			elseif(!isset($arg_options['p']))//if DB password not provided
			{
				echo "---\nUnfortunately, we cannot create the table 'users' as the MySQL password has not been provided.\nPlease provide the MySQL password with the -p option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
				exit();
			}
			elseif(!isset($arg_options['h']))//if DB hostname not provided
			{
				echo "---\nUnfortunately, we cannot create the table 'users' as the MySQL hostname has not been provided.\nPlease provide the MySQL hostname with the -h option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
				exit();
			}
			$dsn="mysql:host=".$arg_options['h'].";dbname=users_db;";
			$options=
			[
				PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
	 			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
	  			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
			];
			try//attempt to connect to db
			{
	  			$pdo = new PDO($dsn, $arg_options['u'], $arg_options['p'], $options);
			}
			catch (Exception $e)
			{
				error_log($e->getMessage());
	  			exit('Unfortunately, there was an error connecting to the database. Please try again with the right database credentials and hostname. Alternatively, please provide the above error code/statement to your administrator to rectify this error.');
			}
			$stmt=$pdo->prepare("DROP TABLE IF EXISTS `users`");
			if($stmt->execute())
			{	
				$sql="CREATE TABLE `users` (ID INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR (20) NOT NULL,surname VARCHAR(20) NOT NULL, email VARCHAR(50) NOT NULL UNIQUE)";
				$stmt=$pdo->prepare($sql);
				if($stmt->execute())//if table 'users' created successfully
				{
					echo "---\nTable 'users' created successfully! However, no record has been inserted into the DB as 'dry_run' is enabled.\n---\n";
					exit();
				}
				else//if table 'users' not created successfully
				{
					print_r($stmt->errorInfo());
					exit("Unfortunately, there was an error in creating the table 'users'. Please contact your administrator with the above error code/statement to help resolve this issue.");
				}
			}
			else //if error in dropping already existing table 'users'
			{
				print_r($stmt->errorInfo());
				exit("Unfortunately, there was an error while attempting to re-create table 'users'. Please contact your administrator with the above error code/statement to help resolve this issue");
			}
		}
		else//if create_table is not set
		{
			echo "Since the 'create_table' flag is not set, the table 'users' has not been created\n";
			exit();
		}
	}
	else//if dry_run is not set, need to create table and insert into table
	{
		if(isset($arg_options['create_table'])) //if create_table is set
		{
			if(!isset($arg_options['u']))//if DB username not provided
			{
				echo "---\nUnfortunately, we cannot create the table 'users' as the MySQL username has not been provided.\nPlease provide the MySQL username with the -u option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
				exit();
			}
			elseif(!isset($arg_options['p']))//if DB password not provided
			{
				echo "---\nUnfortunately, we cannot create the table 'users' as the MySQL password has not been provided.\nPlease provide the MySQL password with the -p option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
				exit();
			}
			elseif(!isset($arg_options['h']))//if DB hostname not provided
			{
				echo "---\nUnfortunately, we cannot create the table 'users' as the MySQL hostname has not been provided.\nPlease provide the MySQL hostname with the -h option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
				exit();
			}
			$dsn="mysql:host=".$arg_options['h'].";dbname=users_db;";
			$options=
			[
				PDO::ATTR_EMULATE_PREPARES   => false, // turn off emulation mode for "real" prepared statements
	 			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, //turn on errors in the form of exceptions
	  			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //make the default fetch be an associative array
			];
			try//attempt to connect to db
			{
	  			$pdo = new PDO($dsn, $arg_options['u'], $arg_options['p'], $options);
			}
			catch (Exception $e)
			{
				error_log($e->getMessage());
	  			exit('Unfortunately, there was an error connecting to the database. Please try again with the right database credentials and hostname. Alternatively, please provide the above error code/statement to your administrator to rectify this error.');
			}
			$stmt=$pdo->prepare("DROP TABLE IF EXISTS `users`");
			if($stmt->execute())
			{	
				$sql="CREATE TABLE `users` (ID INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR (20) NOT NULL,surname VARCHAR(20) NOT NULL, email VARCHAR(50) NOT NULL UNIQUE)";
				$stmt=$pdo->prepare($sql);
				if($stmt->execute())//if table 'users' created successfully
				{
					echo "---\nTable 'users' created successfully!\n---\n";
					for($i=0;$i<count($all_Rows);$i++)
					{
						if(($all_Rows[$i][2]!='NULL')&&($all_Rows[$i][2]!='')) //if valid email format, record to be inserted to table
						{
							try//try to insert into DB
							{
								$sql='INSERT INTO `users` (name,surname,email) VALUES ("'.$all_Rows[$i][0].'","'.$all_Rows[$i][1].'","'.$all_Rows[$i][2].'")';
								$stmt=$pdo->prepare($sql);
								if($stmt->execute())//if insert into table is successful
								{
									//do nothing upon successful insertion of individual records
								}
								else //if insert into table is unsucessful
								{
									print_r($stmt->errorInfo());
									exit("Unfortunately, there was an error in inserting the record(s) into the table 'users'. Please contact your administrator with the above error code/statement to help resolve this issue.");
								}
							}
							catch(Exception $e)
							{
								error_log($e->getMessage());
								exit("Unfortunately, there was an error during table insertion. Please contact your administrator with the above error code/statement to help resolve this error.");
							}
						}
						else //if invalid email format, no record to be inserted to table
						{
							if($all_Rows[$i][2]=='NULL')
								echo "Record number ".($i+1)." could not be entered into the table as the email address is not in a valid format. Please ensure to enter the correct email address in the csv file and try again.\n";
							else
								echo "Record number ".($i+1)." could not be entered into the table as the email address is already assigned to another user. Please ensure to enter the correct email address in the csv file and try again.\n";
						}
					}
					$sql="SELECT count(*) FROM `users`";
					$stmt=$pdo->prepare($sql);
					if($stmt->execute())//obtain no. of records successfully entered into DB
					{
						$records_inserted=$stmt->fetchColumn();
						echo "---\n".$records_inserted." records entered successfully into the table 'users'";
					}
				}
				else //if error in creating table 'users'
				{
					print_r($stmt->errorInfo());
					exit("Unfortunately, there was an error in creating the table 'users'. Please contact your administrator with the above error code/statement to help resolve this issue.");
				}
			}
			else //if error in dropping table 'users' that already exists
			{
				print_r($stmt->errorInfo());
				exit("Unfortunately, there was an error while attempting to re-create table 'users'. Please contact your administrator with the above error code/statement to help resolve this issue");
			}
		}
		else //if create_table & dry_run is not set
		{
			echo "---\nSince the 'create_table' flag is not set, the data cannot be entered into the MySQL table as the table 'users' has not been created.\nBelow is a preview of the data (first three columns) that would be entered if create_table was enabled.\n";
			$table=new Console_Table();//to display data in clear table format via CLI
			$table->setHeaders(array('Name','Surname','Email'));
			$j=0;
			for($i=0;$i<count($all_Rows);$i++)//iterate through $all_Rows
			{
				if($all_Rows[$i][2]=='NULL')
				{
					$table->addRow(array($all_Rows[$i][0],$all_Rows[$i][1],$all_Rows[$i][3],"This record will not be entered into the DB as the email address format is invalid!"));
				}
				elseif($all_Rows[$i][2]=='')
				{
					$table->addRow(array($all_Rows[$i][0],$all_Rows[$i][1],$all_Rows[$i][3],"This record will not be entered into the DB as this email address is already assigned to another user!"));
				}
				else
				{
					$table->addRow(array($all_Rows[$i][0],$all_Rows[$i][1],$all_Rows[$i][2]));
				}
			}
			echo $table->getTable();//display table
			echo "Please indicate whether you want to create the MySQL table by using the --create_table option and try again. Alternatively use the below command to view possible options you can use\n > php user_upload.php --help";
			exit();
		}
	}
}
?>

