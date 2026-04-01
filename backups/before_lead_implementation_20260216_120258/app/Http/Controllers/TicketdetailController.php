<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;



class TicketdetailController extends Controller
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
    public function index()
    {
        //
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
//     public function store(Request $request)
//     {
//         //


//         //

//        $request ->validate([
//         'id_ticket' =>'required',
//         'description' => 'required',
//         'updated_by' => 'required'
//     ]);

//        $description=$request->input('description');
//        $dom = new \DomDocument();
//        $dom->loadHtml($description, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);    
//        $images = $dom->getElementsByTagName('img');

//        foreach($images as $key => $img){
//         $data = $img->getAttribute('src');

//         list($type, $data) = explode(';', $data);
//         list(, $data)      = explode(',', $data);
//         $data = base64_decode($data);

//         $image_name= "/upload/ticket/" . time().$key.'.png';
//         $path = public_path() . $image_name;

//         file_put_contents($path, $data);

//         $img->removeAttribute('src');
//         $img->setAttribute('src', $image_name);
//     }


//     $description = $dom->saveHTML();

//     \App\Ticketdetail::create([
//        'id_ticket' => ($request['id_ticket']),
//        'description' => $description,
//        'updated_by' => ($request['updated_by']),
//    ]);


//         // \App\Ticketdetail::create($request->all());
//     $url ='ticket/'.$request->id_ticket;

//     return redirect ($url)->with('success','Item created successfully!');
// }

    public function store(Request $request)
    {
        $request->validate([
            'id_ticket' => 'required',
            'description' => 'required',
            'updated_by' => 'required'
        ]);

        $description = $request->input('description');

    // Penting: Convert HTML sebelum load
        $description = mb_convert_encoding($description, 'HTML-ENTITIES', 'UTF-8');
        $dom = new \DomDocument();

       libxml_use_internal_errors(true); // Suppress warning
       $dom->loadHtml($description, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
       libxml_clear_errors();

       $images = $dom->getElementsByTagName('img');

       foreach ($images as $key => $img) {
        $data = $img->getAttribute('src');

        // Memeriksa apakah gambar menggunakan base64
        if (strpos($data, 'base64,') !== false) {
            list($type, $data) = explode(';', $data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);

            // Menentukan ekstensi file dari tipe MIME
            $mimeType = explode('/', $type)[1]; // Contoh hasil: jpg, png, dll.
            $image_name = "/upload/ticket/" . time() . $key . '.' . $mimeType;
            $path = public_path() . $image_name;

            // Menyimpan gambar sementara
            file_put_contents($path, $data);

            // Optimasi gambar menggunakan spatie/laravel-image-optimizer
            ImageOptimizer::optimize($path);

            // Memperbarui atribut src gambar
            $img->removeAttribute('src');
            $img->setAttribute('src', $image_name);
        }
    }

    // Mengonversi kembali deskripsi yang telah diperbarui ke HTML
    $description = $dom->saveHTML();

    // // Menyimpan data ke database
    // \App\Ticketdetail::create([
    //     'id_ticket' => $request->id_ticket,
    //     'description' => $description,
    //     'updated_by' => $request->updated_by,
    // ]);

    // // Redirect ke halaman tiket dengan pesan sukses
    // // $url = 'ticket/' . $request->id_ticket;
    // // return redirect($url)->with('success', 'Item created successfully!');
    //  return redirect->back()->with('success', 'Item created successfully!');

    $ticketDetail = \App\Ticketdetail::create([
        'id_ticket' => $request->id_ticket,
        'description' => $description,
        'updated_by' => $request->updated_by,
    ]);

    if ($ticketDetail) {
    // Redirect ke halaman sebelumnya dengan pesan sukses
        return redirect()->back()->with('success', 'Item created successfully!');
    } else {
    // Redirect ke halaman sebelumnya dengan pesan error jika gagal
        return redirect()->back()->with('error', 'Failed to create item!');
    }
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
    }
}
