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
      if (body['data'] == null) return null;
      return ShiftModel.fromJson(body['data']);
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
  }) async {
    final token = await AuthService.getToken();
    final req = http.MultipartRequest('POST', Uri.parse(ApiConfig.clockIn));
    req.headers['Authorization'] = 'Bearer $token';
    req.headers['Accept']        = 'application/json';
    req.fields['latitude']    = lat.toString();
    req.fields['longitude']   = lng.toString();
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
  }) async {
    final token = await AuthService.getToken();
    final req = http.MultipartRequest('POST', Uri.parse(ApiConfig.clockOut));
    req.headers['Authorization'] = 'Bearer $token';
    req.headers['Accept']        = 'application/json';
    req.fields['latitude']  = lat.toString();
    req.fields['longitude'] = lng.toString();
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
}
