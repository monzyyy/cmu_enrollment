<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');


try {

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        throw new Exception('Invalid request.');
    }


    $courseId = (int) ($data['course_id'] ?? 0);
    $program = trim($data['program'] ?? '');
    $yearLevel = (int) ($data['year_level'] ?? 0);
    $section = trim($data['section'] ?? '');
    $semester = trim($data['semester'] ?? '');
    $schoolYear = trim($data['school_year'] ?? '');
    $instructorName = trim($data['instructor_name'] ?? '');

    $schedules = $data['schedules'] ?? [];


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($courseId <= 0) {
        throw new Exception('Please select a course.');
    }

    if ($program === '') {
        throw new Exception('Please select a program.');
    }

    if ($yearLevel < 1 || $yearLevel > 4) {
        throw new Exception('Please select a valid year level.');
    }

    if ($section === '') {
        throw new Exception('Please select a section.');
    }

    if ($semester === '') {
        throw new Exception('Please select a semester.');
    }

    if ($schoolYear === '') {
        throw new Exception('Please enter the school year.');
    }


    /*
    |--------------------------------------------------------------------------
    | Check Course
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'SELECT course_id
         FROM courses
         WHERE course_id = ?
           AND is_active = 1
         LIMIT 1'
    );

    $stmt->bind_param(
        'i',
        $courseId
    );

    $stmt->execute();

    $course = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$course) {
        throw new Exception(
            'The selected course is not available.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Start Transaction
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Offering
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'SELECT offering_id
         FROM course_offerings
         WHERE course_id = ?
           AND program = ?
           AND year_level = ?
           AND section = ?
           AND semester = ?
           AND school_year = ?
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->bind_param(
        'isisss',
        $courseId,
        $program,
        $yearLevel,
        $section,
        $semester,
        $schoolYear
    );

    $stmt->execute();

    $existing = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if ($existing) {

        throw new Exception(
            'This course is already assigned to the selected section.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Insert Course Offering
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'INSERT INTO course_offerings (
            course_id,
            program,
            year_level,
            section,
            semester,
            school_year,
            instructor_name
         )
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $instructorValue = $instructorName !== ''
        ? $instructorName
        : null;

    $stmt->bind_param(
        'isissss',
        $courseId,
        $program,
        $yearLevel,
        $section,
        $semester,
        $schoolYear,
        $instructorValue
    );

    $stmt->execute();

    $offeringId = $stmt->insert_id;

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Insert Schedules
    |--------------------------------------------------------------------------
    */

    if (is_array($schedules)) {

        foreach ($schedules as $schedule) {

            if (!is_array($schedule)) {
                continue;
            }

            $day = trim($schedule['day'] ?? '');
            $startTime = trim($schedule['start_time'] ?? '');
            $endTime = trim($schedule['end_time'] ?? '');
            $room = trim($schedule['room'] ?? '');


            /*
             * Skip completely empty schedule rows.
             */

            if (
                $day === '' &&
                $startTime === '' &&
                $endTime === '' &&
                $room === ''
            ) {
                continue;
            }


            /*
             * If a schedule is partially filled,
             * require the basic schedule information.
             */

            if (
                $day === '' ||
                $startTime === '' ||
                $endTime === ''
            ) {
                throw new Exception(
                    'Please complete each schedule row.'
                );
            }


            $roomValue = $room !== ''
                ? $room
                : null;


            $stmt = $conn->prepare(
                'INSERT INTO course_offering_schedules (
                    offering_id,
                    day_of_week,
                    start_time,
                    end_time,
                    room
                 )
                 VALUES (?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'issss',
                $offeringId,
                $day,
                $startTime,
                $endTime,
                $roomValue
            );

            $stmt->execute();

            $stmt->close();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' => 'Course offering added successfully.'
    ]);


} catch (Throwable $e) {

    if ($conn->errno === 0) {
        // No active database error.
    }

    if ($conn->in_transaction) {
        $conn->rollback();
    }


    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}