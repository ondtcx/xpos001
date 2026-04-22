<?php

namespace App\Support\Reports\Csv;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvStreamDownloader
{
    public function download(string $fileName, array $headers, Closure $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            $writer($output);
            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
