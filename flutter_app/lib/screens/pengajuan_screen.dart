import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import 'submit_leave_screen.dart';
import 'submit_overtime_screen.dart';

class PengajuanScreen extends StatefulWidget {
  const PengajuanScreen({super.key});

  @override
  State<PengajuanScreen> createState() => _PengajuanScreenState();
}

class _PengajuanScreenState extends State<PengajuanScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabs;
  List<dynamic> _leaves   = [];
  List<dynamic> _overtimes = [];
  List<dynamic> _pendingLeaves   = [];
  List<dynamic> _pendingOvertimes = [];
  bool _loading = false;
  bool _isSupervisor = false;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 3, vsync: this);
    _loadAll();
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  Future<void> _loadAll() async {
    setState(() => _loading = true);
    try {
      final results = await Future.wait([
        ApiService.getLeaves(),
        ApiService.getOvertimes(),
        ApiService.getSupervisorLeaves(status: 'pending').catchError((_) => <dynamic>[]),
        ApiService.getSupervisorOvertimes(status: 'pending').catchError((_) => <dynamic>[]),
      ]);
      if (mounted) {
        setState(() {
          _leaves          = results[0];
          _overtimes       = results[1];
          _pendingLeaves   = results[2];
          _pendingOvertimes = results[3];
          _isSupervisor    = _pendingLeaves.isNotEmpty || _pendingOvertimes.isNotEmpty;
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pengajuan'),
        actions: [IconButton(icon: const Icon(Icons.refresh), onPressed: _loadAll)],
        bottom: TabBar(
          controller: _tabs,
          tabs: [
            const Tab(icon: Icon(Icons.beach_access), text: 'Izin/Cuti'),
            const Tab(icon: Icon(Icons.more_time), text: 'Lembur'),
            Tab(
              icon: Stack(
                children: [
                  const Icon(Icons.approval),
                  if (_pendingLeaves.length + _pendingOvertimes.length > 0)
                    Positioned(
                      right: 0, top: 0,
                      child: Container(
                        padding: const EdgeInsets.all(2),
                        decoration: BoxDecoration(color: Colors.red, borderRadius: BorderRadius.circular(6)),
                        constraints: const BoxConstraints(minWidth: 12, minHeight: 12),
                        child: Text(
                          '${_pendingLeaves.length + _pendingOvertimes.length}',
                          style: const TextStyle(color: Colors.white, fontSize: 8),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              ),
              text: 'Approval',
            ),
          ],
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabs,
              children: [
                _buildLeaveTab(),
                _buildOvertimeTab(),
                _buildApprovalTab(),
              ],
            ),
    );
  }

  // ─── Tab Izin/Cuti ────────────────────────────────────────────────────────
  Widget _buildLeaveTab() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: ElevatedButton.icon(
            onPressed: () async {
              final ok = await Navigator.push<bool>(context,
                  MaterialPageRoute(builder: (_) => const SubmitLeaveScreen()));
              if (ok == true) _loadAll();
            },
            icon: const Icon(Icons.add),
            label: const Text('Ajukan Izin / Cuti'),
          ),
        ),
        Expanded(
          child: _leaves.isEmpty
              ? const Center(child: Text('Belum ada pengajuan izin/cuti.', style: TextStyle(color: Colors.grey)))
              : RefreshIndicator(
                  onRefresh: _loadAll,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    itemCount: _leaves.length,
                    itemBuilder: (_, i) => _leaveCard(_leaves[i]),
                  ),
                ),
        ),
      ],
    );
  }

  Widget _leaveCard(Map<String, dynamic> l) {
    final color = _statusColor(l['status'] as String? ?? '');
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.15),
          child: Icon(_leaveIcon(l['type'] as String? ?? ''), color: color, size: 20),
        ),
        title: Text(l['type_text'] as String? ?? '',
            style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(
          '${l['start_date']}${l['start_date'] != l['end_date'] ? ' – ${l['end_date']}' : ''} • ${l['days']} hari\n${l['reason']}',
          maxLines: 2, overflow: TextOverflow.ellipsis,
        ),
        trailing: _statusBadge(l['status_text'] as String? ?? '', color),
        isThreeLine: true,
      ),
    );
  }

  // ─── Tab Lembur ───────────────────────────────────────────────────────────
  Widget _buildOvertimeTab() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: ElevatedButton.icon(
            onPressed: () async {
              final ok = await Navigator.push<bool>(context,
                  MaterialPageRoute(builder: (_) => const SubmitOvertimeScreen()));
              if (ok == true) _loadAll();
            },
            icon: const Icon(Icons.add),
            label: const Text('Ajukan Lembur'),
          ),
        ),
        Expanded(
          child: _overtimes.isEmpty
              ? const Center(child: Text('Belum ada pengajuan lembur.', style: TextStyle(color: Colors.grey)))
              : RefreshIndicator(
                  onRefresh: _loadAll,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    itemCount: _overtimes.length,
                    itemBuilder: (_, i) => _overtimeCard(_overtimes[i]),
                  ),
                ),
        ),
      ],
    );
  }

  Widget _overtimeCard(Map<String, dynamic> o) {
    final color = _statusColor(o['status'] as String? ?? '');
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.15),
          child: Icon(Icons.more_time, color: color, size: 20),
        ),
        title: Text(o['date'] as String? ?? '',
            style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(
          '${o['start_time']} – ${o['end_time']} (${o['duration_hours']} jam)\n${o['reason']}',
          maxLines: 2, overflow: TextOverflow.ellipsis,
        ),
        trailing: _statusBadge(o['status_text'] as String? ?? '', color),
        isThreeLine: true,
      ),
    );
  }

  // ─── Tab Approval (Supervisor) ────────────────────────────────────────────
  Widget _buildApprovalTab() {
    final total = _pendingLeaves.length + _pendingOvertimes.length;
    if (!_isSupervisor && total == 0) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.approval, size: 60, color: Colors.grey),
            SizedBox(height: 12),
            Text('Tidak ada permintaan approval.', style: TextStyle(color: Colors.grey)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _loadAll,
      child: ListView(
        padding: const EdgeInsets.all(12),
        children: [
          if (_pendingLeaves.isNotEmpty) ...[
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text('Izin / Cuti Menunggu Approval',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            ),
            ..._pendingLeaves.map((l) => _approvalLeaveCard(l)),
          ],
          if (_pendingOvertimes.isNotEmpty) ...[
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text('Lembur Menunggu Approval',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            ),
            ..._pendingOvertimes.map((o) => _approvalOvertimeCard(o)),
          ],
        ],
      ),
    );
  }

  Widget _approvalLeaveCard(Map<String, dynamic> l) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.person, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(l['employee_name'] as String? ?? '',
                    style: const TextStyle(fontWeight: FontWeight.bold)),
                const Spacer(),
                Text(l['type_text'] as String? ?? '',
                    style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.w600, fontSize: 12)),
              ],
            ),
            const SizedBox(height: 4),
            Text('${l['start_date']} – ${l['end_date']} (${l['days']} hari)'),
            Text(l['reason'] as String? ?? '', style: const TextStyle(color: Colors.grey, fontSize: 12)),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _doApproveLeave(l['id'] as int, 'rejected'),
                    icon: const Icon(Icons.close, size: 16),
                    label: const Text('Tolak'),
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _doApproveLeave(l['id'] as int, 'approved'),
                    icon: const Icon(Icons.check, size: 16),
                    label: const Text('Setujui'),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _approvalOvertimeCard(Map<String, dynamic> o) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.person, size: 16, color: Colors.grey),
                const SizedBox(width: 4),
                Text(o['employee_name'] as String? ?? '',
                    style: const TextStyle(fontWeight: FontWeight.bold)),
                const Spacer(),
                const Text('Lembur', style: TextStyle(color: Colors.orange, fontWeight: FontWeight.w600, fontSize: 12)),
              ],
            ),
            const SizedBox(height: 4),
            Text('${o['date']}  ${o['start_time']} – ${o['end_time']} (${o['duration_hours']} jam)'),
            Text(o['reason'] as String? ?? '', style: const TextStyle(color: Colors.grey, fontSize: 12)),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _doApproveOvertime(o['id'] as int, 'rejected'),
                    icon: const Icon(Icons.close, size: 16),
                    label: const Text('Tolak'),
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _doApproveOvertime(o['id'] as int, 'approved'),
                    icon: const Icon(Icons.check, size: 16),
                    label: const Text('Setujui'),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _doApproveLeave(int id, String status) async {
    final notes = await _askNotes(status);
    if (notes == null) return;
    try {
      await ApiService.approveLeave(id, status, notes: notes.isEmpty ? null : notes);
      _showSnack(status == 'approved' ? 'Disetujui!' : 'Ditolak.', status == 'approved' ? Colors.green : Colors.red);
      _loadAll();
    } catch (e) {
      _showSnack('Error: $e', Colors.red);
    }
  }

  Future<void> _doApproveOvertime(int id, String status) async {
    final notes = await _askNotes(status);
    if (notes == null) return;
    try {
      await ApiService.approveOvertime(id, status, notes: notes.isEmpty ? null : notes);
      _showSnack(status == 'approved' ? 'Disetujui!' : 'Ditolak.', status == 'approved' ? Colors.green : Colors.red);
      _loadAll();
    } catch (e) {
      _showSnack('Error: $e', Colors.red);
    }
  }

  Future<String?> _askNotes(String status) async {
    final ctrl = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(status == 'approved' ? 'Setujui Pengajuan' : 'Tolak Pengajuan'),
        content: TextField(
          controller: ctrl,
          decoration: const InputDecoration(labelText: 'Catatan (opsional)'),
          maxLines: 2,
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(_, null), child: const Text('Batal')),
          ElevatedButton(onPressed: () => Navigator.pop(_, ctrl.text), child: const Text('Konfirmasi')),
        ],
      ),
    );
  }

  void _showSnack(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: color, behavior: SnackBarBehavior.floating),
    );
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────
  Color _statusColor(String status) => switch (status) {
    'approved' => Colors.green,
    'rejected' => Colors.red,
    _          => Colors.orange,
  };

  IconData _leaveIcon(String type) => switch (type) {
    'sakit'        => Icons.local_hospital,
    'izin_lainnya' => Icons.description,
    _              => Icons.beach_access,
  };

  Widget _statusBadge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color),
      ),
      child: Text(text, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
    );
  }
}
