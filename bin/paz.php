<?php
set_time_limit(0);
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
require_once '../lib/Ini.class.php';

$p = new Params();
$fs = new FileSystem();



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
if($fs->exists($p->get('sdkPath') . "/trunk/library/Microsoft")) {
    $sdk = $p->get('sdkPath') . "/trunk/library";
} else {
    $sdk = $p->get('sdkPath') . "/library";
}

/*
 * Ensure all parameters needed are set to something
 */
checkParams();


/*
 * If the user simply wants to deploy a pre-created package they can do so
 * by specifying a folder containing a *.cspkg and a ServiceConfiguration.cscfg
 */
if(is_dir($p->get('deploy'))) {
    
    
    // Yes, I am using nested if statements instead of &&s. This is for readibility
    if($p->get('deployment_name')) {
        echo "\nAttempting to deploy to Windows Azure from pre-created package";

        if(($cspkg = $fs->findByExtension($p->get('deploy'), 'cspkg')) && $fs->exists($p->get('deploy') . "/ServiceConfiguration.cscfg")) {
            $cscfg = $p->get('deploy') . "/ServiceConfiguration.cscfg";
            // ****** call deploy tool using $cspkg and $cscfg
            // deployment createfromlocal -F="C:\config.ini" --Name="dns_prefix" --DeploymentName="deployment_name" --Label="deployment_label" --Staging --PackageLocation="path\to\your.cspkg" --ServiceConfigLocation="path\to\ServiceConfiguration.cscfg" --StorageAccount="your_storage_account_name"
           
//            $cmd = "deployment createfromlocal --Name=\"" . $p->get('deployment_name'). "\"";
//            $cmd .= " --DeploymentName=\"" . $p->get('deployment_name') . "\"";
//            $cmd .= " --Label=\"" . $p->get('deployment_name') . "\"";
//            $cmd .= " --PackageLocation=\"" . $cspkg[0] . "\"";
//            $cmd .= " --ServiceConfigLocation=\"" . $cscfg . "\"";
//            $cmd .= " --StorageAccount=\"" . $config_ini->get('Azure', 'StorageAccount') . "\"";
//            $cmd .= " -sid=\"" . $config_ini->get('Azure', 'SubscriptionId') . "\"";
//            $cmd .= " -cert=\"" . $config_ini->get('Azure', 'PemCertificate') . "\"";
            
            $deploymentSlot = "production";
            if(!$p->get('deploy_prod')) {
                $deploymentSlot = "staging";
            }
            
        //    deploy($config_ini->get('Azure', 'SubscriptionId'), $config_ini->get('Azure', 'PemCertificate'), $p->get('deployment_name'), $p->get('deployment_name'), $p->get('deployment_name'),
       //$deploymentSlot, $cspkg[0], $cscfg, $config_ini->get('Azure', 'StorageAccount'));
         deploy($cspkg[0], $cscfg);   
            //echo "\n\n$cmd\n\n";
            // exec($cmd);
            //echo $config_ini;
            
            //echo "\n\n**** NOT ACTUALLY DEPLOYING!! ****";
            
        }
    } else {
        echo "\nPlease supply a deployment name with -deployment_name";
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
/**
 * If the user wants to deploy do so now
 */
if(!$p->get('dev') && $p->get('deploy')) {
    echo "\nDeploying package to Windows Azure";
    $cspkg = $fs->findByExtension($p->get('out'), 'cspkg');
    $cscfg = $p->get('out') . "/ServiceConfiguration.cscfg";
    deploy($cspkg[0], $cscfg);
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
    echo "\n\tdeployment_name - The DNS prefix that will be used (required by -deploy)";
    echo "\n\tdeploy_prod - If present will deploy to the production slot. Defaults to staging (only valid when used with -deploy)";
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
}
    
            
function deploy($cspkg, $cscfg) { 
    global $sdk, $p;

     $config_ini = new Ini();
     $config_ini->open(__DIR__ . "/../config.ini");

     $subscriptionId = $config_ini->get('Azure', 'SubscriptionId');
     $certificate = $config_ini->get('Azure', 'PemCertificate');
     $serviceDnsPrefix = $p->get('deployment_name');
     $deploymentName = $p->get('deployment_name');
     $label = $p->get('deployment_name');
     
     $deploymentSlot = 'production';
     if(!$p->get('deploy_prod')) {
         $deploymentSlot = 'staging';
     }
     
     $packageLocation = $cspkg;
     $serviceConfigurationLocation = $cscfg;
     $storageAccount = $config_ini->get('Azure', 'StorageAccount');
     
     
    require_once "$sdk/Microsoft/AutoLoader.php";
    $startImmediately = true;
    $warningsAsErrors = false;
    $waitForOperation = false;
    echo "\n\nCreating deployment from local package";

    $client = new Microsoft_WindowsAzure_Management_Client($subscriptionId, $certificate, '');
    echo "\nGetting blob client for service";
    $blobClient = $client->createBlobClientForService($storageAccount);
    $blobClient->createContainerIfNotExists('phpazuredeployments');
    
    echo "\nUploading " . $cspkg . "...";
    $blobClient->putBlob('phpazuredeployments', basename($cspkg), $cspkg);
    $package = $blobClient->getBlobInstance('phpazuredeployments', basename($cspkg));
    
    echo "\n\nCreating deployment...";
    $client->createDeployment($serviceDnsPrefix, $deploymentSlot, $deploymentName, $label, $package->Url, ($serviceConfigurationLocation), $startImmediately, $warningsAsErrors);

    echo "\nWaiting for operation to complete...";
    $client->waitForOperation();
    echo "\nRemoving package from blob...";
   // $blobClient->deleteBlob('phpazuredeployments', basename($packageLocation));


    if ($waitForOperation) {
            echo "\nWaiting for operation to complete...";
            $client->waitForOperation();
    }
}