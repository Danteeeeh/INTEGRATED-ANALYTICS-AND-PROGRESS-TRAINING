<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(): View
    {
        $classes = ClassModel::with(['course', 'instructor', 'enrollments'])
            ->orderBy('code')
            ->paginate(15);

        return view('registrar.classes.index', compact('classes'));
    }

    public function show(ClassModel $class): View
    {
        $class->load(['course', 'instructor', 'enrollments.student']);

        return view('registrar.classes.show', compact('class'));
    }
}
