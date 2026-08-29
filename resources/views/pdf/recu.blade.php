<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        @page { margin: 30px 40px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            margin: 0;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #0D9E75;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header .logo {
            display: table-cell;
            vertical-align: middle;
            font-size: 22px;
            font-weight: bold;
            color: #0B2545;
        }
        .header .logo span { color: #0D9E75; }
        .header .doc-type {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            font-size: 16px;
            font-weight: bold;
            color: #0D9E75;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .meta-row { display: table; width: 100%; margin-bottom: 6px; }
        .meta-label { display: table-cell; width: 40%; color: #666; font-size: 11px; }
        .meta-value { display: table-cell; font-weight: bold; font-size: 12px; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 14px;
            font-size: 11px;
            font-weight: bold;
            background: #E0F5EE;
            color: #085041;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 22px 0 8px;
        }
        table.detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.detail-table th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            border-bottom: 2px solid #eee;
            padding: 8px 6px;
        }
        table.detail-table td {
            padding: 10px 6px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 12px;
        }
        .amount-box {
            background: #E0F5EE;
            border-radius: 6px;
            padding: 16px 20px;
            text-align: right;
            margin-bottom: 24px;
        }
        .amount-box .label { font-size: 11px; color: #085041; margin-bottom: 4px; }
        .amount-box .value { font-size: 24px; font-weight: bold; color: #085041; }
        .footer {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #eee;
            font-size: 9px;
            color: #999;
            text-align: center;
        }
        .verif-box {
            margin-top: 18px;
            font-size: 9px;
            color: #aaa;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">Edu<span>Pay</span> Cameroun</div>
        <div class="doc-type">{{ __('payeur.pdf_recu_titre') }}</div>
    </div>

    <div class="meta-box">
        <div class="meta-row">
            <div class="meta-label">{{ __('payeur.hist_reference') }}</div>
            <div class="meta-value">{{ $paiement->reference }}</div>
        </div>
        <div class="meta-row">
            <div class="meta-label">{{ __('payeur.pdf_recu_date') }} de paiement</div>
            <div class="meta-value">
                {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y à H:i') : '—' }}
            </div>
        </div>
        <div class="meta-row">
            <div class="meta-label">{{ __('etablissement.statut') }}</div>
            <div class="meta-value">
                <span class="status-badge">{{ $paiement->statut === 'valide' ? __('payeur.statut_valide') : ucfirst($paiement->statut) }}</span>
            </div>
        </div>
    </div>

    <div class="section-title">{{ __('payeur.pdf_section_payeur') }}</div>
    <table class="detail-table">
        <tr>
            <td style="width:50%;"><strong>{{ __('messages.nom') }}</strong><br/>{{ $paiement->user->name ?? '—' }}</td>
            <td><strong>{{ __('public.telephone_label') }}</strong><br/>{{ $paiement->telephone_paiement ?? $paiement->user->telephone ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">{{ __('payeur.pdf_section_apprenant') }}</div>
    <table class="detail-table">
        <tr>
            <td style="width:50%;">
                <strong>{{ __('messages.nom') }}</strong><br/>
                {{ $paiement->apprenant->prenom ?? '' }} {{ $paiement->apprenant->nom ?? '' }}
            </td>
            <td>
                <strong>{{ __('messages.etablissement') }}</strong><br/>
                {{ $paiement->apprenant->etablissement->nom ?? '—' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>{{ __('etablissement.classe') }}</strong><br/>
                {{ $paiement->apprenant->classe ?? '—' }}
            </td>
            <td>
                <strong>{{ __('payeur.pdf_frais_regle') }}</strong><br/>
                {{ $paiement->fraisApprenant->categorieFrais->nom ?? '—' }}
                ({{ $paiement->fraisApprenant->annee_scolaire ?? '—' }})
            </td>
        </tr>
    </table>

    <div class="section-title">{{ __('payeur.pdf_section_detail') }}</div>
    <table class="detail-table">
        <thead>
            <tr>
                <th>{{ __('payeur.em_label_mode') }}</th>
                <th>{{ __('etablissement.type_lbl') }}</th>
                <th>{{ __('etablissement.tranche') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ match($paiement->mode_paiement) {
                        'mtn_momo' => __('etablissement.mt_mtn'),
                        'orange_money' => __('etablissement.mt_orange'),
                        'carte' => __('etablissement.carte'),
                        default => $paiement->mode_paiement,
                    } }}
                </td>
                <td>{{ $paiement->type_paiement === 'integral' ? __('payeur.pay_integral') : __('payeur.pay_tranche') }}</td>
                <td>{{ __('payeur.pdf_numero_tranche', ['n' => $paiement->numero_tranche ?? '—']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="amount-box">
        <div class="label">{{ __('payeur.em_montant_paye') }}</div>
        <div class="value">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
    </div>

    <div class="footer">
        {{ __('admin.em_footer_plateforme') }}<br/>
        {{ __('payeur.pdf_recu_footer') }}
    </div>

    <div class="verif-box">
        {{ __('payeur.pdf_recu_verif', ['ref' => $paiement->reference, 'date' => now()->format('d/m/Y à H:i')]) }}
    </div>

</body>
</html>
