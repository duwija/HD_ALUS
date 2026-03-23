import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';

class SubmitOvertimeScreen extends StatefulWidget {
  const SubmitOvertimeScreen({super.key});

  @override
  State<SubmitOvertimeScreen> createState() => _SubmitOvertimeScreenState();
}

class _SubmitOvertimeScreenState extends State<SubmitOvertimeScreen> {
  final _formKey = GlobalKey<FormState>();
  DateTime? _date;
  TimeOfDay? _startTime;
  TimeOfDay? _endTime;
  final _reasonCtrl = TextEditingController();
  bool _submitting = false;

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 60)),
    );
    if (picked != null) setState(() => _date = picked);
  }

  Future<void> _pickTime(bool isStart) async {
    final picked = await showTimePicker(
      context: context,
      initialTime: isStart ? const TimeOfDay(hour: 17, minute: 0) : const TimeOfDay(hour: 20, minute: 0),
    );
    if (picked == null) return;
    setState(() {
      if (isStart) _startTime = picked;
      else _endTime = picked;
    });
  }

  String _formatTime(TimeOfDay t) =>
      '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}';

  double _calcDuration() {
    if (_startTime == null || _endTime == null) return 0;
    final startMin = _startTime!.hour * 60 + _startTime!.minute;
    final endMin   = _endTime!.hour * 60 + _endTime!.minute;
    return (endMin - startMin) / 60.0;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_date == null || _startTime == null || _endTime == null) {
      _showSnack('Pilih tanggal dan jam lembur.', Colors.orange);
      return;
    }
    if (_calcDuration() <= 0) {
      _showSnack('Jam selesai harus setelah jam mulai.', Colors.orange);
      return;
    }
    setState(() => _submitting = true);
    try {
      final res = await ApiService.submitOvertime(
        date: DateFormat('yyyy-MM-dd').format(_date!),
        startTime: _formatTime(_startTime!),
        endTime: _formatTime(_endTime!),
        reason: _reasonCtrl.text.trim(),
      );
      if (!mounted) return;
      if (res['success'] == true) {
        _showSnack('Pengajuan lembur berhasil dikirim!', Colors.green);
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
    final dur = _calcDuration();

    return Scaffold(
      appBar: AppBar(title: const Text('Pengajuan Lembur')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Tanggal lembur
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.calendar_today, color: Color(0xFF1565C0)),
                title: Text(_date == null ? 'Tanggal Lembur' : fmt.format(_date!)),
                subtitle: _date == null ? const Text('Belum dipilih', style: TextStyle(color: Colors.red)) : null,
                trailing: const Icon(Icons.arrow_drop_down),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(color: Colors.grey[300]!),
                ),
                tileColor: Colors.grey[50],
                onTap: _pickDate,
              ),
              const SizedBox(height: 12),

              // Jam mulai & selesai
              Row(
                children: [
                  Expanded(
                    child: ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.access_time, color: Colors.green),
                      title: Text(_startTime == null ? 'Jam Mulai' : _formatTime(_startTime!)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: BorderSide(color: Colors.grey[300]!),
                      ),
                      tileColor: Colors.grey[50],
                      onTap: () => _pickTime(true),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: const Icon(Icons.access_time_filled, color: Colors.orange),
                      title: Text(_endTime == null ? 'Jam Selesai' : _formatTime(_endTime!)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                        side: BorderSide(color: Colors.grey[300]!),
                      ),
                      tileColor: Colors.grey[50],
                      onTap: () => _pickTime(false),
                    ),
                  ),
                ],
              ),

              if (dur > 0) ...[
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.blue[50],
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.timer, color: Colors.blue, size: 18),
                      const SizedBox(width: 8),
                      Text('Durasi lembur: ${dur.toStringAsFixed(1)} jam',
                          style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 16),
              TextFormField(
                controller: _reasonCtrl,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'Alasan Lembur',
                  hintText: 'Tuliskan pekerjaan yang dikerjakan saat lembur...',
                  alignLabelWithHint: true,
                ),
                validator: (v) => v == null || v.trim().isEmpty ? 'Alasan wajib diisi' : null,
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
