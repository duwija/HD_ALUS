<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeadUpdate extends Model
{
    protected $fillable = [
        'id_customer',
        'updated_by',
        'field_changed',
        'old_value',
        'new_value',
        'notes'
    ];

    /**
     * Relationship to Customer
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer', 'id_customer');
    }

    /**
     * Format display untuk field yang berubah
     */
    public function getFormattedChangeAttribute()
    {
        $fieldLabels = [
            'lead_source' => 'Lead Source',
            'expected_close_date' => 'Expected Close Date',
            'conversion_probability' => 'Conversion Probability',
            'lead_notes' => 'Lead Notes'
        ];

        $label = $fieldLabels[$this->field_changed] ?? $this->field_changed;
        
        if ($this->field_changed == 'conversion_probability') {
            return "{$label}: {$this->old_value}% → {$this->new_value}%";
        }
        
        return "{$label}: " . ($this->old_value ?: '-') . " → " . ($this->new_value ?: '-');
    }
}
