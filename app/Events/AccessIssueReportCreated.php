<?php

namespace App\Events;

use App\Models\AccessIssueReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccessIssueReportCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AccessIssueReport $report
    ) {}

    /**
     * Los reportes de acceso siempre notifican a todos los admins
     * ya que no tienen asignación como los tickets
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'access-issue.created';
    }

    public function broadcastWith(): array
    {
        return [
            'report_id' => $this->report->id,
            'email' => $this->report->email,
            'issue_type' => $this->report->issue_type,
            'issue_type_label' => $this->report->issue_type_label,
            'created_at' => $this->report->created_at->toIso8601String(),
        ];
    }
}
