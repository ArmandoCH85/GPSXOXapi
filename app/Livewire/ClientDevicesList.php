<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

/**
 * Componente Livewire para mostrar la lista de dispositivos/vehículos de un cliente
 * 
 * CORREGIDO: Ya no implementa HasTable (que era el problema del error "abstract method")
 */
class ClientDevicesList extends Component
{
    public $clientId;
    public $search = '';
    public $page = 1;
    public $perPage = 15;

    public function mount($clientId)
    {
        $this->clientId = $clientId;
    }

    public function updatedSearch()
    {
        $this->page = 1;
    }

    public function nextPage()
    {
        $this->page++;
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function render()
    {
        $userApiHash = session('user_api_hash');
        $devices = [];
        $total = 0;
        $lastPage = 1;

        if ($userApiHash) {
            try {
                $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
                $url = "{$baseUrl}/admin/client/{$this->clientId}/devices";

                $params = [
                    'user_api_hash' => $userApiHash,
                    'lang' => 'en',
                    'page' => $this->page,
                    'per_page' => $this->perPage,
                ];

                if (!empty($this->search)) {
                    $params['s'] = $this->search;
                }

                $response = Http::acceptJson()
                    ->timeout(60)
                    ->connectTimeout(60)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    $devices = $data['data'] ?? [];
                    $pagination = $data['pagination'] ?? [];
                    $total = $pagination['total'] ?? 0;
                    $lastPage = $pagination['last_page'] ?? 1;
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching devices: ' . $e->getMessage());
            }
        }

        return view('livewire.client-devices-list', [
            'devices' => $devices,
            'total' => $total,
            'lastPage' => $lastPage,
        ]);
    }
}
