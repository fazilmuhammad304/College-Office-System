<?php
// google_drive.php
require_once 'google-api-php-client/vendor/autoload.php';

function uploadToDrive($localFilePath, $fileName)
{
    $client = new Google_Client();
    $client->setAuthConfig('oauth_credentials.json');
    $client->addScope(Google_Service_Drive::DRIVE_FILE);

    // 1. நாம் உருவாக்கிய Token-ஐ லோட் செய்தல் (User Mode)
    if (file_exists('token.json')) {
        $accessToken = json_decode(file_get_contents('token.json'), true);
        $client->setAccessToken($accessToken);
    } else {
        // Token இல்லை என்றால் எரர் காட்டும்
        die("Error: token.json missing! Please run get_token.php once.");
    }

    // 2. Token காலாவதியாகி இருந்தால் புதுப்பித்தல் (Auto Refresh)
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $newAccessToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents('token.json', json_encode($client->getAccessToken()));
        } else {
            die("Error: Refresh Token missing. Run get_token.php again.");
        }
    }

    $service = new Google_Service_Drive($client);

    // ------------------------------------------
    // உங்கள் FOLDER ID (சரியாக உள்ளதா என பார்க்கவும்)
    // ------------------------------------------
    $folderId = '1GrePb32G1Q66cILZBXr6hVqzSJHK-ObE';

    $fileMetadata = new Google_Service_Drive_DriveFile(array(
        'name' => $fileName,
        'parents' => array($folderId)
    ));

    $content = file_get_contents($localFilePath);

    // 3. அப்லோட் செய்தல்
    $file = $service->files->create($fileMetadata, array(
        'data' => $content,
        'mimeType' => mime_content_type($localFilePath),
        'uploadType' => 'multipart',
        'fields' => 'id, webViewLink'
    ));

    // 4. Permission தேவையில்லை (இது உங்கள் சொந்த டிரைவ்)
    // ஆனால் மற்றவர்கள் பார்க்க வேண்டும் என்றால் இதை அன்கமெண்ட் செய்யவும்:

    $permission = new Google_Service_Drive_Permission();
    $permission->setRole('reader');
    $permission->setType('anyone');
    $service->permissions->create($file->id, $permission);

    // 5. லிங்க்-ஐ திருப்பியனுப்புதல்
    return $file->webViewLink;
}
