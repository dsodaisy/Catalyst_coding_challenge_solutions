<?php

/*AIM: Create a PHP script that is executed form the command line. The script should:
	• Output the numbers from 1 to 100
	• Where the number is divisible by three (3) output the word “foo”
	• Where the number is divisible by five (5) output the word “bar”
	• Where the number is divisible by three (3) and (5) output the word “foobar”
	• Only be a single PHP file
*/

//The only difference between foobar.php and this file (foobar_v2.php) is that for the last number , i.e., 100, this file will not output a comma (,) at the end.
for($i=1; $i<101; $i++)
{
	if($i==100)
		echo "bar";
	elseif($i%15==0)
		echo "foobar, ";
	elseif ($i%5==0)
		echo "bar, ";
	elseif ($i%3==0)
		echo "foo, ";
	else echo $i.", ";
}
?>
