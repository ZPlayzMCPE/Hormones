<?php

/*
 * Hormone
 *
 * Copyright (C) 2015 PEMapModder and contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . "Hormones";

$opts = getopt("", ["out:"]);
if(!isset($opts["out"])){
	die("Usage: " . PHP_BINARY . " --out <output directory>");
}
/** @noinspection PhpUsageOfSilenceOperatorInspection */
@mkdir($opts["out"]);
$opts["out"] = rtrim($opts["out"], "/\\") . "/";

$hash = "";
/** @var \SplFileInfo $file */
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__)) as $file){
	if($file->isFile()){
		$hash .= md5_file($file);
		$hash = md5($hash);
	}
}

$mainPath = $opts["out"] . "Hormones.phar";
$phar = new Phar($mainPath);
$phar->setStub('<?php require_once "phar://" . __FILE__ . "/entry.php"; __HALT_COMPILER();');
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->startBuffering();
$phar->buildFromDirectory($dir);
$phar->addFromString("build.info", json_encode([
	"timestamp" => time(),
		"checksum" => $hash,
]));
$phar->stopBuffering();
echo "Hormones build created at " . realpath($mainPath), PHP_EOL;

$utilsPath = $opts["out"] . "HormonesUtils.phar";
$phar = new Phar($utilsPath);
$phar->setStub('<?php require_once "phar://" . __FILE__ . "/entry.php"; __HALT_COMPILER();');
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->startBuffering();
$phar->buildFromDirectory($dir);
$phar->addFromString("build.info", json_encode([
	"timestamp" => time(),
		"checksum" => $hash,
]));
$phar->stopBuffering();
echo "HormonesUtils build created at " . realpath($utilsPath), PHP_EOL;

echo "Checksum: ", $hash, PHP_EOL;

exec("git add -A", $output);
