class ShiftModel {
  final int id;
  final String name;
  final String startTime;
  final String endTime;
  final int lateTolerance;
  final String color;

  ShiftModel({
    required this.id,
    required this.name,
    required this.startTime,
    required this.endTime,
    required this.lateTolerance,
    required this.color,
  });

  factory ShiftModel.fromJson(Map<String, dynamic> j) => ShiftModel(
    id:            j['id'],
    name:          j['name'] ?? '',
    startTime:     j['start_time'] ?? '',
    endTime:       j['end_time'] ?? '',
    lateTolerance: j['late_tolerance'] ?? 0,
    color:         j['color'] ?? '#1565C0',
  );
}
