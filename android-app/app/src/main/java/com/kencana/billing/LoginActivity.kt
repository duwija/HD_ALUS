package com.kencana.billing

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import com.google.firebase.messaging.FirebaseMessaging
import com.kencana.billing.databinding.ActivityLoginBinding
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONArray

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding
    private lateinit var session: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        session = SessionManager(this)

        // Sudah login → langsung ke dashboard (pass open_url dari notifikasi jika ada)
        if (session.isLoggedIn) {
            goToDashboard(intent)
            return
        }

        requestNotificationPermission()
        setupClickListeners()
    }

    private fun setupClickListeners() {
        binding.btnLogin.setOnClickListener {
            val email    = binding.etEmail.text.toString().trim()
            val password = binding.etPassword.text.toString()

            if (email.isEmpty() || password.isEmpty()) {
                Toast.makeText(this, "Email dan password wajib diisi", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            doLogin(email, password)
        }

        binding.tvActivate.setOnClickListener {
            // Buka halaman aktivasi di WebView sederhana
            val intent = Intent(this, DashboardActivity::class.java).apply {
                putExtra("open_url", "/tagihan/activate")
                putExtra("is_public", true)
            }
            startActivity(intent)
        }
    }

    private fun doLogin(email: String, password: String) {
        binding.btnLogin.isEnabled = false
        binding.progressBar.visibility = View.VISIBLE
        binding.tvError.visibility = View.GONE

        CoroutineScope(Dispatchers.IO).launch {
            val result = ApiHelper.login(email, password)

            withContext(Dispatchers.Main) {
                binding.progressBar.visibility = View.GONE
                binding.btnLogin.isEnabled = true

                if (result.optBoolean("success")) {
                    val data      = result.getJSONObject("data")
                    val token     = data.getString("token")
                    val customers = data.getJSONArray("customers")

                    session.saveSession(token, email, customers.toString())

                    // Daftarkan FCM token ke server
                    registerFcmTokens(token, customers)

                    goToDashboard(intent)
                } else {
                    val msg = result.optString("message", "Login gagal")
                    binding.tvError.text = msg
                    binding.tvError.visibility = View.VISIBLE
                }
            }
        }
    }

    private fun registerFcmTokens(token: String, customers: JSONArray) {
        FirebaseMessaging.getInstance().token.addOnSuccessListener { fcmToken ->
            CoroutineScope(Dispatchers.IO).launch {
                for (i in 0 until customers.length()) {
                    val customer = customers.getJSONObject(i)
                    ApiHelper.registerFcmToken(token, customer.getInt("id"), fcmToken)
                }
            }
        }
    }

    private fun goToDashboard(sourceIntent: Intent? = null) {
        val dashIntent = Intent(this, DashboardActivity::class.java)
        // Teruskan open_url dari notifikasi (jika ada)
        sourceIntent?.getStringExtra("open_url")?.let {
            dashIntent.putExtra("open_url", it)
        }
        startActivity(dashIntent)
        finish()
    }

    private fun requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                != PackageManager.PERMISSION_GRANTED
            ) {
                ActivityCompat.requestPermissions(
                    this,
                    arrayOf(Manifest.permission.POST_NOTIFICATIONS),
                    1001
                )
            }
        }
    }
}
