<?php

/**
 * paz is a simple packaging and deployment tool for PHP projects on Windows Azure.
 * Given a simple application needing only a webrole, paz will automagically 
 * convert the application from a regular application folder to a Windows Azure
 * application package ready to upload or run in the local development environment
 * 
 * Want to contribute? Please keep all code in this single self contained file :) 
 * 
 * @author Ben Lobaugh <ben@lobaugh.net>
 * @url http://ben.lobaugh.net
 * @license 100% free to use and modify. Contributors are not responsible for any damage. Please send back any improvements
 *
 * @todo Allow paz to be called from inside a PHP app instead of just the commandline
 * @todo add param that will use a particular scaffold
 * @todo Add ability to deploy from command line
 */

require_once '../lib/Params.class.php';
require_once '../lib/FileSystem.class.php';

$p = new Params();
$fs = new FileSystem();

/*
 * Ensure all parameters needed are set to something
 */
checkParams();


/*
 * If the user simply wants to deploy a pre-created package they can do so
 * by specifying a folder containing a *.cspkg and a ServiceConfiguration.cscfg
 */
if(is_dir($p->get('deploy'))) {
    
    echo "\n**** DEPLOYMENT PROCESS STILL IN DEVELOPMENT ****"; exit();
    echo "\nAttempting to deploy to Windows Azure from pre-created package";
    
    if(($cspkg = $fs->findByExtension($p->get('deploy'), 'cspkg')) && $fs->exists($p->get('deploy') . "/ServiceConfiguration.cscfg")) {
        $cscfg = $p->get('deploy') . "/ServiceConfiguration.cscfg";
        // ****** call deploy tool using $cspkg and $cscfg
    }
    
    exit();
}

/*
 * Create a project out of a default scaffolder
 */
echo "\nCreating temp build files...\n";
exec("scaffolder run -out=" . $p->get('tempBuild'));

/*
 * Copy the source project files to the scaffold project
 */
echo "\nCreating application...";
$fs->copy($p->get('in'), $p->get('tempBuild') . "/PhpOnAzure.Web");

/*
 * If needed, copy in the Windows Azure SDK for PHP Microsoft folder
 */
if(!$p->get('noSDK')) {
    echo "\nCopying Windows Azure SDK for PHP Microsoft folder to project";
    if($fs->exists($p->get('sdkPath') . "/trunk/library/Microsoft")) {
        $sdk = $p->get('sdkPath') . "/trunk/library";
    } else {
        $sdk = $p->get('sdkPath') . "/library";
    }

    $fs->copy($sdk, $p->get('tempBuild') . "/PhpOnAzure.Web");
}

/*
 * If the ServiceConfiguration.cscfg and ServiceDefinition.csdef files
 * exist in the project dir move them out and use them instead of the supplied
 * defaults
 * 
 * NOTE: Will need to ensure the proper startup scripts are still in place
 */
if($fs->exists($p->get('tempBuild') . "/PhpOnAzure.Web/ServiceConfiguration.cscfg") && $fs->exists($p->get('tempBuild') . "/PhpOnAzure.Web/ServiceDefinition.csdef")) {
    echo "\nCustom .cscfg and .csdef file found. Using";
    $fs->rm($p->get('tempBuild') . "/ServiceConfiguration.cscfg");
    $fs->rm($p->get('tempBuild') . "/ServiceDefinition.csdef");
    $fs->move($p->get('tempBuild') . "/PhpOnAzure.Web/ServiceDefinition.csdef", $p->get('tempBuild') . "/ServiceDefinition.csdef");
    $fs->move($p->get('tempBuild') . "/PhpOnAzure.Web/ServiceConfiguration.cscfg", $p->get('tempBuild') . "/ServiceConfiguration.cscfg");
}

/*
 * Build the package
 */
echo "\nCreating the package...";
//echo "\npackage create -in={$cmdparams['tempBuild']} -out={$cmdparams['out']} -dev={$cmdparams['dev']}";
exec("package create -in=" . $p->get('tempBuild') . " -out=" . $p->get('out') . " -dev=" . $p->get('dev'));

echo "\n\nPackage created in " . $p->get('out') . "\n\n";
die();  // *************** LEFT OFF HERE *************************8888
/**
 * If the user wants to deploy do so now
 */
if(!$p->get('dev') && $p->get('deploy')) {
    echo "\n**** DEPLOYMENT PROCESS STILL IN DEVELOPMENT ****"; exit();
    echo "\nDeploying package to Windows Azure";
}

/*
 * Clean up the temp build directory
 */
//rrmdir($cmdparams['tempBuild']);


function displayHelp() {
    echo "\nSimple packaging and deployment tool for PHP project on Windows Azure";
    echo "\n\nOriginally developed by Ben Lobaugh 2011 <ben@lobaugh.net>";
    echo "\n\nParameters:";
    echo "\n\thelp - Display this menu";
    echo "\n\tin - Source of PHP project";
    echo "\n\tout - Output of Windows Azure package. If not specified the project directory from -in will be used";
    echo "\n\tdev - If flag present local development environment will be used";
    echo "\n\tdeploy - Immediatly attempts to deploy the created package to Windows Azure. \n\t\tUsed as a flag, the created package will be uploaded. \n\t\tOptionally supply a path to the folder containing the .cspkg and ServiceConfiguration.cscfg";
    echo "\n\tnoSDK - If present will not copy the Windows Azure SDK for PHP Microsoft folder to project";
    echo "\n\tsdkPath - Override default Windows Azure SDK for PHP path if not default install";
    echo "\n\ttempBuild - Override the temp build location";
    echo "\n\n\nSee https://github.com/blobaugh/paz for documentation\n";
}


function checkParams() {
    global $p, $fs;
    
    // -help - Optional
    if($p->get('help') || $p->count() < 2) {
        displayHelp();
        exit();
    }
    
    /*
     * If the user did not specify a temp build directory one will be created
     */
    if(!$p->get('tempBuild')) { 
        if(strstr(strtolower(PHP_OS), "win") != false) {
            $p->set('tempBuild', "C:\\temp\\paz_build");
        } else {
            $p->set('tempBuild', "/tmp/paz_build");
        }
    }

    /*
     * Ensure the temp build directory exists and remove old builds if they exist
     */
    if(is_dir($p->get('tempBuild'))) {
        $fs->rm($p->get('tempBuild'));
    }
    $fs->mkdir($p->get('tempBuild'));

    /*
     * Figure out what directory will be used for source input
     */
    if(!$p->get('in')) {
        $p->set('in', __DIR__);
    }

    /*
     * Ensure output directory exists and remove any old output
     * 
     * If no output is specified create an output dir in source
     */
    if(!$p->get('out')) {
        $p->set('out', $p->get('in') . "/paz_build");
    }
    if(is_dir($p->get('out'))) {
        $fs->rm($p->get('out'));
    }
    $fs->mkdir($p->get('out'));

    /*
     * Set build target to local dev or cloud
     */
    if(!$p->get('dev')) {
        $p->set('dev', false);
    }
    
    /*
     * Figure out if there is a need to include the Windows Azure SDK for PHP Microsoft folder
     */
    if(!$p->get('noSDK')) {
        $p->set('noSDK', false);
    }
    
    /*
     * Allow user to override the regular Windows Azure SDK for PHP path
     */
    if(!$p->get('sdkPath')) {
        if(strstr(strtolower(PHP_OS), "win") != false) {
            $p->set('sdkPath', "C:\\Program Files\\Windows Azure SDK for PHP");
        } else {
            $p->set('sdkPath', "/usr/local/Windows Azure SDK for PHP");
        }
    }
}