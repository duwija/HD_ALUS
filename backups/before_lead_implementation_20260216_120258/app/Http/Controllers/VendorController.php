<?php

namespace App\Http\Controllers;
use App\Tiket;
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

class VendorController extends Controller
{
    //
   public function __construct()
   {
    $this->middleware('auth');
     $this->middleware('checkPrivilege:admin,noc,accounting,payment,user,merchant,vendor'); // Daftar privilege
     
 }
 public function vendorticket()
 {
    $from=date('Y-m-1');
    $to=date('y-m-d');

    $id = Auth::user()->id;
    $ticket = \App\Ticket::orderBy('id', 'DESC')
    ->where('assign_to', $id)
    ->whereBetween('date', [$from, $to])
    ->get();


    return view ('ticket/vendorticket',['ticket' =>$ticket, 'title'=> 'Vendor | My Ticket']);
}
public function vendoreditticket(Request $request, $id)
{ 
    $ticket = \App\Ticket::where('id', $id)->first();

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


      if( $ticket->status != 'close' )
      {
         if (env('WAPISENDER_STATUS')!="disable")
         {
             $client = new Clients(); 
             $message  = "Thanks ".\Auth::user()->name.", ";
             $message .= "\n ";

             $message .= "\nTicket with detail below has been *CLOSED*";
             $message .= "\n ";
             $message .= "\nCustomer Name : *".$ticket->customer->name."*";

             $message .= "\nPhone: ".$ticket->customer->phone;
             $message .= "\nAddress : ".$ticket->customer->address;
             $message .= "\n";

             $message .= "\nTittle  : *".$ticket->tittle."*";
                   // $message .= "\nDescription  : ".$ticket->tittle; $ticket->description;
             $message .= "\n";
             $message .= "\nOpen this  url to show the update : http://".env('DOMAIN_NAME')."/ticket/".$ticket->id;

             $result_g = $client->post(env('WAPISENDER_SEND_MESSAGE'), [
                'form_params' => [
                    'token' => env('WAPISENDER_KEY'),
                    'number' =>env('WAPISENDER_GROUPTICKET'),
                    'message' =>$message,
                ]
            ]);


             $result_g= $result_g->getBody();
             $array = json_decode($result_g, true);
   //  dd($array);
         }

     }
 }

 \App\Ticket::where('id', $id)
 ->update([
    'tittle' => $request->tittle,
    'status' => $request->status,
    // 'id_categori' => $request->category,
    'assign_to' => ($request['assign_to']),
    'member' => ($member),
    'date' => ($request['date']),
    'time' => ($request['time']),

]);

 // Record changes
 $note = "";
 if ($ticket->tittle != $request->tittle) {
    $note .= 'tittle changed from ' .$ticket->tittle. ' to ' .$request->tittle ;
}

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


// $url ='/vendorticket/'.$request->id;
// return redirect ($url)->with('success','Item updated successfully! ');
return redirect()->back()->with('success', 'Item Updates successfully!');
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


   return view ('ticket/vendorgroupticket',['ticket' =>$ticket, 'ticketcategorie' =>$ticketcategorie, 'user'=>$users]);


}

public function vendorshow($id)
{
    $ticket = \App\Ticket::findOrFail($id);

    $category = \App\Ticketcategorie::pluck('name', 'id');
      //  $user= \App\User::pluck('name', 'id');

    $distpoint = \App\Distpoint::pluck('name', 'id');
    $sale = \App\Sale::pluck('name', 'id', 'phone');
    $plan = \App\Plan::pluck('name', 'id');
    $user = \App\User::pluck('name', 'id');
       // $users = \App\User::pluck('name', 'id');
    $users= \App\User::where('privilege', '!=','counter')->pluck('name', 'id');

    $currentUser = Auth::user();
    $currentGroupIds = $currentUser->groups->pluck('id');

// Ambil tag untuk form
    $tags = $ticket->tags->pluck('name', 'id')->toArray();
    $alltags = \App\Tag::pluck('name', 'id');

    // Cari semua user yang memiliki grup yang sama
    $users = \App\User::whereHas('groups', function ($query) use ($currentGroupIds) {
        $query->whereIn('groups.id', $currentGroupIds);
    })->pluck('name', 'id');
    $workflowSteps = $ticket->steps()->orderBy('position')->get();
 // 🔑 Ambil step aktif
    $currentStep = $ticket->current_step_id 
    ? \App\TicketStep::find($ticket->current_step_id) 
    : null;


   // dd($users);

    $userRole = \Auth::user()->privilege;
    return view ('ticket/vendorshow',['ticket' => $ticket,
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
        ] );

}

public function table_vendorgroupticket_list(Request $request){

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
    })
->whereHas('assignToUser', function ($query) {
        $query->where('privilege', 'vendor'); // Hanya pengguna dengan privilege 'vendor'
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

// Get the results
$results = $ticket->get();




$total = $results->count();
$open = $results->where('status', 'Open')->count();
$close = $results->where('status', 'Close')->count();
$inprogress = $results->where('status', 'Inprogress')->count();
$pending = $results->where('status', 'pending')->count();


// Return the results using DataTables
return DataTables::of($results)
->addIndexColumn()
->editColumn('id',function($ticket)
{

    return ' <a href="/vendorticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
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
  else
   {  $color='bg-primary'; 
$btn_c='bg-primary'; }
return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
})





->editColumn('id_customer',function($ticket)
{



    return ' <a ><strong> '.$ticket->customer->name. '</strong></a>';
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
->with('pending', $pending)

->make(true);
}
public function table_vendorticket_list(Request $request){
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
    $pending = $results->where('status', 'pending')->count();


// Return the results using DataTables
    return DataTables::of($results)
    ->addIndexColumn()
    ->editColumn('id',function($ticket)
    {

        return ' <a href="/vendorticket/'.$ticket->id.'" title="ticket" class="badge badge-primary text-center  "> '.$ticket->id. '</a>';
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
      else
         {  $color='bg-primary'; 
     $btn_c='bg-primary'; }
     return '<badge class=" badge '. $btn_c. '"<a>'.$ticket->status. '</a>';
 })





    ->editColumn('id_customer',function($ticket)
    {



        return ' <a ><strong> '.$ticket->customer->name. '</strong></a>';
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
    ->with('pending', $pending)

    ->make(true);
}
}
