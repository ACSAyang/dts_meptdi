<?php

return [

    /*
    | Office code used as the prefix of every reference number,
    | e.g. MEPTDI-IN-2026-0001 / MEPTDI-OUT-2026-0001.
    */
    'office_code' => env('DTS_OFFICE_CODE', 'MEPTDI'),
    'office_name' => 'Marine Environmental Protection Training and Doctrine Institute',
    'parent_command' => 'Marine Environmental Protection Command',
    'service' => 'Philippine Coast Guard',
    'system_name' => 'Communications and Document Tracking System',
    'reference_padding' => 4,

    /*
    | Official seals (transparent PNGs cropped from the approved artwork).
    | The placeholder is used only if the MEPTDI seal file is missing.
    */
    'logo' => 'images/meptdi-logo.png',
    'parent_logo' => 'images/mepcom-logo.png',
    'service_logo' => 'images/pcg-logo.png',
    'logo_placeholder' => 'images/meptdi-logo-placeholder.svg',
    'favicon' => 'favicon.png',

    'document_types' => [
        'Letter', 'Memorandum', 'Message / Radio', 'Directive / Order', 'Endorsement',
        'Report', 'Invitation', 'Request', 'Certificate', 'Others',
    ],

    'sensitivities' => [
        'unclassified' => 'Unclassified',
        'restricted' => 'Restricted',
        'confidential' => 'Confidential',
    ],

    'priorities' => [
        'routine' => 'Routine',
        'priority' => 'Priority',
        'urgent' => 'Urgent',
    ],

    'receipt_modes' => [
        'hand_carried' => 'Hand-carried',
        'courier' => 'Courier / Mail',
        'email' => 'E-mail',
        'fax' => 'Fax',
        'radio' => 'Radio / Message Center',
        'other' => 'Other',
    ],

    'dispatch_modes' => [
        'liaison' => 'Liaison (hand-carry)',
        'courier' => 'Courier / Mail',
        'email' => 'E-mail',
        'fax' => 'Fax',
        'radio' => 'Radio / Message Center',
        'other' => 'Other',
    ],

    'incoming_statuses' => [
        'registered' => 'Registered',
        'routed' => 'Routed',
        'in_action' => 'In Action',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ],

    'outgoing_statuses' => [
        'registered' => 'Registered',
        'dispatched' => 'Dispatched',
        'follow_up' => 'For Follow-up',
        'received' => 'Received – For Verification',
        'closed' => 'Received & Verified',
        'cancelled' => 'Cancelled',
    ],

    'delivery_results' => [
        'delivered' => 'Delivered',
        'failed' => 'Failed – addressee unavailable',
        'refused' => 'Refused',
        'rescheduled' => 'Rescheduled',
    ],

    'attachment_categories' => [
        'document' => 'Document copy',
        'proof_of_receipt' => 'Proof of receipt',
        'acknowledgment' => 'Signed acknowledgment',
        'other' => 'Other',
    ],

    'attachment_max_kb' => 20480,
    'attachment_mimes' => 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx,tif,tiff',

    'backup' => [
        'path' => env('DTS_BACKUP_PATH', storage_path('app/backups')),
        'mysqldump' => env('DTS_MYSQLDUMP_PATH', 'mysqldump'),
        'mysql' => env('DTS_MYSQL_PATH', 'mysql'),
        'restore_test_database' => env('DTS_RESTORE_TEST_DB', 'meptdi_dts_restore_test'),
    ],
];
