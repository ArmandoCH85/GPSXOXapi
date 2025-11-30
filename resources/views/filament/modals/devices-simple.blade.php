<div class="p-4">
    <table class="w-full border-collapse border-2 border-gray-400">
        <thead>
            <tr class="bg-gray-200">
                <th class="border-2 border-gray-400 px-4 py-3 text-left font-bold text-gray-800">ID</th>
                <th class="border-2 border-gray-400 px-4 py-3 text-left font-bold text-gray-800">Nombre del Vehículo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
                <tr class="hover:bg-blue-50">
                    <td class="border-2 border-gray-400 px-4 py-3 font-bold text-blue-700">
                        #{{ $device['id'] }}
                    </td>
                    <td class="border-2 border-gray-400 px-4 py-3 text-gray-900">
                        {{ $device['name'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="border-2 border-gray-400 px-4 py-8 text-center text-gray-600">
                        No hay vehículos registrados
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if(count($devices) > 0)
        <div class="mt-4 text-sm font-semibold text-gray-700 text-center">
            Total: <span class="text-blue-600">{{ count($devices) }}</span> vehículos
        </div>
    @endif
</div>
