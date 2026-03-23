class ApiConfig {
  // Ganti dengan domain / IP server Anda
  static const String baseUrl = 'https://kencana.alus.co.id/api/employee';

  static const String login        = '$baseUrl/login';
  static const String logout       = '$baseUrl/logout';
  static const String profile      = '$baseUrl/profile';
  static const String locations    = '$baseUrl/locations';
  static const String locationCheck= '$baseUrl/location/check';
  static const String shiftToday   = '$baseUrl/shift/today';
  static const String schedule     = '$baseUrl/schedule';
  static const String attendanceToday = '$baseUrl/attendance/today';
  static const String clockIn      = '$baseUrl/attendance/clock-in';
  static const String clockOut     = '$baseUrl/attendance/clock-out';
  static const String history      = '$baseUrl/attendance/history';

  // Tiket
  static const String tickets        = '$baseUrl/tickets';
  static const String ticketSummary  = '$baseUrl/tickets/summary';
  static String ticketDetail(int id) => '$baseUrl/tickets/$id';
  static String ticketAddUpdate(int id) => '$baseUrl/tickets/$id/update';
  static String ticketUpdateStatus(int id) => '$baseUrl/tickets/$id/status';

  // URL web tiket (buka di browser)
  static const String ticketWebBase = 'https://kencana.alus.co.id/ticket';
  static String ticketWebView(int id) => 'https://kencana.alus.co.id/ticket/$id';

  // Izin / Cuti
  static const String leaves           = '$baseUrl/leaves';
  static String leaveDetail(int id)    => '$baseUrl/leaves/$id';
  static String leaveApprove(int id)   => '$baseUrl/leaves/$id/approve';

  // Lembur
  static const String overtimes          = '$baseUrl/overtimes';
  static String overtimeDetail(int id)   => '$baseUrl/overtimes/$id';
  static String overtimeApprove(int id)  => '$baseUrl/overtimes/$id/approve';

  // Supervisor
  static const String supervisorLeaves   = '$baseUrl/supervisor/leaves';
  static const String supervisorOvertimes= '$baseUrl/supervisor/overtimes';

  // FCM Token
  static const String fcmToken = '$baseUrl/fcm-token';
}
