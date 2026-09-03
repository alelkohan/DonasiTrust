import re

def fix_campaigns():
    with open('resources/views/admin/campaigns/index.blade.php', 'r') as f:
        content = f.read()
    
    bad_part = """                    <thead>
                        <tr>
                            <th scope="col" class="w-1/3">Kampanye</th>
                            <th scope="col">Pengaju</th>
                            <th scope="col">Target</th>
                            <th scope="col">Diajukan</th>
                            <th scope="col"></th>
                        </tr>
                    @endforeach"""
                    
    good_part = """                    <thead>
                        <tr>
                            <th scope="col">Kampanye</th>
                            <th scope="col">Pengaju</th>
                            <th scope="col" class="text-right">Target</th>
                            <th scope="col">Diajukan</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($campaigns as $campaign)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ Str::limit($campaign->title, 55) }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">{{ $campaign->categoryLabel() }}</p>
                                </td>
                                <td>
                                    <p class="text-ink-800">{{ $campaign->user->name }}</p>
                                    @if ($campaign->user->organization)
                                        <p class="mt-0.5 text-xs text-ink-500">{{ $campaign->user->organization }}</p>
                                    @endif
                                </td>
                                <td class="text-right font-semibold whitespace-nowrap tabular-nums">{{ rupiah($campaign->target_amount) }}</td>
                                <td class="whitespace-nowrap text-ink-600">
                                    {{ $campaign->submitted_at?->translatedFormat('d M Y') ?? '—' }}
                                </td>
                                <td class="text-right flex items-center justify-end gap-4 whitespace-nowrap">
                                    @if (in_array($campaign->status, ['draft', 'pending', 'rejected']))
                                        <form method="POST" action="{{ route('admin.kampanye.destroy', $campaign) }}"
                                              x-data @submit="if (! confirm('Hapus permanen kampanye ini?')) $event.preventDefault()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-700">Hapus</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.kampanye.show', $campaign) }}" class="dt-link text-sm">Tinjau &rarr;</a>
                                </td>
                            </tr>
                        @endforeach"""
                        
    content = content.replace(bad_part, good_part)
    with open('resources/views/admin/campaigns/index.blade.php', 'w') as f:
        f.write(content)


def fix_disbursements():
    with open('resources/views/admin/disbursements/index.blade.php', 'r') as f:
        content = f.read()
    
    bad_part = """                    <thead>
                        <tr>
                                {{ rupiah($disb->amount) }}"""
                                
    good_part = """                    <thead>
                        <tr>
                            <th scope="col">Ref &amp; Kampanye</th>
                            <th scope="col">Pemohon &amp; Keperluan</th>
                            <th scope="col" class="text-right">Nominal</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($disbursements as $disb)
                            <tr>
                                <td class="align-top">
                                    <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">{{ $disb->reference }}</span>
                                    <p class="mt-1.5 font-semibold text-ink-900">{{ Str::limit($disb->campaign->title, 40) }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">Tahap {{ $disb->milestone->sequence }}: {{ $disb->milestone->title }}</p>
                                </td>
                                <td class="align-top">
                                    <p class="font-medium text-ink-900">{{ $disb->requester->name }}</p>
                                    <p class="mt-1 text-xs text-ink-600 max-w-xs leading-relaxed">{{ $disb->purpose }}</p>
                                </td>
                                <td class="text-right font-bold text-ink-900 whitespace-nowrap tabular-nums align-top">
                                    {{ rupiah($disb->amount) }}"""

    content = content.replace(bad_part, good_part)
    with open('resources/views/admin/disbursements/index.blade.php', 'w') as f:
        f.write(content)


def fix_expenses():
    with open('resources/views/admin/expenses/index.blade.php', 'r') as f:
        content = f.read()
        
    bad_part = """                    <thead>
                        <tr>
                            <td class="text-right font-bold text-ink-900 whitespace-nowrap tabular-nums align-top">
                                {{ rupiah($expense->amount) }}"""
                                
    good_part = """                    <thead>
                        <tr>
                            <th scope="col">Nota &amp; Kampanye</th>
                            <th scope="col">Pelapor &amp; Rincian</th>
                            <th scope="col" class="text-right">Nominal</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($expenses as $expense)
                            <tr>
                                <td class="align-top">
                                    <p class="font-semibold text-ink-900">{{ $expense->title }}</p>
                                    <p class="mt-0.5 text-xs text-ink-500">{{ Str::limit($expense->campaign->title, 40) }}</p>
                                    @if ($expense->milestone)
                                        <p class="mt-0.5 text-xs text-ink-500">Tahap {{ $expense->milestone->sequence }}: {{ $expense->milestone->title }}</p>
                                    @endif
                                </td>
                                <td class="align-top">
                                    <p class="font-medium text-ink-900">{{ $expense->author->name }}</p>
                                    <p class="mt-1 text-xs text-ink-600 max-w-xs leading-relaxed">{{ $expense->description }}</p>
                                </td>
                                <td class="text-right font-bold text-ink-900 whitespace-nowrap tabular-nums align-top">
                                    {{ rupiah($expense->amount) }}"""
                                    
    content = content.replace(bad_part, good_part)
    with open('resources/views/admin/expenses/index.blade.php', 'w') as f:
        f.write(content)

fix_campaigns()
fix_disbursements()
fix_expenses()

