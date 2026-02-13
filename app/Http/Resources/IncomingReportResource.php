<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomingReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'received_from' => $this->received_from,
            'problem_description' => $this->problem_description,
            'help_formats' => $this->help_formats,
            'comment' => $this->comment,
            'report' => ReportResource::make($this->whenLoaded('report')),
        ];
    }
}
