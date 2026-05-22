<div class="card">
    <div data-material-detail-layout style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(400px, 0.9fr); gap: 16px; align-items: stretch;">
        <div style="flex: 1;">
            <div data-material-detail-info style="background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%); border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; font-size: 13.5px;">
                    <tr style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <td style="padding: 14px 20px; font-weight: 700; width: 35%; color: #334155; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Nama Material</td>
                        <td style="padding: 14px 20px; width: 65%; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 600;">{{ $pakuTembak->material_name ?? 'Paku Tembak' }}</td>
                    </tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Jenis</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->type ?? '-' }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Merek</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->brand ?? '-' }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Kemasan</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->packageUnit?->name ?? $pakuTembak->package_unit ?? '-' }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Mesiu</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->mesiu_code ?? '-' }} / {{ $pakuTembak->mesiu_size ?? '-' }} / {{ \App\Helpers\NumberHelper::formatPlain((float) ($pakuTembak->mesiu_content ?? 0)) }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Paku</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->paku_code ?? '-' }} / {{ $pakuTembak->paku_size ?? '-' }} / {{ \App\Helpers\NumberHelper::formatPlain((float) ($pakuTembak->paku_content ?? 0)) }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Toko</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->store ?? '-' }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Alamat</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->address ?? '-' }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569; border-bottom: 1px solid #f1f5f9;">Harga Beli</td><td style="padding: 14px 20px; border-bottom: 1px solid #f1f5f9;">{{ $pakuTembak->package_price ? 'Rp ' . number_format((float) $pakuTembak->package_price, 0, ',', '.') . ' / ' . ($pakuTembak->packageUnit?->name ?? $pakuTembak->package_unit ?? '-') : '-' }}</td></tr>
                    <tr><td style="padding: 14px 20px; font-weight: 600; color: #475569;">Harga Komparasi</td><td style="padding: 14px 20px;">{{ $pakuTembak->comparison_price ? 'Rp ' . number_format((float) $pakuTembak->comparison_price, 0, ',', '.') . ' / isi' : '-' }}</td></tr>
                </table>
            </div>
        </div>

        <div data-material-detail-aside style="display: flex; flex-direction: column; min-height: 0; height: 100%;">
            @if($pakuTembak->photo_url)
                <div data-material-detail-photo style="width: 100%; flex-shrink: 0;">
                    <img src="{{ $pakuTembak->photo_url }}" alt="{{ $pakuTembak->material_name ?? 'Paku Tembak' }}" style="width: 100%; height: auto; border-radius: 12px; border: 2px solid #f1f5f9;">
                </div>
            @else
                <div data-material-detail-photo style="width: 100%; flex-shrink: 0; min-height: 300px; border: 2px dashed #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    Tidak ada foto
                </div>
            @endif
            @include('materials.partials.change-history', ['materialEntity' => $pakuTembak])
        </div>
    </div>
</div>

