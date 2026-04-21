<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use Illuminate\View\View;

class InventoryLotController extends Controller
{
    public function index(): View
    {
        return view('inventory.lots.index', [
            'lots' => InventoryLot::query()->with('variant.product')->latest('received_at')->get(),
        ]);
    }
}
