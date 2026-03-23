import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../models/ticket_model.dart';

class MyTicketScreen extends StatefulWidget {
  const MyTicketScreen({super.key});

  @override
  State<MyTicketScreen> createState() => _MyTicketScreenState();
}

class _MyTicketScreenState extends State<MyTicketScreen> {
  List<TicketModel> _tickets = [];
  bool _loading = true;
  int _total = 0, _open = 0, _inprogress = 0, _solved = 0;

  @override
  void initState() {
    super.initState();
    _loadTickets();
  }

  Future<void> _loadTickets() async {
    setState(() => _loading = true);
    try {
      final data = await ApiService.getTickets(mine: true);
      final summary = await ApiService.getTicketSummary();
      final list = (data['data'] as List? ?? [])
          .map((e) => TicketModel.fromJson(e as Map<String, dynamic>))
          .toList();
      if (mounted) {
        setState(() {
          _tickets    = list;
          _total      = summary['total']      as int? ?? 0;
          _open       = summary['open']       as int? ?? 0;
          _inprogress = summary['inprogress'] as int? ?? 0;
          _solved     = summary['solve']      as int? ?? 0;
        });
      }
    } catch (_) {}
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _openTicketInBrowser(int id) async {
    final uri = Uri.parse(ApiConfig.ticketWebView(id));
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tidak dapat membuka browser.'), behavior: SnackBarBehavior.floating),
        );
      }
    }
  }

  Future<void> _openAllTickets() async {
    final uri = Uri.parse(ApiConfig.ticketWebBase);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Ticket'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadTickets),
          IconButton(
            icon: const Icon(Icons.open_in_browser),
            tooltip: 'Buka semua tiket di browser',
            onPressed: _openAllTickets,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadTickets,
              child: Column(
                children: [
                  // Summary bar
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    color: const Color(0xFF1565C0),
                    child: Row(
                      children: [
                        _summaryItem('Total', _total, Colors.white),
                        _summaryItem('Open', _open, Colors.red[200]!),
                        _summaryItem('Proses', _inprogress, Colors.yellow[200]!),
                        _summaryItem('Selesai', _solved, Colors.green[200]!),
                      ],
                    ),
                  ),

                  // Info tap to open browser
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    color: Colors.blue[50],
                    child: const Row(
                      children: [
                        Icon(Icons.info_outline, size: 14, color: Colors.blue),
                        SizedBox(width: 6),
                        Text('Tap tiket untuk membuka di browser',
                            style: TextStyle(fontSize: 12, color: Colors.blue)),
                      ],
                    ),
                  ),

                  Expanded(
                    child: _tickets.isEmpty
                        ? const Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.confirmation_number_outlined, size: 60, color: Colors.grey),
                                SizedBox(height: 8),
                                Text('Tidak ada tiket', style: TextStyle(color: Colors.grey)),
                              ],
                            ),
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.all(12),
                            itemCount: _tickets.length,
                            itemBuilder: (_, i) => _ticketCard(_tickets[i]),
                          ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _ticketCard(TicketModel t) {
    final statusColor = _statusColor(t.status);
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _openTicketInBrowser(t.id),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Container(
                width: 44, height: 44,
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text('#${t.id}',
                      style: TextStyle(fontWeight: FontWeight.bold, color: statusColor, fontSize: 13)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(t.tittle, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 2),
                    Text(t.customerName ?? '-',
                        style: TextStyle(color: Colors.grey[600], fontSize: 12),
                        maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 2),
                    Text(t.date ?? '-', style: const TextStyle(color: Colors.grey, fontSize: 11)),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  _statusChip(t.status, statusColor),
                  const SizedBox(height: 4),
                  const Icon(Icons.open_in_browser, size: 16, color: Colors.grey),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _summaryItem(String label, int count, Color color) {
    return Expanded(
      child: Column(
        children: [
          Text('$count', style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 20)),
          Text(label, style: TextStyle(color: color.withOpacity(0.8), fontSize: 11)),
        ],
      ),
    );
  }

  Widget _statusChip(String status, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color),
      ),
      child: Text(status, style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
    );
  }

  Color _statusColor(String status) => switch (status.toLowerCase()) {
    'open'       => Colors.red,
    'close'      => Colors.grey,
    'solve'      => Colors.blue,
    'pending'    => Colors.orange,
    'inprogress' => const Color(0xFF1565C0),
    _            => Colors.purple,
  };
}
