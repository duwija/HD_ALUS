
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadWorkflowSeeder extends Seeder
{
    public function run()
    {
        DB::table('lead_workflows')->insert([
            ['id' => 1, 'name' => 'New', 'order' => 1, 'description' => 'Baru masuk'],
            ['id' => 2, 'name' => 'Contacted', 'order' => 2, 'description' => 'Sudah dihubungi'],
            ['id' => 3, 'name' => 'Negotiation', 'order' => 3, 'description' => 'Proses negosiasi'],
            ['id' => 4, 'name' => 'Proposal Sent', 'order' => 4, 'description' => 'Penawaran dikirim'],
            ['id' => 5, 'name' => 'Closing', 'order' => 5, 'description' => 'Deal/Active'],
            ['id' => 6, 'name' => 'Lost', 'order' => 6, 'description' => 'Tidak jadi/gagal'],
            ['id' => 7, 'name' => 'Deleted', 'order' => 7, 'description' => 'Dihapus'],
        ]);
    }
}
