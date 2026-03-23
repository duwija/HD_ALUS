import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:safe_device/safe_device.dart';
import '../services/api_service.dart';
import '../models/location_model.dart';

class ClockScreen extends StatefulWidget {
  final bool isClockIn;
  const ClockScreen({super.key, required this.isClockIn});

  @override
  State<ClockScreen> createState() => _ClockScreenState();
}

enum _Step { idle, checkingGps, gpsOk, gpsFail, takingPhoto, confirming, submitting, done }

class _ClockScreenState extends State<ClockScreen> {
  _Step _step = _Step.idle;

  Position? _position;
  LocationCheckResult? _locationResult;
  File? _photo;
  String? _errorMsg;
  bool _isMockDetected = false;
  // GPS metadata dikirim ke server
  double _gpsAccuracy = 0;
  double _gpsAltitude = 0;
  double _gpsSpeed    = 0;

  @override
  void initState() {
    super.initState();
    // Mulai proses GPS otomatis
    WidgetsBinding.instance.addPostFrameCallback((_) => _checkGps());
  }

  // ─── Step 0: Cek keamanan perangkat ──────────────────────────────────────
  Future<bool> _checkDeviceSafety() async {
    try {
      final isMock         = await SafeDevice.isMockLocation;
      final isReal         = await SafeDevice.isRealDevice;
      final allowMock      = await SafeDevice.isAllowMockLocation;
      final isRooted       = await SafeDevice.isJailBroken; // root/jailbreak

      if (isMock) {
        _isMockDetected = true;
        setState(() {
          _step = _Step.gpsFail;
          _errorMsg = 'Absensi ditolak: Fake GPS / Mock Location terdeteksi aktif.\n\nMatikan semua aplikasi GPS palsu lalu coba lagi.';
        });
        return false;
      }
      if (!isReal) {
        _isMockDetected = true;
        setState(() {
          _step = _Step.gpsFail;
          _errorMsg = 'Absensi ditolak: Perangkat emulator atau simulasi terdeteksi.\n\nGunakan perangkat fisik untuk absensi.';
        });
        return false;
      }
      if (allowMock) {
        _isMockDetected = true;
        setState(() {
          _step = _Step.gpsFail;
          _errorMsg = 'Absensi ditolak: Opsi "Izinkan lokasi palsu" aktif di pengaturan developer.\n\nNonaktifkan opsi tersebut lalu coba lagi.';
        });
        return false;
      }
      if (isRooted) {
        _isMockDetected = true;
        setState(() {
          _step = _Step.gpsFail;
          _errorMsg = 'Absensi ditolak: Perangkat terdeteksi telah di-root/jailbreak.\n\nAbsensi hanya diizinkan dari perangkat yang tidak di-root.';
        });
        return false;
      }
      return true;
    } catch (_) {
      return true;
    }
  }

  // ─── Step 1: GPS ────────────────────────────────────────────────────────
  Future<void> _checkGps() async {
    setState(() { _step = _Step.checkingGps; _errorMsg = null; });

    // Cek keamanan perangkat terlebih dahulu
    final safe = await _checkDeviceSafety();
    if (!safe) return;

    try {
      // Cek permission
      LocationPermission perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm == LocationPermission.deniedForever || perm == LocationPermission.denied) {
        setState(() { _step = _Step.gpsFail; _errorMsg = 'Izin lokasi ditolak. Aktifkan di pengaturan.'; });
        return;
      }

      // Cek layanan GPS
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        setState(() { _step = _Step.gpsFail; _errorMsg = 'GPS tidak aktif. Aktifkan GPS terlebih dahulu.'; });
        return;
      }

