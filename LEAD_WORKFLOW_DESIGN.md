# Lead Workflow Design

## 1. Workflow Stages (Tahapan Lead)
- New (Baru)
- Contacted / Follow Up
- Negotiation
- Proposal Sent
- Closing (Active)
- Lost / Gagal
- Deleted

## 2. Database Table: lead_workflows
| id | name           | order | description         |
|----|----------------|-------|---------------------|
| 1  | New            | 1     | Baru masuk          |
| 2  | Contacted      | 2     | Sudah dihubungi     |
| 3  | Negotiation    | 3     | Proses negosiasi    |
| 4  | Proposal Sent  | 4     | Penawaran dikirim   |
| 5  | Closing        | 5     | Deal/Active         |
| 6  | Lost           | 6     | Tidak jadi/gagal    |
| 7  | Deleted        | 7     | Dihapus             |

## 3. Relasi di Customer
- Tambahkan field `workflow_stage_id` pada tabel customer (nullable, default: 1/New)
- Setiap update status, update juga workflow_stage_id

## 4. Model Eloquent
- Model: LeadWorkflow (relasi hasMany ke Customer)
- Customer: belongsTo LeadWorkflow

## 5. UI/UX
- Di halaman customer dan summary, tampilkan badge/tahapan workflow
- Form update: dropdown/tombol untuk pindah ke tahap berikutnya
- Setiap perubahan workflow dicatat di LeadUpdate (log)

## 6. Otomasi & Validasi
- Tidak bisa loncat tahap (harus urut, kecuali admin)
- Bisa trigger notifikasi/reminder jika terlalu lama di satu tahap

## 7. Contoh Migration
```php
Schema::create('lead_workflows', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->integer('order')->default(1);
    $table->string('description')->nullable();
    $table->timestamps();
});

Schema::table('customers', function (Blueprint $table) {
    $table->unsignedBigInteger('workflow_stage_id')->nullable()->default(1);
    $table->foreign('workflow_stage_id')->references('id')->on('lead_workflows');
});
```

## 8. Contoh Relasi Model
```php
// LeadWorkflow.php
public function customers() {
    return $this->hasMany(Customer::class, 'workflow_stage_id');
}

// Customer.php
public function workflowStage() {
    return $this->belongsTo(LeadWorkflow::class, 'workflow_stage_id');
}
```

## 9. Contoh UI
- Di tabel/daftar lead: <span class="badge bg-primary">Negotiation</span>
- Di detail: Tampilkan progress bar/timeline tahapan
- Form: Dropdown/tombol "Next Stage"

---

Jika ingin langsung dibuatkan migration, model, dan contoh UI, silakan lanjutkan!