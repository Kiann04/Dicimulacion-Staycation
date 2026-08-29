<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Guest rules
    |--------------------------------------------------------------------------
    |
    | A staycation sleeps up to "max_guests". Guests beyond "free_guest_threshold"
    | are billed a flat "extra_guest_fee" each for the whole stay.
    |
    */

    'max_guests' => (int) env('BOOKING_MAX_GUESTS', 8),

    'free_guest_threshold' => (int) env('BOOKING_FREE_GUEST_THRESHOLD', 6),

    'extra_guest_fee' => (float) env('BOOKING_EXTRA_GUEST_FEE', 500),

    /*
    |--------------------------------------------------------------------------
    | Stay length
    |--------------------------------------------------------------------------
    */

    'min_nights' => (int) env('BOOKING_MIN_NIGHTS', 1),

    'max_nights' => (int) env('BOOKING_MAX_NIGHTS', 30),

    /*
    |--------------------------------------------------------------------------
    | How far ahead a stay may be booked, in days.
    |--------------------------------------------------------------------------
    */

    'max_advance_days' => (int) env('BOOKING_MAX_ADVANCE_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Deposit
    |--------------------------------------------------------------------------
    |
    | Fraction of the total collected when a customer chooses the "half" option.
    |
    */

    'deposit_ratio' => (float) env('BOOKING_DEPOSIT_RATIO', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Payment proof storage
    |--------------------------------------------------------------------------
    |
    | Proofs contain personal financial information and are stored on a private
    | disk. They are only ever served through an authorized controller action.
    |
    */

    'proof_disk' => env('BOOKING_PROOF_DISK', 'local'),

    'proof_directory' => 'payment_proofs',

    'proof_max_kilobytes' => (int) env('BOOKING_PROOF_MAX_KB', 5120),

    /*
    |--------------------------------------------------------------------------
    | Accepted payment methods
    |--------------------------------------------------------------------------
    */

    'payment_methods' => ['gcash', 'bpi'],

];
