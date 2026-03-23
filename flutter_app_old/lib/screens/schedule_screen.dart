import 'package:flutter/material.dart';
import 'package:table_calendar/table_calendar.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';

class ScheduleScreen extends StatefulWidget {
  const ScheduleScreen({super.key});

  @override
  State<ScheduleScreen> createState() => _ScheduleScreenState();
}

class _ScheduleScreenState extends State<ScheduleScreen> {
  DateTime _focusedDay   = DateTime.now();
  DateTime? _selectedDay = DateTime.now();
  bool _loading = true;
  List<dynamic> _schedules = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final month = DateFormat('yyyy-MM').format(_focusedDay);
      final data  = await ApiService.getSchedule(month);
      if (mounted) setState(() => _schedules = data);
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Map<String, dynamic>? _scheduleForDay(DateTime day) {
    final ds = DateFormat('yyyy-MM-dd').format(day);
    try {
      return _schedules.firstWhere((s) => s['date'] == ds) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Color _dayTypeColor(String? type, String? shiftColor) {
    switch (type) {
      case 'off':     return Colors.grey;
      case 'holiday': return Colors.purple;
      case 'leave':   return Colors.blue;
      case 'work':
        if (shiftColor != null) return _hexColor(shiftColor);
        return Colors.green;
      default: return Colors.transparent;
    }
  }

  Color _hexColor(String hex) {
    try {
      final h = hex.replaceAll('#', '');
      return Color(int.parse('FF$h', radix: 16));
    } catch (_) {
      return Colors.green;
    }
  }

  String _dayTypeLabel(String? type) {
    switch (type) {
      case 'off':     return 'Hari Off';
      case 'holiday': return 'Hari Libur';
      case 'leave':   return 'Izin / Sakit';
      default:        return 'Hari Kerja';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Jadwal Shift')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                TableCalendar(
                  firstDay:    DateTime(2020),
                  lastDay:     DateTime(2099),
                  focusedDay:  _focusedDay,
                  selectedDayPredicate: (d) => isSameDay(_selectedDay, d),
                  calendarFormat: CalendarFormat.month,
                  startingDayOfWeek: StartingDayOfWeek.monday,
                  headerStyle: const HeaderStyle(
                    formatButtonVisible: false,
                    titleCentered: true,
                    titleTextStyle: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  onDaySelected: (selectedDay, focusedDay) {
                    setState(() { _selectedDay = selectedDay; _focusedDay = focusedDay; });
                  },
                  onPageChanged: (focusedDay) {
                    _focusedDay = focusedDay;
                    _load();
                  },
                  calendarBuilders: CalendarBuilders(
                    markerBuilder: (context, day, events) {
                      final sched = _scheduleForDay(day);
                      if (sched == null) return null;
                      final color = _dayTypeColor(sched['day_type'], sched['shift']?['color']);
                      if (color == Colors.transparent) return null;
                      return Positioned(
                        bottom: 4,
                        child: Container(
                          width: 6, height: 6,
                          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                        ),
                      );
                    },
                    selectedBuilder: (context, day, focusedDay) {
                      final sched = _scheduleForDay(day);
                      final color = sched != null
                          ? _dayTypeColor(sched['day_type'], sched['shift']?['color'])
                          : const Color(0xFF1565C0);
                      return Container(
                        margin: const EdgeInsets.all(4),
                        alignment: Alignment.center,
                        decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                        child: Text('${day.day}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                      );
                    },
                  ),
                ),
                const Divider(height: 1),

                // Detail hari terpilih
                if (_selectedDay != null) _buildDayDetail(_selectedDay!),
              ],
            ),
    );
  }

  Widget _buildDayDetail(DateTime day) {
    final sched    = _scheduleForDay(day);
    final dateStr  = DateFormat('EEEE, d MMMM yyyy', 'id').format(day);
    final color    = sched != null
        ? _dayTypeColor(sched['day_type'], sched['shift']?['color'])
        : Colors.grey[300]!;

    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(dateStr, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            const SizedBox(height: 12),
            if (sched == null)
              _infoTile(Icons.event_busy, 'Tidak ada jadwal', Colors.grey)
            else ...[
              _infoTile(
                Icons.circle,
                _dayTypeLabel(sched['day_type']),
                color,
              ),
              if (sched['shift'] != null) ...[
                const SizedBox(height: 8),
                Card(
                  color: color.withOpacity(0.08),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                    side: BorderSide(color: color.withOpacity(0.4)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(children: [
                          Icon(Icons.schedule, color: color, size: 20),
                          const SizedBox(width: 8),
                          Text(sched['shift']['name'] ?? '',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: color)),
                        ]),
                        const SizedBox(height: 8),
                        Row(children: [
                          Icon(Icons.login,  size: 16, color: Colors.green),
                          const SizedBox(width: 4),
                          Text('Masuk: ${sched['shift']['start_time'] ?? '-'}',
                              style: const TextStyle(fontSize: 14)),
                          const SizedBox(width: 16),
                          Icon(Icons.logout, size: 16, color: Colors.red),
                          const SizedBox(width: 4),
                          Text('Pulang: ${sched['shift']['end_time'] ?? '-'}',
                              style: const TextStyle(fontSize: 14)),
                        ]),
                        if ((sched['shift']['late_tolerance'] ?? 0) > 0) ...[
                          const SizedBox(height: 4),
                          Text('Toleransi terlambat: ${sched['shift']['late_tolerance']} menit',
                              style: const TextStyle(color: Colors.orange, fontSize: 12)),
                        ],
                      ],
                    ),
                  ),
                ),
              ],
              if (sched['note'] != null && sched['note'].toString().isNotEmpty) ...[
                const SizedBox(height: 8),
                _infoTile(Icons.note, sched['note'], Colors.grey[600]!),
              ],
            ],
          ],
        ),
      ),
    );
  }

  Widget _infoTile(IconData icon, String text, Color color) {
    return Row(children: [
      Icon(icon, size: 18, color: color),
      const SizedBox(width: 8),
      Text(text, style: TextStyle(fontSize: 14, color: color, fontWeight: FontWeight.w500)),
    ]);
  }
}
