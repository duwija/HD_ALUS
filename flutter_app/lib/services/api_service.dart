import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';
import '../services/auth_service.dart';
import '../models/user_model.dart';
import '../models/attendance_model.dart';
import '../models/shift_model.dart';
import '../models/location_model.dart';
import '../models/ticket_model.dart';

class ApiService {
  // ── Headers ─────────────────────────────────────────────────────────────
  static Future<Map<String, String>> _headers() async {
    final token = await AuthService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // ── Auth ─────────────────────────────────────────────────────────────────
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await http.post(
      Uri.parse(ApiConfig.login),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({'email': email, 'password': password, 'device_name': 'flutter_app'}),
    );
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  static Future<void> logout() async {
    final h = await _headers();
    await http.post(Uri.parse(ApiConfig.logout), headers: h);
  }

  // ── Profile ──────────────────────────────────────────────────────────────
  static Future<UserModel> getProfile() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.profile), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return UserModel.fromJson(body['data']);
    throw Exception(body['message'] ?? 'Gagal memuat profil');
  }

  // ── Shift ─────────────────────────────────────────────────────────────────
  static Future<ShiftModel?> getTodayShift() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.shiftToday), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) {
      if (body['shift'] == null) return null;
      return ShiftModel.fromJson(body['shift']);
    }
    throw Exception(body['message'] ?? 'Gagal memuat shift');
  }

  // ── Attendance Today ──────────────────────────────────────────────────────
  static Future<AttendanceModel?> getTodayAttendance() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.attendanceToday), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) {
      if (body['data'] == null) return null;
      return AttendanceModel.fromJson(body['data']);
    }
    throw Exception(body['message'] ?? 'Gagal memuat absensi');
  }

  // ── Locations ─────────────────────────────────────────────────────────────
  static Future<List<AttendanceLocation>> getLocations() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.locations), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) {
      return (body['data'] as List).map((e) => AttendanceLocation.fromJson(e)).toList();
    }
    throw Exception(body['message'] ?? 'Gagal memuat lokasi');
  }

  // ── Check location ────────────────────────────────────────────────────────
  static Future<LocationCheckResult> checkLocation(double lat, double lng) async {
    final h   = await _headers();
    final res = await http.post(
      Uri.parse(ApiConfig.locationCheck),
      headers: h,
      body: jsonEncode({'latitude': lat, 'longitude': lng}),
    );
    return LocationCheckResult.fromJson(jsonDecode(res.body));
  }

  // ── Clock In ──────────────────────────────────────────────────────────────
  static Future<Map<String, dynamic>> clockIn({
    required double lat,
    required double lng,
    required File photo,
    String? deviceInfo,
    bool isMock = false,
    double accuracy = 0,
    double altitude = 0,
    double speed    = 0,
  }) async {
    final token = await AuthService.getToken();
    final req = http.MultipartRequest('POST', Uri.parse(ApiConfig.clockIn));
    req.headers['Authorization'] = 'Bearer $token';
    req.headers['Accept']        = 'application/json';
    req.fields['latitude']     = lat.toString();
    req.fields['longitude']    = lng.toString();
    req.fields['is_mock']      = isMock ? '1' : '0';
    req.fields['gps_accuracy'] = accuracy.toStringAsFixed(4);
    req.fields['gps_altitude'] = altitude.toStringAsFixed(4);
    req.fields['gps_speed']    = speed.toStringAsFixed(4);
    if (deviceInfo != null) req.fields['device_info'] = deviceInfo;
    req.files.add(await http.MultipartFile.fromPath('photo', photo.path));

    final streamed = await req.send();
    final res = await http.Response.fromStream(streamed);
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  // ── Clock Out ─────────────────────────────────────────────────────────────
  static Future<Map<String, dynamic>> clockOut({
    required double lat,
    required double lng,
    required File photo,
    bool isMock = false,
    double accuracy = 0,
    double altitude = 0,
    double speed    = 0,
  }) async {
    final token = await AuthService.getToken();
    final req = http.MultipartRequest('POST', Uri.parse(ApiConfig.clockOut));
    req.headers['Authorization'] = 'Bearer $token';
    req.headers['Accept']        = 'application/json';
    req.fields['latitude']     = lat.toString();
    req.fields['longitude']    = lng.toString();
    req.fields['is_mock']      = isMock ? '1' : '0';
    req.fields['gps_accuracy'] = accuracy.toStringAsFixed(4);
    req.fields['gps_altitude'] = altitude.toStringAsFixed(4);
    req.fields['gps_speed']    = speed.toStringAsFixed(4);
    req.files.add(await http.MultipartFile.fromPath('photo', photo.path));

    final streamed = await req.send();
    final res = await http.Response.fromStream(streamed);
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  // ── History ───────────────────────────────────────────────────────────────
  static Future<Map<String, dynamic>> getHistory(String month) async {
    final h   = await _headers();
    final uri = Uri.parse('${ApiConfig.history}?month=$month');
    final res = await http.get(uri, headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body;
    throw Exception(body['message'] ?? 'Gagal memuat riwayat');
  }

  // ── Schedule ──────────────────────────────────────────────────────────────
  static Future<List<dynamic>> getSchedule(String month) async {
    final h   = await _headers();
    final uri = Uri.parse('${ApiConfig.schedule}?month=$month');
    final res = await http.get(uri, headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body['data'] as List? ?? [];
    throw Exception(body['message'] ?? 'Gagal memuat jadwal');
  }

  // ── Tickets ───────────────────────────────────────────────────────────────
  static Future<Map<String, dynamic>> getTickets({
    bool mine = false,
    String? status,
    String? search,
    int page = 1,
  }) async {
    final h   = await _headers();
    final params = {'page': '$page', if (mine) 'mine': '1', if (status != null) 'status': status, if (search != null) 'search': search};
    final uri = Uri.parse(ApiConfig.tickets).replace(queryParameters: params);
    final res = await http.get(uri, headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body;
    throw Exception(body['message'] ?? 'Gagal memuat tiket');
  }

  static Future<Map<String, dynamic>> getTicketSummary() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.ticketSummary), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body['data'];
    throw Exception(body['message'] ?? 'Gagal memuat summary');
  }

  static Future<TicketModel> getTicketDetail(int id) async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.ticketDetail(id)), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return TicketModel.fromJson(body['data']);
    throw Exception(body['message'] ?? 'Gagal memuat detail tiket');
  }

  static Future<Map<String, dynamic>> addTicketUpdate(int id, String description) async {
    final h   = await _headers();
    final res = await http.post(
      Uri.parse(ApiConfig.ticketAddUpdate(id)),
      headers: h,
      body: jsonEncode({'description': description}),
    );
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> updateTicketStatus(int id, String status) async {
    final h   = await _headers();
    final res = await http.patch(
      Uri.parse(ApiConfig.ticketUpdateStatus(id)),
      headers: h,
      body: jsonEncode({'status': status}),
    );
    return jsonDecode(res.body);
  }

  // ── Leaves ────────────────────────────────────────────────────────────────
  static Future<List<dynamic>> getLeaves() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.leaves), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body['data'] as List? ?? [];
    throw Exception(body['message'] ?? 'Gagal memuat izin');
  }

  static Future<Map<String, dynamic>> submitLeave({
    required String type,
    required String startDate,
    required String endDate,
    required String reason,
    File? attachment,
  }) async {
    final token = await AuthService.getToken();
    final req   = http.MultipartRequest('POST', Uri.parse(ApiConfig.leaves));
    req.headers['Authorization'] = 'Bearer $token';
    req.headers['Accept']        = 'application/json';
    req.fields['type']       = type;
    req.fields['start_date'] = startDate;
    req.fields['end_date']   = endDate;
    req.fields['reason']     = reason;
    if (attachment != null) {
      req.files.add(await http.MultipartFile.fromPath('attachment', attachment.path));
    }
    final streamed = await req.send();
    final res = await http.Response.fromStream(streamed);
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  // ── Overtimes ─────────────────────────────────────────────────────────────
  static Future<List<dynamic>> getOvertimes() async {
    final h   = await _headers();
    final res = await http.get(Uri.parse(ApiConfig.overtimes), headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body['data'] as List? ?? [];
    throw Exception(body['message'] ?? 'Gagal memuat lembur');
  }

  static Future<Map<String, dynamic>> submitOvertime({
    required String date,
    required String startTime,
    required String endTime,
    required String reason,
  }) async {
    final h   = await _headers();
    final res = await http.post(
      Uri.parse(ApiConfig.overtimes),
      headers: h,
      body: jsonEncode({'date': date, 'start_time': startTime, 'end_time': endTime, 'reason': reason}),
    );
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  // ── Supervisor ────────────────────────────────────────────────────────────
  static Future<List<dynamic>> getSupervisorLeaves({String? status}) async {
    final h   = await _headers();
    final uri = Uri.parse(ApiConfig.supervisorLeaves)
        .replace(queryParameters: status != null ? {'status': status} : null);
    final res = await http.get(uri, headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body['data'] as List? ?? [];
    throw Exception(body['message'] ?? 'Gagal');
  }

  static Future<Map<String, dynamic>> approveLeave(int id, String status, {String? notes}) async {
    final h   = await _headers();
    final res = await http.post(
      Uri.parse(ApiConfig.leaveApprove(id)),
      headers: h,
      body: jsonEncode({'status': status, 'notes': notes}),
    );
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  static Future<List<dynamic>> getSupervisorOvertimes({String? status}) async {
    final h   = await _headers();
    final uri = Uri.parse(ApiConfig.supervisorOvertimes)
        .replace(queryParameters: status != null ? {'status': status} : null);
    final res = await http.get(uri, headers: h);
    final body = jsonDecode(res.body);
    if (res.statusCode == 200) return body['data'] as List? ?? [];
    throw Exception(body['message'] ?? 'Gagal');
  }

  static Future<Map<String, dynamic>> approveOvertime(int id, String status, {String? notes}) async {
    final h   = await _headers();
    final res = await http.post(
      Uri.parse(ApiConfig.overtimeApprove(id)),
      headers: h,
      body: jsonEncode({'status': status, 'notes': notes}),
    );
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  // ── FCM Token ────────────────────────────────────────────────────────────
  static Future<void> saveFcmToken(String token) async {
    try {
      final h   = await _headers();
      await http.post(
        Uri.parse(ApiConfig.fcmToken),
        headers: h,
        body: jsonEncode({'fcm_token': token}),
      );
    } catch (_) {}
  }
}
