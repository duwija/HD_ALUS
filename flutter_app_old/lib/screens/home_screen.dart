import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../models/attendance_model.dart';
import '../models/shift_model.dart';
import 'clock_screen.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  AttendanceModel? _attendance;
  ShiftModel? _shift;
  bool _loading = true;
  String _name  = '';

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _loading = true);
    try {
      final name  = await AuthService.getName();
      final att   = await ApiService.getTodayAttendance();
      final shift = await ApiService.getTodayShift();
      if (mounted) setState(() { _attendance = att; _shift = shift; _name = name ?? ''; });
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _confirmLogout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Keluar'),
        content: const Text('Apakah Anda yakin ingin keluar?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(_, false), child: const Text('Batal')),
          TextButton(onPressed: () => Navigator.pop(_, true),
              child: const Text('Keluar', style: TextStyle(color: Colors.red))),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    await ApiService.logout();
    await AuthService.logout();
    if (!mounted) return;
    Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (_) => const LoginScreen()), (_) => false);
  }

  @override
  Widget build(BuildContext context) {
    final now   = DateTime.now();
    final today = DateFormat('EEEE, d MMMM yyyy', 'id').format(now);
    final time  = DateFormat('HH:mm').format(now);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Absensi Karyawan'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
          IconButton(icon: const Icon(Icons.logout), onPressed: _confirmLogout),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header greeting
                    _buildGreetingCard(today, time),
                    const SizedBox(height: 16),

                    // Shift hari ini
                    _buildShiftCard(),
                    const SizedBox(height: 16),

                    // Status absensi
                    _buildAttendanceCard(),
                    const SizedBox(height: 24),

                    // Tombol clock in/out
                    _buildClockButton(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildGreetingCard(String today, String time) {
    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Selamat Pagi' : hour < 15 ? 'Selamat Siang' : hour < 18 ? 'Selamat Sore' : 'Selamat Malam';
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1565C0), Color(0xFF1976D2)],
          begin: Alignment.topLeft, end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(greeting, style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 13)),
                const SizedBox(height: 2),
                Text(_name, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Text(today, style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 12)),
              ],
            ),
          ),
          Text(time, style: const TextStyle(color: Colors.white, fontSize: 36, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildShiftCard() {
    if (_shift == null) {
      return Card(
        child: ListTile(
          leading: const Icon(Icons.calendar_today, color: Colors.grey),
          title: const Text('Tidak ada shift hari ini'),
          subtitle: const Text('Anda memiliki hari libur atau belum dijadwalkan'),
        ),
      );
    }
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFF1565C0).withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.schedule, color: Color(0xFF1565C0)),
            ),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_shift!.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                const SizedBox(height: 2),
                Text('${_shift!.startTime} – ${_shift!.endTime}',
                    style: TextStyle(color: Colors.grey[600], fontSize: 13)),
                if (_shift!.lateTolerance > 0)
                  Text('Toleransi terlambat: ${_shift!.lateTolerance} menit',
                      style: const TextStyle(color: Colors.orange, fontSize: 12)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAttendanceCard() {
    final att = _attendance;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Status Kehadiran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
            const Divider(height: 20),
            if (att == null)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(8),
                  child: Text('Belum absen hari ini', style: TextStyle(color: Colors.grey)),
                ),
              )
            else
              Column(
                children: [
                  _infoRow('Masuk',   att.clockIn  ?? '-', Icons.login,  Colors.green),
                  if (att.lateMinutes != null && att.lateMinutes! > 0)
                    _infoRow('Terlambat', '${att.lateMinutes} menit', Icons.warning, Colors.orange),
                  _infoRow('Keluar',  att.clockOut ?? '-', Icons.logout, Colors.red),
                  if (att.workMinutes != null && att.workMinutes! > 0)
                    _infoRow('Jam kerja', att.workHours, Icons.timer, Colors.blue),
                  _infoRow('Lokasi',  att.locationName ?? '-', Icons.location_on, Colors.purple),
                  const SizedBox(height: 8),
                  _StatusBadge(status: att.status),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _infoRow(String label, String value, IconData icon, Color color) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 8),
          Text('$label: ', style: const TextStyle(fontSize: 13, color: Colors.black54)),
          Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Widget _buildClockButton() {
    final canClockIn  = _attendance == null;
    final canClockOut = _attendance?.clockIn != null && _attendance?.clockOut == null;
    final isDone      = _attendance?.clockOut != null;

    if (isDone) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.green[50],
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.green[200]!),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle, color: Colors.green, size: 26),
            SizedBox(width: 10),
            Text('Absensi hari ini selesai!',
                style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
      );
    }

    return ElevatedButton.icon(
      onPressed: (canClockIn || canClockOut)
          ? () async {
              final result = await Navigator.push<bool>(
                context,
                MaterialPageRoute(builder: (_) => ClockScreen(isClockIn: canClockIn)),
              );
              if (result == true) _loadData();
            }
          : null,
      icon: Icon(canClockIn ? Icons.login : Icons.logout),
      label: Text(
        canClockIn ? 'Clock In — Absen Masuk' : 'Clock Out — Absen Pulang',
        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
      ),
      style: ElevatedButton.styleFrom(
        backgroundColor: canClockIn ? Colors.green : Colors.orange,
        minimumSize: const Size(double.infinity, 56),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final Map<String, Map<String, dynamic>> map = {
      'present': {'label': 'Hadir',      'color': Colors.green},
      'late':    {'label': 'Terlambat',  'color': Colors.orange},
      'absent':  {'label': 'Absen',      'color': Colors.red},
      'leave':   {'label': 'Izin/Sakit', 'color': Colors.blue},
      'holiday': {'label': 'Libur',      'color': Colors.purple},
    };
    final info  = map[status] ?? {'label': status, 'color': Colors.grey};
    final color = info['color'] as Color;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color),
      ),
      child: Text(info['label'] as String,
          style: TextStyle(color: color, fontWeight: FontWeight.bold)),
    );
  }
}
