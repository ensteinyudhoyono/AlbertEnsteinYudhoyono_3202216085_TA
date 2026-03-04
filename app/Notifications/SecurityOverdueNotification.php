<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Rent;

class SecurityOverdueNotification extends Notification implements ShouldQueue
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
        $userPhone = $this->rent->user->phone;
        $startTime = $this->rent->time_start_use->format('d/m/Y H:i');
        $endTime = $this->rent->time_end_use->format('d/m/Y H:i');
        $purpose = $this->rent->purpose;
        
        // Calculate how many minutes overdue
        $now = now();
        $endTimeObj = $this->rent->time_end_use;
        $minutesOverdue = $now->diffInMinutes($endTimeObj);
        $overdueText = $minutesOverdue > 0 ? " (TERLAMBAT {$minutesOverdue} MENIT)" : " (BARU SAJA BERAKHIR)";
        
        // Get items if any
        $itemsList = '';
        if ($this->rent->items->count() > 0) {
            $items = [];
            foreach ($this->rent->items as $item) {
                $items[] = "• {$item->name} ({$item->pivot->quantity})";
            }
            $itemsList = "\nItem yang dipinjam:\n" . implode("\n", $items);
        }
        
        // Format phone number for display
        $displayPhone = $userPhone ? "📞 {$userPhone}" : "📞 Nomor tidak tersedia";
        
        $message = "🚨 *PEMINJAMAN BERAKHIR - SEGERA TEGUR PEMINJAM*{$overdueText}\n\n" .
                  "🏢 *Ruangan:* {$roomName}\n" .
                  "👤 *Peminjam:* {$userName}\n" .
                  "📞 *Kontak:* {$displayPhone}\n" .
                  "⏰ *Waktu:* {$startTime} - {$endTime}\n" .
                  "🎯 *Tujuan:* {$purpose}" .
                  $itemsList . "\n\n" .
                  "⚠️ *TINDAKAN SEGERA YANG HARUS DILAKUKAN:*\n" .
                  "1. 🚶‍♂️ Datangi ruangan {$roomName} sekarang juga\n" .
                  "2. 📞 Hubungi peminjam di {$displayPhone}\n" .
                  "3. 🔍 Pastikan ruangan sudah dikembalikan\n" .
                  "4. 📦 Cek semua item sudah dikembalikan\n" .
                  "5. ⚖️ Berikan peringatan jika terlambat\n\n" .
                  "💡 *Tips:* Jika peminjam tidak merespons, segera laporkan ke admin\n" .
                  "🕐 *Waktu notifikasi:* " . now()->format('d/m/Y H:i:s') . "\n" .
                  "🔄 *Status:* MENUNGGU TINDAK LANJUT SECURITY";
        
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
            ->subject('Peminjaman Telah Berakhir - Tindak Lanjuti - ' . $this->rent->room->name)
            ->line('Ada peminjaman yang telah berakhir dan memerlukan tindak lanjut.')
            ->line('Ruangan: ' . $this->rent->room->name)
            ->line('Peminjam: ' . $this->rent->user->name)
            ->line('Waktu: ' . $this->rent->time_start_use->format('d/m/Y H:i') . ' - ' . $this->rent->time_end_use->format('d/m/Y H:i'))
            ->action('Lihat Detail', url('/dashboard/rents/' . $this->rent->id));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'rent_id' => $this->rent->id,
            'type' => 'security_overdue',
            'message' => 'Peminjaman telah berakhir dan memerlukan tindak lanjut security',
            'room_name' => $this->rent->room->name,
            'user_name' => $this->rent->user->name,
            'end_time' => $this->rent->time_end_use,
        ];
    }
}


