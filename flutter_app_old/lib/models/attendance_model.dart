class AttendanceModel {
  final int id;
  final String date;
  final String? clockIn;
  final String? clockOut;
  final String status;
  final int? lateMinutes;
  final int? workMinutes;
  final String? photoIn;
  final String? photoOut;
  final String? shiftName;
  final String? locationName;
  final int? distanceIn;

  AttendanceModel({
    required this.id,
    required this.date,
    required this.status,
    this.clockIn,
    this.clockOut,
    this.lateMinutes,
    this.workMinutes,
    this.photoIn,
    this.photoOut,
    this.shiftName,
    this.locationName,
    this.distanceIn,
  });

  factory AttendanceModel.fromJson(Map<String, dynamic> j) => AttendanceModel(
    id:           j['id'],
    date:         j['date'] ?? '',
    status:       j['status'] ?? 'absent',
    clockIn:      j['clock_in'],
    clockOut:     j['clock_out'],
    lateMinutes:  j['late_minutes'],
    workMinutes:  j['work_minutes'],
    // API returns photo_in / photo_out (full URL)
    photoIn:      j['photo_in'],
    photoOut:     j['photo_out'],
    // API returns shift name as string (not object)
    shiftName:    j['shift'] is String ? j['shift'] : j['shift']?['name'],
    // API returns location_in name as string (not object)
    locationName: j['location_in'] is String ? j['location_in'] : j['location_in']?['name'],
    distanceIn:   j['distance_in'],
  );

  String get workHours {
    if (workMinutes == null || workMinutes == 0) return '-';
    final h = workMinutes! ~/ 60;
    final m = workMinutes! % 60;
    return '${h}j ${m}m';
  }

  String get statusLabel {
    switch (status) {
      case 'present': return 'Hadir';
      case 'late':    return 'Terlambat';
      case 'absent':  return 'Absen';
      case 'leave':   return 'Izin';
      case 'holiday': return 'Libur';
      default:        return status;
    }
  }
}