      // Ambil posisi sample pertama
      final pos1 = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 15),
      );

      // Cek flag isMocked dari Geolocator (Android: isFromMockProvider)
      if (pos1.isMocked) {
        _isMockDetected = true;
        setState(() {
          _step = _Step.gpsFail;
          _errorMsg = 'Absensi ditolak: Lokasi terdeteksi sebagai GPS palsu oleh sistem.\n\nMatikan aplikasi fake GPS lalu coba lagi.';
        });
        return;
      }

      // Kumpulkan 3 sample via stream selama ~3 detik untuk deteksi drift alami
      final List<Position> samples = [pos1];
      try {
        final stream = Geolocator.getPositionStream(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.high,
            distanceFilter: 0,
          ),
        ).timeout(const Duration(seconds: 4));
        await for (final p in stream) {
          if (p.isMocked) {
            _isMockDetected = true;
            setState(() {
              _step = _Step.gpsFail;
              _errorMsg = 'Absensi ditolak: Stream GPS palsu terdeteksi oleh sistem.';
            });
            return;
          }
          samples.add(p);
          if (samples.length >= 3) break;
        }
      } catch (_) { /* timeout ok */ }

      final pos2 = samples.last;

      // Deteksi posisi statis sempurna — GPS asli selalu sedikit bergerak
      if (samples.length >= 2) {
        final latDiffs = <double>[];
        final lngDiffs = <double>[];
        for (int i = 1; i < samples.length; i++) {
          latDiffs.add((samples[i].latitude  - samples[i-1].latitude).abs());
          lngDiffs.add((samples[i].longitude - samples[i-1].longitude).abs());
        }
        final totalDrift = latDiffs.fold(0.0, (a, b) => a + b)
                         + lngDiffs.fold(0.0, (a, b) => a + b);
        // GPS asli di area terbuka biasanya drift > 0.000005 antar sample
        if (totalDrift < 0.0000005 && pos1.accuracy < 5) {
          _isMockDetected = true;
          setState(() {
            _step = _Step.gpsFail;
            _errorMsg = 'Absensi ditolak: Koordinat GPS terlalu statis (tidak wajar untuk GPS asli).\n\nPastikan tidak ada aplikasi GPS palsu yang aktif.';
          });
          return;
        }
      }

      // Simpan metadata untuk dikirim ke server
      _gpsAccuracy = pos2.accuracy;
      _gpsAltitude = pos2.altitude;
      _gpsSpeed    = pos2.speed < 0 ? 0 : pos2.speed;

      final pos = pos2;

      // Validasi ke server
      final result = await ApiService.checkLocation(pos.latitude, pos.longitude);

      setState(() {
        _position       = pos;
        _locationResult = result;
        _step = result.valid ? _Step.gpsOk : _Step.gpsFail;
        if (!result.valid) _errorMsg = result.message ?? 'Anda berada di luar area absensi.';
      });
    } catch (e) {
      setState(() {
        _step     = _Step.gpsFail;
        _errorMsg = 'Gagal mendapatkan lokasi: ${e.toString()}';
      });
    }
  }

  // ─── Step 2: Foto selfie ────────────────────────────────────────────────
  Future<void> _takePhoto() async {
    setState(() => _step = _Step.takingPhoto);
    try {
      final picker = ImagePicker();
      final xfile  = await picker.pickImage(
        source: ImageSource.camera,
        preferredCameraDevice: CameraDevice.front,
        imageQuality: 70,
        maxWidth: 800,
      );
      if (xfile == null) {
        setState(() => _step = _Step.gpsOk);
        return;
      }
      setState(() { _photo = File(xfile.path); _step = _Step.confirming; });
    } catch (e) {
      setState(() { _step = _Step.gpsOk; _errorMsg = 'Gagal membuka kamera.'; });
    }
  }

  // ─── Step 3: Submit ─────────────────────────────────────────────────────
  Future<void> _submit() async {
    if (_photo == null || _position == null) return;
    setState(() { _step = _Step.submitting; });

    try {
      final Map<String, dynamic> res;
      if (widget.isClockIn) {
        res = await ApiService.clockIn(
          lat: _position!.latitude,
          lng: _position!.longitude,
          photo: _photo!,
          isMock: _isMockDetected,
          accuracy: _gpsAccuracy,
          altitude: _gpsAltitude,
          speed:    _gpsSpeed,
        );
      } else {
        res = await ApiService.clockOut(
          lat: _position!.latitude,
          lng: _position!.longitude,
          photo: _photo!,
          isMock: _isMockDetected,
          accuracy: _gpsAccuracy,
          altitude: _gpsAltitude,
          speed:    _gpsSpeed,
        );
      }

      if (res['success'] == true) {
        setState(() => _step = _Step.done);
        await Future.delayed(const Duration(seconds: 2));
        if (mounted) Navigator.pop(context, true);
      } else {
        setState(() {
          _step     = _Step.confirming;
          _errorMsg = res['message'] ?? 'Gagal menyimpan absensi.';
        });
      }
    } catch (e) {
      setState(() {
        _step     = _Step.confirming;
        _errorMsg = 'Error: ${e.toString()}';
      });
    }
  }

  // ─── UI ─────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final isIn    = widget.isClockIn;
    final color   = isIn ? Colors.green : Colors.orange;
    final title   = isIn ? 'Absen Masuk' : 'Absen Pulang';

    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        backgroundColor: color,
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Progress steps
            _buildStepIndicator(),
            const SizedBox(height: 32),
            Expanded(child: _buildBody(color)),
          ],
        ),
      ),
    );
  }

  Widget _buildStepIndicator() {
    final steps = ['GPS', 'Foto', 'Kirim'];
    int active = 0;
    if (_step.index >= _Step.gpsOk.index)    active = 1;
    if (_step.index >= _Step.confirming.index) active = 2;
    if (_step == _Step.done)                  active = 3;

    return Row(
      children: List.generate(steps.length * 2 - 1, (i) {
        if (i.isOdd) {
          return Expanded(child: Container(height: 2, color: i ~/ 2 < active ? Colors.green : Colors.grey[300]));
        }
        final idx = i ~/ 2;
        final done = idx < active;
        return CircleAvatar(
          radius: 18,
          backgroundColor: done ? Colors.green : (idx == active ? Colors.blue : Colors.grey[300]),
          child: done
              ? const Icon(Icons.check, color: Colors.white, size: 16)
              : Text('${idx+1}', style: TextStyle(color: done || idx == active ? Colors.white : Colors.grey)),
        );
      }),
    );
  }

  Widget _buildBody(Color color) {
    switch (_step) {
      case _Step.idle:
      case _Step.checkingGps:
        return _centered(
          icon: Icons.gps_fixed,
          iconColor: Colors.blue,
          title: 'Mendapatkan Lokasi...',
          subtitle: 'Memvalidasi posisi Anda',
          showLoading: true,
        );

      case _Step.gpsFail:
        return _centered(
          icon: Icons.location_off,
          iconColor: Colors.red,
          title: 'Lokasi Tidak Valid',
          subtitle: _errorMsg ?? 'Anda di luar area absensi',
          showLoading: false,
          action: TextButton.icon(
            onPressed: _checkGps,
            icon: const Icon(Icons.refresh),
            label: const Text('Coba Lagi'),
          ),
        );

      case _Step.gpsOk:
        return _centered(
          icon: Icons.location_on,
          iconColor: Colors.green,
          title: 'Lokasi Terverifikasi',
          subtitle: '📍 ${_locationResult?.locationName ?? "-"}\n'
              '📏 Jarak: ${_locationResult?.distance ?? "-"} meter',
          showLoading: false,
          errorMsg: _errorMsg,
          action: ElevatedButton.icon(
            onPressed: _takePhoto,
            icon: const Icon(Icons.camera_alt),
            label: const Text('Ambil Foto Selfie'),
            style: ElevatedButton.styleFrom(backgroundColor: color),
          ),
        );

      case _Step.takingPhoto:
        return _centered(
          icon: Icons.camera_alt,
          iconColor: Colors.blue,
          title: 'Membuka Kamera...',
          subtitle: 'Ambil foto selfie Anda',
          showLoading: true,
        );

      case _Step.confirming:
        return Column(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Image.file(_photo!, width: double.infinity, height: 280, fit: BoxFit.cover),
            ),
            const SizedBox(height: 16),
            if (_locationResult != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    children: [
                      _row(Icons.location_on, 'Lokasi', _locationResult!.locationName ?? '-', Colors.green),
                      _row(Icons.social_distance, 'Jarak',  '${_locationResult!.distance ?? "-"} meter', Colors.blue),
                    ],
                  ),
                ),
              ),
            if (_errorMsg != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(_errorMsg!, style: const TextStyle(color: Colors.red, fontSize: 13)),
              ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => setState(() { _photo = null; _step = _Step.gpsOk; _errorMsg = null; }),
                    icon: const Icon(Icons.refresh),
                    label: const Text('Ulang Foto'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _submit,
                    icon: const Icon(Icons.send),
                    label: const Text('Kirim Absensi'),
                    style: ElevatedButton.styleFrom(backgroundColor: color),
                  ),
                ),
              ],
            ),
          ],
        );

      case _Step.submitting:
        return _centered(
          icon: Icons.cloud_upload,
          iconColor: color,
          title: 'Mengirim Data...',
          subtitle: 'Menyimpan absensi Anda',
          showLoading: true,
        );

      case _Step.done:
        return _centered(
          icon: Icons.check_circle,
          iconColor: Colors.green,
          title: widget.isClockIn ? 'Absen Masuk Berhasil!' : 'Absen Pulang Berhasil!',
          subtitle: 'Data kehadiran tersimpan',
          showLoading: false,
        );
    }
  }

  Widget _centered({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required bool showLoading,
    String? errorMsg,
    Widget? action,
  }) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 80, color: iconColor),
          const SizedBox(height: 16),
          Text(title, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Text(subtitle, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey[600], fontSize: 14)),
          if (errorMsg != null) ...[
            const SizedBox(height: 8),
            Text(errorMsg, style: const TextStyle(color: Colors.red, fontSize: 13), textAlign: TextAlign.center),
          ],
          if (showLoading) ...[
            const SizedBox(height: 24),
            const CircularProgressIndicator(),
          ],
          if (action != null) ...[
            const SizedBox(height: 24),
            action,
          ],
        ],
      ),
    );
  }

  Widget _row(IconData icon, String label, String value, Color color) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 8),
        Text('$label: ', style: const TextStyle(fontSize: 13, color: Colors.black54)),
        Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
      ]),
    );
  }
}
