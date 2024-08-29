<?php

// Define the public directory path
$publicPath = __DIR__ . '/public';

// Extract the request URI
$requestUri = $_SERVER['REQUEST_URI'];

// Set the base URL
$baseUrl = str_replace('/public', '', $requestUri);

// Create a new request with the base URL
$_SERVER['REQUEST_URI'] = $baseUrl;

// Include the Laravel bootstrap file
chdir("public/");
require $publicPath . '/index.php';
