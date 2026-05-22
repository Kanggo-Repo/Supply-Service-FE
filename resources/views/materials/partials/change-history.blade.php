@php
    $hiddenHistoryFields = [
        'store_location_id',
        'material_name',
        'cat_name',
        'cement_name',
        'nat_name',
        'sand_name',
    ];
    $formatHistoryValue = static function (string $field, mixed $value): string {
        if ($field === 'photo' && is_string($value) && trim($value) !== '') {
            return basename($value);
        }

        return \App\Models\MaterialChangeLog::formatFieldValue($field, $value);
    };
    $historyPreviewUrl = static function (string $field, mixed $value): ?string {
        if ($field !== 'photo' || ! is_string($value) || trim($value) === '') {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::url($value);
    };
    if (is_object($materialEntity) && method_exists($materialEntity, 'loadMissing')) {
        $materialEntity->loadMissing('materialChangeLogs.user');
    }
    $historyRestoreRouteName = is_object($materialEntity) && method_exists($materialEntity, 'materialHistoryRestoreRouteName')
        ? $materialEntity->materialHistoryRestoreRouteName()
        : ($materialEntity->history_restore_route_name ?? null);
    $historyRouteParameters = ($materialEntity instanceof \Illuminate\Database\Eloquent\Model)
        ? fn ($historyEntry) => [$materialEntity, $historyEntry]
        : fn ($historyEntry) => [data_get($materialEntity, 'id'), data_get($historyEntry, 'id')];
    $historyEntries = collect($materialEntity->materialChangeLogs ?? [])
        ->filter(function ($historyEntry) use ($hiddenHistoryFields) {
            $visibleChanges = collect($historyEntry->changes ?? [])
                ->reject(fn ($change, $field) => in_array($field, $hiddenHistoryFields, true));

            return $historyEntry->action === 'restored' || $visibleChanges->isNotEmpty();
        })
        ->values();
@endphp

<div data-material-history-panel style="margin-top: 16px; display: flex; flex: 1 1 auto; min-height: 0; flex-direction: column;">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 5px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">Riwayat Perubahan</h3>
    </div>

    @if ($historyEntries->isEmpty())
        <div style="display: flex; align-items: center; padding: 16px 18px; border: 1px dashed #cbd5e1; border-radius: 14px; background: #f8fafc; color: #64748b; font-size: 13px; flex: 1 1 auto; min-height: 0;">
            Belum ada riwayat perubahan untuk material ini.
        </div>
    @else
        <div data-history-stage style="display: flex; flex: 1 1 auto; min-height: 0;">
            @foreach ($historyEntries as $historyIndex => $historyEntry)
                @php
                    $actionLabel = match ($historyEntry->action) {
                        'created' => 'Dibuat',
                        'deleted' => 'Dihapus',
                        'restored' => 'Dipulihkan',
                        default => 'Diedit',
                    };
                    $visibleChanges = $historyEntry->action === 'created'
                        ? collect()
                        : collect($historyEntry->changes ?? [])
                            ->reject(fn ($change, $field) => in_array($field, $hiddenHistoryFields, true));
                @endphp
                <section
                    data-history-slide
                    @if ($historyIndex !== 0) hidden @endif
                    style="border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%); display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; width: 100%;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 8px; border-bottom: 1px solid #eef2f7; background: linear-gradient(135deg, #fff7ed 0%, #fff1f2 100%);">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 8px; min-width: 0;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #ffffff; border: 1px solid #fed7aa; font-size: 11px; font-weight: 800; color: #9a3412;">
                                {{ $actionLabel }}
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #ffffff; border: 1px solid #dbe4f0; font-size: 11px; font-weight: 700; color: #334155;">
                                <i class="bi bi-person"></i>
                                {{ $historyEntry->user?->name ?? 'Sistem' }}
                                <span style="display: inline-flex; align-items: center; gap: 5px; margin-left: 6px; padding-left: 8px; border-left: 1px solid #e2e8f0; color: #64748b; font-weight: 700;">
                                    <i class="bi bi-clock-history"></i>
                                    {{ optional($historyEntry->edited_at)->format('d M Y, H:i') ?? '-' }}
                                </span>
                            </span>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <form
                                method="POST"
                                action="{{ $historyRestoreRouteName ? route($historyRestoreRouteName, $historyRouteParameters($historyEntry)) : '#' }}"
                                data-history-restore="1"
                                data-confirm="Pulihkan nilai material ke riwayat pada halaman ini?"
                                data-confirm-title="Pulihkan Riwayat"
                                data-confirm-type="warning"
                                data-confirm-ok="Ya, Pulihkan"
                                data-confirm-cancel="Batal"
                                style="margin: 0;">
                                @csrf
                                <input type="hidden" name="_redirect_url" value="{{ url()->previous() }}">
                                <button
                                    type="submit"
                                    style="height: 40px; border: 1px solid #fdba74; border-radius: 999px; background: #fff7ed; color: #9a3412; padding: 0 14px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            @if ($historyEntries->count() > 1)
                                <div
                                    data-history-nav
                                    style="display: inline-flex; align-items: center; gap: 4px; padding: 4px; border: 1px solid #e2e8f0; border-radius: 999px; background: #fff; flex-shrink: 0;">
                                    <span
                                        data-history-page-indicator
                                        aria-live="polite"
                                        style="display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; font-size: 12px; font-weight: 800; color: #475569;">
                                        1/{{ $historyEntries->count() }}
                                    </span>
                                    <button
                                        type="button"
                                        data-history-prev
                                        aria-label="Riwayat sebelumnya"
                                        style="width: 32px; height: 32px; border: none; border-radius: 999px; background: #fff7ed; color: #9a3412; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button
                                        type="button"
                                        data-history-next
                                        aria-label="Riwayat berikutnya"
                                        style="width: 32px; height: 32px; border: none; border-radius: 999px; background: #fff7ed; color: #9a3412; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div data-history-card-body style="padding: 14px 18px; display: grid; gap: 10px; flex: 0 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain;">
                        @if ($visibleChanges->isEmpty() && $historyEntry->action === 'restored')
                            <div style="padding: 12px 14px; border-radius: 14px; background: #eff6ff; border: 1px solid #bfdbfe; font-size: 12px; color: #1d4ed8; font-weight: 700;">
                                Riwayat ini mencatat aksi pemulihan oleh {{ $historyEntry->user?->name ?? 'Sistem' }}.
                            </div>
                        @else
                            @foreach ($visibleChanges as $field => $change)
                                @php
                                    $fromPreviewUrl = $historyPreviewUrl($field, $change['from'] ?? null);
                                    $toPreviewUrl = $historyPreviewUrl($field, $change['to'] ?? null);
                                @endphp
                                <div data-history-change-row style="display: grid; grid-template-columns: minmax(130px, 180px) minmax(0, 1fr) auto minmax(0, 1fr); gap: 10px; align-items: stretch;">
                                    <div style="padding: 10px 12px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: #334155;">
                                        {{ \App\Models\MaterialChangeLog::labelForField($field) }}
                                    </div>
                                    <div style="padding: 10px 12px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; font-size: 12px; color: #9a3412; overflow-wrap: anywhere; display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                        <span>{{ $formatHistoryValue($field, $change['from'] ?? null) }}</span>
                                        @if ($fromPreviewUrl)
                                            <button
                                                type="button"
                                                data-history-photo-preview
                                                data-photo-url="{{ $fromPreviewUrl }}"
                                                data-photo-label="{{ $formatHistoryValue($field, $change['from'] ?? null) }}"
                                                style="height: 30px; border: 1px solid #fdba74; border-radius: 999px; background: #ffffff; color: #9a3412; padding: 0 12px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                                                <i class="bi bi-eye"></i>
                                                Preview
                                            </button>
                                        @endif
                                    </div>
                                    <div style="display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; font-weight: 900;">
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                    <div style="padding: 10px 12px; border-radius: 12px; background: #ecfdf5; border: 1px solid #86efac; font-size: 12px; color: #166534; overflow-wrap: anywhere; display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                        <span>{{ $formatHistoryValue($field, $change['to'] ?? null) }}</span>
                                        @if ($toPreviewUrl)
                                            <button
                                                type="button"
                                                data-history-photo-preview
                                                data-photo-url="{{ $toPreviewUrl }}"
                                                data-photo-label="{{ $formatHistoryValue($field, $change['to'] ?? null) }}"
                                                style="height: 30px; border: 1px solid #86efac; border-radius: 999px; background: #ffffff; color: #166534; padding: 0 12px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                                                <i class="bi bi-eye"></i>
                                                Preview
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>



