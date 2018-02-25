<?php
spl_autoload_register(function($_class) {

	$path=__DIR__.DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR.strtolower(str_replace("\\", DIRECTORY_SEPARATOR, $_class)).'.php';
	if(!file_exists($path) || !is_file($path)) {
		throw new \Exception("Unable to register ".$_class." from ".$path);
	}
	require($path);
});
