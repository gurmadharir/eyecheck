<?php
require_once __DIR__ . '/../../config/db.php';

/**
 * Fetches a patient's profile using the user ID.
 *
 * @param int|string $user_id - The ID of the logged-in user.
 * @return array - Patient data or empty defaults if not found.
 */
function getPatientProfile($user_id) {
    global $pdo;

    // Validate user ID as integer
    $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
    if (!$user_id) {
        return [
            'readonly' => false,
            'name' => '',
            'contact' => '',
            'town' => '',
            'gender' => '',
            'dob' => '',
            'region' => '',
        ];
    }

    try {
        $stmt = $pdo->prepare("SELECT name, contact, town, gender, dob, region FROM patients WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            return array_merge(['readonly' => true], $patient);
        }
    } catch (PDOException $e) {
        // Log error or notify dev if needed
    }

    return [
        'readonly' => false,
        'name' => '',
        'contact' => '',
        'town' => '',
        'gender' => '',
        'dob' => '',
        'region' => '',
    ];
}
