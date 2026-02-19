<?php

use Carbon\Carbon;

if (! function_exists('convertToIST')) {
    function convertToIST($dateTime)
    {
        // Parse the date-time and convert it to IST (Indian Standard Time)
        return Carbon::parse($dateTime)->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
    }
}
