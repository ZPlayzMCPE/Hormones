<?php

/*
 *
 * Hormones
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

if(!isset($argv[1])){
	echo "Usage: php $argv[0]";
	exit(2);
}

$action = $argv[1];

if($action === "phagocyte" || $action === "p"){
	return require "phar:///" . __FILE__ . "stubs/phagocyte.php";
}
