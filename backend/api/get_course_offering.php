<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_role('ADMIN');

header('Content-Type: application/json');


try {

    $offeringId = (int) ($_GET['offering_id'] ?? 0);


    if ($offeringId <= 0) {
        throw new Exception(
            'Invalid course offering.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Offering
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'SELECT
            co.offering_id,
            co.program,
            co.year_level,
            co.section,
            co.semester,
            co.school_year,
            co.instructor_name,
            c.course_code,
            c.course_name,
            c.units
         FROM course_offerings co
         INNER JOIN courses c
            ON c.course_id = co.course_id
         WHERE co.offering_id = ?
         LIMIT 1'
    );

    $stmt->bind_param(
        'i',
        $offeringId
    );

    $stmt->execute();

    $offering = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$offering) {
        throw new Exception(
            'Course offering not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Schedules
    |--------------------------------------------------------------------------
    */

    $schedules = [];


    $stmt = $conn->prepare(
        'SELECT
            schedule_id,
            day_of_week,
            start_time,
            end_time,
            room
         FROM course_offering_schedules
         WHERE offering_id = ?
         ORDER BY schedule_id ASC'
    );

    $stmt->bind_param(
        'i',
        $offeringId
    );

    $stmt->execute();

    $result = $stmt->get_result();


    while ($row = $result->fetch_assoc()) {

        $schedules[] = [
            'schedule_id' => $row['schedule_id'],
            'day' => $row['day_of_week'],
            'start_time' => substr(
                $row['start_time'],
                0,
                5
            ),
            'end_time' => substr(
                $row['end_time'],
                0,
                5
            ),
            'room' => $row['room']
        ];

    }


    $stmt->close();


    echo json_encode([
        'success' => true,
        'offering' => $offering,
        'schedules' => $schedules
    ]);


} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}