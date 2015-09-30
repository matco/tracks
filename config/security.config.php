<?php
//sanity check of http parameters
if($_GET) {
	foreach($_GET as $index => $valeur) {
		if(is_string($_GET[$index])) {
			$_GET[$index] = htmlentities($valeur, ENT_QUOTES, 'UTF-8');
		}
	}
}
if($_POST) {
	foreach($_POST as $index => $valeur) {
		if(is_string($_POST[$index])) {
			$_POST[$index] = htmlentities($valeur, ENT_QUOTES, 'UTF-8');
		}
	}
}
