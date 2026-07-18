<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewReservationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reservationId;
    public $userId;
    public $userName;
    public $userNim;
    public $userProdi;
    public $storeName;
    public $tenantCode;
    public $startDate;
    public $endDate;
    public $menusCount;
    public $createdAt;
    /**
     * Create a new event instance.
     */
    public function __construct($reservation, $approvalTenant)
    {
        $this->reservationId = $reservation->id;
        $this->userId = $reservation->user_id;
        $this->userName = $reservation->user->name;
        $this->userNim = $reservation->user->nim;
        $this->userProdi = $reservation->user->prodi;
        $this->storeName = $approvalTenant->store_name;
        $this->tenantCode = $approvalTenant->tenant_code;
        $this->startDate = $reservation->start_date->format('Y-m-d');
        $this->endDate = $reservation->end_date->format('Y-m-d');
        $this->menusCount = $approvalTenant->menus->count();
        $this->createdAt = $reservation->created_at->toDateTimeString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('reservations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-reservation';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reservationId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_nim' => $this->userNim,
            'user_prodi' => $this->userProdi,
            'store_name' => $this->storeName,
            'tenant_code' => $this->tenantCode,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'menus_count' => $this->menusCount,
            'created_at' => $this->createdAt,
        ];
    }
}
