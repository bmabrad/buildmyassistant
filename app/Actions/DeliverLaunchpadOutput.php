<?php

namespace App\Actions;

use App\Mail\LaunchpadDeliveryMail;
use App\Models\Delivery;
use App\Models\Generation;
use App\Models\Lead;
use App\Services\LaunchpadPdfService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverLaunchpadOutput
{
    public function __construct(private readonly LaunchpadPdfService $pdf) {}

    public function __invoke(Lead $lead, Generation $generation): Delivery
    {
        $delivery = Delivery::create([
            'lead_id' => $lead->id,
            'channel' => 'email',
            'provider' => config('mail.default', 'log'),
            'to_email' => $lead->buyer_email,
            'subject' => $generation->output['email_body']['subject'] ?? 'Your starter prompt',
            'status' => 'pending',
        ]);

        try {
            $pdfPath = $this->pdf->render($lead, $generation);
            $filename = $this->pdf->filenameFor($lead, $generation);

            Mail::to($lead->buyer_email)->send(new LaunchpadDeliveryMail(
                lead: $lead,
                output: $generation->output,
                pdfPath: $pdfPath,
                pdfFilename: $filename,
            ));

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'attachments' => [
                    ['filename' => $filename, 'path' => $pdfPath],
                ],
            ]);

            $lead->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        } catch (Throwable $e) {
            $delivery->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            Log::error('Launchpad delivery failed', [
                'lead_id' => $lead->id,
                'generation_id' => $generation->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $delivery;
    }
}
