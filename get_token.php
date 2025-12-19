<?php
// get_token.php
require_once 'google-api-php-client/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setAuthConfig('oauth_credentials.json'); // Step 1-ல் டவுன்லோட் செய்த ஃபைல்
$client->setRedirectUri('http://localhost/system/get_token.php'); // Cloud Console-ல் கொடுத்த அதே URL
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline'); // Refresh Token-க்காக
$client->setPrompt('select_account consent');

if (isset($_GET['code'])) {
    // கூகுளில் இருந்து Code வந்துவிட்டது, Token-ஐ வாங்குவோம்
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        // Token-ஐ சேமிக்கவும்
        file_put_contents('token.json', json_encode($token));
        echo "<h1>Success! Token Saved.</h1>";
        echo "You can now delete this file and start uploading.";
    } else {
        echo "Error: " . $token['error'];
    }
} else {
    // லாகின் செய்யச் சொல்லுவோம்
    $authUrl = $client->createAuthUrl();
    echo "<a href='$authUrl' style='font-size:20px; font-weight:bold;'>Click here to Connect Google Drive</a>";
}
