<?php

namespace App\Support\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportDateRange
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $today = Carbon::today();

        return new self(
            Carbon::parse($request->input('start_date', $today->toDateString()))->startOfDay(),
            Carbon::parse($request->input('end_date', $today->toDateString()))->endOfDay(),
        );
    }
}
