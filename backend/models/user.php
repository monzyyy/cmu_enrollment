<?php

function user_find_by_account_number(
    mysqli $conn,
    string $account_number
): ?array
{
    $stmt = $conn->prepare(
        'SELECT
            user_id,
            account_number,
            password_hash,
            role,
            is_active
        FROM users
        WHERE account_number = ?
        LIMIT 1'
    );

    $stmt->bind_param('s', $account_number);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}