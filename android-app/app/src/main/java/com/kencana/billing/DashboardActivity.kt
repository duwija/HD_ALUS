package com.kencana.billing

import android.annotation.SuppressLint
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.Menu
import android.view.MenuItem
import android.view.View
import android.webkit.*
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.kencana.billing.BuildConfig
import com.kencana.billing.databinding.ActivityDashboardBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

class DashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDashboardBinding
    private lateinit var session: SessionManager

    private val BASE = BuildConfig.BASE_URL

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        session = SessionManager(this)

        setupToolbar()
        setupWebView()
        setupBottomNav()
        startNotifBadgePolling()

        // Halaman yang diminta (dari notifikasi atau default ke beranda app)
        val openUrl = intent.getStringExtra("open_url") ?: "/tagihan/app/home"
        loadPage(openUrl)
    }

    // ── Toolbar ─────────────────────────────────────────────────────────
    private fun setupToolbar() {
        setSupportActionBar(binding.toolbar)
        supportActionBar?.title = "Portal Pelanggan"
    }

    override fun onCreateOptionsMenu(menu: Menu): Boolean {
        menuInflater.inflate(R.menu.dashboard_toolbar_menu, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        return when (item.itemId) {
            R.id.menu_logout -> { confirmLogout(); true }
            else -> super.onOptionsItemSelected(item)
        }
    }

    private fun confirmLogout() {
        AlertDialog.Builder(this)
            .setTitle("Keluar")
            .setMessage("Yakin ingin keluar dari akun?")
            .setPositiveButton("Keluar") { _, _ -> doLogout() }
            .setNegativeButton("Batal", null)
            .show()
    }

    // ── WebView ──────────────────────────────────────────────────────────
    @SuppressLint("SetJavaScriptEnabled")
    private fun setupWebView() {
        android.webkit.CookieManager.getInstance().apply {
            setAcceptCookie(true)
            setAcceptThirdPartyCookies(binding.webView, true)
        }
        binding.webView.apply {
            settings.apply {
                javaScriptEnabled    = true
                domStorageEnabled    = true
                loadWithOverviewMode = true
                useWideViewPort      = true
                builtInZoomControls  = false
                displayZoomControls  = false
                mixedContentMode     = WebSettings.MIXED_CONTENT_NEVER_ALLOW
            }

            webViewClient = object : WebViewClient() {
                override fun onPageStarted(v: WebView?, url: String?, favicon: android.graphics.Bitmap?) {
                    binding.progressBar.visibility = View.VISIBLE
                }

                override fun onPageFinished(v: WebView?, url: String?) {
                    binding.progressBar.visibility = View.GONE
                    updateBottomNavFromUrl(url ?: "")

                    if (url == null) return

                    // Paksa logout: server mendeteksi token sudah direvoke admin
                    if (isForceLogoutPage(url)) {
                        session.clear()
                        startActivity(Intent(this@DashboardActivity, LoginActivity::class.java))
                        finish()
                        return
                    }

                    // Re-trigger SSO jika mendarat di halaman login web (tanpa force_logout)
                    if (isWebLoginPage(url)) {
                        val token = session.token
                        if (token != null) {
                            val t = java.net.URLEncoder.encode(token, "UTF-8")
                            val r = java.net.URLEncoder.encode("/tagihan/app/home", "UTF-8")
                            loadUrl("$BASE/tagihan/app-login?token=$t&redirect=$r")
                        } else {
                            startActivity(Intent(this@DashboardActivity, LoginActivity::class.java))
                            finish()
                        }
                    }
                }

                override fun onReceivedError(v: WebView?, req: WebResourceRequest?, err: WebResourceError?) {
                    binding.progressBar.visibility = View.GONE
                    Toast.makeText(this@DashboardActivity, "Gagal memuat halaman. Cek koneksi internet.", Toast.LENGTH_SHORT).show()
                }

                override fun shouldOverrideUrlLoading(v: WebView?, req: WebResourceRequest?): Boolean {
                    val url = req?.url?.toString() ?: return false
                    if (!url.startsWith(BASE)) {
                        startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                        return true
                    }
                    return false
                }
            }
        }

        binding.swipeRefresh.setOnRefreshListener {
            binding.webView.reload()
            binding.swipeRefresh.isRefreshing = false
        }
    }

    // ── Bottom Nav ───────────────────────────────────────────────────────
    private fun setupBottomNav() {
        binding.bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_home    -> { loadPage("/tagihan/app/home");    true }
                R.id.nav_tagihan -> { loadPage("/tagihan/app/tagihan"); true }
                R.id.nav_laporan -> { loadPage("/tagihan/app/laporan"); true }
                R.id.nav_notif   -> {
                    binding.bottomNav.getBadge(R.id.nav_notif)?.isVisible = false
                    loadPage("/tagihan/app/notif")
                    true
                }
                else -> false
            }
        }
    }

    // ── Navigasi via SSO bridge ──────────────────────────────────────────
    private fun loadPage(path: String) {
        val token = session.token
        if (token != null) {
            val t = java.net.URLEncoder.encode(token, "UTF-8")
            val r = java.net.URLEncoder.encode(path, "UTF-8")
            binding.webView.loadUrl("$BASE/tagihan/app-login?token=$t&redirect=$r")
        } else {
            binding.webView.loadUrl("$BASE$path")
        }
    }

    // ── Notif Badge polling (setiap 60 detik) ───────────────────────────
    private fun startNotifBadgePolling() {
        lifecycleScope.launch {
            while (true) {
                fetchAndUpdateBadge()
                delay(60_000L)
            }
        }
    }

    private suspend fun fetchAndUpdateBadge() {
        val token = session.token ?: return
        try {
            val count = withContext(Dispatchers.IO) {
                val t   = java.net.URLEncoder.encode(token, "UTF-8")
                val url = URL("$BASE/tagihan/app/notif-badge?token=$t")
                val con = url.openConnection() as HttpURLConnection
                con.connectTimeout = 5000
                con.readTimeout    = 5000
                val json = JSONObject(con.inputStream.bufferedReader().readText())
                con.disconnect()
                json.optInt("count", 0)
            }
            withContext(Dispatchers.Main) {
                val badge = binding.bottomNav.getOrCreateBadge(R.id.nav_notif)
                if (count > 0) {
                    badge.isVisible = true
                    badge.number    = count
                } else {
                    badge.isVisible = false
                }
            }
        } catch (_: Exception) { /* network error — abaikan */ }
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private fun updateBottomNavFromUrl(url: String) {
        val id = when {
            url.contains("/tagihan/app/notif")   -> R.id.nav_notif
            url.contains("/tagihan/app/laporan") -> R.id.nav_laporan
            url.contains("/tagihan/app/tagihan") -> R.id.nav_tagihan
            url.contains("/tagihan/app/home")    -> R.id.nav_home
            url.contains("/invoice/cst")         -> R.id.nav_tagihan
            else                                  -> R.id.nav_home
        }
        binding.bottomNav.menu.findItem(id)?.isChecked = true
    }

    private fun isForceLogoutPage(url: String): Boolean {
        return url.contains("app_force_logout=1")
    }

    private fun isWebLoginPage(url: String): Boolean {
        return url.substringBefore("?").endsWith("/tagihan/login")
    }

    private fun doLogout() {
        val token = session.token
        if (token != null) {
            lifecycleScope.launch(Dispatchers.IO) { ApiHelper.logout(token) }
        }
        session.clear()
        startActivity(Intent(this, LoginActivity::class.java))
        finish()
    }

    override fun onBackPressed() {
        if (binding.webView.canGoBack()) binding.webView.goBack()
        else super.onBackPressed()
    }

    override fun onNewIntent(intent: Intent?) {
        super.onNewIntent(intent)
        intent?.getStringExtra("open_url")?.let { loadPage(it) }
    }
}
