<?php

// Start Sessions
session_start();

// Development error reporting without notices
error_reporting(E_ALL ^ E_NOTICE);

// Set default value for user_id if not logged in
if (!$_SESSION['user_id']) {
    $_SESSION['user_id'] = null;
}

// Create users folder if it doesn't exist
if (!is_dir("../users")) {
    // Create Directory
    mkdir("../users");

    // Modify directory permissions
    chmod("../users", 0744);
}

// Require Composer Autoloader
require '../vendor/autoload.php';

// Require Config
require '../config/config.php';

// Require Setup
require 'setup.php';

// Unset Messages
Utilities::removeMessage();

// Create new Slim instance
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../templates', [
        'cache' => false
    ]);
    
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    return $view;
};

// Require Routes
require "routes.php";