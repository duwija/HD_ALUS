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
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailTicketNotification;
use App\User;
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



    $q = \App\Ticket::with(['customer','categorie','user','steps','currentStep'])
        ->whereBetween('date', [$date_from, $date_end])
        ->where('assign_to', $user);

    if (!empty($id_status)) {
        $q->where('status', $id_status);
    }

    $q->orderBy('date', 'DESC')->orderBy('time', 'DESC');
    $results = $q->get();

    $total      = $results->count();
    $open       = $results->where('status', 'Open')->count();
    $close      = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve      = $results->where('status', 'Solve')->count();
    $pending    = $results->where('status', 'Pending')->count();

    // MTTR aggregate — sama persis dengan table_ticket_list (berbasis workflow steps)
    $mttrValues = [];
    foreach ($results as $t) {
        if ($t->steps->isEmpty()) continue;
        $firstStep  = $t->steps->first();
        $finishStep = $t->steps->firstWhere('name', 'Finish');
        if (!$finishStep) continue;
        $isFinished = ($t->current_step_id == $finishStep->id)
                   || in_array($t->status, ['Close', 'Solve']);
        if (!$isFinished) continue;
        $start = \Carbon\Carbon::parse($firstStep->created_at);
        $end   = null;
        if ($finishStep->updated_at && $finishStep->updated_at != $finishStep->created_at) {
            $end = \Carbon\Carbon::parse($finishStep->updated_at);
        } elseif ($t->solved_at) {
            $end = \Carbon\Carbon::parse($t->solved_at);
        } elseif ($t->updated_at) {
            $end = \Carbon\Carbon::parse($t->updated_at);
        }
        if ($end && $end->gt($start)) {
            $mttrValues[] = $start->diffInMinutes($end) / 60;
        }
    }
    $mttr      = count($mttrValues) ? round(array_sum($mttrValues) / count($mttrValues), 2) : 0;
    $mttrCount = count($mttrValues);

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
    ->addColumn('mttr', function ($t) {
        if ($t->steps->isEmpty()) {
            return '-';
        }
        $firstStep  = $t->steps->first();
        $finishStep = $t->steps->firstWhere('name', 'Finish');
        if (!$finishStep) {
            $hours = \Carbon\Carbon::parse($firstStep->created_at)->diffInMinutes(\Carbon\Carbon::now()) / 60;
            return '<span class="badge badge-secondary" title="Belum ada step Finish">'.round($hours, 1).' h</span>';
        }
        $isFinished = ($t->current_step_id == $finishStep->id)
                   || in_array($t->status, ['Close', 'Solve']);
        if (!$isFinished) {
            $hours = \Carbon\Carbon::parse($firstStep->created_at)->diffInMinutes(\Carbon\Carbon::now()) / 60;
            return '<span class="badge badge-warning" title="Ongoing">'.round($hours, 1).' h</span>';
        }
        $start = \Carbon\Carbon::parse($firstStep->created_at);
        $end   = null;
        if ($finishStep->updated_at && $finishStep->updated_at != $finishStep->created_at) {
            $end = \Carbon\Carbon::parse($finishStep->updated_at);
        } elseif ($t->solved_at) {
            $end = \Carbon\Carbon::parse($t->solved_at);
        } elseif ($t->updated_at) {
            $end = \Carbon\Carbon::parse($t->updated_at);
        }
        if (!$end || !$end->gt($start)) {
            return '<span class="badge badge-secondary">-</span>';
        }
        $hours = $start->diffInMinutes($end) / 60;
        $cls = $hours < 4 ? 'badge-success' : ($hours < 8 ? 'badge-warning' : 'badge-danger');
        return '<span class="badge '.$cls.'">'.round($hours, 1).' h</span>';
    })

    ->rawColumns(['DT_RowIndex','id','id_customer','status','id_categori','tittle','assign_to','date','mttr'])
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

