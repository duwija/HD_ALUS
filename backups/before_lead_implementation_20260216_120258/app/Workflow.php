<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Relasi ke step-step workflow
     */
    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    /**
     * Ambil step pertama (untuk set status awal tiket)
     */
    public function firstStep()
    {
        return $this->steps()->orderBy('order')->first();
    }
}
