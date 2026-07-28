<?php
require_once '../../config/connection.php';
require_once '../../config/staff-session-check.php';

try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in.");
    }

    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }

    ////////////////// Variables //////////////////
    $pageId = trim($_GET['pageId'] ?? '');
    $pagePixArr = $_FILES['pagePixArr']['name'] ?? [];
    ////////////////// Validation //////////////////
    validateEmptyField($pageId, 'PAGE ID');
    if (count($pagePixArr) === 0) {
        throw new BadRequestException("NO FILES SELECTED! Please select files to upload.");
    }
    ////////////////// Upload Page Pictures //////////////////
    $pagePixNames = [];
    foreach ($pagePixArr as $i => $imageName) {
        $imageExt = pathinfo($imageName, PATHINFO_EXTENSION);
        $newImageName = $pageId . "_{$i}_" . uniqid() . "." . $imageExt;

        insertQuery(
            $conn,
            "INSERT INTO PAGE_PICTURES_TAB (pageId, pagePix, createdTime) VALUES (?,?,NOW())",
            "ss",
            [$pageId, $newImageName]
        );

        $pagePixNames[] = $newImageName;
    }


    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PROCEED TO UPLOAD! Presigned links generated successfully.",
        'pagePixNames' => $pagePixNames
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>