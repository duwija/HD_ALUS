class TicketModel {
  final int id;
  final String tittle;
  final String status;
  final String? calledBy;
  final String? phone;
  final String? date;
  final String? time;
  final String? category;
  final String? assignTo;
  final int? assignToId;
  final int updatesCount;
  final String? createdAt;

  // For detail page
  final String? description;
  final String? member;
  final String? customerName;
  final String? customerPhone;
  final List<TicketUpdate> updates;
  final List<TicketStep> steps;

  TicketModel({
    required this.id,
    required this.tittle,
    required this.status,
    this.calledBy,
    this.phone,
    this.date,
    this.time,
    this.category,
    this.assignTo,
    this.assignToId,
    this.updatesCount = 0,
    this.createdAt,
    this.description,
    this.member,
    this.customerName,
    this.customerPhone,
    this.updates = const [],
    this.steps = const [],
  });

  factory TicketModel.fromJson(Map<String, dynamic> j) => TicketModel(
    id:           j['id'],
    tittle:       j['tittle'] ?? '',
    status:       j['status'] ?? 'Open',
    calledBy:     j['called_by'],
    phone:        j['phone'],
    date:         j['date'],
    time:         j['time'],
    category:     j['category'],
    assignTo:     j['assign_to'],
    assignToId:   j['assign_to_id'],
    updatesCount: j['updates_count'] ?? 0,
    createdAt:    j['created_at'],
    description:  j['description'],
    member:       j['member'],
    customerName: j['customer']?['name'],
    customerPhone:j['customer']?['phone'],
    updates: (j['updates'] as List? ?? [])
        .map((e) => TicketUpdate.fromJson(e)).toList(),
    steps: (j['steps'] as List? ?? [])
        .map((e) => TicketStep.fromJson(e)).toList(),
  );

  String get statusLabel => status;

  Map<String, dynamic> get statusStyle {
    switch (status) {
      case 'Open':       return {'label': 'Open',       'color': 0xFF2196F3};
      case 'Inprogress': return {'label': 'Inprogress', 'color': 0xFFFF9800};
      case 'Solve':      return {'label': 'Solve',      'color': 0xFF4CAF50};
      case 'Close':      return {'label': 'Closed',     'color': 0xFF9E9E9E};
      default:           return {'label': status,       'color': 0xFF9E9E9E};
    }
  }
}

class TicketUpdate {
  final int id;
  final String description;
  final String? updatedBy;
  final String? createdAt;

  TicketUpdate({required this.id, required this.description, this.updatedBy, this.createdAt});

  factory TicketUpdate.fromJson(Map<String, dynamic> j) => TicketUpdate(
    id:          j['id'],
    description: j['description'] ?? '',
    updatedBy:   j['updated_by'],
    createdAt:   j['created_at'],
  );
}

class TicketStep {
  final int id;
  final String name;
  final int position;
  final bool isCurrent;

  TicketStep({required this.id, required this.name, required this.position, this.isCurrent = false});

  factory TicketStep.fromJson(Map<String, dynamic> j) => TicketStep(
    id:        j['id'],
    name:      j['name'] ?? '',
    position:  j['position'] ?? 0,
    isCurrent: j['is_current'] == true,
  );
}
