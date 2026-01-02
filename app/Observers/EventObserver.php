<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\UserNotificationSetting;
use App\Services\GreenApiWhatsAppService;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    protected GreenApiWhatsAppService $whatsappService;

    public function __construct(GreenApiWhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        try {
            // Verificar si el evento es reciente (ej. últimas 24 horas) para evitar spam masivo de historial
            // Si el usuario quiere probar, probablemente quiera recibir el mensaje.
            // Por seguridad, limitemos a eventos ocurridos en las últimas 24 horas.
            // if ($event->event_time && $event->event_time->diffInHours(now()) > 24) {
            //    Log::info("Evento {$event->id} omitido de notificación por ser antiguo ({$event->event_time})");
            //    return;
            // }

            $user = $event->user;
            if (!$user) {
                return;
            }

            $setting = UserNotificationSetting::where('user_id', $user->id)->first();

            if (!$setting || $setting->channel !== 'whatsapp' || empty($setting->whatsapp_number)) {
                Log::info("Usuario {$user->id} no tiene configurado canal WhatsApp o número");
                return;
            }

            $message = "🚨 *Nueva Alerta GPS*\n\n";
            $message .= "📝 *Mensaje:* {$event->message}\n";
            $message .= "⏰ *Hora:* " . ($event->event_time ? $event->event_time->format('d/m/Y H:i:s') : 'N/A') . "\n";
            if ($event->address) {
                $message .= "📍 *Ubicación:* {$event->address}\n";
            }
            if ($event->speed) {
                $message .= "🚗 *Velocidad:* {$event->speed} km/h\n";
            }
            
            // Validación y formateo básico del número (asumiendo Perú 51 por defecto si faltase)
            $phone = $setting->whatsapp_number;
            // Si el número tiene 9 dígitos y no empieza con 51, agregarlo (caso común Perú)
            if (strlen($phone) === 9 && is_numeric($phone)) {
                $phone = '51' . $phone;
            }

            Log::info("Enviando notificación WhatsApp al número {$phone} (original: {$setting->whatsapp_number}) para evento {$event->id}");
            
            $this->whatsappService->sendMessage($phone, $message);

        } catch (\Exception $e) {
            Log::error("Error enviando notificación WhatsApp para evento {$event->id}: " . $e->getMessage());
        }
    }
}
