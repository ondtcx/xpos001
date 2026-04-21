<?php

namespace App\Http\Controllers;

use App\Models\BaseUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BaseUnitController extends Controller
{
    public function index(): View
    {
        return view('catalog.base-units.index', [
            'baseUnits' => BaseUnit::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('catalog.base-units.form', [
            'baseUnit' => new BaseUnit(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:base_units,name'],
            'symbol' => ['required', 'string', 'max:20'],
        ]);

        BaseUnit::query()->create($validated);

        return redirect()->route('base-units.index')->with('status', 'Unidad base creada correctamente.');
    }

    public function edit(BaseUnit $baseUnit): View
    {
        return view('catalog.base-units.form', compact('baseUnit'));
    }

    public function update(Request $request, BaseUnit $baseUnit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:base_units,name,' . $baseUnit->id],
            'symbol' => ['required', 'string', 'max:20'],
        ]);

        $baseUnit->update($validated);

        return redirect()->route('base-units.index')->with('status', 'Unidad base actualizada correctamente.');
    }
}
