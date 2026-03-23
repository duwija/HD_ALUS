<?php
// app/LeadWorkflow.php
namespace App;

use Illuminate\Database\Eloquent\Model;

class LeadWorkflow extends Model
{
    protected $fillable = ['name', 'order', 'description', 'color'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'workflow_stage_id');
    }

    /**
     * Get percentage based on order relative to total active stages.
     * Stage dengan color='danger' dianggap stage batal/gagal dan tidak dihitung dalam progres.
     */
    public function getPercentageAttribute()
    {
        $total = static::where('color', '!=', 'danger')->count();
        if ($total <= 1) return 100;
        return round(($this->order - 1) / ($total - 1) * 100);
    }
}
