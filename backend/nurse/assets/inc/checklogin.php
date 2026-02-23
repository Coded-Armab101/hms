<?php
function check_login()
{
if(strlen($_SESSION['ns_id'])==0)
	{
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="index.php";
		$_SESSION["ns_email"]="";
		header("Location: http://$host$uri/$extra");
	}
}
?>
