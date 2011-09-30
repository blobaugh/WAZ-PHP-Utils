<?php

require_once '../lib/Params.class.php';
require_once '../lib/FileSystem.class.php';
require_once '../lib/Ini.class.php';

$p = new Params();
$fs = new FileSystem();



/*
 * Ensure all parameters needed are set to something
 */
checkParams();

echo "\n\nCreating " . $p->get('name') . " scaffold";
$fs->copy(__DIR__ . "/../etc/Scaffold", $p->get('out'));

updateScaffoldName();

if($p->get('in')) {
    echo "\nImporting project files";
    $fs->copy($p->get('in'), $p->get('out') . "/resources/WebRole/");
}

function updateScaffoldName() {
    global $p;
    
    // Update index.php
    $contents = file_get_contents($p->get('out') . "/index.php");
    $contents = str_replace('{{ScaffoldName}}', $p->get('name'), $contents);
    file_put_contents($p->get('out') . "/index.php", $contents);
}



function displayHelp() {
    echo "\nSimple packaging and deployment tool for PHP project on Windows Azure";
    echo "\n\nOriginally developed by Ben Lobaugh 2011 <ben@lobaugh.net>";
    echo "\n\nParameters:";
    echo "\n\thelp - Display this menu";
    echo "\n\t[in] - Optional, Source of PHP project to put inside scaffold";
    echo "\n\tout - Output of Windows Azure package";
    echo "\n\tname - Name of new scaffold";
    echo "\n\n\nSee https://github.com/blobaugh/Windows-Azure-PHP-Scaffold-Generator for documentation\n";
}

function checkParams() {
    global $p, $fs;
    
    
    if($p->get('help') || $p->count() < 2) {
        displayHelp();
        exit();
    }
    
    if($p->get('in') && !$fs->exists($p->get('in'))) {
        echo "\n\nInput directory does not exist\n";
        displayHelp();
        exit();
    } 
    
    if(!$p->get('out')) {
       echo "\n\nMissing or invalid -out parameter\n";
        displayHelp();
        exit(); 
    }
    
    if(!$p->get('name')) {
        echo "\n\nMissing or invalid -name parameter\n";
        displayHelp();
        exit();
    }
}