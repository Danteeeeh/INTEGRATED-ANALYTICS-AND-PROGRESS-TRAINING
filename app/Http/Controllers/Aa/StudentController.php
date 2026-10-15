<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only student listing for AA Staff — view only.
 */
class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $studentRoleId = Role::where('slug', Role::STUDENT)->value('id');

        $students = User::where('role_id', $studentRoleId)
            ->with(['department', 'program', 'section'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('identifier', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('aa.students.index', compact('students'));
    }
}
