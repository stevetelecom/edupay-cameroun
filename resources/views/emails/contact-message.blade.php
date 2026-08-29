<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ __('public.em_contact_titre') }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1a1a2e; }
        .wrapper { max-width: 680px; margin: 0 auto; padding: 24px; background: #f8f9fb; }
        .card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 12px 30px rgba(0,0,0,.06); }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 16px; }
        .row { margin-bottom: 12px; }
        .label { font-size: 13px; font-weight: 700; color: #0b2545; margin-bottom: 4px; }
        .value { font-size: 14px; color: #333; line-height: 1.7; }
        .footer { margin-top: 24px; font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="title">{{ __('public.em_contact_titre') }}</div>

        <div class="row">
            <div class="label">{{ __('messages.nom') }}</div>
            <div class="value">{{ $contact['name'] }}</div>
        </div>
        <div class="row">
            <div class="label">{{ __('public.email_label') }}</div>
            <div class="value">{{ $contact['email'] }}</div>
        </div>
        <div class="row">
            <div class="label">{{ __('public.telephone_label') }}</div>
            <div class="value">{{ $contact['phone'] }}</div>
        </div>
        <div class="row">
            <div class="label">{{ __('public.sujet') }}</div>
            <div class="value">{{ $contact['subject'] }}</div>
        </div>
        <div class="row">
            <div class="label">{{ __('public.message_label') }}</div>
            <div class="value">{!! nl2br(e($contact['message'])) !!}</div>
        </div>

        <div class="footer">{{ __('public.em_contact_footer') }}</div>
    </div>
</div>
</body>
</html>
