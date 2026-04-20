<?php

namespace App\Services;

use App\Models\Generation;
use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class LaunchpadPdfService
{
    public const DISK = 'local';
    public const DIRECTORY = 'launchpad/deliveries';

    /**
     * Render a Launchpad PDF for a lead's generation and write it to storage.
     *
     * @return string  Absolute path to the stored PDF.
     */
    public function render(Lead $lead, Generation $generation): string
    {
        if (! is_array($generation->output)) {
            throw new \RuntimeException('Generation has no output payload.');
        }

        $html = view('pdf.launchpad', [
            'lead' => $lead,
            'output' => $generation->output,
            'deliveredAt' => now(),
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $filename = $this->filenameFor($lead, $generation);

        Storage::disk(self::DISK)->makeDirectory(self::DIRECTORY);
        $relativePath = self::DIRECTORY . '/' . $filename;
        Storage::disk(self::DISK)->put($relativePath, $pdf->output());

        return Storage::disk(self::DISK)->path($relativePath);
    }

    public function filenameFor(Lead $lead, Generation $generation): string
    {
        return sprintf('launchpad-%d-%d.pdf', $lead->id, $generation->id);
    }
}
