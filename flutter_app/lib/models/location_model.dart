class AttendanceLocation {
  final int id;
  final String name;
  final double latitude;
  final double longitude;
  final int radius;
  final String? address;

  AttendanceLocation({
    required this.id,
    required this.name,
    required this.latitude,
    required this.longitude,
    required this.radius,
    this.address,
  });

  factory AttendanceLocation.fromJson(Map<String, dynamic> j) => AttendanceLocation(
    id:        j['id'],
    name:      j['name'] ?? '',
    latitude:  double.tryParse(j['latitude'].toString()) ?? 0,
    longitude: double.tryParse(j['longitude'].toString()) ?? 0,
    radius:    j['radius'] ?? 100,
    address:   j['address'],
  );
}

class LocationCheckResult {
  final bool valid;
  final String? locationName;
  final int? distance;
  final int? radius;
  final String? message;

  LocationCheckResult({
    required this.valid,
    this.locationName,
    this.distance,
    this.radius,
    this.message,
  });

  factory LocationCheckResult.fromJson(Map<String, dynamic> j) => LocationCheckResult(
    valid:        j['valid'] == true,
    locationName: j['location']?['name'],
    // API returns distance as 'X meter' string inside location object
    distance:     j['location']?['distance'] != null
        ? int.tryParse(j['location']['distance'].toString().replaceAll(RegExp(r'[^0-9]'), ''))
        : null,
    radius:       j['location']?['radius'],
    message:      j['message'],
  );
}
