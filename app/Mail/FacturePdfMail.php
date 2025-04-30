<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Facture;
use Illuminate\Support\Facades\Log;

class FacturePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $dacture;
    protected $pdfPath;

    public function __construct($pdfPath, Facture $dacture)
    {
        $this->pdfPath = $pdfPath;
        $this->dacture = $dacture;
    }

    public function build()
    {
        Log::info('Attaching PDF to email', [
            'pdfPath' => $this->pdfPath,
            'dactureReference' => $this->dacture->reference,
        ]);

        return $this->subject('Facture Details - ' . $this->dacture->reference)
                    ->view('emails.dacture')
                    ->attach($this->pdfPath, [
                        'as' => 'dacture-' . $this->dacture->reference . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}