
<?php



function checkEvaluationCompletion(
    mysqli $conn,
    int $studentId,
    string $semester,
    string $schoolYear
): array {

    $sql = "
        SELECT
            COUNT(*) AS total_subjects,

            COALESCE(SUM(
                CASE
                    WHEN co.instructor_name IS NULL
                      OR TRIM(co.instructor_name) = ''
                    THEN 1 ELSE 0
                END
            ), 0) AS missing_professors,

            COALESCE(SUM(
                CASE
                    WHEN pe.evaluation_id IS NOT NULL
                    THEN 1 ELSE 0
                END
            ), 0) AS completed_evaluations

        FROM student_course_enrollments sce

        INNER JOIN course_offerings co
            ON co.offering_id = sce.offering_id

        LEFT JOIN professor_evaluations pe
            ON pe.offering_id = co.offering_id
            AND pe.student_id = sce.student_id

        WHERE sce.student_id = ?
          AND co.semester = ?
          AND co.school_year = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iss",
        $studentId,
        $semester,
        $schoolYear
    );

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $total = (int)$result['total_subjects'];
    $missing = (int)$result['missing_professors'];
    $completed = (int)$result['completed_evaluations'];

    return [
        'total' => $total,
        'completed' => $completed,
        'missing_professors' => $missing,
        'all_evaluated' => (
            $total > 0 &&
            $missing === 0 &&
            $completed === $total
        )
    ];
}



function checkFacultyFinalization(
    mysqli $conn,
    int $studentId,
    string $semester,
    string $schoolYear
): bool {

    $sql = "
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(
                CASE WHEN faf.finalization_id IS NULL
                THEN 1 ELSE 0 END
            ), 0) AS unfinalized

        FROM student_course_enrollments sce

        INNER JOIN course_offerings co
            ON co.offering_id = sce.offering_id

        LEFT JOIN faculty_assignment_finalizations faf
            ON faf.program = co.program
            AND faf.year_level = co.year_level
            AND faf.section = co.section
            AND faf.semester = co.semester
            AND faf.school_year = co.school_year

        WHERE sce.student_id = ?
          AND co.semester = ?
          AND co.school_year = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iss",
        $studentId,
        $semester,
        $schoolYear
    );

    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (
        (int)$row['total'] > 0 &&
        (int)$row['unfinalized'] === 0
    );
}

 
function evaluationPreviousTerm(string $semester, string $schoolYear): array
{
    if (!preg_match('/^(\d{4})\s*-\s*(\d{4})$/', trim($schoolYear), $m)) {
        throw new RuntimeException('Invalid enrollment school year.');
    }
    $start = (int) $m[1];
    $end = (int) $m[2];
    if ($end !== $start + 1) {
        throw new RuntimeException('Invalid enrollment school year range.');
    }
    $separator = preg_match('/\s-\s/', $schoolYear) ? ' - ' : '-';
    return match ($semester) {
        '1st Semester' => ['2nd Semester', ($start - 1) . $separator . $start],
        '2nd Semester' => ['1st Semester', $start . $separator . $end],
        'Summer' => ['2nd Semester', $start . $separator . $end],
        default => throw new RuntimeException('Invalid enrollment semester.'),
    };
}



function syncCompletedEvaluationPhase(mysqli $conn, int $studentId): void
{
    $settingsResult = $conn->query(
        "SELECT semester, school_year, enrollment_status FROM system_settings WHERE setting_id = 1 LIMIT 1"
    );
    $settings = $settingsResult->fetch_assoc();
    if (!$settings || $settings['enrollment_status'] !== 'OPEN') {
        return;
    }

    [$semester, $schoolYear] = evaluationPreviousTerm(
        $settings['semester'], $settings['school_year']
    );
    $progress = checkEvaluationCompletion($conn, $studentId, $semester, $schoolYear);
    if (!$progress['all_evaluated']) {
        return;
    }

    $stmt = $conn->prepare(
        "UPDATE students
         SET enrollment_phase = 'EVALUATION', enrollment_status = 'Evaluation'
         WHERE student_id = ? AND enrollment_phase = 'NOT_STARTED'"
    );
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $stmt->close();
}
