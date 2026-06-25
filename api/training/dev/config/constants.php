<?php
/////// developed by Mike Afolabi on 19-02-2025//////////////////////
$appName = "Afootech Global Training Program";
$appDescription = "Join AfooTECH Global ICT Training Centre and learn practical digital skills including software development, networking, UI/UX design, graphics design, and more.";

////////////////////////////////////////////////////////////////////////
$userOsBrowser = isset($_SERVER['HTTP_USEROSBROWSER']) ? $_SERVER['HTTP_USEROSBROWSER'] : null;
$userIpAddress = isset($_SERVER['HTTP_USERIPADDRESS']) ? $_SERVER['HTTP_USERIPADDRESS'] : null;
$frontEndApiKey = isset($_SERVER['HTTP_APIKEY']) ? $_SERVER['HTTP_APIKEY'] : null;
////////////////////////////////////////////////////////////////////////

/// all constance
$websiteUrl = 'https://afootech.com';
$backEndApiKey = '0cda191ec51136e7e3d60195ec753d30'; //afootechglobal@june232026


// Read the raw JSON input
$json = file_get_contents('php://input');
// Decode the JSON into an associative array
$data = json_decode($json, true);

$checkBasicSecurity = true;
///// check for API security
if ($frontEndApiKey != $backEndApiKey) {/// start if 1
    $checkBasicSecurity = false;
}

///// check for userOsBrowser security
if (empty($userOsBrowser)) {/// start if 1
    $checkBasicSecurity = false;
}

///// check for userIpAddress security
if (empty($userIpAddress)) {/// start if 1
    $checkBasicSecurity = false;
}