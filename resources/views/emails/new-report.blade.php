<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau signalement</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #F5F5F5;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" style="padding: 40px 20px;">

                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%); padding: 40px 30px; text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 12px;">🚩</div>
                            <h1 style="color: #FFFFFF; margin: 0; font-size: 24px; font-weight: 700;">
                                Nouveau signalement
                            </h1>
                            <p style="color: rgba(255,255,255,0.9); margin: 8px 0 0; font-size: 14px;">
                                Signalement #{{ $report->id }}
                            </p>
                        </td>
                    </tr>

                    {{-- CONTENU --}}
                    <tr>
                        <td style="padding: 32px 30px;">

                            {{-- Raison --}}
                            <div style="margin-bottom: 24px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                    Raison du signalement
                                </div>
                                <div style="background-color: #FEF3C7; color: #92400E; padding: 12px 16px; border-radius: 10px; font-weight: 600; font-size: 15px;">
                                    @php
                                        $reasonLabels = [
                                            'spam' => '📢 Spam ou publicité',
                                            'abuse' => '⚠️ Contenu abusif',
                                            'inappropriate' => '🚫 Contenu inapproprié',
                                            'scam' => '💸 Arnaque / Fraude',
                                            'fake' => '🎭 Faux produit / Faux compte',
                                            'other' => '❓ Autre raison',
                                        ];
                                    @endphp
                                    {{ $reasonLabels[$report->reason] ?? $report->reason }}
                                </div>
                            </div>

                            {{-- Description --}}
                            @if($report->description)
                                <div style="margin-bottom: 24px;">
                                    <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                        Description
                                    </div>
                                    <div style="background-color: #F9FAFB; padding: 16px; border-radius: 10px; color: #333; font-size: 14px; line-height: 1.6; border-left: 4px solid #DC2626;">
                                        {{ $report->description }}
                                    </div>
                                </div>
                            @endif

                            {{-- Type --}}
                            <div style="margin-bottom: 24px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                    Type de contenu
                                </div>
                                <div style="font-size: 15px; color: #333;">
                                    @if(str_contains($report->reportable_type, 'Product'))
                                        📦 <strong>Produit</strong> (ID: {{ $report->reportable_id }})
                                    @elseif(str_contains($report->reportable_type, 'User'))
                                        👤 <strong>Utilisateur</strong> (ID: {{ $report->reportable_id }})
                                    @endif
                                </div>
                            </div>

                            {{-- Contenu signalé --}}
                            @if($target)
                                <div style="margin-bottom: 24px;">
                                    <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                        Contenu signalé
                                    </div>
                                    <div style="background-color: #F9FAFB; padding: 16px; border-radius: 10px;">
                                        <div style="font-weight: 600; color: #333; font-size: 15px; margin-bottom: 4px;">
                                            {{ $target->name }}
                                        </div>
                                        @if(str_contains($report->reportable_type, 'Product'))
                                            <div style="color: #FF6B00; font-weight: 700; font-size: 14px;">
                                                {{ number_format($target->price, 0, ',', ' ') }} FCFA
                                            </div>
                                        @elseif(str_contains($report->reportable_type, 'User'))
                                            <div style="color: #666; font-size: 13px;">
                                                {{ $target->email }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Signalé par --}}
                            <div style="margin-bottom: 32px;">
                                <div style="font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                    Signalé par
                                </div>
                                <div style="font-size: 14px; color: #333;">
                                    <strong>{{ $report->reporter->name ?? 'Inconnu' }}</strong>
                                    @if($report->reporter)
                                        <br>
                                        <span style="color: #666; font-size: 13px;">{{ $report->reporter->email }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- BOUTON --}}
                            <div style="text-align: center; margin-top: 32px;">
                                <a href="{{ config('app.url') }}/admin/reports/{{ $report->id }}"
                                   style="display: inline-block; background: linear-gradient(135deg, #FF6B00 0%, #FF8B3D 100%); color: #FFFFFF; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 15px;">
                                    🚩 Traiter ce signalement
                                </a>
                            </div>

                            {{-- DATE --}}
                            <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #E5E7EB; text-align: center; color: #9CA3AF; font-size: 12px;">
                                Signalement créé le {{ $report->created_at->format('d/m/Y à H:i') }}
                            </div>

                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 20px 30px; text-align: center; color: #9CA3AF; font-size: 12px;">
                            © {{ date('Y') }} Masasugu — Tous droits réservés
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
