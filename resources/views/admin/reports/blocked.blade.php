@extends('admin.layouts.app')

@section('title', 'Utilisateurs bloqués')

@section('content')

<div class="mb-6">
    <a href="{{ route('admin.reports.index') }}" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left"></i> Retour aux signalements
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Qui a bloqué</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Utilisateur bloqué</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Raison</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($blocked as $block)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-800">{{ $block->blocker->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $block->blocker->email ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-800">{{ $block->blocked->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $block->blocked->email ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $block->reason ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $block->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <form method="POST" action="{{ route('admin.reports.unblock', $block->id) }}"
                              onsubmit="return confirm('Débloquer cet utilisateur ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-green-500 hover:text-green-700" title="Débloquer">
                                <i class="fas fa-unlock"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-user-slash text-4xl mb-3"></i>
                        <div>Aucun utilisateur bloqué</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($blocked->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $blocked->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection
