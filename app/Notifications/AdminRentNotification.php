<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Rent;

class AdminRentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rent;

    /**
     * Create a new notification instance.
     */
    public function __construct(Rent $rent)
    {
        $this->rent = $rent;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'whatsapp'];
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp($notifiable)
    {
        $roomName = $this->rent->room->name;
        $userName = $this->rent->user->name;
        $startTime = $this->rent->time_start_use->format('d/m/Y H:i');
        $endTime = $this->rent->time_end_use->format('d/m/Y H:i');
        $purpose = $this->rent->purpose;
        
        // Get items if any
        $itemsList = '';
        if ($this->rent->items->count() > 0) {
            $items = [];
            foreach ($this->rent->items as $item) {
                $items[] = "• {$item->name} ({$item->pivot->quantity})";
            }
            $itemsList = "\nItem yang dipinjam:\n" . implode("\n", $items);
        }
        
        $message = "🔔 *PEMINJAMAN BARU MENUNGGU PERSETUJUAN*\n\n" .
                  "Ruangan: *{$roomName}*\n" .
                  "Peminjam: *{$userName}*\n" .
                  "Waktu: *{$startTime} - {$endTime}*\n" .
                  "Tujuan: *{$purpose}*" .
                  $itemsList . "\n\n" .
                  "Silakan review dan approve peminjaman ini di dashboard admin.";
        
        return [
            'phone' => $notifiable->phone,
            'text' => $message
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Peminjaman Baru - ' . $this->rent->room->name)
            ->line('Ada peminjaman baru yang menunggu persetujuan.')
            ->line('Ruangan: ' . $this->rent->room->name)
            ->line('Peminjam: ' . $this->rent->user->name)
            ->action('Lihat Detail', url('/dashboard/temporaryRents'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'rent_id' => $this->rent->id,
            'type' => 'new_rent_request',
            'message' => 'Peminjaman baru dari ' . $this->rent->user->name,
            'room_name' => $this->rent->room->name,
            'user_name' => $this->rent->user->name,
        ];
    }
} 