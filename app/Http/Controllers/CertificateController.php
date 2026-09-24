<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function verify(string $code): View
    {
        $certificate = Certificate::where('verification_code', $code)
            ->where('status', Certificate::STATUS_ISSUED)
            ->first();

        if (! $certificate) {
            abort(404);
        }

        $verificationData = [
            'certificate_number' => $certificate->certificate_number,
            'issue_date' => $certificate->issued_at ?? $certificate->completion_date,
            'course_title' => $certificate->course_name_display ?? $certificate->course?->title,
            'student_name' => $certificate->student_name_display ?? $certificate->student?->name,
            'status' => $certificate->status,
            'final_grade' => $certificate->final_grade,
        ];

        return view('certificate.verify', ['certificate' => $verificationData, 'valid' => true]);
    }
}
