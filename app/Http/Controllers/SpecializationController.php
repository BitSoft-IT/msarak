<?php

namespace App\Http\Controllers;

use App\Services\SpecializationCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecializationController extends Controller
{
    public function __construct(
        private readonly SpecializationCatalogService $catalog,
    ) {}

    public function index(): View
    {
        return view('specializations.index', [
            'specializations' => $this->catalog->all(),
        ]);
    }

    public function compare(Request $request): View
    {
        $firstId = $request->query('first');
        $secondId = $request->query('second');

        abort_if(
            ! is_string($firstId) || ! is_string($secondId)
                || $firstId === '' || $secondId === '' || $firstId === $secondId,
            404
        );

        $first = $this->catalog->find($firstId);
        $second = $this->catalog->find($secondId);

        abort_if($first === null || $second === null, 404);

        return view('specializations.compare', compact('first', 'second'));
    }

    public function show(string $specialization): View
    {
        $found = $this->catalog->find($specialization);

        abort_if($found === null, 404);

        return view('specializations.show', ['specialization' => $found]);
    }
}