import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../services/auth_service.dart';

class TicketWebviewScreen extends StatefulWidget {
  const TicketWebviewScreen({super.key});

  @override
  State<TicketWebviewScreen> createState() => _TicketWebviewScreenState();
}

class _TicketWebviewScreenState extends State<TicketWebviewScreen> {
  WebViewController? _controller;
  bool _isLoading = true;
  bool _hasError = false;
  String? _errorMsg;

  static const String _baseUrl = 'https://kencana.alus.co.id';

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  Future<void> _initWebView() async {
    final token = await AuthService.getToken();

    final uri = token != null && token.isNotEmpty
        ? Uri.parse('$_baseUrl/webview/ticket-auth')
            .replace(queryParameters: {'token': token, 'redirect': '/ticket'})
        : Uri.parse('$_baseUrl/ticket');

    _controller = WebViewController()      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..setNavigationDelegate(NavigationDelegate(
        onPageStarted: (_) => setState(() {
          _isLoading = true;
          _hasError = false;
        }),
        onPageFinished: (_) => setState(() => _isLoading = false),
        onWebResourceError: (err) {
          if (err.isForMainFrame ?? true) {
            setState(() {
              _isLoading = false;
              _hasError = true;
              _errorMsg = err.description;
            });
          }
        },
      ))
      ..loadRequest(uri);

    setState(() {}); // trigger rebuild with controller ready
  }

  Future<bool> _onWillPop() async {
    if (await _controller?.canGoBack() ?? false) {
      await _controller?.goBack();
      return false;
    }
    return false; // tetap di tab, jangan pop MainScreen
  }

  void _refresh() {
    setState(() {
      _isLoading = true;
      _hasError = false;
    });
    _controller?.reload();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: _onWillPop,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Tiket'),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              tooltip: 'Refresh',
              onPressed: _refresh,
            ),
            IconButton(
              icon: const Icon(Icons.arrow_back_ios),
              tooltip: 'Kembali',
              onPressed: () async {
                if (await _controller?.canGoBack() ?? false) {
                  _controller?.goBack();
                }
              },
            ),
          ],
        ),
        body: Stack(
          children: [
            // WebView — hanya tampil jika controller sudah siap
            if (!_hasError && _controller != null)
              WebViewWidget(controller: _controller!),

            // Loading bar di atas WebView
            if (_isLoading)
              const LinearProgressIndicator(
                minHeight: 3,
                backgroundColor: Colors.transparent,
              ),

            // Inisialisasi
            if (_controller == null && !_hasError)
              const Center(child: CircularProgressIndicator()),

            // Error state
            if (_hasError)
              Center(
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.wifi_off_rounded,
                          size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      const Text('Tidak dapat memuat halaman',
                          style: TextStyle(
                              fontSize: 16, fontWeight: FontWeight.w600)),
                      if (_errorMsg != null) ...[
                        const SizedBox(height: 8),
                        Text(_errorMsg!,
                            style: const TextStyle(
                                fontSize: 12, color: Colors.grey),
                            textAlign: TextAlign.center),
                      ],
                      const SizedBox(height: 24),
                      ElevatedButton.icon(
                        onPressed: _refresh,
                        icon: const Icon(Icons.refresh),
                        label: const Text('Coba Lagi'),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
