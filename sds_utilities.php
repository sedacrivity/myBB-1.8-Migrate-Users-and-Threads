<?php

function sds_echo_linebreak()
{
	echo "<br/>";
}	

function sds_echo_header($info)
{

	echo "<p><b><font color='magenta'>".$info."</font></b></br>";
}

function sds_echo_debug($info)
{

	echo "<br><b><font color='grey'>DEBUG -> ".$info."</font></b>";
}

function sds_echo_info($info)
{

	echo "<br><b><font color='blue'>".$info."</font></b>";
}

function sds_echo_warning($info)
{

	echo "<br><b><font color='orange'>".$info."</font></b>";
}

function sds_echo_success($success)
{

	echo "<br><b><font color='green'>".$success."</font></b>";
}


function sds_echo_error($error)
{

	echo "<br><b><font color='red'>".$error."</font></b>";
}

function sds_display_errors($errors)
{

	sds_display_array($errors);
}

function sds_display_array($array) 
{

foreach($array as $x => $x_value) {
    			echo "Key=" . $x . ", Value=" . $x_value;
			echo "<br>";
	}
}

?>

