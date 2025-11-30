<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class ClientDevicesList extends Component
{
    public $clientId;
    public $page = 1;
    public $search = '';

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

    public function gotoPage($page)
    {
        $this->page = $page;
    }

    public function render()
    {
        $userApiHash = session('user_api_hash');
        $devices = [];
        $pagination = null;
        $error = null;

        if ($userApiHash) {
            $baseUrl = rtrim(config('services.kiangel.base_url'), '/');
            $url = "{$baseUrl}/admin/client/{$this->clientId}/devices";

            try {
                $queryParams = [
                    'user_api_hash' => $userApiHash,
                    'lang' => 'en',
                    'page' => $this->page,
                ];

                if (!empty($this->search)) {
                    $queryParams['s'] = $this->search;
                }

                $response = Http::acceptJson()
                    ->get($url, $queryParams);

                if ($response->successful()) {
                    $data = $response->json();
                    $devices = $data['data'] ?? [];
                    $pagination = $data['pagination'] ?? null;
                } else {
                    $error = 'Error al cargar los vehículos. Código: ' . $response->status();
                }
            } catch (\Exception $e) {
                $error = 'Error de conexión: ' . $e->getMessage();
            }
        } else {
            $error = 'No se encontró el token de autenticación.';
        }

        return view('livewire.client-devices-list', [
            'devices' => $devices,
            'pagination' => $pagination,
            'error' => $error,
        ]);
    }
}
