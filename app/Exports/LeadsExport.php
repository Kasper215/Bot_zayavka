<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Lead::with(['user', 'manager']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('contacts', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Клиент',
            'Username',
            'Услуга',
            'Объем',
            'Контакты',
            'Статус',
            'Менеджер',
            'Дата создания',
        ];
    }

    public function map($lead): array
    {
        return [
            $lead->id,
            $lead->client_name ?: $lead->user?->name,
            '@' . ($lead->user?->username ?: 'no_username'),
            $lead->service_type,
            $lead->volume_stage,
            $lead->contacts,
            $lead->status,
            $lead->manager?->name ?: 'Не назначен',
            $lead->created_at->format('d.m.Y H:i'),
        ];
    }
}
