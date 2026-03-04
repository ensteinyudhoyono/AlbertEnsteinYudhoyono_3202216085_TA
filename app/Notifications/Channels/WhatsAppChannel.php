<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\WhatsAppService;

class WhatsAppChannel
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toWhatsApp')) {
            $message = $notification->toWhatsApp($notifiable);
            
            if (isset($message['phone']) && isset($message['text'])) {
                return $this->whatsappService->sendTextMessage(
                    $message['phone'],
                    $message['text']
                );
            }
        }
    }
} 