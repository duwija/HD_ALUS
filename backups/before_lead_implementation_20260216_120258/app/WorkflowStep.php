<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkflowStep extends Model
{
    use HasFactory;
    protected $table = 'workflow_steps';
    protected $fillable = ['workflow_id', 'name', 'order'];
    public $timestamps = false;

    /**
     * Relasi balik ke workflow
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
