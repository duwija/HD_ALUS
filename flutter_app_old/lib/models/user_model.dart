class UserModel {
  final int id;
  final String name;
  final String? fullName;
  final String email;
  final String? jobTitle;
  final String? employeeId;
  final String? photo;
  final String? employeeType;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.fullName,
    this.jobTitle,
    this.employeeId,
    this.photo,
    this.employeeType,
  });

  factory UserModel.fromJson(Map<String, dynamic> j) => UserModel(
    id:          j['id'],
    name:        j['name'] ?? '',
    email:       j['email'] ?? '',
    fullName:    j['full_name'],
    jobTitle:    j['job_title'],
    employeeId:  j['employee_id'],
    photo:       j['photo'],
    employeeType:j['employee_type'],
  );
}
