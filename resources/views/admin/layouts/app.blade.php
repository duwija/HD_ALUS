<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - {{ config('app.name', 'ISP Management') }}</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #adb5bd;
            padding: 1rem 1rem;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #495057;
            border-left-color: #007bff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
            border-left-color: #007bff;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        main {
            margin-left: 250px;
            padding-top: 70px;
        }
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            main {
                margin-left: 0;
            }
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="{{ route('admin.tenants.index') }}">
            <i class="fas fa-shield-alt"></i> Admin Panel
        </a>
        <ul class="navbar-nav px-3 ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
                    <i class="fas fa-user-shield"></i> {{ auth('admin')->user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('admin.users.edit', auth('admin')->id()) }}">
                        <i class="fas fa-user-cog"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}" 
                               href="{{ route('admin.tenants.index') }}">
                                <i class="fas fa-building"></i>
                                Tenant Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                               href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users-cog"></i>
                                Admin Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}" 
                               href="{{ route('admin.logs.index') }}">
                                <i class="fas fa-scroll"></i>
                                Application Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.github-sync*') ? 'active' : '' }}" 
                               href="{{ route('admin.github-sync') }}">
                                <i class="fas fa-code-branch"></i>
                                GitHub Sync
                            </a>
                        </li>
                        <li class="nav-item">
                            <hr class="bg-secondary my-3">
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.documentation') ? 'active' : '' }}" 
                               href="{{ route('admin.documentation') }}">
                                <i class="fas fa-book"></i>
                                Documentation
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-10 ml-sm-auto px-4">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    @yield('scripts')
</body>
</html>
