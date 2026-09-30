@extends('layouts.admin')
@section('title', __('admin.notifications_titre') . ' — EduPay Cameroun')

@section('content')

<div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:18px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="ep-ico or ep-ico-entete"><span class="material-symbols-outlined">notifications</span></div>
    <div>
      <h3 style="margin:0;">
        {{ __('admin.notifications_titre') }}
        @if($nonLues > 0)
        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;background:#E8A020;color:#1E2B20;border-radius:999px;font-size:11px;font-weight:700;margin-left:6px;padding:0 7px;">
          {{ $nonLues }}
        </span>
        @endif
      </h3>
      <p class="text-sm text-gray-500 mt-0.5 ep-sous-titre" style="margin-top:2px;">{{ __('admin.sous_titre_notifications') }}</p>
    </div>
  </div>

  @if($nonLues > 0)
  <form method="POST" action="{{ route('admin.notifications.toutLu') }}">
    @csrf @method('PATCH')
    <button type="submit"
            style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:13px;font-weight:500;color:#444;cursor:pointer;">
      <span class="material-symbols-outlined" style="font-size:15px;">done_all</span>
      {{ __('admin.tout_marquer_lu') }}
    </button>
  </form>
  @endif
</div>

@if($notifications->count() > 0)
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <div class="responsive-admin-table-container">
    <table class="responsive-admin-table text-sm">
      <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.notif_col') }}</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.sujet_col') }}</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.date_col') }}</th>
          <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('messages.actions') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach($notifications as $notif)
          @php
            $lue = $notif->estLue();
            $ns = match($notif->type) {
                'reclamation' => 'ep-badge-yellow',
                'error'       => 'ep-badge-red',
                'warning'     => 'ep-badge-yellow',
                'success'     => 'ep-badge-green',
                default       => 'ep-badge-gray',
            };
            $nico = match($notif->type) {
                'reclamation' => 'forum',
                'error'       => 'error',
                'warning'     => 'warning',
                'success'     => 'check_circle',
                default       => 'info',
            };
          @endphp
        <tr style="{{ $lue ? '' : 'background:#FEF9EC;' }}">
          <td class="px-4 py-3">
            <div style="display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="font-size:17px;color:{{ $lue ? '#B8BEC7' : '#E8A020' }};">{{ $nico }}</span>
              <span class="ep-badge {{ $ns }}">{{ $notif->type }}</span>
              @unless($lue)
              <span style="display:inline-flex;align-items:center;justify-content:center;width:7px;height:7px;background:#E8A020;border-radius:50%;" title="{{ __('admin.non_lue') }}"></span>
              @endunless
            </div>
          </td>
          <td class="px-4 py-3">
            <div style="font-weight:{{ $lue ? '600' : '700' }};color:#1A1A2E;">{{ $notif->titre }}</div>
            <div style="font-size:11px;color:#8B93A1;margin-top:2px;">{{ str($notif->message)->limit(110) }}</div>
          </td>
          <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $notif->created_at->format('d/m/Y H:i') }}</td>
          <td class="px-4 py-3">
            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px;">
              @if($notif->reclamation_id)
              <a href="{{ route('admin.reclamations.index', ['search' => $notif->reclamation?->numero_ticket]) }}"
                 class="ep-act-btn ep-act-btn-bleu" title="{{ __('admin.detail_reclamation') }}">
                <span class="material-symbols-outlined" style="font-size:16px;">visibility</span>
              </a>
              @endif
              @if(!$lue)
              <form method="POST" action="{{ route('admin.notifications.lu', $notif) }}">
                @csrf @method('PATCH')
                <button type="submit" class="ep-act-btn ep-act-btn-vert" title="{{ __('admin.marquer_lu') }}">
                  <span class="material-symbols-outlined" style="font-size:16px;">done</span>
                </button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @if($notifications->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $notifications->links() }}</div>
  @endif
</div>
@else
<div class="bg-white border border-gray-200 rounded-xl">
  <div style="padding:56px 20px;text-align:center;">
    <div class="ep-ico" style="width:56px;height:56px;border-radius:16px;background:#F4F6F8;color:#B8BEC7;display:inline-flex;align-items:center;justify-content:center;">
      <span class="material-symbols-outlined" style="font-size:28px;">notifications_off</span>
    </div>
    <div style="font-size:14px;font-weight:700;color:#1A1A2E;margin-top:14px;">{{ __('admin.aucune_notification') }}</div>
    <p style="font-size:12px;color:#8B93A1;margin-top:6px;">{{ __('admin.aucune_notification_desc') }}</p>
  </div>
</div>
@endif

@endsection
