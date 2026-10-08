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


    $offeringId = (int) ($data['offering_id'] ?? 0);
    $instructorName = trim($data['instructor_name'] ?? '');
    $schedules = $data['schedules'] ?? [];


    if ($offeringId <= 0) {
        throw new Exception('Invalid course offering.');
    }


    if (!is_array($schedules)) {
        throw new Exception('Invalid schedule data.');
    }


    /*
    |--------------------------------------------------------------------------
    | Check Offering
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'SELECT offering_id
         FROM course_offerings
         WHERE offering_id = ?
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
    | Start Transaction
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();


    /*
    |--------------------------------------------------------------------------
    | Update Instructor
    |--------------------------------------------------------------------------
    */

    $instructorValue = $instructorName !== ''
        ? $instructorName
        : null;


    $stmt = $conn->prepare(
        'UPDATE course_offerings
         SET instructor_name = ?
         WHERE offering_id = ?'
    );

    $stmt->bind_param(
        'si',
        $instructorValue,
        $offeringId
    );

    $stmt->execute();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Remove Existing Schedules
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'DELETE FROM course_offering_schedules
         WHERE offering_id = ?'
    );

    $stmt->bind_param(
        'i',
        $offeringId
    );

    $stmt->execute();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Add Updated Schedules
    |--------------------------------------------------------------------------
    */

    foreach ($schedules as $schedule) {

        if (!is_array($schedule)) {
            continue;
        }


        $day = trim($schedule['day'] ?? '');
        $startTime = trim($schedule['start_time'] ?? '');
        $endTime = trim($schedule['end_time'] ?? '');
        $room = trim($schedule['room'] ?? '');


        /*
         * Ignore completely empty rows.
         */

        if (
            $day === '' &&
            $startTime === '' &&
            $endTime === '' &&
            $room === ''
        ) {
            continue;
        }


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


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' => 'Course offering updated successfully.'
    ]);


} catch (Throwable $e) {

    if ($conn->in_transaction) {
        $conn->rollback();
    }


    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}