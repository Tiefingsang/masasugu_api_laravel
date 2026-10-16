<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewReportNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Report $report;
    public $target;

    /**
     * 🎯 Créer une nouvelle instance
     */
    public function __construct(Report $report)
    {
        $this->report = $report;

        // Charger le contenu signalé (produit ou user)
        if (str_contains($report->reportable_type, 'Product')) {
            $this->target = \App\Models\Product::find($report->reportable_id);
        } elseif (str_contains($report->reportable_type, 'User')) {
            $this->target = \App\Models\User::find($report->reportable_id);
        } else {
            $this->target = null;
        }
    }

    /**
     * 📧 Sujet de l'email
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚩 Nouveau signalement #' . $this->report->id . ' — Masasugu Admin',
        );
    }

    /**
     * 📄 Contenu de l'email
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-report',
        );
    }

    /**
     * 📎 Pièces jointes
     */
    public function attachments(): array
    {
        return [];
    }
}
