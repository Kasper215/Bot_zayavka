<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_type' => $this->report_type,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'municipality_id' => $this->municipality_id,
            'received_at' => $this->received_at,
            'documents' => $this->documents,
        ];
    }
}
