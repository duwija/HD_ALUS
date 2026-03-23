import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../models/ticket_model.dart';
import 'ticket_detail_screen.dart';

class TicketListScreen extends StatefulWidget {
  const TicketListScreen({super.key});

  @override
  State<TicketListScreen> createState() => _TicketListScreenState();
}

class _TicketListScreenState extends State<TicketListScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabs;

  // Tab 0 = Semua Tiket, Tab 1 = My Ticket
  final List<_TabState> _states = [_TabState(), _TabState()];

  Map<String, dynamic> _summary = {};

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
    _loadSummary();
    _states[0].load(mine: false);
    _states[1].load(mine: true);
  }

  Future<void> _loadSummary() async {
    try {
      final s = await ApiService.getTicketSummary();
      if (mounted) setState(() => _summary = s);
    } catch (_) {}
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tiket'),
        bottom: TabBar(
          controller: _tabs,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: [
            Tab(text: 'Semua  (${_summary['all_open'] ?? ''})', icon: const Icon(Icons.list_alt)),
            Tab(text: 'My Ticket  (${_summary['my_total'] ?? ''})', icon: const Icon(Icons.person)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabs,
        children: [
          _TicketTab(state: _states[0], mine: false),
          _TicketTab(state: _states[1], mine: true),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Per-tab state
// ─────────────────────────────────────────────────────────────────────────────
class _TabState extends ChangeNotifier {
  List<TicketModel> tickets = [];
  bool loading = false;
  bool hasMore = true;
  int  page    = 1;
  String? filterStatus;
  String? search;

  Future<void> load({required bool mine, bool reset = true}) async {
    if (loading) return;
    if (reset) { page = 1; tickets = []; hasMore = true; }
    if (!hasMore) return;

    loading = true;
    notifyListeners();

    try {
      final res = await ApiService.getTickets(
        mine: mine,
        status: filterStatus,
        search: search,
        page: page,
      );
      final data = (res['data'] as List? ?? []).map((e) => TicketModel.fromJson(e)).toList();
      final meta = res['meta'] as Map<String, dynamic>? ?? {};
      tickets  = reset ? data : [...tickets, ...data];
      hasMore  = (meta['current_page'] ?? 1) < (meta['last_page'] ?? 1);
      page     = (meta['current_page'] ?? 1) + 1;
    } catch (_) {}

    loading = false;
    notifyListeners();
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Isi satu tab
// ─────────────────────────────────────────────────────────────────────────────
class _TicketTab extends StatefulWidget {
  final _TabState state;
  final bool mine;
  const _TicketTab({required this.state, required this.mine});

  @override
  State<_TicketTab> createState() => _TicketTabState();
}

class _TicketTabState extends State<_TicketTab> {
  final _searchCtrl = TextEditingController();
  final _scroll     = ScrollController();

  @override
  void initState() {
    super.initState();
    widget.state.addListener(_rebuild);
    _scroll.addListener(_onScroll);
  }

  void _rebuild() { if (mounted) setState(() {}); }

  void _onScroll() {
    if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
      widget.state.load(mine: widget.mine, reset: false);
    }
  }

  @override
  void dispose() {
    widget.state.removeListener(_rebuild);
    _searchCtrl.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _applySearch(String v) {
    widget.state.search = v.isEmpty ? null : v;
    widget.state.load(mine: widget.mine);
  }

  void _applyFilter(String? status) {
    widget.state.filterStatus = status;
    widget.state.load(mine: widget.mine);
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;
    return Column(
      children: [
        // Search + filter bar
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 4),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchCtrl,
                  decoration: InputDecoration(
                    hintText: 'Cari tiket...',
                    prefixIcon: const Icon(Icons.search, size: 18),
                    contentPadding: const EdgeInsets.symmetric(vertical: 8),
                    isDense: true,
                    suffixIcon: _searchCtrl.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () { _searchCtrl.clear(); _applySearch(''); })
                        : null,
                  ),
                  onSubmitted: _applySearch,
                  textInputAction: TextInputAction.search,
                ),
              ),
              const SizedBox(width: 8),
              _FilterChipMenu(
                selected: state.filterStatus,
                onSelected: _applyFilter,
              ),
            ],
          ),
        ),

        // List
        Expanded(
          child: state.loading && state.tickets.isEmpty
              ? const Center(child: CircularProgressIndicator())
              : state.tickets.isEmpty
                  ? const Center(child: Text('Tidak ada tiket', style: TextStyle(color: Colors.grey)))
                  : RefreshIndicator(
                      onRefresh: () => state.load(mine: widget.mine),
                      child: ListView.builder(
                        controller: _scroll,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        itemCount: state.tickets.length + (state.hasMore ? 1 : 0),
                        itemBuilder: (_, i) {
                          if (i == state.tickets.length) {
                            return const Center(child: Padding(
                              padding: EdgeInsets.all(16),
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ));
                          }
                          return _TicketCard(
                            ticket: state.tickets[i],
                            onTap: () async {
                              await Navigator.push(context, MaterialPageRoute(
                                builder: (_) => TicketDetailScreen(ticketId: state.tickets[i].id),
                              ));
                              state.load(mine: widget.mine);
                            },
                          );
                        },
                      ),
                    ),
        ),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Card tiket
// ─────────────────────────────────────────────────────────────────────────────
class _TicketCard extends StatelessWidget {
  final TicketModel ticket;
  final VoidCallback onTap;
  const _TicketCard({required this.ticket, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final style = ticket.statusStyle;
    final color = Color(style['color'] as int);
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 4),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text('#${ticket.id} — ${ticket.tittle}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        maxLines: 2, overflow: TextOverflow.ellipsis),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: color),
                    ),
                    child: Text(style['label'] as String,
                        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Wrap(
                spacing: 12, runSpacing: 4,
                children: [
                  if (ticket.calledBy != null)
                    _info(Icons.person_outline, ticket.calledBy!),
                  if (ticket.category != null)
                    _info(Icons.category_outlined, ticket.category!),
                  if (ticket.assignTo != null)
                    _info(Icons.engineering_outlined, ticket.assignTo!),
                  if (ticket.date != null)
                    _info(Icons.calendar_today, '${ticket.date}${ticket.time != null ? ' ${ticket.time}' : ''}'),
                  _info(Icons.chat_bubble_outline, '${ticket.updatesCount} update'),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _info(IconData icon, String text) {
    return Row(mainAxisSize: MainAxisSize.min, children: [
      Icon(icon, size: 13, color: Colors.grey[600]),
      const SizedBox(width: 3),
      Text(text, style: TextStyle(fontSize: 12, color: Colors.grey[700])),
    ]);
  }
}

// ─── Filter menu ─────────────────────────────────────────────────────────────
class _FilterChipMenu extends StatelessWidget {
  final String? selected;
  final ValueChanged<String?> onSelected;
  const _FilterChipMenu({required this.selected, required this.onSelected});

  @override
  Widget build(BuildContext context) {
    const statuses = ['Open', 'Inprogress', 'Solve', 'Close'];
    return PopupMenuButton<String?>(
      icon: Badge(
        isLabelVisible: selected != null,
        child: const Icon(Icons.filter_list),
      ),
      onSelected: onSelected,
      itemBuilder: (_) => [
        const PopupMenuItem(value: null, child: Text('Semua Status')),
        ...statuses.map((s) => PopupMenuItem(
          value: s,
          child: Row(children: [
            if (selected == s) const Icon(Icons.check, size: 16) else const SizedBox(width: 16),
            const SizedBox(width: 8),
            Text(s),
          ]),
        )),
      ],
    );
  }
}
