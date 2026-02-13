<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'topic' => $this->topic,
            'description' => $this->description,
            'actions' => $this->actions,
            'result' => $this->result,
            'difficulties' => $this->difficulties,
            'report' => ReportResource::make($this->whenLoaded('report')),
        ];
    }
}
