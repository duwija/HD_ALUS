@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-book"></i> System Documentation
                    </h3>
                </div>
                <div class="card-body">
                    <p class="lead">
                        Dokumentasi lengkap untuk sistem ISP Multi-Tenant Management.
                    </p>

                    <div class="row mt-4">
                        <!-- Admin Management -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-primary">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-user-shield text-primary"></i> Admin Management
                                    </h5>
                                    <p class="card-text">
                                        Panduan lengkap mengelola admin users, authentication, dan permissions.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Create Admin Users</li>
                                        <li><i class="fas fa-check text-success"></i> Role Management</li>
                                        <li><i class="fas fa-check text-success"></i> Authentication System</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'admin-user') }}" 
                                       class="btn btn-primary btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Tenant Management -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-info">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-building text-info"></i> Tenant Management
                                    </h5>
                                    <p class="card-text">
                                        Cara menambah, mengelola, dan konfigurasi tenant dalam sistem multi-tenant.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Add New Tenant</li>
                                        <li><i class="fas fa-check text-success"></i> Database Configuration</li>
                                        <li><i class="fas fa-check text-success"></i> ENV Variables</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'tenant') }}" 
                                       class="btn btn-info btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Merchant Management -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-handshake text-success"></i> Merchant Management
                                    </h5>
                                    <p class="card-text">
                                        Dokumentasi lengkap untuk mengelola merchant/reseller termasuk WhatsApp Provider configuration.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Add Merchant</li>
                                        <li><i class="fas fa-check text-success"></i> WhatsApp Provider (Gateway/Qontak)</li>
                                        <li><i class="fas fa-check text-success"></i> Accounting Integration</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'merchant') }}" 
                                       class="btn btn-success btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- ENV Variables -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-warning">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-cog text-warning"></i> ENV Variables
                                    </h5>
                                    <p class="card-text">
                                        Panduan konfigurasi environment variables per tenant dan global settings.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Database Configuration</li>
                                        <li><i class="fas fa-check text-success"></i> Custom Variables</li>
                                        <li><i class="fas fa-check text-success"></i> Priority System</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'env-variables') }}" 
                                       class="btn btn-warning btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp Provider -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fab fa-whatsapp text-success"></i> WhatsApp Provider
                                    </h5>
                                    <p class="card-text">
                                        Konfigurasi multi-provider WhatsApp per tenant: Gateway, Fonnte, Wablas, dan Qontak.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Self-hosted WA Gateway</li>
                                        <li><i class="fas fa-check text-success"></i> Fonnte / Wablas (Cloud SaaS)</li>
                                        <li><i class="fas fa-check text-success"></i> Qontak (Official WA Business API)</li>
                                        <li><i class="fas fa-check text-success"></i> Routing otomatis per customer</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'wa-provider') }}" 
                                       class="btn btn-success btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Add Payment Gateway -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-warning">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-credit-card text-warning"></i> Tambah Payment Gateway
                                    </h5>
                                    <p class="card-text">
                                        Panduan lengkap menambahkan provider payment gateway baru ke dalam sistem.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Daftarkan provider di model</li>
                                        <li><i class="fas fa-check text-success"></i> Jalankan seeder ke semua tenant DB</li>
                                        <li><i class="fas fa-check text-success"></i> Tambah tampilan & route</li>
                                        <li><i class="fas fa-check text-success"></i> Konfigurasi fee via admin panel</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'add-payment-gateway') }}"
                                       class="btn btn-warning btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Database Guide -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-danger">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-database text-danger"></i> Database Management
                                    </h5>
                                    <p class="card-text">
                                        Struktur database, migrations, dan cara mengelola multi-tenant databases.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Multi-Tenant Architecture</li>
                                        <li><i class="fas fa-check text-success"></i> Migrations</li>
                                        <li><i class="fas fa-check text-success"></i> Backups</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'database') }}" 
                                       class="btn btn-danger btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Start -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-secondary">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-rocket text-secondary"></i> Quick Start Guide
                                    </h5>
                                    <p class="card-text">
                                        Panduan cepat untuk memulai menggunakan sistem dan setup awal.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Initial Setup</li>
                                        <li><i class="fas fa-check text-success"></i> Add First Tenant</li>
                                        <li><i class="fas fa-check text-success"></i> Basic Configuration</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'quick-start') }}" 
                                       class="btn btn-secondary btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Automatic Backup -->
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-dark">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-database text-dark"></i> Automatic Backup
                                    </h5>
                                    <p class="card-text">
                                        Setup dan konfigurasi automatic database backup untuk semua tenant.
                                    </p>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success"></i> Laravel Artisan Command</li>
                                        <li><i class="fas fa-check text-success"></i> Cron Job Setup</li>
                                        <li><i class="fas fa-check text-success"></i> Auto Cleanup</li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="{{ route('admin.documentation.show', 'automatic-backup') }}" 
                                       class="btn btn-dark btn-sm btn-block">
                                        <i class="fas fa-eye"></i> View Guide
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Resources -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-folder-open"></i> Additional Resources
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-file-code"></i> Technical Documentation</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'readme') }}">
                                                <i class="fas fa-chevron-right text-primary"></i> README.md
                                            </a>
                                        </li>
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'wa-provider') }}">
                                                <i class="fab fa-whatsapp text-success"></i> WhatsApp Provider Guide
                                            </a>
                                        </li>
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'env-changelog') }}">
                                                <i class="fas fa-chevron-right text-primary"></i> ENV Variables Changelog
                                            </a>
                                        </li>
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'css-guide') }}">
                                                <i class="fas fa-chevron-right text-primary"></i> CSS Centralization Guide
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-chart-line"></i> Reports & Analysis</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'financial-changelog') }}">
                                                <i class="fas fa-chevron-right text-primary"></i> Financial Reports Changelog
                                            </a>
                                        </li>
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'report-analysis') }}">
                                                <i class="fas fa-chevron-right text-primary"></i> Report Calculation Analysis
                                            </a>
                                        </li>
                                        <li class="list-group-item">
                                            <a href="{{ route('admin.documentation.show', 'financial-styling') }}">
                                                <i class="fas fa-chevron-right text-primary"></i> Financial Report Styling
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Help Section -->
                    <div class="alert alert-info mt-4">
                        <h5><i class="fas fa-question-circle"></i> Need Help?</h5>
                        <p class="mb-0">
                            Jika Anda tidak menemukan informasi yang dicari, atau ada pertanyaan teknis, 
                            silakan hubungi tim support atau developer system.
                        </p>
                        <hr>
                        <p class="mb-0 small">
                            <strong>Email:</strong> support@alus.co.id | 
                            <strong>Developer:</strong> dev@alus.co.id
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .list-group-item a {
        text-decoration: none;
        color: #333;
    }
    .list-group-item a:hover {
        color: #007bff;
    }
</style>
@endsection
