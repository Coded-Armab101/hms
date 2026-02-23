<?php
function check_login()
{
if(strlen($_SESSION['lab_id'])==0)
	{
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="index.php";
		$_SESSION["lab_email"]="";
		header("Location: http://$host$uri/$extra");
	}
}
?>
