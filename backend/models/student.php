<?php

function student_find_by_number(mysqli $conn, string $student_number): ?array
{
    $stmt = $conn->prepare(
        'SELECT
            s.student_id,
            s.user_id,
            s.student_number,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.program,
            s.year_level,
            s.section,
            s.email,
            s.phone,
            u.password_hash,
            u.role,
            s.enrollment_phase
         FROM students s
         INNER JOIN users u
            ON u.user_id = s.user_id
         WHERE s.student_number = ?
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
            s.student_id,
            s.user_id,
            s.student_number,
            s.first_name,
            s.middle_name,
            s.last_name,
            s.program,
            s.year_level,
            s.section,
            s.email,
            s.phone,
            u.role,
            s.enrollment_phase
         FROM students s
         INNER JOIN users u
            ON u.user_id = s.user_id
         WHERE s.student_id = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $student_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}