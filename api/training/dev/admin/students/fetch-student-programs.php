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


    $studentId = trim($_GET['studentId'] ?? '');
    validateEmptyField($studentId, "STUDENT ID");
    $studentProgramId = trim($_GET['studentProgramId'] ?? '');

    $selectQuery = "SELECT * FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE studentId = ?";
    $types = "s";
    $params = [$studentId];

    $studentProgramData = selectQuery($conn, $selectQuery, $types, $params);

    ////////////////// Process Data //////////////////

    foreach ($studentProgramData as &$studentProgram) {
        $programId = $studentProgram['programId'];
        $courseId = $studentProgram['courseId'];
        $durationId = $studentProgram['durationId'];
        $certificateStatusId = $studentProgram['certificateStatusId'];
        $trainingStatusId = $studentProgram['trainingStatusId'];
        $updatedBy = $studentProgram['updatedBy'];

        /// get programData
        $programData = _get_program_details($conn, $programId);
        $studentProgram['programData'] = $programData;

        /// get courseData
        $courseData = _get_course_details($conn, $courseId);
        $studentProgram['courseData'] = $courseData;

        /// get durationData
        $durationData = _get_program_course_duration_details($conn, $durationId);
        $studentProgram['durationData'] = $durationData;

        $certificateStatusData = _get_status_details($conn, $certificateStatusId);
        $studentProgram['certificateStatusData'] = $certificateStatusData;

        $trainingStatusData = _get_status_details($conn, $trainingStatusId);
        $studentProgram['trainingStatusData'] = $trainingStatusData;

        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $studentProgram['updatedByData'] = $updatedByData;

        if (!empty($studentProgramId)) {
            $selectInstituteQuery = "SELECT * FROM STUDENTS_INSTITUTION_DETAILS_TAB WHERE studentId=? AND studentProgramId = ?";
            $instituteParams = [$studentId, $studentProgramId];
            $instituteData = selectQuery($conn, $selectInstituteQuery, 'ss', $instituteParams)[0];

            $institutionTypeId = $instituteData['institutionTypeId'];
            $levelId = $instituteData['levelId'];

            /// get institutionTypeData
            $institutionTypeData = _get_institution_type_details($conn, $institutionTypeId);
            $instituteData['institutionTypeData'] = $institutionTypeData;

            /// get levelData
            $levelData = _get_institution_level_details($conn, $institutionTypeId, $levelId);
            $instituteData['levelData'] = $levelData;

            $studentProgram['institutionData'] = $instituteData; // Reindex the array with the updated institute data
        }
    }



    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STUDENT PROGRAMS FETCH SUCCESSFULLY!",
        'allRecordCount' => count($studentProgramData),
        'data' => $studentProgramData,
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>