<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class ToolController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function burstcalc()
    {
     return view('tools.burstcalc');
 }
 public function macvendor()
 {
     return view('tools/macvendor');
 }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }
    public function maclookup(Request $request)
    {
        $macAddress = $request->input('mac_address');
        $url = "https://api.macvendors.com/" . urlencode($macAddress);
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        // If response is empty or false, set vendor as not found
        $vendor = $response ? $response : false;

        return view('tools.macvendor', compact('vendor'));
    }
    public function showipcalc()
    {
        return view('/tools/ipcalc');
    }

    public function ipcalc(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'subnet_mask' => 'required|regex:/^\/\d{1,2}$/'
        ]);

        $ip = $request->input('ip_address');
        $cidr = ltrim($request->input('subnet_mask'), '/');

        if ($cidr < 0 || $cidr > 32) {
            return redirect()->back()->withErrors(['subnet_mask' => 'Invalid CIDR notation. Must be between /0 and /32.']);
        }

    // Convert CIDR to dotted-decimal subnet mask
        $maskLong = ~((1 << (32 - $cidr)) - 1);
        $mask = long2ip($maskLong);

    // Calculate network address
        $ipLong = ip2long($ip);
        $network = long2ip($ipLong & $maskLong);

    // Calculate broadcast address
        $broadcast = long2ip($ipLong | (~$maskLong));

    // Calculate wildcard mask
        $wildcardMask = long2ip(~$maskLong);

    // Calculate number of hosts
        $numHosts = pow(2, 32 - $cidr) - 2;

    // Calculate first and last usable IP
        $firstIp = long2ip(($ipLong & $maskLong) + 1);
        $lastIp = long2ip(($ipLong | (~$maskLong)) - 1);

        return view('/tools/ipcalc', [
            'ip' => $ip,
            'mask' => $mask,
            'cidr' => $cidr,
            'network' => $network,
            'broadcast' => $broadcast,
            'wildcardMask' => $wildcardMask,
            'numHosts' => $numHosts,
            'firstIp' => $firstIp,
            'lastIp' => $lastIp,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


      // Validation
      $request->validate([
        'file' => 'required'
    ]); 

      if($request->file('file')) {
       $file = $request->file('file');
       $name = str_replace('-', ' ', $file->getClientOriginalName());
       $filename = time().'_'.$name;

         // File upload location
       $location = 'upload/customerfiles';

         // Upload file
       $file->move($location,$filename);

       $id_customer = ($request['id_customer']);

       \App\File::create([
        'id_customer' => $id_customer,
        'name' =>$file->getClientOriginalName(),
        'path' => $location.'/'.$filename, 

    ]);


       return redirect ('/customer/'.$id_customer)->with('success','Item Updates successfully!');
   }else{
    return redirect ('/customer'.$id_customer)->with('success','File Not Uploaded!');
}

      // return redirect('/');

}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
      \App\File::destroy($id);
      return redirect ('/customer')->with('success','Item deleted successfully!');
  }
}
