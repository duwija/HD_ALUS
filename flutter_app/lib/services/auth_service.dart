import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  static const _storage = FlutterSecureStorage();
  static const _keyToken = 'employee_token';
  static const _keyName  = 'employee_name';
  static const _keyEmail = 'employee_email';

  static Future<void> saveToken(String token, {String? name, String? email}) async {
    await _storage.write(key: _keyToken, value: token);
    if (name  != null) await _storage.write(key: _keyName,  value: name);
    if (email != null) await _storage.write(key: _keyEmail, value: email);
  }

  static Future<String?> getToken() => _storage.read(key: _keyToken);
  static Future<String?> getName()  => _storage.read(key: _keyName);
  static Future<String?> getEmail() => _storage.read(key: _keyEmail);

  static Future<bool> isLoggedIn() async {
    final t = await getToken();
    return t != null && t.isNotEmpty;
  }

  static Future<void> logout() async {
    await _storage.deleteAll();
  }
}
