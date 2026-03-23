import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class NotifItem {
  final String id;
  final String title;
  final String body;
  final String? url;
  final DateTime receivedAt;
  bool isRead;

  NotifItem({
    required this.id,
    required this.title,
    required this.body,
    this.url,
    required this.receivedAt,
    this.isRead = false,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'body': body,
        'url': url,
        'receivedAt': receivedAt.toIso8601String(),
        'isRead': isRead,
      };

  factory NotifItem.fromJson(Map<String, dynamic> j) => NotifItem(
        id: j['id'],
        title: j['title'],
        body: j['body'],
        url: j['url'],
        receivedAt: DateTime.parse(j['receivedAt']),
        isRead: j['isRead'] ?? false,
      );
}

class NotificationService {
  static const _key = 'notif_history';
  static const _maxItems = 100;

  /// Jumlah notifikasi belum dibaca — subscribe dengan ValueListenableBuilder
  static final ValueNotifier<int> unreadCount = ValueNotifier(0);

  // ── Public API ───────────────────────────────────────────────────────────

  static Future<void> save({
    required String title,
    required String body,
    String? url,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final list = await _load(prefs);

    list.insert(
      0,
      NotifItem(
        id: DateTime.now().millisecondsSinceEpoch.toString(),
        title: title,
        body: body,
        url: url,
        receivedAt: DateTime.now(),
        isRead: false,
      ),
    );

    // Batasi jumlah item
    if (list.length > _maxItems) list.removeRange(_maxItems, list.length);

    await _save(prefs, list);
    _updateUnread(list);
  }

  static Future<List<NotifItem>> getAll() async {
    final prefs = await SharedPreferences.getInstance();
    final list = await _load(prefs);
    _updateUnread(list);
    return list;
  }

  static Future<void> markAllRead() async {
    final prefs = await SharedPreferences.getInstance();
    final list = await _load(prefs);
    for (final item in list) {
      item.isRead = true;
    }
    await _save(prefs, list);
    unreadCount.value = 0;
  }

  static Future<void> markRead(String id) async {
    final prefs = await SharedPreferences.getInstance();
    final list = await _load(prefs);
    for (final item in list) {
      if (item.id == id) item.isRead = true;
    }
    await _save(prefs, list);
    _updateUnread(list);
  }

  static Future<void> deleteAll() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
    unreadCount.value = 0;
  }

  static Future<void> refreshUnread() async {
    final prefs = await SharedPreferences.getInstance();
    final list = await _load(prefs);
    _updateUnread(list);
  }

  // ── Private helpers ──────────────────────────────────────────────────────

  static Future<List<NotifItem>> _load(SharedPreferences prefs) async {
    final raw = prefs.getString(_key);
    if (raw == null) return [];
    try {
      final List decoded = jsonDecode(raw);
      return decoded.map((e) => NotifItem.fromJson(e)).toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> _save(SharedPreferences prefs, List<NotifItem> list) async {
    await prefs.setString(_key, jsonEncode(list.map((e) => e.toJson()).toList()));
  }

  static void _updateUnread(List<NotifItem> list) {
    unreadCount.value = list.where((e) => !e.isRead).length;
  }
}
