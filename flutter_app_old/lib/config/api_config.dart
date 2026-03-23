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
}