public function view($id)
{
    $ticket = \App\Ticket::orderBy('id', 'DESC')
        ->where('id_customer', '=', $id)
        ->get();

    $category = \App\Ticketcategorie::pluck('name', 'id');

    return view('ticket/view', [
        'ticket'      => $ticket,
        'category'    => $category,
        'id_customer' => $id,
    ]);
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
    && empty($created_end);

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
    $q = \App\Ticket::with(['customer','customer.merchant_name','categorie','user','tags','steps','currentStep']);

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

    $q->orderByDesc('id');

    $results = $q->get();

    // Hitung ringkasan
    $total      = $results->count();
    $open       = $results->where('status', 'Open')->count();
    $close      = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve      = $results->where('status', 'Solve')->count();
    $pending    = $results->where('status', 'Pending')->count();

    // Hitung MTTR aggregate (rata-rata jam dari step pertama ke step 'Finish')
    $mttrValues = [];
    foreach ($results as $t) {
        if ($t->steps->isEmpty()) continue;
        $firstStep  = $t->steps->first();   // sudah diurutkan by position
        $finishStep = $t->steps->firstWhere('name', 'Finish');
        if (!$finishStep) continue; // Tidak ada step Finish
        // Ticket dianggap selesai jika: current_step sudah di Finish ATAU status Close/Solve
        $isFinished = ($t->current_step_id == $finishStep->id)
                   || in_array($t->status, ['Close', 'Solve']);
        if (!$isFinished) continue;
        $start = \Carbon\Carbon::parse($firstStep->created_at);
        // Prioritas end time: finishStep.updated_at (jika berbeda) -> ticket.solved_at -> ticket.updated_at
        $end = null;
        if ($finishStep->updated_at && $finishStep->updated_at != $finishStep->created_at) {
            $end = \Carbon\Carbon::parse($finishStep->updated_at);
        } elseif ($t->solved_at) {
            $end = \Carbon\Carbon::parse($t->solved_at);
        } elseif ($t->updated_at) {
            $end = \Carbon\Carbon::parse($t->updated_at);
        }
        if ($end && $end->gt($start)) {
            $mttrValues[] = $start->diffInMinutes($end) / 60;
        }
    }
    $mttrAvg   = count($mttrValues) ? round(array_sum($mttrValues) / count($mttrValues), 2) : 0;
    $mttrCount = count($mttrValues);

    return \DataTables::of($results)
    ->addIndexColumn()

    ->editColumn('id', function ($t) {
        return '<a href="'.url("/ticket/{$t->id}").'" class="badge badge-primary">'.$t->id.'</a>';
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

    ->editColumn('solved_at', function ($t) {
        if ($t->status !== 'Close') return '<span class="text-muted">-</span>';
        $closedAt = $t->solved_at
            ? Carbon::parse($t->solved_at)
            : ($t->updated_at ? Carbon::parse($t->updated_at) : null);
        if (!$closedAt) return '<span class="text-muted">-</span>';
        $diff  = Carbon::parse($t->created_at)->diffInMinutes($closedAt);
        $days  = intdiv($diff, 1440);
        $hours = intdiv($diff % 1440, 60);
        $mins  = $diff % 60;
        $duration = $days > 0
            ? "{$days}d {$hours}h {$mins}m"
            : ($hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m");
        return '<span class="text-muted small d-block">'.e($closedAt->format('Y-m-d H:i:s')).'</span>'
             . '<span class="badge badge-secondary">'.e($duration).'</span>';
    })

    ->addColumn('progress', function ($t) {
        if ($t->steps->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }
        $total   = $t->steps->count();
        $current = $t->currentStep;
        $currentPos = $current ? $current->position : 0;
        $pct = $total > 0 ? round(($currentPos / $total) * 100) : 0;
        $name = $current ? e($current->name) : e($t->steps->first()->name);
        $bar = '<div style="min-width:110px">'
             . '<small class="d-block">'.$name.' ('.$currentPos.'/'.$total.')</small>'
             . '<div class="progress progress-xs mt-1">'
             . '<div class="progress-bar bg-info" style="width:'.$pct.'%"></div>'
             . '</div></div>';
        return $bar;
    })

    ->addColumn('mttr', function ($t) {
        if ($t->steps->isEmpty()) {
            return '-';
        }
        $firstStep  = $t->steps->first();
        $finishStep = $t->steps->firstWhere('name', 'Finish');
        if (!$finishStep) {
            $hours = Carbon::parse($firstStep->created_at)->diffInMinutes(Carbon::now()) / 60;
            return '<span class="badge badge-secondary" title="Belum ada step Finish">'.round($hours, 1).' h</span>';
        }
        $isFinished = ($t->current_step_id == $finishStep->id)
                   || in_array($t->status, ['Close', 'Solve']);
        if (!$isFinished) {
            $hours = Carbon::parse($firstStep->created_at)->diffInMinutes(Carbon::now()) / 60;
            return '<span class="badge badge-warning" title="Ongoing">'.round($hours, 1).' h</span>';
        }
        $start = Carbon::parse($firstStep->created_at);
        $end   = null;
        if ($finishStep->updated_at && $finishStep->updated_at != $finishStep->created_at) {
            $end = Carbon::parse($finishStep->updated_at);
        } elseif ($t->solved_at) {
            $end = Carbon::parse($t->solved_at);
        } elseif ($t->updated_at) {
            $end = Carbon::parse($t->updated_at);
        }
        if (!$end || !$end->gt($start)) {
            return '<span class="badge badge-secondary">-</span>';
        }
        $hours = $start->diffInMinutes($end) / 60;
        $cls = $hours < 4 ? 'badge-success' : ($hours < 8 ? 'badge-warning' : 'badge-danger');
        return '<span class="badge '.$cls.'">'.round($hours, 1).' h</span>';
    })

        // Hanya kolom yang berisi HTML yang dimasukkan ke rawColumns
    ->rawColumns([
        'DT_RowIndex','id','status','id_customer','merchant',
        'address','id_categori','tags','assign_to','date','created_at','solved_at',
        'progress','mttr',
    ])

        // Statistik ringkasan
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)
    ->with('mttr', $mttrAvg)
    ->with('mttr_count', $mttrCount)

    ->make(true);
}



public function table_groupticket_list(Request $request){

    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $id_categori = $request->input('id_categori');
    $assign_to = $request->input('assign_to');
    $id_status = $request->input('id_status');


 // Dapatkan grup dari pengguna aktif
    $currentUser = Auth::user();
    $currentGroupIds = $currentUser->groups->pluck('id'); // Grup pengguna aktif

    // Inisialisasi query
    $ticket = \App\Ticket::whereBetween('date', [$date_from, $date_end])
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

// Order the results
    $ticket->orderBy('id', 'DESC');

// Get the results with eager loading
    $results = $ticket->with(['customer', 'customer.merchant_name', 'categorie', 'user', 'tags', 'steps', 'currentStep'])->get();



    $total = $results->count();
    $open = $results->where('status', 'Open')->count();
    $close = $results->where('status', 'Close')->count();
    $inprogress = $results->where('status', 'Inprogress')->count();
    $solve = $results->where('status', 'Solve')->count();
    $pending = $results->where('status', 'pending')->count();

    // Hitung MTTR aggregate (groupticket)
    $mttrValues = [];
    foreach ($results as $t) {
        if ($t->steps->isEmpty()) continue;
        $firstStep  = $t->steps->first();
        $finishStep = $t->steps->firstWhere('name', 'Finish');
        if (!$finishStep) continue;
        $isFinished = ($t->current_step_id == $finishStep->id)
                   || in_array($t->status, ['Close', 'Solve']);
        if (!$isFinished) continue;
        $start = \Carbon\Carbon::parse($firstStep->created_at);
        $end = null;
        if ($finishStep->updated_at && $finishStep->updated_at != $finishStep->created_at) {
            $end = \Carbon\Carbon::parse($finishStep->updated_at);
        } elseif ($t->solved_at) {
            $end = \Carbon\Carbon::parse($t->solved_at);
        } elseif ($t->updated_at) {
            $end = \Carbon\Carbon::parse($t->updated_at);
        }
        if ($end && $end->gt($start)) {
            $mttrValues[] = $start->diffInMinutes($end) / 60;
        }
    }
    $mttrAvg   = count($mttrValues) ? round(array_sum($mttrValues) / count($mttrValues), 2) : 0;
    $mttrCount = count($mttrValues);



// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {

        return ' <a href="/ticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
    })

    ->addColumn('merchant',function($ticket)
    {
        $merchant = optional(optional($ticket->customer)->merchant_name);
        if (!$merchant || !$merchant->id) return '-';
        return '<a href="/merchant/'.$merchant->id.'" class="badge p-1 badge-primary">'.$merchant->name.'</a>';
    })
    ->addColumn('address', function($ticket)
    {
        return e(optional($ticket->customer)->address ?: '-');
    })
    ->addColumn('tags', function($ticket)
    {
        $out = '';
        foreach ($ticket->tags as $tag) {
            $out .= '<span class="badge badge-info mr-1">'.e($tag->name).'</span>';
        }
        return $out ?: '-';
    })
    ->addColumn('progress', function($ticket)
    {
        if ($ticket->steps->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }
        $total   = $ticket->steps->count();
        $current = $ticket->currentStep;
        $currentPos = $current ? $current->position : 0;
        $pct = $total > 0 ? round(($currentPos / $total) * 100) : 0;
        $name = $current ? e($current->name) : e($ticket->steps->first()->name);
        return '<div style="min-width:110px">'
             . '<small class="d-block">'.$name.' ('.$currentPos.'/'.$total.')</small>'
             . '<div class="progress progress-xs mt-1">'
             . '<div class="progress-bar bg-info" style="width:'.$pct.'%"></div>'
             . '</div></div>';
    })
    ->addColumn('mttr', function($ticket)
    {
        if ($ticket->steps->isEmpty()) return '-';
        $firstStep  = $ticket->steps->first();
        $finishStep = $ticket->steps->firstWhere('name', 'Finish');
        if (!$finishStep) {
            $hours = Carbon::parse($firstStep->created_at)->diffInMinutes(Carbon::now()) / 60;
            return '<span class="badge badge-secondary" title="Belum ada step Finish">'.round($hours,1).' h</span>';
        }
        $isFinished = ($ticket->current_step_id == $finishStep->id)
                   || in_array($ticket->status, ['Close','Solve']);
        if (!$isFinished) {
            $hours = Carbon::parse($firstStep->created_at)->diffInMinutes(Carbon::now()) / 60;
            return '<span class="badge badge-warning" title="Ongoing">'.round($hours,1).' h</span>';
        }
        $start = Carbon::parse($firstStep->created_at);
        $end   = null;
        if ($finishStep->updated_at && $finishStep->updated_at != $finishStep->created_at) {
            $end = Carbon::parse($finishStep->updated_at);
        } elseif ($ticket->solved_at) {
            $end = Carbon::parse($ticket->solved_at);
        } elseif ($ticket->updated_at) {
            $end = Carbon::parse($ticket->updated_at);
        }
        if (!$end || !$end->gt($start)) return '<span class="badge badge-secondary">-</span>';
        $hours = $start->diffInMinutes($end) / 60;
        $cls = $hours < 4 ? 'badge-success' : ($hours < 8 ? 'badge-warning' : 'badge-danger');
        return '<span class="badge '.$cls.'">'.round($hours,1).' h</span>';
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
      elseif ($ticket->status == "olve")
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

    ->editColumn('created_at',function($ticket)
    {
        if (!$ticket->created_at) return '-';
        return '<span>'.Carbon::parse($ticket->created_at)->format('Y-m-d H:i:s').'</span>';
    })

    ->editColumn('solved_at',function($ticket)
    {
        if ($ticket->status !== 'Close') {
            return '<span class="text-muted">-</span>';
        }
        // Waktu closed: solved_at atau updated_at
        $closedAt = $ticket->solved_at
            ? Carbon::parse($ticket->solved_at)
            : ($ticket->updated_at ? Carbon::parse($ticket->updated_at) : null);
        if (!$closedAt) {
            return '<span class="text-muted">-</span>';
        }
        $createdAt = Carbon::parse($ticket->created_at);
        $diffMins  = $createdAt->diffInMinutes($closedAt);
        $days  = intdiv($diffMins, 1440);
        $hours = intdiv($diffMins % 1440, 60);
        $mins  = $diffMins % 60;
        if ($days > 0) {
            $duration = $days.'d '.$hours.'h '.$mins.'m';
        } elseif ($hours > 0) {
            $duration = $hours.'h '.$mins.'m';
        } else {
            $duration = $mins.'m';
        }
        $closedFmt = $closedAt->format('Y-m-d H:i:s');
        return '<span class="text-muted small d-block">'.$closedFmt.'</span>'
             . '<span class="badge badge-secondary">'.$duration.'</span>';
    })


    ->rawColumns(['DT_RowIndex','id','id_customer','status','id_categori','tittle','assign_to','date','merchant','address','tags','progress','mttr','created_at','solved_at'])
    ->with('total', $total)
    ->with('open', $open)
    ->with('close', $close)
    ->with('inprogress', $inprogress)
    ->with('solve', $solve)
    ->with('pending', $pending)
    ->with('mttr', $mttrAvg)
    ->with('mttr_count', $mttrCount)

    ->make(true);
}


public function report(Request $request)
{
   if (($request->date_from == null) or ($request->date_end == null))
   {
     $from=date('Y-m-1');
     $to=date('Y-m-d');

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

 // === Status count ===
 $ticket_status = \App\Ticket::whereBetween('date', [$from, $to])
 ->select('status', DB::raw('count(*) as count'))
 ->groupBy('status')
 ->pluck('count', 'status');

 // === MTTR: avg hours from created_at to updated_at for Closed/Solved tickets ===
 $closedTickets = \App\Ticket::whereBetween('date', [$from, $to])
 ->whereIn('status', ['Close', 'Solve'])
 ->select('created_at', 'updated_at')
 ->get();

 $mttrValues = [];
 foreach ($closedTickets as $t) {
     $mins = \Carbon\Carbon::parse($t->created_at)->diffInMinutes(\Carbon\Carbon::parse($t->updated_at));
     if ($mins > 0) $mttrValues[] = $mins / 60;
 }
 $mttr_avg   = count($mttrValues) ? round(array_sum($mttrValues) / count($mttrValues), 2) : 0;
 $mttr_count = count($mttrValues);
 $mttr_min   = count($mttrValues) ? round(min($mttrValues), 2) : 0;
 $mttr_max   = count($mttrValues) ? round(max($mttrValues), 2) : 0;

 // === MTTR by category ===
 $mttr_by_category = \App\Ticket::join('ticketcategories','tickets.id_categori','=','ticketcategories.id')
 ->whereBetween('tickets.date', [$from, $to])
 ->whereIn('tickets.status', ['Close','Solve'])
 ->select('ticketcategories.name as category',
     DB::raw('COUNT(*) as count'),
     DB::raw('ROUND(AVG(TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.updated_at))/60, 2) as avg_hours'),
     DB::raw('ROUND(MIN(TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.updated_at))/60, 2) as min_hours'),
     DB::raw('ROUND(MAX(TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.updated_at))/60, 2) as max_hours')
 )
 ->groupBy('ticketcategories.name')
 ->orderByDesc('count')
 ->get();

 // === MTTR trend per day ===
 $mttr_trend = \App\Ticket::whereBetween('date', [$from, $to])
 ->whereIn('status', ['Close','Solve'])
 ->select('date', DB::raw('ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at))/60, 2) as avg_hours'))
 ->groupBy('date')
 ->orderBy('date')
 ->get();

 // === Assigned user performance ===
 $user_performance = \App\Ticket::join('users','tickets.assign_to','=','users.id')
 ->whereBetween('tickets.date', [$from, $to])
 ->select('users.name as agent',
     DB::raw('COUNT(*) as total'),
     DB::raw('SUM(CASE WHEN tickets.status IN (\'Close\',\'Solve\') THEN 1 ELSE 0 END) as resolved'),
     DB::raw('ROUND(AVG(CASE WHEN tickets.status IN (\'Close\',\'Solve\') THEN TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.updated_at)/60 ELSE NULL END), 2) as mttr')
 )
 ->groupBy('users.name')
 ->orderByDesc('resolved')
 ->limit(10)
 ->get();

 $date_from = $from;
 $date_end  = $to;

 return view('ticket/report', compact(
     'ticket_report', 'ticket_customer', 'date_from', 'date_end', 'ticket_date',
     'ticket_status',
     'mttr_avg', 'mttr_count', 'mttr_min', 'mttr_max',
     'mttr_by_category', 'mttr_trend',
     'user_performance'
 ));



}

public function search(Request $request)
{
    $date_from   = $request->input('date_from');
    $date_end    = $request->input('date_end');
    $id_customer = $request->input('id_customer');
    $id_categori = $request->input('id_categori');

    $query = \App\Ticket::orderBy('id', 'DESC')
        ->whereBetween('date', [$date_from, $date_end]);

    if (!empty($id_customer)) {
        $query->where('id_customer', $id_customer);
    }

    if (!empty($id_categori)) {
        $query->where('id_categori', $id_categori);
    }

    $ticket = $query->get();

    // Customer-specific view
    if (!empty($id_customer)) {
        $category = \App\Ticketcategorie::pluck('name', 'id');
        return view('ticket/view', [
            'ticket'      => $ticket,
            'date_from'   => $date_from,
            'date_end'    => $date_end,
            'category'    => $category,
            'id_customer' => $id_customer,
            'id_categori' => $id_categori,
        ]);
    }

    return view('ticket/index', ['ticket' => $ticket, 'date_from' => $date_from, 'date_end' => $date_end, 'ticket_date']);
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

        // Skip jika sudah berupa URL (bukan base64)
        if (strpos($data, 'data:') !== 0) {
            continue;
        }

        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $rescode   = config('app.rescode') ?? config('tenant.rescode', 'default');
        $uploadDir = public_path("tenants/{$rescode}/ticket");
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename   = time() . $key . '.png';
        $image_name = "/tenants/{$rescode}/ticket/{$filename}";
        $path       = $uploadDir . '/' . $filename;

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




public function startWorkflow(Request $request, $id)
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

    // Log workflow mulai + koordinat
    \App\Ticketdetail::create([
        'id_ticket'   => $ticket->id,
        'description' => 'Workflow dimulai.',
        'updated_by'  => \Auth::user()->name,
        'coordinate'  => $request->input('coordinate'),
        'device_type' => $request->input('device_type'),
    ]);

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

    // Touch step agar updated_at mencatat waktu step ini dipilih
    $step->touch();

    // Simpan log perubahan step ke Ticketdetail
    \App\Ticketdetail::create([
        'id_ticket'   => $ticket->id,
        'description' => "Workflow pindah ke step: <b>{$step->name}</b>",
        'updated_by'  => \Auth::user()->name,
        'coordinate'  => $request->input('coordinate'),
        'device_type' => $request->input('device_type'),
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

            // Skip jika sudah berupa URL (bukan base64)
            if (strpos($data, 'data:') !== 0) {
                continue;
            }

            list($type, $data) = explode(';', $data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);

            $rescode   = config('app.rescode') ?? config('tenant.rescode', 'default');
            $uploadDir = public_path("tenants/{$rescode}/ticket");
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename   = time() . $key . '.png';
            $image_name = "/tenants/{$rescode}/ticket/{$filename}";
            $path       = $uploadDir . '/' . $filename;

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

    // ── Notify Employee via Email / WhatsApp / App (FCM) ─────────────────────

    /** POST /ticket/notify */
    public function notifyTicket(Request $request)
    {
        $request->validate([
            'id_ticket' => 'required|integer',
            'channels'  => 'required|array|min:1',
            'channels.*'=> 'in:whatsapp,email,app',
            'message'   => 'nullable|string|max:1000',
        ]);

        $ticket   = \App\Ticket::with(['user', 'customer'])->findOrFail($request->id_ticket);
        $employee = $ticket->user;
        $channels = $request->channels;
        $results  = [];

        $mapsUrl   = 'https://www.google.com/maps/place/' . str_replace(' ', '', $ticket->customer->coordinate ?? '');
        $ticketUrl = rtrim(config('app.url'), '/') . '/ticket/' . $ticket->id;
        $customMsg = trim($request->message ?? '');

        // WhatsApp
        if (in_array('whatsapp', $channels)) {
            if (env('WAPISENDER_STATUS') != 'disable') {
                $phone = $employee->phone ?? null;
                if ($phone) {
                    $waMsg  = "Halo, *{$employee->name}*\n\n";
                    $waMsg .= "*Ada Tiket buat Kamu nih* 🎫\n";
                    $waMsg .= "━━━━━━━━━━━━━━━\n";
                    $waMsg .= "Judul: {$ticket->tittle}\n";
                    $waMsg .= "*Member*: {$ticket->member}\n";
                    $waMsg .= "*Pelanggan*: {$ticket->customer->name}\n";
                    $waMsg .= "*No. HP*: {$ticket->customer->phone}\n";
                    $waMsg .= "*Alamat*: {$ticket->customer->address}\n";
                    if ($customMsg) $waMsg .= "*Pesan*: {$customMsg}\n";
                    $waMsg .= "\n📍 Maps: {$mapsUrl}\n";
                    $waMsg .= "━━━━━━━━━━━━━━━\n";
                    $waMsg .= "🔗 Cek tiket: {$ticketUrl}\n\n";
                    $waMsg .= "~ *" . config('app.signature') . "* ~";
                    try {
                        $res = \App\Helpers\WaGatewayHelper::wa_payment($phone, $waMsg);
                        $results['whatsapp'] = ($res['status'] === 'success') ? 'OK' : ($res['message'] ?? 'Gagal');
                    } catch (\Throwable $e) {
                        $results['whatsapp'] = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $results['whatsapp'] = 'Nomor HP karyawan tidak tersedia.';
                }
            } else {
                $results['whatsapp'] = 'WhatsApp dinonaktifkan.';
            }
        }

        // Email
        if (in_array('email', $channels)) {
            $email = $employee->email ?? null;
            if ($email) {
                $data = [
                    'ticket_id'        => $ticket->id,
                    'title'            => $ticket->tittle,
                    'employee_name'    => $employee->name,
                    'customer_name'    => $ticket->customer->name,
                    'customer_phone'   => $ticket->customer->phone,
                    'customer_address' => $ticket->customer->address,
                    'status'           => $ticket->status,
                    'maps_url'         => $mapsUrl,
                    'ticket_url'       => $ticketUrl,
                    'custom_message'   => $customMsg,
                ];
                try {
                    Mail::to($email)->send(new EmailTicketNotification($data));
                    $results['email'] = 'OK';
                } catch (\Throwable $e) {
                    $results['email'] = 'Error: ' . $e->getMessage();
                    \Log::error('notifyTicket email error: ' . $e->getMessage());
                }
            } else {
                $results['email'] = 'Email karyawan tidak tersedia.';
            }
        }

        // App (FCM V1)
        if (in_array('app', $channels)) {
            $fcmToken = $employee->fcm_token ?? null;
            if ($fcmToken) {
                $title = 'Tiket: ' . $ticket->tittle;
                $body  = "Pelanggan: {$ticket->customer->name}\nAlamat: {$ticket->customer->address}";
                if ($customMsg) $body .= "\nPesan: {$customMsg}";
                try {
                    $projectId = config('services.firebase.project_id');
                    $creds     = config('services.firebase.credentials');
                    if ($projectId && $creds && file_exists($creds)) {
                        $sa  = json_decode(file_get_contents($creds), true);
                        $now = time();
                        $b64 = fn(string $d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
                        $hdr = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
                        $pay = $b64(json_encode([
                            'iss' => $sa['client_email'], 'sub' => $sa['client_email'],
                            'aud' => 'https://oauth2.googleapis.com/token',
                            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                            'iat' => $now, 'exp' => $now + 3600,
                        ]));
                        $sig = ''; openssl_sign("$hdr.$pay", $sig, openssl_pkey_get_private($sa['private_key']), OPENSSL_ALGO_SHA256);
                        $jwt = "$hdr.$pay." . $b64($sig);

                        $resp  = (new \GuzzleHttp\Client(['timeout' => 10]))
                            ->post('https://oauth2.googleapis.com/token', ['form_params' => [
                                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                                'assertion'  => $jwt,
                            ]]);
                        $oauthToken = json_decode($resp->getBody(), true)['access_token'] ?? null;

                        if ($oauthToken) {
                            (new \GuzzleHttp\Client(['timeout' => 5]))
                                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                                    'headers' => ['Authorization' => 'Bearer ' . $oauthToken, 'Content-Type' => 'application/json'],
                                    'json'    => ['message' => [
                                        'token'        => $fcmToken,
                                        'notification' => ['title' => $title, 'body' => $body],
                                        'data'         => [
                                            'title'     => $title,
                                            'body'      => $body,
                                            'ticket_id' => (string)$ticket->id,
                                            'url'       => $ticketUrl,
                                        ],
                                    ]],
                                ]);
                            $results['app'] = 'OK';
                        } else {
                            $results['app'] = 'Gagal OAuth FCM.';
                        }
                    } else {
                        $results['app'] = 'Firebase credentials belum dikonfigurasi.';
                    }
                } catch (\Throwable $e) {
                    $results['app'] = 'Error: ' . $e->getMessage();
                    \Log::error('notifyTicket FCM error: ' . $e->getMessage());
                }
            } else {
                $results['app'] = 'FCM token karyawan tidak tersedia (belum login di aplikasi).';
            }
        }

        $ok   = array_keys(array_filter($results, fn($v) => $v === 'OK'));
        $fail = array_keys(array_filter($results, fn($v) => $v !== 'OK'));

        if ($ok && !$fail) {
            return redirect()->back()->with('success', 'Notifikasi terkirim via: ' . implode(', ', $ok));
        } elseif ($ok) {
            $details = implode('; ', array_map(fn($k) => "$k: {$results[$k]}", $fail));
            return redirect()->back()->with('warning', 'Sebagian berhasil (' . implode(', ', $ok) . '). Gagal — ' . $details);
        } else {
            $details = implode('; ', array_map(fn($k) => "$k: {$results[$k]}", $fail));
            return redirect()->back()->with('error', 'Notifikasi gagal. ' . $details);
        }
    }

}

