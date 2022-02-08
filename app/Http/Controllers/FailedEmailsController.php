<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reports\FailedEmails;

class FailedEmailsController extends Controller
{
    public function show(Request $request)
    {
        $failedEmails = (new FailedEmails('-1 year'))();
        return view('internal/failed-emails', ['failed_emails' => $failedEmails]);
    }
}
