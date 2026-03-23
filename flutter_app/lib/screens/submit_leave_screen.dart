import 'dart:io';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';

class SubmitLeaveScreen extends StatefulWidget {
  const SubmitLeaveScreen({super.key});

  @override
  State<SubmitLeaveScreen> createState() => _SubmitLeaveScreenState();
}

class _SubmitLeaveScreenState extends State<SubmitLeaveScreen> {
  final _formKey = GlobalKey<FormState>();
  String _type = 'cuti';
  DateTime? _startDate;
  DateTime? _endDate;
  final _reasonCtrl = TextEditingController();
  File? _attachment;
  bool _submitting = false;

  Future<void> _pickDate(bool isStart) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked == null) return;
    setState(() {
      if (isStart) {
        _startDate = picked;
        if (_endDate != null && _endDate!.isBefore(picked)) _endDate = picked;
      } else {
        _endDate = picked;
      }
    });
  }

  Future<void> _pickAttachment() async {
    final img = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 70);
    if (img != null) setState(() => _attachment = File(img.path));
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_startDate == null || _endDate == null) {
      _showSnack('Pilih tanggal mulai dan selesai.', Colors.orange);
      return;
    }
    setState(() => _submitting = true);
    try {
      final res = await ApiService.submitLeave(
        type: _type,
        startDate: DateFormat('yyyy-MM-dd').format(_startDate!),
        endDate: DateFormat('yyyy-MM-dd').format(_endDate!),
        reason: _reasonCtrl.text.trim(),
        attachment: _attachment,
      );
      if (!mounted) return;
      if (res['success'] == true) {
        _showSnack('Pengajuan berhasil dikirim!', Colors.green);
        await Future.delayed(const Duration(milliseconds: 800));
        if (mounted) Navigator.pop(context, true);
      } else {
        _showSnack(res['message'] ?? 'Gagal mengirim.', Colors.red);
      }
    } catch (e) {
      _showSnack('Error: $e', Colors.red);
    }
    setState(() => _submitting = false);
  }

  void _showSnack(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: color, behavior: SnackBarBehavior.floating),
    );
  }

  @override
  Widget build(BuildContext context) {
    final fmt = DateFormat('dd MMM yyyy', 'id');
    return Scaffold(
      appBar: AppBar(title: const Text('Pengajuan Izin / Cuti')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Tipe
              DropdownButtonFormField<String>(
                value: _type,
                decoration: const InputDecoration(labelText: 'Jenis Pengajuan'),
                items: const [
                  DropdownMenuItem(value: 'cuti',         child: Text('Cuti')),
                  DropdownMenuItem(value: 'sakit',        child: Text('Sakit')),
                  DropdownMenuItem(value: 'izin_lainnya', child: Text('Izin Lainnya')),
                ],
                onChanged: (v) => setState(() => _type = v!),
              ),
              const SizedBox(height: 16),

              // Tanggal mulai
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.calendar_today, color: Color(0xFF1565C0)),
                title: Text(_startDate == null ? 'Tanggal Mulai' : fmt.format(_startDate!)),
                subtitle: _startDate == null ? const Text('Belum dipilih', style: TextStyle(color: Colors.red)) : null,
                trailing: const Icon(Icons.arrow_drop_down),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(color: Colors.grey[300]!),
                ),
                tileColor: Colors.grey[50],
                onTap: () => _pickDate(true),
              ),
              const SizedBox(height: 12),

              // Tanggal selesai
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.calendar_today, color: Color(0xFF1565C0)),
                title: Text(_endDate == null ? 'Tanggal Selesai' : fmt.format(_endDate!)),
                subtitle: _endDate == null ? const Text('Belum dipilih', style: TextStyle(color: Colors.red)) : null,
                trailing: const Icon(Icons.arrow_drop_down),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(color: Colors.grey[300]!),
                ),
                tileColor: Colors.grey[50],
                onTap: () => _pickDate(false),
              ),
              const SizedBox(height: 16),

              // Alasan
              TextFormField(
                controller: _reasonCtrl,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'Alasan',
                  hintText: 'Tuliskan alasan pengajuan...',
                  alignLabelWithHint: true,
                ),
                validator: (v) => v == null || v.trim().isEmpty ? 'Alasan wajib diisi' : null,
              ),
              const SizedBox(height: 16),

              // Lampiran (opsional)
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _pickAttachment,
                      icon: const Icon(Icons.attach_file),
                      label: Text(_attachment == null ? 'Lampiran (opsional)' : 'Berkas dipilih ✓'),
                    ),
                  ),
                  if (_attachment != null)
                    IconButton(
                      icon: const Icon(Icons.clear, color: Colors.red),
                      onPressed: () => setState(() => _attachment = null),
                    ),
                ],
              ),
              const SizedBox(height: 28),

              ElevatedButton.icon(
                onPressed: _submitting ? null : _submit,
                icon: _submitting
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.send),
                label: const Text('Kirim Pengajuan', style: TextStyle(fontSize: 15)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
