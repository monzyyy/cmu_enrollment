<?php

function student_find_by_number(mysqli $conn, string $student_number): ?array
{
    $stmt = $conn->prepare(
        'SELECT
            student_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            program,
            year_level,
            section AS block_section,
            email,
            phone,
            password_hash,
            enrollment_status
         FROM students
         WHERE student_number = ?
         LIMIT 1'
    );

    $stmt->bind_param('s', $student_number);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}


function student_find_by_id(mysqli $conn, int $student_id): ?array
{
    $stmt = $conn->prepare(
        'SELECT
            student_id,
            student_number,
            first_name,
            middle_name,
            last_name,
            program,
            year_level,
            section AS block_section,
            email,
            phone,
            enrollment_status
         FROM students
         WHERE student_id = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $student_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}