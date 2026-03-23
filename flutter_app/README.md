# Aplikasi Absensi Karyawan — Flutter

Aplikasi mobile untuk absensi karyawan dengan validasi GPS dan foto selfie.

## Fitur

- Login dengan email & password (Sanctum token)
- **Absen Masuk / Pulang**: validasi GPS → foto selfie → kirim
- **Riwayat** absensi bulanan dengan ringkasan
- **Jadwal** shift bulanan dengan kalender interaktif

## Struktur Project

```
lib/
  main.dart
  config/
    api_config.dart          ← Ganti BASE URL di sini
  models/
    user_model.dart
    attendance_model.dart
    shift_model.dart
    location_model.dart
  services/
    auth_service.dart        ← Token storage (secure)
    api_service.dart         ← Semua HTTP request
  screens/
    splash_screen.dart
    login_screen.dart
    main_screen.dart         ← Bottom navigation
    home_screen.dart         ← Dashboard + tombol absen
    clock_screen.dart        ← GPS + selfie + submit
    history_screen.dart      ← Riwayat bulanan
    schedule_screen.dart     ← Kalender jadwal shift
```

## Setup

### 1. Install Flutter

```bash
# Download Flutter SDK dari https://flutter.dev/docs/get-started/install
flutter --version  # Minimal 3.x
```

### 2. Clone & Install Dependencies

```bash
cd flutter_app
flutter pub get
```

### 3. Ganti URL Server

Edit `lib/config/api_config.dart`:

```dart
static const String baseUrl = 'https://DOMAIN_ANDA/api/employee';
```

### 4. Jalankan

```bash
# Android (hubungkan HP via USB, aktifkan Developer Mode + USB Debugging)
flutter run

# Build APK release
flutter build apk --release
# APK ada di: build/app/outputs/flutter-apk/app-release.apk
```

### 5. iOS (butuh Mac + Xcode)

```bash
flutter build ios --release
```

## Catatan Android

File `android/app/src/main/AndroidManifest.xml` sudah include permission:
- `ACCESS_FINE_LOCATION` — GPS
- `CAMERA` — selfie
- `INTERNET`

## API Endpoints

| Method | URL | Deskripsi |
|--------|-----|-----------|
| POST | `/api/employee/login` | Login karyawan |
| POST | `/api/employee/logout` | Logout |
| GET  | `/api/employee/profile` | Data profil |
| GET  | `/api/employee/shift/today` | Shift hari ini |
| GET  | `/api/employee/attendance/today` | Absensi hari ini |
| POST | `/api/employee/attendance/clock-in` | Absen masuk |
| POST | `/api/employee/attendance/clock-out` | Absen pulang |
| GET  | `/api/employee/attendance/history?month=2026-02` | Riwayat |
| GET  | `/api/employee/schedule?month=2026-02` | Jadwal shift |
| POST | `/api/employee/location/check` | Validasi GPS |

### Contoh Login Request

```json
POST /api/employee/login
{
  "email": "karyawan@perusahaan.com",
  "password": "password123",
  "device_name": "flutter_app"
}
```

### Contoh Clock-In (multipart/form-data)

```
POST /api/employee/attendance/clock-in
Authorization: Bearer {token}

latitude:   -8.612345
longitude:  115.097234
photo:      [file: selfie.jpg]
device_info: Samsung Galaxy A54
```
