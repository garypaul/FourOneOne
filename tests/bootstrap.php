<?php

// Grab Autoloader
require './vendor/autoload.php';

// Initiate Twig
$loader = new Twig_Loader_Filesystem('/templates');
$twig = new Twig_Environment( $loader );