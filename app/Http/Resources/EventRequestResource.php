<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'event_date' => $this->event_date,
            'description' => $this->description,
            'target_audience' => $this->target_audience,
            'participants_count' => $this->participants_count,
            'help_formats' => $this->help_formats,
            'comment' => $this->comment,
            'report' => ReportResource::make($this->whenLoaded('report')),
        ];
    }
}
