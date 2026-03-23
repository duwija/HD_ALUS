import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:url_launcher/url_launcher.dart';
import 'screens/splash_screen.dart';
import 'services/api_service.dart';
import 'services/auth_service.dart';
import 'services/notification_service.dart';

// ── Flutter Local Notifications ────────────────────────────────────────────
final FlutterLocalNotificationsPlugin _localNotif = FlutterLocalNotificationsPlugin();

const _channel = AndroidNotificationChannel(
  'kencana_channel',
  'Kencana Notifikasi',
  description: 'Notifikasi pengajuan & tiket Kencana',
  importance: Importance.high,
);

// Background message handler (harus top-level function)
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  // Simpan ke history
  final notif = message.notification;
  if (notif != null) {
    await NotificationService.save(
      title: notif.title ?? '',
      body: notif.body ?? '',
      url: message.data['url']?.toString(),
    );
  }
  _showLocalNotif(message);
}

/// Buka URL tiket di browser
Future<void> _openUrl(String? url) async {
  if (url == null || url.isEmpty) return;
  final uri = Uri.tryParse(url);
  if (uri != null && await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

/// Ambil URL dari data payload FCM
String? _extractUrl(RemoteMessage message) {
  return message.data['url']?.toString();
}

void _showLocalNotif(RemoteMessage message) {
  final notif = message.notification;
  if (notif == null) return;

  // Simpan URL di payload notifikasi lokal agar bisa dibuka saat tap
  final url = message.data['url']?.toString() ?? '';

  _localNotif.show(
    notif.hashCode,
    notif.title,
    notif.body,
    NotificationDetails(
      android: AndroidNotificationDetails(
        _channel.id, _channel.name,
        channelDescription: _channel.description,
        importance: Importance.high,
        priority: Priority.high,
        icon: '@mipmap/ic_launcher',
      ),
    ),
    payload: url, // URL dikirimkan sebagai payload
  );
}

/// Simpan notifikasi ke history dan tampilkan local notif (foreground)
Future<void> _saveAndShowNotif(RemoteMessage message) async {
  final notif = message.notification;
  if (notif != null) {
    await NotificationService.save(
      title: notif.title ?? '',
      body: notif.body ?? '',
      url: message.data['url']?.toString(),
    );
  }
  _showLocalNotif(message);
}

Future<void> _initNotifications() async {
  // Local notifications
  await _localNotif
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(_channel);

  await _localNotif.initialize(
    const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      iOS: DarwinInitializationSettings(),
    ),
    // Saat notifikasi lokal (foreground) di-tap → buka URL dari payload
    onDidReceiveNotificationResponse: (NotificationResponse response) {
      _openUrl(response.payload);
    },
  );

  // FCM foreground — simpan ke history + tampilkan sebagai local notif
  FirebaseMessaging.onMessage.listen((msg) => _saveAndShowNotif(msg));

  // FCM background — saat app di background dan notifikasi di-tap
  FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
    _openUrl(_extractUrl(message));
  });

  // Request permission
  final messaging = FirebaseMessaging.instance;
  await messaging.requestPermission(alert: true, badge: true, sound: true);

  // FCM terminated — saat app tertutup lalu notifikasi di-tap
  final initialMessage = await messaging.getInitialMessage();
  if (initialMessage != null) {
    // Tunda sebentar agar UI sudah siap
    Future.delayed(const Duration(seconds: 1), () {
      _openUrl(_extractUrl(initialMessage));
    });
  }

  // Simpan token ke server (jika sudah login)
  final token = await messaging.getToken();
  if (token != null) {
    final loggedIn = await AuthService.isLoggedIn();
    if (loggedIn) await ApiService.saveFcmToken(token);
  }

  // Refresh token
  messaging.onTokenRefresh.listen((newToken) async {
    final loggedIn = await AuthService.isLoggedIn();
    if (loggedIn) await ApiService.saveFcmToken(newToken);
  });
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id', null);
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);

  // Firebase init
  try {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
    await _initNotifications();
  } catch (_) {
    // Firebase belum dikonfigurasi — app tetap berjalan
  }

  runApp(const AttendanceApp());
}

class AttendanceApp extends StatelessWidget {
  const AttendanceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Absensi Karyawan',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF1565C0),
          brightness: Brightness.light,
        ),
        useMaterial3: true,
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF1565C0),
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF1565C0),
            foregroundColor: Colors.white,
            minimumSize: const Size(double.infinity, 50),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          filled: true,
          fillColor: Colors.grey[50],
        ),
        cardTheme: CardThemeData(
          elevation: 2,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
      ),
      home: const SplashScreen(),
    );
  }
}
