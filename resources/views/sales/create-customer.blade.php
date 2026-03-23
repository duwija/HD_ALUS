<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Customer Baru - {{ $sales->name }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 0;
        }
        .navbar-custom .navbar-brand {
            color: white;
            font-weight: 600;
            font-size: 18px;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .nav-username {
            color: rgba(255,255,255,0.9);
            font-size: 13px;
            white-space: nowrap;
        }
        @media (max-width: 576px) {
            .navbar-custom .navbar-brand { font-size: 15px; }
            .nav-username { display: none; }
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .form-card h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }
        .form-group label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        @media (max-width: 576px) {
            .btn-submit, .btn-back {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-box i {
            color: #667eea;
            margin-right: 10px;
        }
        /* Select2 visibility fix */
        .select2-container--bootstrap4 .select2-selection--single,
        .select2-container--bootstrap4 .select2-selection--multiple {
            border: 1px solid #ced4da !important;
            border-radius: 8px !important;
            min-height: 42px !important;
            padding: 5px 10px !important;
            background-color: #fff !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: 30px !important;
            color: #495057 !important;
            padding-left: 5px !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            background-color: #667eea !important;
            border-color: #5a6fd6 !important;
            color: #fff !important;
            border-radius: 4px !important;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255,255,255,0.8) !important;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
            color: #495057 !important;
        }
        .select2-container--bootstrap4.select2-container--focus .select2-selection--single,
        .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
            border-color: #667eea !important;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25) !important;
        }
        .select2-dropdown {
            border: 1px solid #ced4da !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }
        .select2-results__option--highlighted {
            background-color: #667eea !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom">
        <div class="container d-flex justify-content-between align-items-center">
            <a class="navbar-brand" href="{{ url('/sales') }}">
                <i class="fas fa-user-tie"></i> Portal Sales
            </a>
            <div class="nav-right">
                <span class="nav-username"><i class="fas fa-user mr-1"></i>{{ $sales->name }}</span>
                <a href="{{ url('/sales/logout') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="mt-4">
            <a href="{{ url('/sales') }}" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="form-card">
            <h4><i class="fas fa-user-plus"></i> Tambah Customer Baru</h4>

            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ url('/sales/customer/store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}"
                                   placeholder="Masukkan nama lengkap"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_name">Nama Kontak</label>
                            <input type="text" 
                                   class="form-control @error('contact_name') is-invalid @enderror" 
                                   id="contact_name" 
                                   name="contact_name" 
                                   value="{{ old('contact_name') }}"
                                   placeholder="Nama kontak">
                            <small class="form-text text-muted">Opsional</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone') }}"
                                   placeholder="Contoh: 081234567890"
                                   required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date_of_birth">Tanggal Lahir</label>
                            <input type="date" 
                                   class="form-control @error('date_of_birth') is-invalid @enderror" 
                                   id="date_of_birth" 
                                   name="date_of_birth" 
                                   value="{{ old('date_of_birth', '1990-01-01') }}">
                            <small class="form-text text-muted">Opsional</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('address') is-invalid @enderror" 
                              id="address" 
                              name="address" 
                              rows="3"
                              placeholder="Masukkan alamat lengkap"
                              required>{{ old('address') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}"
                                   placeholder="email@example.com">
                            <small class="form-text text-muted">Opsional</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="npwp">NPWP</label>
                            <input type="text" 
                                   class="form-control @error('npwp') is-invalid @enderror" 
                                   id="npwp" 
                                   name="npwp" 
                                   value="{{ old('npwp') }}"
                                   placeholder="Nomor NPWP">
                            <small class="form-text text-muted">Opsional</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="id_plan">Paket Internet <span class="text-danger">*</span></label>
                            <select name="id_plan" 
                                    id="id_plan" 
                                    class="form-control @error('id_plan') is-invalid @enderror"
                                    required>
                                <option value="">-- Pilih Paket --</option>
                                @foreach ($plan as $id => $name)
                                <option value="{{ $id }}" {{ old('id_plan') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="id_merchant">Merchant</label>
                            <select name="id_merchant" 
                                    id="id_merchant" 
                                    class="form-control select2 @error('id_merchant') is-invalid @enderror">
                                <option value="">-- Pilih Merchant --</option>
                                @foreach ($merchant as $id => $name)
                                <option value="{{ $id }}" {{ old('id_merchant') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Opsional</small>
                        </div>
                    </div>
                </div>

                {{-- Status selalu Potensial --}}
                <input type="hidden" name="id_status" value="1">

                {{-- Addon --}}
                <div class="form-group">
                    <label>Add-on Layanan
                        <span id="addon-total-badge" class="badge badge-success ml-1" style="display:none">Total: Rp 0</span>
                    </label>
                    <div class="addon-box">
                        @if(count($addons) > 4)
                        <input type="text" class="addon-search" id="addon-search" placeholder="&#128269; Cari add-on...">
                        @endif
                        <div class="addon-scroll">
                            <div class="addon-pills">
                                @forelse($addons as $addon)
                                @php $checked = in_array($addon->id, old('addons', [])); @endphp
                                <label class="addon-pill {{ $checked ? 'selected' : '' }}" data-name="{{ strtolower($addon->name) }}">
                                    <input type="checkbox" name="addons[]" value="{{ $addon->id }}"
                                           data-price="{{ $addon->price }}"
                                           {{ $checked ? 'checked' : '' }}>
                                    <i class="pill-icon fas {{ $checked ? 'fa-check-circle' : 'fa-plus-circle' }}"></i>
                                    <span class="pill-name">{{ $addon->name }}</span>
                                    <span class="pill-price">Rp {{ number_format($addon->price, 0, ',', '.') }}</span>
                                </label>
                                @empty
                                <p class="text-muted small mb-0">Tidak ada add-on tersedia.</p>
                                @endforelse
                            </div>
                            <div class="addon-empty">Tidak ada hasil ditemukan.</div>
                        </div>
                    </div>
                    <small class="form-text text-muted mt-1">Opsional &mdash; klik untuk memilih</small>
                </div>

                {{-- Lead Fields --}}
                <div id="lead-fields">
                    <hr class="my-2">
                    <h6 class="text-primary font-weight-bold mb-3"><i class="fas fa-user-tie mr-1"></i> Informasi Lead</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="lead_source">Sumber Lead</label>
                                <select name="lead_source" id="lead_source" class="form-control">
                                    <option value="">-- Pilih Sumber --</option>
                                    <option value="WA" {{ old('lead_source') == 'WA' ? 'selected' : '' }}>WhatsApp</option>
                                    <option value="Phone" {{ old('lead_source') == 'Phone' ? 'selected' : '' }}>Telepon</option>
                                    <option value="Walk-in" {{ old('lead_source') == 'Walk-in' ? 'selected' : '' }}>Walk-in</option>
                                    <option value="Referral" {{ old('lead_source') == 'Referral' ? 'selected' : '' }}>Referral</option>
                                    <option value="Social Media" {{ old('lead_source') == 'Social Media' ? 'selected' : '' }}>Social Media</option>
                                    <option value="Website" {{ old('lead_source') == 'Website' ? 'selected' : '' }}>Website</option>
                                    <option value="Other" {{ old('lead_source') == 'Other' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="expected_close_date">Target Closing</label>
                                <input type="date" class="form-control" name="expected_close_date"
                                       value="{{ old('expected_close_date') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="conversion_probability">Peluang Konversi (%)</label>
                                <input type="number" class="form-control" name="conversion_probability"
                                       min="0" max="100" placeholder="0–100"
                                       value="{{ old('conversion_probability') }}">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="lead_notes">Catatan Follow-up</label>
                                <textarea class="form-control" name="lead_notes" rows="2"
                                          placeholder="Catatan sales, minat customer, dll.">{{ old('lead_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">
                </div>

                <div class="form-group">
                    <label for="coordinate">Koordinat (Latitude, Longitude)</label>
                    <div class="input-group mb-3">
                        <input type="text" 
                               class="form-control @error('coordinate') is-invalid @enderror" 
                               id="coordinate" 
                               name="coordinate" 
                               value="{{ old('coordinate') }}"
                               placeholder="Contoh: -7.250445, 112.768845">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-maps">
                                <i class="fas fa-map-marker-alt"></i> Pilih dari Peta
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Opsional</small>
                </div>

                <div class="form-group">
                    <label for="note">Catatan</label>
                    <textarea class="form-control @error('note') is-invalid @enderror" 
                              id="note" 
                              name="note" 
                              rows="3"
                              placeholder="Masukkan catatan tambahan (opsional)">{{ old('note') }}</textarea>
                    <small class="form-text text-muted">Default PPPoE User: Customer ID, Password: Customer ID</small>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between">
                    <a href="{{ url('/sales') }}" class="btn btn-back">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> Simpan Customer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Maps -->
    <div class="modal fade" id="modal-maps" tabindex="-1" role="dialog" aria-labelledby="modal-mapsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Lokasi dari Peta</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="map" style="height: 400px;"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" id="btn-current-location">
                        <i class="fas fa-location-arrow"></i> Gunakan Lokasi Saya
                    </button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">
                        <i class="fas fa-check"></i> Set Koordinat
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- /.modal -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <!-- Leaflet Geocoder (Search) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <script>
        $(document).ready(function() {
            // Select2 inits
            $('#id_plan').select2({ theme: 'bootstrap4', placeholder: '-- Pilih Paket --', width: '100%' });
            $('#id_merchant').select2({ theme: 'bootstrap4', placeholder: '-- Pilih Merchant --', width: '100%' });

            // Addon search filter
            $('#addon-search').on('input', function() {
                var q = $(this).val().toLowerCase();
                var visible = 0;
                $('.addon-pill').each(function() {
                    var match = $(this).data('name').includes(q);
                    $(this).toggleClass('hidden', !match);
                    if (match) visible++;
                });
                $('.addon-empty').toggle(visible === 0);
            });

            // Addon pill toggle
            $('.addon-pill').on('click', function() {
                var cb = $(this).find('input[type=checkbox]');
                cb.prop('checked', !cb.prop('checked'));
                var isChecked = cb.prop('checked');
                $(this).toggleClass('selected', isChecked);
                $(this).find('.pill-icon').toggleClass('fa-plus-circle', !isChecked).toggleClass('fa-check-circle', isChecked);
                updateAddonTotal();
            });

            function updateAddonTotal() {
                var total = 0;
                $('.addon-pill.selected input[type=checkbox]').each(function() { total += parseInt($(this).data('price')) || 0; });
                var badge = $('#addon-total-badge');
                total > 0 ? badge.text('Total: Rp ' + total.toLocaleString('id-ID')).show() : badge.hide();
            }
            updateAddonTotal();
        });

        // Maps functionality
        let map;
        let marker;
        let isMapInitialized = false;

        $('#modal-maps').on('shown.bs.modal', function () {
            if (!isMapInitialized) {
                // Default center coordinates (Jakarta or from ENV)
                const defaultLatLng = "-6.200000,106.816666".split(',');
                const lat = parseFloat(defaultLatLng[0]);
                const lng = parseFloat(defaultLatLng[1]);

                map = L.map('map').setView([lat, lng], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Draggable marker
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function (e) {
                    const latlng = e.target.getLatLng();
                    document.getElementById('coordinate').value = `${latlng.lat.toFixed(6)},${latlng.lng.toFixed(6)}`;
                });

                // Search bar with Geocoder
                L.Control.geocoder({
                    defaultMarkGeocode: false
                })
                .on('markgeocode', function(e) {
                    const latlng = e.geocode.center;
                    map.setView(latlng, 16);
                    marker.setLatLng(latlng);
                    document.getElementById('coordinate').value = `${latlng.lat.toFixed(6)},${latlng.lng.toFixed(6)}`;
                })
                .addTo(map);

                isMapInitialized = true;
            }

            setTimeout(() => {
                map.invalidateSize();
            }, 300);
        });

        // Current location button
        document.getElementById('btn-current-location').addEventListener('click', function () {
            if (!map) return;

            map.locate({ setView: true, maxZoom: 18 });

            map.once('locationfound', function (e) {
                const { lat, lng } = e.latlng;
                marker.setLatLng(e.latlng);
                document.getElementById('coordinate').value = `${lat.toFixed(6)},${lng.toFixed(6)}`;
            });

            map.once('locationerror', function () {
                alert('Tidak dapat menemukan lokasi Anda. Pastikan izin lokasi aktif di browser.');
            });
        });
    </script>
</body>
</html>
