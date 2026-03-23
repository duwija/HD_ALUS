import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';
import '../models/ticket_model.dart';

class TicketDetailScreen extends StatefulWidget {
  final int ticketId;
  const TicketDetailScreen({super.key, required this.ticketId});

  @override
  State<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends State<TicketDetailScreen> {
  TicketModel? _ticket;
  bool _loading = true;
  bool _sending = false;
  final _replyCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final t = await ApiService.getTicketDetail(widget.ticketId);
      if (mounted) setState(() { _ticket = t; });
    } catch (e) {
      if (mounted) _showSnack('Gagal memuat tiket: $e', Colors.red);
    }
    if (mounted) setState(() => _loading = false);
  }

  // ── Kirim komentar/update ─────────────────────────────────────────────────
  Future<void> _sendReply() async {
    final text = _replyCtrl.text.trim();
    if (text.isEmpty) return;

    setState(() => _sending = true);
    try {
      final res = await ApiService.addTicketUpdate(widget.ticketId, text);
      if (res['success'] == true) {
        _replyCtrl.clear();
        FocusScope.of(context).unfocus();
        await _load();
        // Scroll ke bawah
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (_scrollCtrl.hasClients) {
            _scrollCtrl.animateTo(_scrollCtrl.position.maxScrollExtent,
                duration: const Duration(milliseconds: 300), curve: Curves.easeOut);
          }
        });
        _showSnack('Update berhasil dikirim', Colors.green);
      } else {
        _showSnack(res['message'] ?? 'Gagal mengirim update', Colors.red);
      }
    } catch (e) {
      _showSnack('Error: $e', Colors.red);
    }
    if (mounted) setState(() => _sending = false);
  }

  // ── Ubah status ────────────────────────────────────────────────────────────
  Future<void> _changeStatus() async {
    final ticket = _ticket;
    if (ticket == null) return;

    const statuses = ['Open', 'Inprogress', 'Solve', 'Close'];
    final newStatus = await showModalBottomSheet<String>(
      context: context,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Padding(
            padding: EdgeInsets.all(16),
            child: Text('Ubah Status Tiket',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ),
          ...statuses.map((s) {
            final isCurrent = s == ticket.status;
            final style     = TicketModel(id: 0, tittle: '', status: s).statusStyle;
            final color     = Color(style['color'] as int);
            return ListTile(
              leading: Icon(
                isCurrent ? Icons.radio_button_checked : Icons.radio_button_off,
                color: isCurrent ? color : Colors.grey,
              ),
              title: Text(s, style: TextStyle(
                fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                color: isCurrent ? color : null,
              )),
              onTap: () => Navigator.pop(_, s),
            );
          }).toList(),
          const SizedBox(height: 8),
        ],
      ),
    );

    if (newStatus == null || newStatus == ticket.status) return;

    try {
      final res = await ApiService.updateTicketStatus(ticket.id, newStatus);
      if (res['success'] == true) {
        _showSnack('Status diperbarui ke $newStatus', Colors.green);
        _load();
      } else {
        _showSnack(res['message'] ?? 'Gagal memperbarui status', Colors.red);
      }
    } catch (e) {
      _showSnack('Error: $e', Colors.red);
    }
  }

  void _showSnack(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: color, behavior: SnackBarBehavior.floating),
    );
  }

  @override
  void dispose() {
    _replyCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ticket = _ticket;
    return Scaffold(
      appBar: AppBar(
        title: Text(_loading ? 'Tiket' : '#${ticket?.id}'),
        actions: [
          if (ticket != null)
            IconButton(
              icon: const Icon(Icons.swap_horiz),
              tooltip: 'Ubah Status',
              onPressed: _changeStatus,
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _load,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ticket == null
              ? const Center(child: Text('Tiket tidak ditemukan'))
              : Column(
                  children: [
                    Expanded(
                      child: SingleChildScrollView(
                        controller: _scrollCtrl,
                        padding: const EdgeInsets.all(14),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildHeader(ticket),
                            const SizedBox(height: 12),
                            if (ticket.description?.isNotEmpty == true) ...[
                              _buildSection('Deskripsi', ticket.description!),
                              const SizedBox(height: 12),
                            ],
                            if (ticket.steps.isNotEmpty) ...[
                              _buildSteps(ticket),
                              const SizedBox(height: 12),
                            ],
                            _buildTimeline(ticket),
                          ],
                        ),
                      ),
                    ),

                    // Reply box
                    _buildReplyBox(),
                  ],
                ),
    );
  }

  // ── Header tiket ──────────────────────────────────────────────────────────
  Widget _buildHeader(TicketModel ticket) {
    final style = ticket.statusStyle;
    final color = Color(style['color'] as int);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(ticket.tittle,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: _changeStatus,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: color),
                    ),
                    child: Text(style['label'] as String,
                        style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
            const Divider(height: 16),
            Wrap(
              spacing: 16, runSpacing: 8,
              children: [
                if (ticket.calledBy != null)
                  _infoItem(Icons.person, 'Pelapor', ticket.calledBy!),
                if (ticket.phone != null)
                  _infoItem(Icons.phone, 'Telepon', ticket.phone!),
                if (ticket.customerName != null)
                  _infoItem(Icons.account_circle, 'Customer', ticket.customerName!),
                if (ticket.category != null)
                  _infoItem(Icons.label, 'Kategori', ticket.category!),
                if (ticket.assignTo != null)
                  _infoItem(Icons.engineering, 'Teknisi', ticket.assignTo!),
                if (ticket.date != null)
                  _infoItem(Icons.schedule, 'Tanggal', '${ticket.date} ${ticket.time ?? ''}'),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoItem(IconData icon, String label, String value) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(icon, size: 14, color: Colors.grey),
      const SizedBox(width: 4),
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey)),
        Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      ]),
    ]);
  }

  // ── Steps progress bar ────────────────────────────────────────────────────
  Widget _buildSteps(TicketModel ticket) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Progress', style: TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            Row(
              children: ticket.steps.asMap().entries.map((e) {
                final step     = e.value;
                final isLast   = e.key == ticket.steps.length - 1;
                final isCurrent= step.isCurrent;
                final isDone   = ticket.steps.any((s) => s.isCurrent && s.position > step.position)
                    || (!isCurrent && ticket.steps.indexWhere((s) => s.isCurrent) >
                        ticket.steps.indexOf(step));
                final color    = isCurrent ? Colors.blue : isDone ? Colors.green : Colors.grey[300]!;
                return Expanded(
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          children: [
                            CircleAvatar(
                              radius: 14,
                              backgroundColor: color,
                              child: isCurrent
                                  ? const Icon(Icons.circle, color: Colors.white, size: 10)
                                  : isDone
                                      ? const Icon(Icons.check, color: Colors.white, size: 14)
                                      : Text('${step.position}',
                                          style: TextStyle(fontSize: 11,
                                              color: isDone ? Colors.white : Colors.black54)),
                            ),
                            const SizedBox(height: 4),
                            Text(step.name,
                                style: TextStyle(fontSize: 10,
                                    fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                                    color: isCurrent ? Colors.blue : null),
                                textAlign: TextAlign.center),
                          ],
                        ),
                      ),
                      if (!isLast)
                        Expanded(child: Container(height: 2, color: isDone ? Colors.green : Colors.grey[200])),
                    ],
                  ),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }

  // ── Deskripsi ─────────────────────────────────────────────────────────────
  Widget _buildSection(String title, String content) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 6),
            Text(content, style: const TextStyle(fontSize: 14, height: 1.5)),
          ],
        ),
      ),
    );
  }

  // ── Timeline update ───────────────────────────────────────────────────────
  Widget _buildTimeline(TicketModel ticket) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Riwayat Update (${ticket.updates.length})',
                style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            if (ticket.updates.isEmpty)
              const Text('Belum ada update', style: TextStyle(color: Colors.grey, fontSize: 13))
            else
              ...ticket.updates.asMap().entries.map((e) {
                final update = e.value;
                final isLast = e.key == ticket.updates.length - 1;
                return IntrinsicHeight(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Column(children: [
                        CircleAvatar(
                          radius: 14,
                          backgroundColor: Colors.blue[50],
                          child: Text(
                            (update.updatedBy?.substring(0, 1) ?? '?').toUpperCase(),
                            style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 12),
                          ),
                        ),
                        if (!isLast)
                          Expanded(child: Container(width: 2, color: Colors.grey[200])),
                      ]),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Padding(
                          padding: EdgeInsets.only(bottom: isLast ? 0 : 16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Text(update.updatedBy ?? '-',
                                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                  const Spacer(),
                                  Text(_formatDate(update.createdAt),
                                      style: TextStyle(fontSize: 11, color: Colors.grey[500])),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: Colors.grey[50],
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: Colors.grey[200]!),
                                ),
                                child: Text(update.description,
                                    style: const TextStyle(fontSize: 13, height: 1.4)),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }

  // ── Reply box ─────────────────────────────────────────────────────────────
  Widget _buildReplyBox() {
    return Container(
      padding: EdgeInsets.only(
        left: 12, right: 12, top: 10,
        bottom: MediaQuery.of(context).viewInsets.bottom + 10,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 8, offset: const Offset(0, -2))],
      ),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _replyCtrl,
              decoration: InputDecoration(
                hintText: 'Tulis update / komentar...',
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                isDense: true,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(24)),
              ),
              maxLines: 3,
              minLines: 1,
              textInputAction: TextInputAction.newline,
            ),
          ),
          const SizedBox(width: 8),
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            child: _sending
                ? const SizedBox(width: 44, height: 44, child: CircularProgressIndicator(strokeWidth: 2))
                : FloatingActionButton.small(
                    key: const ValueKey('send'),
                    onPressed: _sendReply,
                    backgroundColor: const Color(0xFF1565C0),
                    child: const Icon(Icons.send, color: Colors.white, size: 18),
                  ),
          ),
        ],
      ),
    );
  }

  String _formatDate(String? dt) {
    if (dt == null) return '';
    try {
      return DateFormat('d MMM, HH:mm', 'id').format(DateTime.parse(dt));
    } catch (_) {
      return dt;
    }
  }
}
