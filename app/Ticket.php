<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Auth;
use App\TicketStep;
class Ticket extends Model
{
    //
    protected $fillable = ['id_customer', 'called_by','phone', 'status','id_categori','tittle','description','assign_to','member','date','time','create_by','created_at','deleted_at','current_step_id','parent_ticket_id','ticket_type'];


    public function category()
    {
        return $this->belongsTo(\App\Ticketcategorie::class, 'id_categori', 'id');
    }


    public function tags()
    {
        return $this->belongsToMany(\App\Tag::class, 'tickettags', 'ticket_id', 'tag_id');
    }

    public function user()
    {
        return $this->belongsTo('\App\User', 'assign_to')->withTrashed();
    }

    
    public function categorie()
    {
        return $this->belongsTo('\App\Ticketcategorie', 'id_categori');
    }
    public function customer()
    {
        return $this->belongsTo('\App\Customer', 'id_customer')->withTrashed();
    }
    public function ticketdetail()
    {

        return $this->hasMany('\App\Ticketdetail', 'id_ticket');
    }

    
    public function status()
    {

        return $this->hasMany('\App\Ticketdetail', 'id_ticket');
    }
    public function my_ticket()
    {
        if (!Auth::check()) {
        return 0; // kalau belum login, jangan error, kembalikan nilai 0
    }

    $id = Auth::id(); // lebih aman daripada Auth::user()->id

    return $this->where('assign_to', $id)
    ->where('status', '!=', 'Close')
    ->count();
}


public function assignToUser()
{
    return $this->belongsTo(\App\User::class, 'assign_to', 'id');
}


public function steps()
{
    return $this->hasMany(TicketStep::class)->orderBy('position');
}
public function currentStep()
{
    return $this->belongsTo(\App\TicketStep::class, 'current_step_id');
}

// Parent-Child Ticket Relationships
public function parent()
{
    return $this->belongsTo(Ticket::class, 'parent_ticket_id');
}

public function children()
{
    return $this->hasMany(Ticket::class, 'parent_ticket_id')->orderBy('created_at');
}

public function siblings()
{
    return $this->hasMany(Ticket::class, 'parent_ticket_id')
                ->where('id', '!=', $this->id)
                ->orderBy('created_at');
}

// Helper methods
public function isParent()
{
    return $this->ticket_type === 'parent';
}

public function isChild()
{
    return $this->ticket_type === 'child';
}

public function isStandalone()
{
    return $this->ticket_type === 'standalone';
}

public function getChildrenProgress()
{
    if (!$this->isParent()) {
        return 0;
    }
    
    $total = $this->children()->count();
    if ($total === 0) {
        return 0;
    }
    
    $closed = $this->children()->whereIn('status', ['Close', 'Solve'])->count();
    return round(($closed / $total) * 100, 2);
}

public function autoCloseIfChildrenComplete()
{
    if (!$this->isParent()) {
        return false;
    }
    
    $total = $this->children()->count();
    $closed = $this->children()->whereIn('status', ['Close', 'Solve'])->count();
    
    if ($total > 0 && $total === $closed && !in_array($this->status, ['Close', 'Solve'])) {
        $this->update(['status' => 'Close']);
        return true;
    }
    
    return false;
}

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::created(function ($ticket) {
    //         // Set step awal & akhir setiap kali Ticket baru dibuat
    //         TicketStep::create([
    //             'ticket_id' => $ticket->id,
    //             'name' => 'Open',
    //             'position' => 1,
    //         ]);

    //         TicketStep::create([
    //             'ticket_id' => $ticket->id,
    //             'name' => 'Closed',
    //             'position' => 2,
    //         ]);
    //     });
    // }
}
