{{-- Input bukti transaksi (dipakai di form kas masuk, kas keluar, transfer kas, jurnal umum) --}}
@if(\App\JurnalAttachment::available())
<div class="mt-3">
  <label class="form-label" for="attachments"><i class="fas fa-paperclip mr-1"></i> Bukti Transaksi <small class="text-muted">(opsional)</small></label>
  <input type="file" name="attachments[]" id="attachments" class="form-control-file" multiple
         accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
  <small class="form-text text-muted">
    Bisa lebih dari satu file. Format: gambar, PDF, Word, Excel, CSV, TXT. Maks {{ \App\JurnalAttachment::MAX_KB / 1024 }} MB per file.
  </small>
  @error('attachments.*')
    <div class="text-danger small">{{ $message }}</div>
  @enderror
</div>
@endif
