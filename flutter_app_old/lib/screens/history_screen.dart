import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../models/attendance_model.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  DateTime _selectedMonth = DateTime.now();
  bool  _loading = true;
  List<AttendanceModel> _records  = [];
  Map<String, int>      _summary  = {};

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final month = DateFormat('yyyy-MM').format(_selectedMonth);
      final res   = await ApiService.getHistory(month);
      final data  = res['data'] as List? ?? [];
      final sum   = (res['summary'] as Map<String, dynamic>?) ?? {};
      if (mounted) setState(() {
        _records = data.map((e) => AttendanceModel.fromJson(e)).toList();
        _summary = sum.map((k, v) => MapEntry(k, (v as num).toInt()));
      });
    } catch(_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _pickMonth() async {
    DateTime temp = _selectedMonth;
    await showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Pilih Bulan'),
        content: SizedBox(
          height: 200,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(icon: const Icon(Icons.chevron_left),
                      onPressed: () { temp = DateTime(temp.year, temp.month-1); (context as Element).markNeedsBuild(); }),
                  StatefulBuilder(builder: (ctx, setSt) => Text(DateFormat('MMMM yyyy','id').format(temp))),
                  IconButton(icon: const Icon(Icons.chevron_right),
                      onPressed: () { temp = DateTime(temp.year, temp.month+1); }),
                ],
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(_), child: const Text('Batal')),
          TextButton(onPressed: () { _selectedMonth = temp; Navigator.pop(_); _load(); }, child: const Text('OK')),
        ],
      ),
    );
  }

  Color _statusColor(String s) {
    switch(s) {
      case 'present': return Colors.green;
      case 'late':    return Colors.orange;
      case 'absent':  return Colors.red;
      case 'leave':   return Colors.blue;
      default:        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Riwayat Absensi'),
        actions: [
          TextButton.icon(
            onPressed: _pickMonth,
            icon: const Icon(Icons.calendar_month, color: Colors.white),
            label: Text(DateFormat('MMM yyyy', 'id').format(_selectedMonth),
                style: const TextStyle(color: Colors.white)),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: Column(
                children: [
                  _buildSummary(),
                  Expanded(child: _buildList()),
                ],
              ),
            ),
    );
  }

  Widget _buildSummary() {
    final items = [
      {'label': 'Hadir',     'key': 'present', 'color': Colors.green},
      {'label': 'Terlambat', 'key': 'late',    'color': Colors.orange},
      {'label': 'Absen',     'key': 'absent',  'color': Colors.red},
      {'label': 'Izin',      'key': 'leave',   'color': Colors.blue},
    ];
    return Container(
      color: const Color(0xFF1565C0),
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: items.map((item) {
          final val = _summary[item['key']] ?? 0;
          final color = item['color'] as Color;
          return Column(
            children: [
              Text('$val', style: TextStyle(color: color == Colors.green ? Colors.greenAccent
                  : color == Colors.orange ? Colors.orangeAccent
                  : color == Colors.red ? Colors.redAccent : Colors.lightBlueAccent,
                  fontWeight: FontWeight.bold, fontSize: 22)),
              const SizedBox(height: 2),
              Text(item['label'] as String, style: const TextStyle(color: Colors.white70, fontSize: 11)),
            ],
          );
        }).toList(),
      ),
    );
  }

  Widget _buildList() {
    if (_records.isEmpty) {
      return const Center(child: Text('Tidak ada data absensi bulan ini', style: TextStyle(color: Colors.grey)));
    }
    return ListView.builder(
      padding: const EdgeInsets.symmetric(vertical: 8),
      itemCount: _records.length,
      itemBuilder: (_, i) {
        final rec = _records[i];
        final color = _statusColor(rec.status);
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: color.withOpacity(0.15),
              child: Text(
                rec.date.substring(8, 10),
                style: TextStyle(color: color, fontWeight: FontWeight.bold),
              ),
            ),
            title: Row(
              children: [
                Expanded(child: Text(
                  DateFormat('EEE, d MMM', 'id').format(DateTime.parse(rec.date)),
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                )),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: color.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: color),
                  ),
                  child: Text(rec.statusLabel, style: TextStyle(color: color, fontSize: 11)),
                ),
              ],
            ),
            subtitle: Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Row(
                children: [
                  if (rec.clockIn != null)
                    _chip(Icons.login,  rec.clockIn!,  Colors.green),
                  if (rec.clockOut != null) ...[
                    const SizedBox(width: 8),
                    _chip(Icons.logout, rec.clockOut!, Colors.orange),
                  ],
                  if (rec.lateMinutes != null && rec.lateMinutes! > 0) ...[
                    const SizedBox(width: 8),
                    _chip(Icons.warning, '${rec.lateMinutes}m', Colors.red),
                  ],
                  if (rec.workMinutes != null && rec.workMinutes! > 0) ...[
                    const SizedBox(width: 8),
                    _chip(Icons.timer, rec.workHours, Colors.blue),
                  ],
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _chip(IconData icon, String label, Color color) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(icon, size: 12, color: color),
      const SizedBox(width: 2),
      Text(label, style: TextStyle(fontSize: 12, color: color)),
    ]);
  }
}
