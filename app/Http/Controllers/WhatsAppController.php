<?php

namespace App\Http\Controllers;

use App\Services\GreenApiWhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected GreenApiWhatsAppService $whatsapp;

    public function __construct(GreenApiWhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Muestra el formulario para probar el envío de mensajes.
     */
    public function showForm()
    {
        return view('whatsapp.test');
    }

    /**
     * Procesa el envío del mensaje.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'phone'   => ['required', 'string'],
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'phone.required'   => 'El número de teléfono es obligatorio.',
            'message.required' => 'El mensaje es obligatorio.',
        ]);

        try {
            $result = $this->whatsapp->sendMessage($data['phone'], $data['message']);

            // Puedes inspeccionar $result para ver el idMessage, tiempo, etc.
            return back()
                ->with('status', 'Mensaje enviado correctamente ✅')
                ->with('api_result', $result);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['general' => 'No se pudo enviar el mensaje: ' . $e->getMessage()]);
        }
    }
}
