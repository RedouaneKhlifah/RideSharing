<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    protected $pdfPath;

    public function __construct($pdfPath, Order $order)
    {
        $this->pdfPath = $pdfPath;
        $this->order = $order;
    }

    public function build()
    {
        Log::info('Attaching PDF to email', [
            'pdfPath' => $this->pdfPath,
            'orderReference' => $this->order->reference,
        ]);

        return $this->subject('Order Details - ' . $this->order->reference)
                    ->view('emails.order')
                    ->attach($this->pdfPath, [
                        'as' => 'order-' . $this->order->reference . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}