<?php

namespace App\Http\Controllers;

use App\Ticket;
use App\TicketStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \Auth;
use \RouterOS\Client;
use \RouterOS\Query;
Use GuzzleHttp\Clients;
use Exception;
use PDF;
use \Carbon\Carbon;
use DataTables;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use App\Workflow;
use App\WorkflowStep;
use App\Ticketcategorie;
use App\Tag;
use App\Helpers\WaGatewayHelper;
class TicketController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
     $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,marketing,vendor'); // Daftar privilege
 }

    /**
     * Display a listing of the resource.

     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
     $from=date('Y-m-1');
     $to=date('y-m-d');
     $ticket = \App\Ticket::orderBy('id', 'DESC')
     ->whereBetween('date', [$from, $to])
     ->get();
     $ticketcategorie = \App\Ticketcategorie::pluck('name', 'id');
     $tags = \App\Tag::pluck('name', 'id');
     $user = \App\User::where('privilege', '!=', 'counter')->pluck('name', 'id');

     return view ('ticket/index',['ticket' =>$ticket, 'ticketcategorie' =>$ticketcategorie, 'user'=>$user, 'tags'=>$tags]);


 }

//    public function updateStep(Request $request, Ticket $ticket)
//    {
//     $stepId = $request->input('step_id');

//     // Ambil step dari DB
//     $step = \App\WorkflowStep::find($stepId);

//     if (!$step) {
//         return response()->json(['success' => false, 'message' => 'Step tidak ditemukan'], 404);
//     }

//     // Update ticket -> status ke nama step
//     $ticket->status = $step->name;
//     $ticket->save();

//     return response()->json([
//         'success' => true,
//         'step' => $step->name,
//     ]);
// }


 public function updateStep(Request $request, $ticketId)
 {
    $ticket = \App\Ticket::findOrFail($ticketId);
    $step   = \App\WorkflowStep::find($request->step_id);

    if ($step) {
        $ticket->status = $step->name;
        $ticket->save();

        return response()->json([
            'success' => true,
            'step'    => $step->name
        ]);
    }

    return response()->json(['success' => false]);
}

// public function reorderSteps(Request $request)
// {
//     foreach ($request->order as $item) {
//         \App\WorkflowStep::where('id', $item['id'])
//         ->update(['order' => $item['position']]);
//     }

//     return response()->json(['success' => true]);
// }


public function moveToInprogress($ticketId)
{
    $ticket = Ticket::with('category')->findOrFail($ticketId);

    if ($ticket->status !== 'Inprogress') {
        $ticket->update(['status' => 'Inprogress']);

            // kalau workflow di ticketcategories disimpan dalam string "Survey,Installasi,Testing"
        $defaultSteps = $ticket->category && $ticket->category->workflow
        ? explode(',', $ticket->category->workflow)
        : [];

            $position = 2; // setelah Open
            foreach ($defaultSteps as $stepName) {
                if (!$ticket->steps()->where('name', trim($stepName))->exists()) {
                    TicketStep::create([
                        'ticket_id' => $ticket->id,
                        'name'      => trim($stepName),
                        'position'  => $position++,
                    ]);
                }
            }

            // pastikan Closed ada di akhir
            $closed = $ticket->steps()->where('name', 'Finish')->first();
            if ($closed) {
                $closed->update(['position' => $position]);
            }
        }

        return back()->with('success', 'Ticket berhasil dipindah ke Inprogress & workflow default diterapkan');
    }

    public function reorder(Request $request, $ticketId)
    {
        $order = $request->order;

        $max = count($order);

        foreach ($order as $item) {
            $step = TicketStep::find($item['id']);
            if (!$step) continue;

            if (strtolower($step->name) === 'Start') {
                $step->update(['position' => 1]);
                continue;
            }

            if (strtolower($step->name) === 'Finish') {
                $step->update(['position' => $max]);
                continue;
            }

            $step->update(['position' => $item['position']]);
        }

        return response()->json(['success' => true]);
    }


    public function addStep(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $steps = $ticket->steps()->get();

    // selalu pastikan Closed ada di akhir
        $closed = $steps->where('name', 'Finish')->first();
        $order = $closed ? $closed->position : $steps->count() + 1;

        if ($closed) {
            $closed->update(['position' => $order + 1]);
        }

        $step = TicketStep::create([
            'ticket_id' => $ticket->id,
            'name'      => $request->name,
            'position'  => $order,
        ]);

        return response()->json(['success' => true, 'step' => $step]);
    }


    public function groupticket()
    {
     $from=date('Y-m-1');
     $to=date('y-m-d');
     $ticket = \App\Ticket::orderBy('id', 'DESC')
     ->whereBetween('date', [$from, $to])
     ->get();
     $ticketcategorie = \App\Ticketcategorie::pluck('name', 'id');


     $currentUser = Auth::user();
     $currentGroupIds = $currentUser->groups->pluck('id');

    // Cari semua user yang memiliki grup yang sama
     $users = \App\User::whereHas('groups', function ($query) use ($currentGroupIds) {
        $query->whereIn('groups.id', $currentGroupIds);
    })->pluck('name', 'id');


     return view ('ticket/groupticket',['ticket' =>$ticket, 'ticketcategorie' =>$ticketcategorie, 'user'=>$users]);


 }






 public function myticket()
 {
    $from=date('Y-m-1');
    $to=date('y-m-d');

    $id = Auth::user()->id;
    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('assign_to', $id)
    ->whereBetween('date', [$from, $to])
    ->get();


    return view ('ticket/myticket',['ticket' =>$ticket, 'title'=> 'Ticket List | My Ticket']);
}

public function table_myticket_list(Request $request){
    $user = Auth::user()->id;

    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $id_status = $request->input('id_status');



    $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end]);

    if (!empty($id_status)) {
        $ticket->where('status', $id_status);
    }

    $ticket->where('assign_to', $user);
// Order the results
    $ticket->orderBy('date', 'DESC');
    $ticket->orderBy('time', 'DESC');
    $results = $ticket->get();

    

    $total = $results->count();
    $open = $results->where('status', 'Open')->count();
    $close = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve = $results->where('status', 'Solve')->count();
    $pending = $results->where('status', 'pending')->count();


// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {

        return ' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
    })


    ->editColumn('status',function($ticket)
    {



        if ($ticket->status == "Open")

        {
          $color='bg-danger'; 
          $btn_c='bg-danger'; }


          elseif ($ticket->status == "Close")
            {$color='bg-secondary'; 
        $btn_c='bg-secondary'; }
        elseif ($ticket->status == "Pending")
          {  $color='bg-warning'; 
      $btn_c='bg-warning'; }
      elseif ($ticket->status == "Solve")
          {  $color='bg-info'; 
      $btn_c='bg-info'; }
      else
       {  $color='bg-primary'; 
   $btn_c='bg-primary'; }
   return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
})





    ->editColumn('id_customer',function($ticket)
    {



        return ' <a href="/customer/'.$ticket->id_customer.'" title="ticket" class="badge p-1 badge-success text-center  "> '.$ticket->customer->name. '</a>';
    })
    ->editColumn('id_categori',function($ticket)
    {

     return '<a>'.$ticket->categorie->name. '</a>';
 })
    ->editColumn('assign_to',function($ticket)
    {

     return '<a>'.$ticket->user->name. '</a>';
 })
    ->editColumn('date',function($ticket)
    {

     return '<a>'.$ticket->date. ' '. $ticket->time.'</a>';
 })




    ->rawColumns(['DT_RowIndex','id','id_customer','status','id_categori','tittle','assign_to','date'])
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)

    ->make(true);
}

public function view($id)
{


    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('id_customer','=', $id)
    ->get();


    return view ('ticket/view',['ticket' =>$ticket]);



}
// public function table_ticket_list(Request $request){


//     $date_from = $request->input('date_from');
//     $date_end = $request->input('date_end');
//     $id_categori = $request->input('id_categori');
//     $assign_to = $request->input('assign_to');
//     $id_status = $request->input('id_status');
//     $ticketid = $request->input('ticketid');
//     $title = $request->input('title');
//     $tags = $request->input('tags', []);
//       // NEW:
//     $create_by   = $request->input('create_by');      // user id pembuat ticket
//     $created_from = $request->input('created_from');    // YYYY-MM-DD
//     $created_end  = $request->input('created_end');     // YYYY-MM-DD


// // Initialize the query
//     $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end]);

// // Apply filters based on input
//     if (!empty($id_categori)) {
//         $ticket->where('id_categori', $id_categori);
//     }

//     if (!empty($assign_to)) {
//         $ticket->where('assign_to', $assign_to);
//     }

//     if (!empty($id_status)) {
//         $ticket->where('status', $id_status);
//     }
//     if (!empty($ticketid)) {
//         $ticket->where('id', $ticketid);
//     }
//     if (!empty($title)) {
//         $ticket->where('tittle', 'like', "%{$title}%");
//     }


//     if (!empty($tags) && is_array($tags)) {
//         $ticket->whereHas('tags', function ($query) use ($tags) {
//             $query->whereIn('tags.id', $tags);
//         });
//     }


//      // NEW: Filter Created By
//     if (!empty($create_by)) {
//         // sesuaikan nama kolom pembuat tiket di tabel tickets: umum: created_by / user_id / author_id
//         $ticket->where('create_by', $create_by);
//     }

//     // NEW: Filter Created At range (inclusive)
//     if (!empty($created_from) || !empty($created_end)) {
//         // gunakan startOfDay / endOfDay agar inklusif
//         $start = $created_from ? \Carbon\Carbon::parse($created_from)->startOfDay() : null;
//         $end   = $created_end   ? \Carbon\Carbon::parse($created_end)->endOfDay()   : null;

//         if ($start && $end) {
//             $ticket->whereBetween('created_at', [$start, $end]);
//         } elseif ($start) {
//             $ticket->where('created_at', '>=', $start);
//         } elseif ($end) {
//             $ticket->where('created_at', '<=', $end);
//         }
//     }

// // Order the results
//     $ticket->orderBy('id', 'DESC');

// // Get the results
//     $results = $ticket->get();



//     $total = $results->count();
//     $open = $results->where('status', 'Open')->count();
//     $close = $results->where('status', 'Close')->count();
//     $inprogress = $results->where('status', 'Inprogress')->count();
//     $solve = $results->where('status', 'Solve')->count();
//     $pending = $results->where('status', 'pending')->count();


// // Return the results using DataTables
//     return DataTables::of($results)
//     ->addIndexColumn()
//     ->editColumn('id',function($ticket)
//     {

//         return ' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
//     })


//     ->editColumn('status',function($ticket)
//     {



//         if ($ticket->status == "Open")

//         {
//           $color='bg-danger'; 
//           $btn_c='bg-danger'; }


//           elseif ($ticket->status == "Close")
//             {$color='bg-secondary'; 
//         $btn_c='bg-secondary'; }
//         elseif ($ticket->status == "Pending")
//           {  $color='bg-warning'; 
//       $btn_c='bg-warning'; }
//       elseif ($ticket->status == "Solve")
//           {  $color='bg-info'; 
//       $btn_c='bg-info'; }
//       else
//          {  $color='bg-primary'; 
//      $btn_c='bg-primary'; }
//      return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
//  })





//     ->editColumn('id_customer',function($ticket)
//     {



//         return ' <a href="/customer/'.$ticket->id_customer.'" title="ticket" class="badge p-1 badge-success text-center  "> '.$ticket->customer->name. '</a>';
//     })
//     ->addColumn('merchant',function($ticket)
//     {



//         return ' <a href="/merchant/'.$ticket->customer->merchant_name->id.'" title="ticket" class="badge p-1 badge-primary text-center  "> '.$ticket->customer->merchant_name->name. '</a>';
//     })
//     ->addColumn('address',function($ticket)
//     {



//         return $ticket->customer->address;
//     })
//     ->editColumn('id_categori',function($ticket)
//     {

//        return '<a>'.$ticket->categorie->name. '</a>';
//    })
//     ->addColumn('tags', function($ticket) {
//     // Mengambil tags yang terkait dengan tiket dan menampilkannya dalam badge
//     $tags = $ticket->tags->pluck('name')->toArray(); // Ambil nama-nama tag
//     $tagBadges = '';

//     foreach ($tags as $tag) {
//         // Membuat badge untuk setiap tag
//         $tagBadges .= '<span class="badge badge-info">' . $tag . '</span> ';
//     }

//     return $tagBadges;
// })
//  //    ->editColumn('create_by',function($ticket)
//  //    {

//  //     return '<a>'.$ticket->user->name. '</a>';
//  // })
//     ->editColumn('assign_to',function($ticket)
//     {

//        return '<a>'.$ticket->user->name. '</a>';
//    })
//     ->editColumn('date',function($ticket)
//     {

//        return '<a>'.$ticket->date. ' '. $ticket->time.'</a>';
//    })

//     ->editColumn('created_at',function($ticket)
//     {
//         $formattedDate = Carbon::parse($ticket->created_at)->format('Y-m-d H:i:s');
//         $humanFormat =Carbon::parse($formattedDate)->diffForHumans(); 

//         return '<a>'.$humanFormat. '</br>'.$formattedDate.'</a>';
//     })

//     ->editColumn('solved_at',function($ticket)
//     {
//      $solvedDate = $ticket->solved_at 
//      ? Carbon::parse($ticket->solved_at)->format('Y-m-d H:i:s') 
//     : ''; // Nilai default jika null

//     return '<a>'.$solvedDate.'</a>';
// })



//     ->rawColumns(['DT_RowIndex','id','id_customer','address','merchant','status','id_categori','tittle','created_by','assign_to','date','created_at','solved_at', 'tags'])
//     ->with('total', $total)
//     ->with('open', $open)
//     ->with('close', $close)
//     ->with('inprogress', $inprogress)
//     ->with('solve', $solve)
//     ->with('pending', $pending)

//     ->make(true);
// }


public function table_ticket_list(Request $request)
{

    $date_from    = $request->input('date_from');
    $date_end     = $request->input('date_end');
    $id_categori  = $request->input('id_categori');
    $assign_to    = $request->input('assign_to');
    $id_status    = $request->input('id_status');
    $ticketid     = $request->input('ticketid');
    $title        = $request->input('title');
    $tags         = (array) $request->input('tags', []);
    $create_by    = $request->input('create_by');
    $created_from = $request->input('created_from');
    $created_end  = $request->input('created_end');
    $ticket_type  = $request->input('ticket_type');

    // ✅ Cek semua filter
    $allEmpty = empty($date_from) 
    && empty($date_end) 
    && empty($id_categori) 
    && empty($assign_to) 
    && empty($id_status) 
    && empty($ticketid) 
    && empty($title) 
    && empty($tags) 
    && empty($create_by) 
    && empty($created_from) 
    && empty($created_end)
    && empty($ticket_type);

    if ($allEmpty) {
        // return DataTables kosong
        return \DataTables::of(collect([]))
        ->with('total', 0)
        ->with('open', 0)
        ->with('close', 0)
        ->with('inprogress', 0)
        ->with('solve', 0)
        ->with('pending', 0)
        ->with('mttr', 0)
        ->with('mttr_count', 0)
        ->make(true);
    }

    // --- lanjut query normal kalau ada filter ---
    $q = \App\Ticket::with(['customer','customer.merchant_name','categorie','user','tags','steps'])
                     ->withCount('children');

    // filter tanggal dan lainnya seperti biasa...
    if ($date_from && $date_end) {
        $q->whereBetween('date', [$date_from, $date_end]);
    } elseif ($date_from) {
        $q->where('date', '>=', $date_from);
    } elseif ($date_end) {
        $q->where('date', '=', $date_end);
    }

    $q->when($id_categori, fn($qq) => $qq->where('id_categori', $id_categori))
    ->when($assign_to,   fn($qq) => $qq->where('assign_to', $assign_to))
    ->when($id_status,   fn($qq) => $qq->where('status', $id_status))
    ->when($ticketid,    fn($qq) => $qq->where('id', $ticketid))
    ->when($title,       fn($qq) => $qq->where('tittle', 'like', "%{$title}%"));

    if (!empty($tags)) {
        $q->whereHas('tags', function ($sub) use ($tags) {
            $sub->whereIn('tags.id', $tags);
        });
    }

    if ($create_by) {
        $q->where('create_by', $create_by);
    }

    if ($created_from || $created_end) {
        $start = $created_from ? \Carbon\Carbon::parse($created_from)->startOfDay() : null;
        $end   = $created_end   ? \Carbon\Carbon::parse($created_end)->endOfDay()   : null;
        if ($start && $end) {
            $q->whereBetween('created_at', [$start, $end]);
        } elseif ($start) {
            $q->where('created_at', '>=', $start);
        } elseif ($end) {
            $q->where('created_at', '=', $end);
        }
    }

    // Filter by ticket type (parent/child/standalone)
    if ($ticket_type) {
        if ($ticket_type === 'parent') {
            // Only tickets that have children
            $q->has('children');
        } elseif ($ticket_type === 'child') {
            // Only tickets that have a parent
            $q->whereNotNull('parent_ticket_id');
        } elseif ($ticket_type === 'standalone') {
            // Only tickets without parent and without children
            $q->whereNull('parent_ticket_id')->doesntHave('children');
        }
    }

    $q->orderByDesc('id');

    $results = $q->get();

    // Hitung ringkasan (pastikan konsistensi case status)
    $total      = $results->count();
    $open       = $results->where('status', 'Open')->count();
    $close      = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve      = $results->where('status', 'Solve')->count();
    $pending    = $results->where('status', 'Pending')->count(); // di kode awal Anda 'pending' kecil

    // ========================================
    // Calculate MTTR for Closed Tickets
    // ========================================
    $closedTickets = $results->where('status', 'Close');
    $totalResolutionTime = 0;
    $mttrCount = 0;

    foreach ($closedTickets as $ticket) {
        $ticket->load('steps'); // Eager load steps jika belum
        $firstStep = $ticket->steps()->orderBy('position', 'asc')->first();
        $finishStep = $ticket->steps()->where('name', 'Finish')->first();
        
        if ($firstStep && $finishStep) {
            $resolutionHours = \Carbon\Carbon::parse($firstStep->created_at)
                ->diffInHours($finishStep->created_at);
            $totalResolutionTime += $resolutionHours;
            $mttrCount++;
        }
    }

    $mttr = $mttrCount > 0 ? round($totalResolutionTime / $mttrCount, 2) : 0;

    return \DataTables::of($results)
    ->addIndexColumn()

    ->editColumn('id', function ($t) {
        $parentBadge = $t->children_count > 0 
            ? '<span class="badge badge-warning mr-1" title="Parent Ticket with '.$t->children_count.' child ticket(s)"><i class="fas fa-sitemap"></i> PARENT ('.$t->children_count.')</span>' 
            : '';
        return $parentBadge.'<a href="'.url("/ticket/{$t->id}").'" class="badge badge-primary">#'.$t->id.'</a>';
    })

    ->editColumn('status', function ($t) {
            // Map warna bootstrap
        $map = [
            'Open'       => 'danger',
            'Close'      => 'secondary',
            'Pending'    => 'warning',
            'Solve'      => 'info',
            'Inprogress' => 'primary',
        ];
        $cls = $map[$t->status] ?? 'primary';
            // Perbaiki HTML tag (jangan pakai <badge>)
        return '<span class="badge badge-'.$cls.'">'.$t->status.'</span>';
    })

    ->editColumn('id_customer', function ($t) {
        $name = optional($t->customer)->name ?: '-';
        $cid  = $t->id_customer ?: 0;
        return '<a href="'.url("/customer/{$cid}").'" class="badge p-1 badge-success">'.$name.'</a>';
    })

    ->addColumn('merchant', function ($t) {
        $merchant = optional(optional($t->customer)->merchant_name);
        if (!$merchant) return '-';
        return '<a href="'.url("/merchant/{$merchant->id}").'" class="badge p-1 badge-primary">'.$merchant->name.'</a>';
    })

    ->addColumn('address', function ($t) {
        return e(optional($t->customer)->address ?: '-');
    })

    ->editColumn('id_categori', function ($t) {
        return '<span>'.e(optional($t->categorie)->name ?: '-').'</span>';
    })

    ->addColumn('tags', function ($t) {
        if (!$t->relationLoaded('tags')) return '';
        $out = '';
        foreach ($t->tags as $tag) {
            $out .= '<span class="badge badge-info mr-1">'.e($tag->name).'</span>';
        }
        return $out ?: '-';
    })

    ->editColumn('assign_to', function ($t) {
        return '<span>'.e(optional($t->user)->name ?: '-').'</span>';
    })

    ->editColumn('date', function ($t) {
        return '<span>'.e($t->date).' '.e($t->time).'</span>';
    })

    ->editColumn('created_at', function ($t) {
     return '<span>'.e($t->created_at).'</span>';
        // $formatted = $t->created_at ? Carbon::parse($t->created_at)->format('Y-m-d H:i:s') : '';
        // $human     = $formatted ? Carbon::parse($formatted)->diffForHumans() : '';
        // return '<span>'.e($human).'<br>'.e($formatted).'</span>';
 })

    ->addColumn('workflow_progress', function ($t) {
        // Calculate workflow progress percentage
        $totalSteps = $t->steps()->count();
        
        if ($totalSteps == 0) {
            return '<span class="badge badge-secondary">N/A</span>';
        }
        
        $currentStepId = $t->current_step_id;
        
        if (!$currentStepId) {
            return '<div class="progress" style="height: 20px;">
                      <div class="progress-bar bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>';
        }
        
        $currentStep = $t->steps()->where('id', $currentStepId)->first();
        
        if (!$currentStep) {
            return '<span class="badge badge-secondary">N/A</span>';
        }
        
        $currentPosition = $currentStep->position;
        $progressPercent = round(($currentPosition / $totalSteps) * 100);
        
        // If current step is "Finish" or ticket is closed, set to 100%
        if (strtolower($currentStep->name) === 'finish' || in_array($t->status, ['Close', 'Solve'])) {
            $progressPercent = 100;
        }
        
        // Determine color based on progress
        if ($progressPercent >= 75) {
            $color = 'bg-success';
        } elseif ($progressPercent >= 50) {
            $color = 'bg-info';
        } elseif ($progressPercent >= 25) {
            $color = 'bg-warning';
        } else {
            $color = 'bg-danger';
        }
        
        return '<div class="progress" style="height: 20px;">
                  <div class="progress-bar '.$color.'" role="progressbar" style="width: '.$progressPercent.'%;" aria-valuenow="'.$progressPercent.'" aria-valuemin="0" aria-valuemax="100">'.$progressPercent.'%</div>
                </div>';
    })

    ->addColumn('mttr', function ($t) {
        // Calculate MTTR for this specific ticket
        if ($t->status !== 'Close') {
            return '<span class="badge badge-secondary">-</span>';
        }
        
        $t->load('steps'); // Eager load steps if not loaded
        $firstStep = $t->steps()->orderBy('position', 'asc')->first();
        $finishStep = $t->steps()->where('name', 'Finish')->first();
        
        if (!$firstStep || !$finishStep) {
            return '<span class="badge badge-secondary">N/A</span>';
        }
        
        $resolutionHours = \Carbon\Carbon::parse($firstStep->created_at)
            ->diffInHours($finishStep->created_at);
        
        // Format output
        if ($resolutionHours < 1) {
            $minutes = \Carbon\Carbon::parse($firstStep->created_at)
                ->diffInMinutes($finishStep->created_at);
            return '<span class="badge badge-success" title="'.$minutes.' minutes">&lt; 1h</span>';
        } elseif ($resolutionHours < 24) {
            return '<span class="badge badge-success" title="'.$resolutionHours.' hours">'.round($resolutionHours, 1).'h</span>';
        } else {
            $days = floor($resolutionHours / 24);
            $hours = $resolutionHours % 24;
            return '<span class="badge badge-info" title="'.$resolutionHours.' hours">'.$days.'d '.round($hours).'h</span>';
        }
    })

        // Hanya kolom yang berisi HTML yang dimasukkan ke rawColumns
    ->rawColumns([
        'DT_RowIndex','id','status','id_customer','merchant',
        'address','id_categori','tags','assign_to','date','created_at','workflow_progress','mttr'
    ])

        // Statistik ringkasan
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)
    ->with('mttr', $mttr)
    ->with('mttr_count', $mttrCount)

    ->make(true);
}



public function table_groupticket_list(Request $request){

    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $id_categori = $request->input('id_categori');
    $assign_to = $request->input('assign_to');
    $id_status = $request->input('id_status');
    $ticket_type = $request->input('ticket_type');


 // Dapatkan grup dari pengguna aktif
    $currentUser = Auth::user();
    $currentGroupIds = $currentUser->groups->pluck('id'); // Grup pengguna aktif

    // Inisialisasi query
    $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end])
    ->withCount('children')
    ->with(['steps', 'customer', 'customer.merchant_name', 'categorie', 'user', 'tags'])
    ->whereHas('assignToUser.groups', function ($query) use ($currentGroupIds) {
            $query->whereIn('groups.id', $currentGroupIds); // Grup harus sama
        });

// Apply filters based on input
    if (!empty($id_categori)) {
        $ticket->where('id_categori', $id_categori);
    }

    if (!empty($assign_to)) {
        $ticket->where('assign_to', $assign_to);
    }

    if (!empty($id_status)) {
        $ticket->where('status', $id_status);
    }

// Filter by ticket type (parent/child/standalone)
    if (!empty($ticket_type)) {
        if ($ticket_type === 'parent') {
            // Only tickets that have children
            $ticket->has('children');
        } elseif ($ticket_type === 'child') {
            // Only tickets that have a parent
            $ticket->whereNotNull('parent_ticket_id');
        } elseif ($ticket_type === 'standalone') {
            // Only tickets without parent and without children
            $ticket->whereNull('parent_ticket_id')->doesntHave('children');
        }
    }

// Order the results
    $ticket->orderBy('id', 'DESC');

// Get the results
    $results = $ticket->get();



    $total = $results->count();
    $open = $results->where('status', 'Open')->count();
    $close = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve = $results->where('status', 'Solve')->count();
    $pending = $results->where('status', 'pending')->count();

    // ========================================
    // Calculate MTTR for Closed Tickets
    // ========================================
    $closedTickets = $results->where('status', 'Close');
    $totalResolutionTime = 0;
    $mttrCount = 0;

    foreach ($closedTickets as $ticket) {
        $ticket->load('steps'); // Eager load steps jika belum
        $firstStep = $ticket->steps()->orderBy('position', 'asc')->first();
        $finishStep = $ticket->steps()->where('name', 'Finish')->first();
        
        if ($firstStep && $finishStep) {
            $resolutionHours = \Carbon\Carbon::parse($firstStep->created_at)
                ->diffInHours($finishStep->created_at);
            $totalResolutionTime += $resolutionHours;
            $mttrCount++;
        }
    }

    $mttr = $mttrCount > 0 ? round($totalResolutionTime / $mttrCount, 2) : 0;


// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {
        $parentBadge = $ticket->children_count > 0 
            ? '<span class="badge badge-warning mr-1" title="Parent Ticket with '.$ticket->children_count.' child ticket(s)"><i class="fas fa-sitemap"></i> PARENT ('.$ticket->children_count.')</span>' 
            : '';
        return $parentBadge.' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center"> #'.$ticket->id. '</a>';
    })

    ->editColumn('id_customer',function($ticket)
    {
        $name = optional($ticket->customer)->name ?: '-';
        $cid  = $ticket->id_customer ?: 0;
        return '<a href="/customer/'.$cid.'" class="badge p-1 badge-success">'.$name.'</a>';
    })
    
    ->addColumn('address', function ($ticket) {
        return e(optional($ticket->customer)->address ?: '-');
    })
    
    ->addColumn('merchant',function($ticket)
    {
        $merchant = optional(optional($ticket->customer)->merchant_name);
        if (!$merchant) return '-';
        return '<a href="/merchant/'.$merchant->id.'" class="badge p-1 badge-primary">'.$merchant->name.'</a>';
    })
    
    ->editColumn('status',function($ticket)
    {
        if ($ticket->status == "Open") {
          $color='bg-danger'; 
          $btn_c='bg-danger'; 
        } elseif ($ticket->status == "Close") {
            $color='bg-secondary'; 
            $btn_c='bg-secondary'; 
        } elseif ($ticket->status == "Pending") {
          $color='bg-warning'; 
          $btn_c='bg-warning'; 
        } elseif ($ticket->status == "Solve") {
          $color='bg-info'; 
          $btn_c='bg-info'; 
        } else {
          $color='bg-primary'; 
          $btn_c='bg-primary'; 
        }
        return '<span class=" badge '. $btn_c. '">'.$ticket->status. '</span>';
    })
    
    ->editColumn('id_categori',function($ticket)
    {
        return '<span>'.e(optional($ticket->categorie)->name ?: '-').'</span>';
    })
    
    ->addColumn('tags', function ($ticket) {
        if (!$ticket->relationLoaded('tags')) return '-';
        $out = '';
        foreach ($ticket->tags as $tag) {
            $out .= '<span class="badge badge-info mr-1">'.e($tag->name).'</span>';
        }
        return $out ?: '-';
    })
    
    ->addColumn('create_by', function ($ticket) {
        return '<span>'.e($ticket->create_by ?: '-').'</span>';
    })
    
    ->editColumn('assign_to',function($ticket)
    {
        return '<span>'.e(optional($ticket->user)->name ?: '-').'</span>';
    })
    ->editColumn('date',function($ticket)
    {

     return '<a>'.$ticket->date. ' '. $ticket->time.'</a>';
 })

    ->editColumn('created_at',function($ticket)
    {
        $formattedDate = Carbon::parse($ticket->created_at)->format('Y-m-d H:i:s');
        $humanFormat =Carbon::parse($formattedDate)->diffForHumans(); 

        return '<a>'.$humanFormat. '</br>'.$formattedDate.'</a>';
    })

    ->addColumn('workflow_progress', function ($ticket) {
        // Calculate workflow progress percentage
        $totalSteps = $ticket->steps()->count();
        
        if ($totalSteps == 0) {
            return '<span class="badge badge-secondary">N/A</span>';
        }
        
        $currentStepId = $ticket->current_step_id;
        
        if (!$currentStepId) {
            return '<div class="progress" style="height: 20px;">
                      <div class="progress-bar bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>';
        }
        
        $currentStep = $ticket->steps()->where('id', $currentStepId)->first();
        
        if (!$currentStep) {
            return '<span class="badge badge-secondary">N/A</span>';
        }
        
        $currentPosition = $currentStep->position;
        $progressPercent = round(($currentPosition / $totalSteps) * 100);
        
        // If current step is "Finish" or ticket is closed, set to 100%
        if (strtolower($currentStep->name) === 'finish' || in_array($ticket->status, ['Close', 'Solve'])) {
            $progressPercent = 100;
        }
        
        // Determine color based on progress
        if ($progressPercent >= 75) {
            $color = 'bg-success';
        } elseif ($progressPercent >= 50) {
            $color = 'bg-info';
        } elseif ($progressPercent >= 25) {
            $color = 'bg-warning';
        } else {
            $color = 'bg-danger';
        }
        
        return '<div class="progress" style="height: 20px;">
                  <div class="progress-bar '.$color.'" role="progressbar" style="width: '.$progressPercent.'%;" aria-valuenow="'.$progressPercent.'" aria-valuemin="0" aria-valuemax="100">'.$progressPercent.'%</div>
                </div>';
    })

    ->addColumn('mttr', function ($ticket) {
        // Calculate MTTR for this specific ticket
        if ($ticket->status !== 'Close') {
            return '<span class="badge badge-secondary">-</span>';
        }
        
        $ticket->load('steps'); // Eager load steps if not loaded
        $firstStep = $ticket->steps()->orderBy('position', 'asc')->first();
        $finishStep = $ticket->steps()->where('name', 'Finish')->first();
        
        if (!$firstStep || !$finishStep) {
            return '<span class="badge badge-secondary">N/A</span>';
        }
        
        $resolutionHours = \Carbon\Carbon::parse($firstStep->created_at)
            ->diffInHours($finishStep->created_at);
        
        // Format output
        if ($resolutionHours < 1) {
            $minutes = \Carbon\Carbon::parse($firstStep->created_at)
                ->diffInMinutes($finishStep->created_at);
            return '<span class="badge badge-success" title="'.$minutes.' minutes">&lt; 1h</span>';
        } elseif ($resolutionHours < 24) {
            return '<span class="badge badge-success" title="'.$resolutionHours.' hours">'.round($resolutionHours, 1).'h</span>';
        } else {
            $days = floor($resolutionHours / 24);
            $hours = $resolutionHours % 24;
            return '<span class="badge badge-info" title="'.$resolutionHours.' hours">'.$days.'d '.round($hours).'h</span>';
        }
    })


    ->rawColumns(['DT_RowIndex','id','id_customer','address','status','id_categori','tittle','tags','create_by','assign_to','date','merchant','created_at','workflow_progress','mttr'])
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)
    ->with('mttr', $mttr)
    ->with('mttr_count', $mttrCount)

    ->make(true);
}


public function report(Request $request)
{
   if (($request->date_from == null) or ($request->date_end == null))
   {
     $from=date('Y-m-1');
     $to=date('y-m-d');

 }
 else
 {
     $from=$request->date_from;
     $to=$request->date_end;

 }


 $ticket_report = \App\Ticket::Join('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
 ->whereBetween('tickets.date', [$from, $to])
 ->groupBy('id_categori')
 ->select('tickets.id_categori as categori','ticketcategories.name as name', DB::raw("count(tickets.id_categori) as count"))->get();

 $ticket_date = \App\Ticket::whereBetween('tickets.date', [$from, $to])
 ->groupBy('date')
 ->select('date', DB::raw("count(date) as countdate"))->get();

 $ticket_customer = \App\Ticket::Join('customers', 'tickets.id_customer', '=', 'customers.id')
 ->whereBetween('tickets.date', [$from, $to])
 ->groupBy('id_customer')
 ->select('customers.id as cust_id','customers.name as name', DB::raw("count(tickets.id_customer) as count"))
 ->orderBy('count', 'DESC')
 ->limit(10)
 ->get();

 // Created tickets per day
 $ticket_created = \App\Ticket::whereBetween('tickets.date', [$from, $to])
 ->groupBy('date')
 ->select('date', DB::raw("count(id) as count"))
 ->orderBy('date', 'ASC')
 ->get();

 // Closed tickets per day
 $ticket_closed = \App\Ticket::where('status', 'Close')
 ->whereBetween('tickets.date', [$from, $to])
 ->groupBy('date')
 ->select('date', DB::raw("count(id) as count"))
 ->orderBy('date', 'ASC')
 ->get();

 // ========================================
 // MTTR Calculation (Mean Time To Resolution)
 // Based on Workflow Steps
 // ========================================
 $closedTickets = \App\Ticket::where('status', 'Close')
     ->whereBetween('tickets.date', [$from, $to])
     ->with('steps')
     ->get();

 $totalResolutionTime = 0;
 $mttrCount = 0;
 $stepTimeData = []; // For average time per step

 foreach ($closedTickets as $ticket) {
     $firstStep = $ticket->steps()->orderBy('position', 'asc')->first();
     $finishStep = $ticket->steps()->where('name', 'Finish')->first();
     
     if ($firstStep && $finishStep) {
         $resolutionHours = \Carbon\Carbon::parse($firstStep->created_at)
             ->diffInHours($finishStep->created_at);
         
         $totalResolutionTime += $resolutionHours;
         $mttrCount++;
         
         // Collect time between steps for analysis
         $steps = $ticket->steps()->orderBy('position', 'asc')->get();
         for ($i = 0; $i < count($steps) - 1; $i++) {
             $stepName = $steps[$i]->name;
             $timeInStep = \Carbon\Carbon::parse($steps[$i]->created_at)
                 ->diffInHours($steps[$i + 1]->created_at);
             
             if (!isset($stepTimeData[$stepName])) {
                 $stepTimeData[$stepName] = ['total' => 0, 'count' => 0];
             }
             $stepTimeData[$stepName]['total'] += $timeInStep;
             $stepTimeData[$stepName]['count']++;
         }
     }
 }

 $mttr = $mttrCount > 0 ? round($totalResolutionTime / $mttrCount, 2) : 0;
 
 // Calculate average time per step
 $avgStepTime = [];
 foreach ($stepTimeData as $stepName => $data) {
     $avgStepTime[$stepName] = round($data['total'] / $data['count'], 2);
 }

 return view ('ticket/report',['ticket_report' =>$ticket_report, 'ticket_customer' => $ticket_customer, 'date_from' =>$from, 'date_end' =>$to, 'ticket_date' => $ticket_date, 'ticket_created' => $ticket_created, 'ticket_closed' => $ticket_closed, 'mttr' => $mttr, 'mttr_count' => $mttrCount, 'avg_step_time' => $avgStepTime ]);



}

public function search(Request $request)
{
 $date_from = ($request['date_from']);
 $date_end = ($request['date_end']);

 $ticket = \App\Ticket::orderBy('id', 'DESC')
 ->whereBetween('date',[($request['date_from']), ($request['date_end'])])
 ->get();


 return view ('ticket/index',['ticket' =>$ticket, 'date_from' =>$date_from, 'date_end'  =>$date_end, 'ticket_date']);



}




public function uncloseticket()
{
    $id = Auth::user()->id;
    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('assign_to','=', $id)
    ->where('status','!=', 'Close')
    ->get();


    return view ('ticket/myticket',['ticket' =>$ticket, 'title'=>'Ticket List | My UnClose Ticket']);
}


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {

        $category = \App\Ticketcategorie::pluck('name', 'id');
        $user= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');
        $status = \App\Statuscustomer::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $plan = \App\Plan::pluck('name', 'id');
        $customer_coordinate = \App\Customer::where('id', $id)->pluck('coordinate');
        $tags = \App\Tag::pluck('name', 'id');

        $customer = \App\Customer::where('customers.id', $id)
        ->Join('distpoints', 'customers.id_distpoint', '=', 'distpoints.id')
        ->Join('statuscustomers', 'customers.id_status', '=', 'statuscustomers.id')
        ->Join('plans', 'customers.id_plan', '=', 'plans.id')
        ->select('customers.*','distpoints.name as distpoint_name','statuscustomers.name as status_name','plans.name as plan_name')->first();

        if ( $customer == null)
        {
            return abort(404);
        }
        else
        {

     //   dd($customer);
            if ($customer_coordinate == null)

            {
                $customer_coordinate ='-8.471722, 115.289472';
            }



            $config['center'] = $customer_coordinate;
            $config['zoom'] = '13';
//$this->googlemaps->initialize($config);

            $marker = array();
            $marker['position'] =$customer_coordinate;
            $marker['draggable'] = true;
            $marker['ondragend'] = 'updateDatabase(event.latLng.lat(), event.latLng.lng());';

            app('map')->initialize($config);

            app('map')->add_marker($marker);
            $map = app('map')->create_map();


            return view ('ticket/create',['customer' => $customer, 'map' => $map, 'status' => $status, 'distpoint'=>$distpoint, 'plan' => $plan, 'category'=>$category, 'user'=>$user , 'tags'=>$tags ] );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

       // dd ($request);


       $request ->validate([

        'id_customer' => 'required',
        'called_by' => 'required',
        'phone' => 'required',
        'status' => 'required',
        'id_categori' => 'required',
        'tittle'  => 'required',
        'description' => 'required',
        'assign_to' => 'required',
        'date' => 'required',
        'time' => 'required',
         'tags' => 'nullable|array', // Validasi untuk tags (boleh kosong)
     ]);




       $member = $request->input('member');
       if ($member == null)
       {
        $member ="";
    }
    else{



        $member = implode(',', $member);
    }
      //  dd ($member);
    $description=$request->input('description');
    $dom = new \DomDocument();
    $dom->loadHtml($description, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);    
    $images = $dom->getElementsByTagName('img');

    foreach($images as $key => $img){
        $data = $img->getAttribute('src');

        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $image_name= "/upload/ticket/" . time().$key.'.png';
        $path = public_path() . $image_name;

        file_put_contents($path, $data);

        $img->removeAttribute('src');
        $img->setAttribute('src', $image_name);
    }

    $description = $dom->saveHTML();
    $newTicket = \App\Ticket::create([
        'id_customer' => ($request['id_customer']),
        'called_by' => ($request['called_by']),
        'phone' => ($request['phone']), 
        'status' => ($request['status']),
        'id_categori' => ($request['id_categori']),
        'tittle' => ($request['tittle']),
            // 'description' => ($request['description']),
        'description' => $description,
        'assign_to' => ($request['assign_to']),
        'member' => ($member),
        'date' => ($request['date']),
        'time' => ($request['time']),
        'create_by' => ($request['create_by']),
        'updated_at' => ($request['created_at']),




    ]);
    if ($request->has('tags')) {
        $newTicket->tags()->sync($request->tags);
        
    }
    $customer = \App\Customer::findOrFail($request['id_customer']);


    if (env('WAPISENDER_STATUS')!="disable")
    {


       // $message = "[NEW TICKET]";
       // $message .= "\n\nHalo Tim,";
       // $message .= "\n\nSebuah tiket baru telah dibuat dengan detail berikut:";
       // $message .= "\n━━━━━━━━━━━━━━━";
       // $message .= "\nJudul: " . $request['tittle'];
       // $message .= "\nNama Pelanggan: " . $customer->name;
       // $message .= "\nNomor HP: " . $customer->phone;
       // $message .= "\nAlamat: " . $customer->address;
       // $message .= "\n━━━━━━━━━━━━━━━";

       // $message .= "\n\n🔗 Untuk melihat detail tiket, silakan klik tautan berikut:";
       // $message .= "\n" . "https://" . env('DOMAIN_NAME') . "/ticket/" . $newTicket->id;
       // $message .= "\n\n📌 Tiket ini dibuat oleh: *" . \Auth::user()->name . "*";
       // $message .= "\n\nTerima kasih! Jika ada yang perlu dikonfirmasi, silakan koordinasikan lebih lanjut.";
       // $message .= "\n\n~ " . config("app.signature") . "~";
       // $msgresult = WaGatewayHelper::wa_payment($customers->phone, $message);

    //  $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
    //     'form_params' => [
    //         'token' => env('WAPISENDER_KEY'),
    //         'number' =>env('WAPISENDER_GROUPTICKET'),
    //         'message' =>$message,
    //     ]
    // ]);


     // $result_g= $result_g->getBody();
     // $array = json_decode($result_g, true);
    //    $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
    //     env("TELEGRAM_GROUP_SUPPORT"), $message]);
    //    try {
    //         // Menjalankan proses
    //     $process->run();

    //         // Memeriksa apakah proses berhasil
    //     if (!$process->isSuccessful()) {
    //         throw new ProcessFailedException($process);
    //     }

    //         // Mendapatkan output dari proses
    //     $output = $process->getOutput();

    //     return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
    // } catch (ProcessFailedException $e) {
    //         // Jika proses gagal, kembalikan pesan kesalahan
    //     $errorMessage = $e->getMessage();
    //     return redirect()->back()->with('error', $errorMessage);
    // }

    }


    return redirect ('/ticket/view/'.$request['id_customer'])->with('success','Item created successfully!');


}


    /**
     * Display the specified resource.
     *
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
//     public function show($id)
//     {
//         $ticket = \App\Ticket::findOrFail($id);

//         $category = \App\Ticketcategorie::pluck('name', 'id');
//       //  $user= \App\User::pluck('name', 'id');

//         $distpoint = \App\Distpoint::pluck('name', 'id');
//         $sale = \App\Sale::pluck('name', 'id', 'phone');
//         $plan = \App\Plan::pluck('name', 'id');
//         $user = \App\User::pluck('name', 'id');
//        // $users = \App\User::pluck('name', 'id');
//         $users= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');
//         // Ambil tag ID dan nama agar bisa dipilih di select box
//         $tags = $ticket->tags->pluck('name', 'id')->toArray();
// // Ambil semua tag dengan ID dan name agar bisa dipakai di form select
// $alltags = \App\Tag::pluck('name', 'id'); // Perbaikan: Ambil ID juga, bukan hanya nama


// $user = Auth::user()->privilege;

// if ($user == "vendor")
// {
//  return view ('ticket/vendorshow',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category, 'sale'=>$sale ] );
// }
// else
// {
//     return view ('ticket/show',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category, 'sale'=>$sale,'tags'=>$tags, 'alltags'=>$alltags ] );
// }


//      //return view ('ticket/show',['ticket' => $ticket] );
// }




    public function show($id)
    {
        $ticket = \App\Ticket::with('category', 'steps')->findOrFail($id);

        $category = \App\Ticketcategorie::pluck('name', 'id');
        $distpoint = \App\Distpoint::pluck('name', 'id');
        $sale = \App\Sale::pluck('name', 'id', 'phone');
        $plan = \App\Plan::pluck('name', 'id');
        $user = \App\User::pluck('name', 'id');
        $users = \App\User::where('privilege', '!=', 'counter')->pluck('name', 'id');

    // Ambil tag untuk form
        $tags = $ticket->tags->pluck('name', 'id')->toArray();
        $alltags = \App\Tag::pluck('name', 'id');
    //     $workflow = Workflow::where('name', 'Ticket Workflow')->first();

    // // 🔑 Tambahan: Ambil workflow & steps
    // $workflowSteps = collect(); // default kosong

    // if ($ticket->category && $ticket->category->workflow) {
    //     $workflow = \App\Workflow::where('name', $ticket->category->workflow)
    //     ->with('steps')
    //     ->first();

    //     if ($workflow) {
    //         $workflowSteps = $workflow->steps;
    //     }
    // }

        $workflowSteps = $ticket->steps()->orderBy('position')->get();
 // 🔑 Ambil step aktif
        $currentStep = $ticket->current_step_id 
        ? \App\TicketStep::find($ticket->current_step_id) 
        : null;

        $userRole = \Auth::user()->privilege;

        if ($userRole == "vendor") {
            return view('ticket/vendorshow', [
                'ticket' => $ticket,
                'distpoint' => $distpoint,
                'user' => $userRole,
                'users' => $users,
                'plan' => $plan,
                'category' => $category,
                'sale' => $sale,
            'currentStep'   => $currentStep, // 👈 kirim ke view
            'workflowSteps' => $workflowSteps, // kirim ke view
        ]);
        } else {
            return view('ticket/show', [
                'ticket' => $ticket,
                'distpoint' => $distpoint,
                'user' => $userRole,
                'users' => $users,
                'plan' => $plan,
                'category' => $category,
                'sale' => $sale,
                'tags' => $tags,
                'alltags' => $alltags,
                'currentStep'   => $currentStep, // 👈 kirim ke view
            'workflowSteps' => $workflowSteps, // kirim ke view
        ]);
        }
    }




    // public function vendorshow($id)
    // {
    //     $ticket = \App\Ticket::findOrFail($id);

    //     $category = \App\Ticketcategorie::pluck('name', 'id');
    //   //  $user= \App\User::pluck('name', 'id');

    //     $distpoint = \App\Distpoint::pluck('name', 'id');
    //     $sale = \App\Sale::pluck('name', 'id', 'phone');
    //     $plan = \App\Plan::pluck('name', 'id');
    //     $user = \App\User::pluck('name', 'id');
    //    // $users = \App\User::pluck('name', 'id');
    //     $users= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');




    //     return view ('ticket/vendorshow',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category, 'sale'=>$sale ] );
    //  //return view ('ticket/show',['ticket' => $ticket] );
    // }

    public function print_ticket($id)
    {
        $ticket = \App\Ticket::findOrFail($id);

        $category = \App\Ticketcategorie::pluck('name', 'id');
        $user= \App\User::pluck('name', 'id');

        $distpoint = \App\Distpoint::pluck('name', 'id');

        $plan = \App\Plan::pluck('name', 'id');
        $user = \App\User::pluck('name', 'id');
        $users = \App\User::pluck('name', 'id');


        $pdf = PDF::loadview('pdf',['ticket' => $ticket, 'distpoint'=>$distpoint,'user'=>$user,'users'=>$users, 'plan' => $plan, 'category'=>$category ] );
        return $pdf->download('Ticket-pdf'.$id);


     //return view ('ticket/show',['ticket' => $ticket] );
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
       $ticket = \App\Ticket::findOrFail($id);
       $customer = \App\Customer::findOrFail($ticket->id_customer);

       $category = \App\Ticketcategorie::pluck('name', 'id');
      //  $user= \App\User::pluck('name', 'id');

       $distpoint = \App\Distpoint::pluck('name', 'id');

       $plan = \App\Plan::pluck('name', 'id');
       // $user = \App\User::pluck('name', 'id');
       $user= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');
       return view ('ticket/edit',['ticket' => $ticket, 'customer' =>$customer, 'distpoint'=>$distpoint,'user'=>$user, 'plan' => $plan, 'category'=>$category ] );

   }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tiket $tiket)
    {
        //
    }
    public function workflowDelete(Request $request, Ticket $ticket)
    {
        $stepId = $request->input('step_id');
        $step = $ticket->steps()->where('id', $stepId)->first();

        if ($step) {
            $step->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function vendoreditticket(Request $request, $id)
    {
       $solved_at = null;
       $member = $request->input('member');
       if ($member == null)
       {
        $member ="";
    }
    else{



        $member = implode(',', $member);
    }
    if ($request->status == 'Close')
    {

      $ticket = \App\Ticket::where('id', $id)->first();

      if( $ticket->status != 'close' )
      {
       if (env('WAPISENDER_STATUS')!="disable")
       {

                 // $message .= "\nOpen this  url to show the update : http://".env('DOMAIN_NAME')."/ticket/".$ticket->id;

         $message = "[TICKET CLOSED]";
         $message .= "\n\nTerima Kasih " . \Auth::user()->name . ",";
         $message .= "\n\nKami ingin menginformasikan bahwa tiket berikut telah di CLOSE:";
         $message .= "\n━━━━━━━━━━━━━━━";
         $message .= "\nJudul Tiket: " . $ticket->tittle;
         $message .= "\nNama Pelanggan: " . $ticket->customer->name;
         $message .= "\nNomor HP: " . $ticket->customer->phone;
         $message .= "\nAlamat: " . $ticket->customer->address;
         $message .= "\n━━━━━━━━━━━━━━━";

         $message .= "\n\nUntuk informasi lebih lanjut, silakan kunjungi tautan berikut:";
         $message .= "\nhttps://".env('DOMAIN_NAME')."/ticket/".$ticket->id;
         $message .= "\n\nSalam 😊";
         $message .= "\n\n~ " . config("app.signature") . "~";



         $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_group.py", 
            env("TELEGRAM_GROUP_SUPPORT"), $message]);
         try {
            // Menjalankan proses
            $process->run();

            // Memeriksa apakah proses berhasil
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Mendapatkan output dari proses
            $output = $process->getOutput();

          //  return redirect ('/ticket/view/'.$request['id_customer'])->with('success', $output);
        } catch (ProcessFailedException $e) {
            // Jika proses gagal, kembalikan pesan kesalahan
            $errorMessage = $e->getMessage();
          //  return redirect()->back()->with('error', $errorMessage);
        }

    }

}



}


elseif ($request->status == 'Solve')
{
    $solved_at = Carbon::now();

}

\App\Ticket::where('id', $id)
->update([
    // 'tittle' => $request->tittle,
    'status' => $request->status,
    'id_categori' => $request->category,
    'assign_to' => ($request['assign_to']),
    'member' => ($member),
    'date' => ($request['date']),
    'time' => ($request['time']),
    'solved_at' => ($solved_at),



]);

 // Record changes
$note = "";
// if ($ticket->tittle != $request->tittle) {
//     $note .= 'tittle changed from ' .$ticket->tittle. ' to ' .$request->tittle ;
// }

if ($ticket->status != $request->status) {
    $note .= '<p> Status changed from ' .$ticket->status. ' to ' .$request->status.'</p>' ;
}

if($ticket->assign_to != $request['assign_to'])
{
    $newAssignee = $request['assign_to'] ? \App\User::find($request['assign_to']) : null;
    $newAssigneeName = $newAssignee ? $newAssignee->name : 'Unassigned';

    $note .= "<p>Assigned changed  to " . $newAssigneeName . '</p>';
}



if (!empty($note))
{

    \App\Ticketdetail::create([
            'id_ticket' => $id, // Assuming `id_ticket` is the ticket ID
            'description' => $note,    // Assuming a `note` field exists in the `Ticketdetail` model
            'updated_by' => \Auth::user()->name,
        ]);


}

$url ='/vendorticket/'.$request->id;
return redirect ($url)->with('success','Item updated successfully!. ');
}

public function editticket(Request $request, $id)
{

    $ticket = \App\Ticket::where('id', $id)->first();
    $oldTicket = clone $ticket;
    $member = $request->input('member');
    $solved_at = null;  
    $member = $member ? implode(',', $member) : '';
    // if ($member == null)
    // {
    //     $member ="";
    // }
    // else{



    //     $member = implode(',', $member);
    // }


 // =========================================================
    // HANDLE STATUS CLOSE
    // =========================================================
    if ($request->status === 'Close') {

        if ($ticket->status !== 'Close') {
            if (env('WAPISENDER_STATUS') != "disable") {
                $message = "Terima Kasih " . \Auth::user()->name . "," .
                "\n\nKami ingin menginformasikan bahwa tiket berikut telah di CLOSE" .
                "\n━━━━━━━━━━━━━━━" .
                "\nJudul Tiket: " . $ticket->tittle .
                "\nNama Pelanggan: " . $ticket->customer->name .
                "\nNomor HP: " . $ticket->customer->phone .
                "\nAlamat: " . $ticket->customer->address .
                "\n━━━━━━━━━━━━━━━" .
                "\n\nUntuk informasi lebih lanjut, silakan kunjungi tautan berikut:" .
                "\n https://" . env('DOMAIN_NAME') . "/ticket/" . $ticket->id .
                "\n\nSalam 😊" .
                "\n\n~ " . config("app.signature") . " ~";

                $process = new Process(["python3", env("PHYTON_DIR") . "telegram_send_to_group.py",
                    env("TELEGRAM_GROUP_SUPPORT"), $message]);
                try {
                    $process->run();
                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }
                } catch (ProcessFailedException $e) {
                    \Log::error("Telegram error: " . $e->getMessage());
                }
            }
        }

        // === Tambahkan atau ambil Step "Finish" ===
        $lastPosition = $ticket->steps()->max('position') ?? 0;
        $finishStep = $ticket->steps()->where('name', 'Finish')->first();

        if (!$finishStep) {
            $finishStep = $ticket->steps()->create([
                'name'     => 'Finish',
                'position' => $lastPosition + 1,
            ]);
        }

        // Pastikan current_step_id diset ke Finish
        $ticket->update(['current_step_id' => $finishStep->id]);
        $ticket->refresh();
    }
    elseif ($request->status == 'Solve')
    {
        $solved_at = Carbon::now();

    }


    \App\Ticket::where('id', $id)
    ->update([
        'tittle' => $request->tittle,
        'status' => $request->status,
        'id_categori' => $request->category,
        'assign_to' => ($request['assign_to']),
        'member' => ($member),
        'date' => ($request['date']),
        'time' => ($request['time']),
        'solved_at' => ($solved_at),


    ]);


   // =========================================================
    // BUAT DEFAULT WORKFLOW SAAT INPROGRESS (JIKA BELUM ADA)
    // =========================================================
    if ($request->status === 'Inprogress' && $ticket->steps()->count() === 0) {
        $defaultWorkflow = [];

        if ($ticket->categorie && $ticket->categorie->workflow) {
            if (is_array($ticket->categorie->workflow)) {
                $defaultWorkflow = $ticket->categorie->workflow;
            } elseif (is_string($ticket->categorie->workflow)) {
                $defaultWorkflow = array_map('trim', explode(',', $ticket->categorie->workflow));
            }
        }

        if (!empty($defaultWorkflow)) {
            foreach ($defaultWorkflow as $index => $stepName) {
                $step = $ticket->steps()->create([
                    'name'     => $stepName,
                    'position' => $index + 1,
                ]);

                if ($index === 0) {
                    $ticket->current_step_id = $step->id;
                }
            }
            $ticket->save();
        }
    }


//=====================================================


//==================================================


// if ($request->has('tags')) {
//     $ticket->tags()->sync($request->tags);
// }

    $ticket->tags()->sync($request->input('tags', []));

 // Record changes
    $note = "";
    if ($oldTicket->tittle != $request->tittle) {
        $note .= 'tittle changed from ' .$oldTicket->tittle. ' to ' .$request->tittle ;
    }

    if ($oldTicket->status != $request->status) {
        $note .= '<p> Status changed from ' .$oldTicket->status. ' to ' .$request->status.'</p>' ;
    }

    if($oldTicket->assign_to != $request['assign_to'])
    {
        $newAssignee = $request['assign_to'] ? \App\User::find($request['assign_to']) : null;
        $newAssigneeName = $newAssignee ? $newAssignee->name : 'Unassigned';

        $note .= "<p>Assigned changed  to " . $newAssigneeName . '</p>';
    }

    if ($oldTicket->id_categori != $ticket->id_categori) { $oldCategoryName = $oldTicket->category ? $oldTicket->category->name : '-'; $newCategory = \App\Ticketcategorie::find($ticket->id_categori); $newCategoryName = $newCategory ? $newCategory->name : '-'; $note .= "<p>Category changed from {$oldCategoryName} to {$newCategoryName}</p>"; }

    if (!empty($note))
    {

        \App\Ticketdetail::create([
            'id_ticket' => $id,
            'description' => $note,
            'updated_by' => \Auth::user()->name,
        ]);
    }

    // === Auto-close parent if this is a child ticket and status changed to Close/Solve ===
    if ($ticket->isChild() && in_array($request->status, ['Close', 'Solve'])) {
        if ($ticket->parent) {
            $autoClosedParent = $ticket->parent->autoCloseIfChildrenComplete();
            if ($autoClosedParent) {
                \Log::info("Parent ticket #{$ticket->parent->id} auto-closed because all children are complete");
            }
        }
    }

    $url = '/ticket/' . $request->id;
    return redirect($url)->with('success', 'Item updated successfully!');
}



public function tvwall()
{
    $tickets = \App\Ticket::with(['steps', 'user', 'customer'])
    ->latest()
    ->take(40)
    ->get();

    return view('ticket.tvwall', compact('tickets'));
}

// public function tvwallData()
// {
//     $tickets = \App\Ticket::with(['steps', 'user', 'customer'])
//     ->latest()
//     ->take(40)
//     ->get();

//     return view('ticket.tvwall-cards', compact('tickets'));
// }

// public function tvwallData(Request $req)
// {
//     $query = Ticket::with(['steps', 'user', 'customer'])
//     ->leftJoin('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
//     ->leftJoin('tickettags', 'tickets.id', '=', 'tickettags.ticket_id')
//     ->leftJoin('tags', 'tickettags.tag_id', '=', 'tags.id')
//     ->select('tickets.*', 'ticketcategories.name as category_name', 'tags.name as tag_name')
//     ->latest('tickets.id');

//         // === FILTER ===
//     if ($req->filled('status') && $req->status !== 'all') {
//         $query->where('tickets.status', $req->status);
//     }

//     if ($req->filled('category') && $req->category !== 'all') {
//         $query->where('ticketcategories.id', $req->category);
//     }

//     if ($req->filled('tag') && $req->tag !== 'all') {
//         $query->where('tags.id', $req->tag);
//     }

//     $tickets = $query->take(40)->get();

//     return view('ticket.tvwall-cards', compact('tickets'));
// }


// public function tvwallData(Request $req)
// {
//     $query = \App\Ticket::with(['steps', 'user', 'customer'])
//     ->leftJoin('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
//     ->leftJoin('tickettags', 'tickets.id', '=', 'tickettags.ticket_id')
//     ->leftJoin('tags', 'tickettags.tag_id', '=', 'tags.id')
//     ->select('tickets.*', 'ticketcategories.name as category_name', 'tags.name as tag_name')
//     ->latest('tickets.id');

//     // === FILTERS ===
//     if ($req->filled('status') && $req->status !== 'all') {
//         $query->where('tickets.status', $req->status);
//     }

//     if ($req->filled('category') && $req->category !== 'all') {
//         $query->where('ticketcategories.id', $req->category);
//     }

//     if ($req->filled('tag') && $req->tag !== 'all') {
//         $query->where('tags.id', $req->tag);
//     }

//     // 🔍 Filter tanggal
//     if ($req->filled('start_date') && $req->filled('end_date')) {
//         $query->whereBetween('tickets.date', [$req->start_date, $req->end_date]);
//     } elseif ($req->filled('start_date')) {
//         $query->whereDate('tickets.date', '>=', $req->start_date);
//     } elseif ($req->filled('end_date')) {
//         $query->whereDate('tickets.date', '<=', $req->end_date);
//     }

//     $tickets = $query->take(40)->get();

//     return view('ticket.tvwall-cards', compact('tickets'));
// }
public function tvwallData(Request $req)
{
    $query = \App\Ticket::with(['steps', 'user', 'customer'])
    ->leftJoin('ticketcategories', 'tickets.id_categori', '=', 'ticketcategories.id')
    ->leftJoin('tickettags', 'tickets.id', '=', 'tickettags.ticket_id')
    ->leftJoin('tags', 'tickettags.tag_id', '=', 'tags.id')
    ->select('tickets.*')
    ->distinct('tickets.id')
    ->latest('tickets.id');

    if ($req->filled('status') && $req->status !== 'all')
        $query->where('tickets.status', $req->status);
    if ($req->filled('category') && $req->category !== 'all')
        $query->where('ticketcategories.id', $req->category);
    if ($req->filled('tag') && $req->tag !== 'all')
        $query->where('tags.id', $req->tag);
    if ($req->filled('start_date') && $req->filled('end_date'))
        $query->whereBetween('tickets.date', [$req->start_date, $req->end_date]);

    $tickets = $query->get();

    $summary = [
        'Open'       => $tickets->where('status', 'Open')->count(),
        'Inprogress' => $tickets->where('status', 'Inprogress')->count(),
        'Pending'    => $tickets->where('status', 'Pending')->count(),
        'Solve'      => $tickets->where('status', 'Solve')->count(),
        'Close'      => $tickets->where('status', 'Close')->count(),
        'Total'      => $tickets->count(),
    ];

    return response()->json([
        'grid' => view('ticket.tvwall-cards', compact('tickets'))->render(),
        'summary' => view('ticket.tvwall-summary', compact('tickets'))->render(),
    ]);
}




public function startWorkflow($id)
{
    $ticket = Ticket::findOrFail($id);

    // Cek jika sudah ada workflow steps
    if ($ticket->steps()->count() > 0) {
        return response()->json([
            'success' => false,
            'message' => 'Workflow sudah ada untuk ticket ini'
        ]);
    }

    // Ambil default workflow dari kategori
    $defaultWorkflow = [];
    if ($ticket->categorie && $ticket->categorie->workflow) {
        if (is_array($ticket->categorie->workflow)) {
            $defaultWorkflow = $ticket->categorie->workflow;
        } elseif (is_string($ticket->categorie->workflow)) {
            $defaultWorkflow = array_map('trim', explode(',', $ticket->categorie->workflow));
        }
    }

    // Jika tidak ada workflow di kategori, gunakan default sederhana
    if (empty($defaultWorkflow)) {
        $defaultWorkflow = ['Start', 'Inprogress', 'Complete', 'Finish'];
    }

    // Buat workflow steps
    foreach ($defaultWorkflow as $index => $stepName) {
        if (empty(trim($stepName))) continue; // Skip empty steps
        
        $step = $ticket->steps()->create([
            'name'     => trim($stepName),
            'position' => $index + 1,
        ]);

        // Set step pertama jadi current_step
        if ($index === 0) {
            $ticket->current_step_id = $step->id;
        }
    }

    // Update status ticket ke Inprogress (kecuali sudah Close/Solve)
    if (!in_array($ticket->status, ['Close', 'Solve'])) {
        $ticket->status = "Inprogress";
    }
    $ticket->save();

    return response()->json([
        'success' => true,
        'message' => 'Workflow berhasil dimulai'
    ]);
}


public function moveStep(Request $request, $ticketId)
{
    $ticket = \App\Ticket::findOrFail($ticketId);
    $stepId = $request->input('step_id');

    // Pastikan step itu milik tiket ini
    $step = $ticket->steps()->where('id', $stepId)->first();

    if (!$step) {
        return response()->json([
            'success' => false,
            'message' => 'Step tidak valid untuk tiket ini'
        ], 400);
    }

    // Update hanya current_step_id, tanpa mengubah status
    $ticket->current_step_id = $step->id;
    $ticket->save();

    // Simpan log perubahan step ke Ticketdetail (opsional tapi recommended)
    \App\Ticketdetail::create([
        'id_ticket'   => $ticket->id,
        'description' => "Workflow pindah ke step: <b>{$step->name}</b>",
        'updated_by'  => \Auth::user()->name,
    ]);

    return response()->json([
        'success' => true,
        'step' => $step->name
    ]);
}


public function updateassign(Request $request, $id)
{
    // $member = $request->input('member');
    //     if ($member == null)
    //     {
    //         $member ="";
    //     }
    //     else{



    //     $member = implode(',', $member);
    // }

    //     \App\Ticket::where('id', $id)
    //         ->update([
    //          'assign_to' => ($request['assign_to']),
    //         'member' => ($member),


    //         ]);
    //         $url ='ticket/'.$request->id;
    //    return redirect ($url)->with('success','Item updated successfully!');
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tiket  $tiket
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tiket $tiket)
    {
        //
    }


//     public function wa_ticket(Request $request)
//     {

//        if (env('WAPISENDER_STATUS')!="disable")
//        {
//         $client = new Clients();
//         $number = $request->phone;

//         if(substr(trim($number), 0, 2)=="62"){
//             $hp    =trim($number);
//         }
//             // cek apakah no hp karakter ke 1 adalah angka 0
//         else if(substr(trim($number), 0, 1)=="0"){
//             $hp    ="62".substr(trim($number), 1);
//         }

//         $result = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
//             'form_params' => [
//             // 'api_key' => env('WAPISENDER_KEY'),
//             // 'device_key' => $request->device,
//             // 'destination' => $hp,
//                'token' => env('WAPISENDER_KEY'),
//                'number' => $hp,
//                'message' =>$request->message,
//            ]
//        ]);

// //Kirim pesan ke group
//         $result= $result->getBody();
//         $array = json_decode($result, true);

//     // $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
//     //     'form_params' => [
//     //         'key' => env('WAPISENDER_KEY'),
//     //         'device' => $request->device,
//     //         'group_id' =>env('WAPISENDER_GROUPTICKET'),
//     //         'message' =>$request->message,
//     //     ]
//     // ]);


//     // $result_g= $result_g->getBody();
//     // $array = json_decode($result_g, true);
// //    return redirect ('/ticket/'.$request->id_ticket)->with('success','Message '.$array['status'].' - '.$array['message']); 
//         return redirect()->back()->with('success','Message '.$array['status']); 
//     }
//     else
//     {
//         return redirect()->back()->with('error','WA Disabled');
//     }

// }

    public function datamap()
    {
        $today = Carbon::today();

        $tickets = \App\Ticket::with('customer')
        ->whereDate('date', $today)
        ->get();

        $result = $tickets->map(function ($ticket) {
        // Ambil koordinat customer dalam format "latitude,longitude"
            $coordinates = $ticket->customer->coordinate;

        // Pisahkan koordinat menjadi array
            $coords = explode(',', $coordinates);

        // Kembalikan data yang diperlukan dalam array
            return [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'description' => $ticket->tittle,
            'lat' => isset($coords[0]) ? $coords[0] : null, // Pastikan ada data lat
            'lng' => isset($coords[1]) ? $coords[1] : null, // Pastikan ada data lng
            'customer_name' => $ticket->customer->name,
            'assign_to' => $ticket->assignToUser->name,
            'date' => $ticket->date,
        ];
    });

        return response()->json($result);
    }



   // public function wa_ticket(Request $request)
   //// {

       // $msgresult = WaGatewayHelper::wa_payment($request->phone, $request->message);
        // if (env('WAPISENDER_STATUS') != "disable") {
        //     $number = trim($request->phone);

        //     if (substr($number, 0, 2) == "62") {
        //         $hp = "+" . $number;
        //     } elseif (substr($number, 0, 1) == "0") {
        //         $hp = "+62" . substr($number, 1);
        //     } else {
        //         $hp = "+" . $number;
        //     }

        //     $phone = $hp;
        //     $message = $request->message;


        //     $process = new Process(["python3", env("PHYTON_DIR")."telegram_send_to_phone.py", 
        //         $phone, $message]);


        //     try {
        //     // Menjalankan proses
        //         $process->run();

        //     // Memeriksa apakah proses berhasil
        //         if (!$process->isSuccessful()) {
        //             throw new ProcessFailedException($process);
        //         }

        //     // Mendapatkan output dari proses
        //         $output = $process->getOutput();

        //         return redirect()->back()->with('success', $output);
        //     } catch (ProcessFailedException $e) {
        //     // Jika proses gagal, kembalikan pesan kesalahan
        //         $errorMessage = $e->getMessage();
        //         return redirect()->back()->with('error', $errorMessage);
        //     }
        // } else {
        //     return redirect()->back()->with('error', 'Telegram Disabled');
        // }
   // }


    public function wa_ticket(Request $request)
    {

        if (env('WAPISENDER_STATUS') != "disable") {
            $request->validate([
                'phone'   => 'required|string',
                'message' => 'required|string',
            ]);

            try {
                $msgresult = \App\Helpers\WaGatewayHelper::wa_payment($request->phone, $request->message);

                if ($msgresult['status'] === 'success') {
                    return redirect()
                    ->back()
                    ->with('success', 'Pesan berhasil dikirim via ' . ($msgresult['session'] ?? 'session tidak diketahui'));
                }

                return redirect()
                ->back()
                ->with('error', $msgresult['message'] ?? 'Gagal mengirim pesan ke WhatsApp.');
            } catch (\Throwable $e) {
                \Log::error('wa_ticket() error: ' . $e->getMessage());

                return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan internal: ' . $e->getMessage());
            }
        }
        else {
            return redirect()->back()->with('error', 'WA Disabled');
        }
    }

    // Parent-Child Ticket Methods
    public function createChild($parentId)
    {
        $parent = Ticket::findOrFail($parentId);
        
        // Update parent type jika masih standalone
        if ($parent->ticket_type === 'standalone') {
            $parent->update(['ticket_type' => 'parent']);
        }
        
        $ticketcategorie = Ticketcategorie::pluck('name', 'id');
        $tags = Tag::pluck('name', 'id');
        $user = \App\User::where('privilege', '!=', 'counter')->pluck('name', 'id');
        $customer = \App\Customer::pluck('name', 'id');
        
        return view('ticket.create_child', compact('parent', 'ticketcategorie', 'tags', 'user', 'customer'));
    }

    public function storeChild(Request $request, $parentId)
    {
        $parent = Ticket::findOrFail($parentId);
        
        $request->validate([
            'tittle' => 'required',
            'description' => 'required',
            'id_categori' => 'required',
            'assign_to' => 'required',
            'status' => 'required',
            'date' => 'required',
            'time' => 'required',
        ]);

        // Process description images
        $description = $request->input('description');
        $dom = new \DomDocument();
        $dom->loadHtml($description, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);    
        $images = $dom->getElementsByTagName('img');

        foreach($images as $key => $img){
            $data = $img->getAttribute('src');
            list($type, $data) = explode(';', $data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);

            $image_name = "/upload/ticket/" . time().$key.'.png';
            $path = public_path() . $image_name;
            file_put_contents($path, $data);

            $img->removeAttribute('src');
            $img->setAttribute('src', $image_name);
        }
        $description = $dom->saveHTML();

        $member = $request->input('member');
        if ($member != null) {
            $member = implode(',', $member);
        } else {
            $member = "";
        }

        // Create child ticket
        $childTicket = Ticket::create([
            'parent_ticket_id' => $parent->id,
            'ticket_type' => 'child',
            'id_customer' => $parent->id_customer,
            'called_by' => $request->input('called_by', $parent->called_by),
            'phone' => $request->input('phone', $parent->phone),
            'status' => $request->status,
            'id_categori' => $request->id_categori,
            'tittle' => $request->tittle,
            'description' => $description,
            'assign_to' => $request->assign_to,
            'member' => $member,
            'date' => $request->date,
            'time' => $request->time,
            'create_by' => Auth::user()->name,
        ]);

        // Attach tags
        if ($request->filled('tags')) {
            $childTicket->tags()->attach($request->tags);
        }

        return redirect("/ticket/{$parent->id}")->with('success', 'Child ticket created successfully');
    }

    public function convertToParent($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        if ($ticket->ticket_type === 'standalone') {
            $ticket->update(['ticket_type' => 'parent']);
            return response()->json(['success' => true, 'message' => 'Ticket converted to parent']);
        }
        
        return response()->json(['success' => false, 'message' => 'Ticket is already a parent or child']);
    }

    public function checkParentAutoClose($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        
        if ($ticket->isChild() && $ticket->parent) {
            $closed = $ticket->parent->autoCloseIfChildrenComplete();
            return response()->json([
                'success' => true, 
                'parent_closed' => $closed,
                'message' => $closed ? 'Parent ticket auto-closed' : 'Parent ticket still open'
            ]);
        }
        
        return response()->json(['success' => false, 'message' => 'Not a child ticket']);
    }

}
