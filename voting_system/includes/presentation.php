<?php

if (!function_exists('presentation_mode_enabled')) {
    function presentation_mode_enabled(): bool
    {
        $value = strtolower(trim((string) getenv('PRESENTATION_MODE')));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('database_ready')) {
    function database_ready($conn): bool
    {
        return is_object($conn);
    }
}

if (!function_exists('presentation_elections')) {
    function presentation_elections(): array
    {
        return [
            [
                'election_id' => 1,
                'title' => 'Student Leaders Election',
                'description' => 'Presentation mode sample election',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+2 days')),
            ],
            [
                'election_id' => 2,
                'title' => 'Department Delegates Poll',
                'description' => 'Presentation mode sample election',
                'start_date' => date('Y-m-d', strtotime('+7 days')),
                'end_date' => date('Y-m-d', strtotime('+9 days')),
            ],
        ];
    }
}

if (!function_exists('presentation_public_results')) {
    function presentation_public_results(): array
    {
        return [
            'Male Delegate' => [
                ['candidate_id' => 1, 'name' => 'Brian Otieno', 'image_url' => '', 'position_name' => 'Male Delegate', 'total_votes' => 54],
                ['candidate_id' => 2, 'name' => 'Kevin Mwangi', 'image_url' => '', 'position_name' => 'Male Delegate', 'total_votes' => 41],
            ],
            'Female Delegate' => [
                ['candidate_id' => 3, 'name' => 'Mercy Achieng', 'image_url' => '', 'position_name' => 'Female Delegate', 'total_votes' => 63],
                ['candidate_id' => 4, 'name' => 'Faith Wanjiru', 'image_url' => '', 'position_name' => 'Female Delegate', 'total_votes' => 37],
            ],
            'Departmental Delegate' => [
                ['candidate_id' => 5, 'name' => 'Ian Kiptoo', 'image_url' => '', 'position_name' => 'Departmental Delegate', 'total_votes' => 48],
                ['candidate_id' => 6, 'name' => 'Sharon Mutheu', 'image_url' => '', 'position_name' => 'Departmental Delegate', 'total_votes' => 44],
                ['candidate_id' => 7, 'name' => 'Peter Odhiambo', 'image_url' => '', 'position_name' => 'Departmental Delegate', 'total_votes' => 28],
            ],
        ];
    }
}

if (!function_exists('presentation_notice')) {
    function presentation_notice(): string
    {
        return 'Presentation mode is active. Live database features are temporarily replaced with demo data.';
    }
}
