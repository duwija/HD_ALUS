package com.kencana.billing

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class MyFirebaseMessagingService : FirebaseMessagingService() {

    companion object {
        const val CHANNEL_ID   = "billing_channel"
        const val CHANNEL_NAME = "Tagihan & Notifikasi"
    }

    // ------------------------------------------------------------------
    // Dipanggil saat token FCM berubah (install baru / token refresh)
    // ------------------------------------------------------------------
    override fun onNewToken(fcmToken: String) {
        super.onNewToken(fcmToken)
        val session = SessionManager(applicationContext)

        if (!session.isLoggedIn) return

        // Kirim token baru ke server untuk semua customer yang terdaftar
        val customersJson = session.customersJson ?: return
        val arr = org.json.JSONArray(customersJson)

        CoroutineScope(Dispatchers.IO).launch {
            for (i in 0 until arr.length()) {
                val cust = arr.getJSONObject(i)
                ApiHelper.registerFcmToken(
                    token      = session.token ?: return@launch,
                    customerId = cust.getInt("id"),
                    fcmToken   = fcmToken
                )
            }
        }
    }

    // ------------------------------------------------------------------
    // Dipanggil saat notifikasi masuk (app foreground / background)
    // ------------------------------------------------------------------
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        super.onMessageReceived(remoteMessage)

        val title   = remoteMessage.notification?.title
            ?: remoteMessage.data["title"]
            ?: "Notifikasi"
        val body    = remoteMessage.notification?.body
            ?: remoteMessage.data["body"]
            ?: ""
        val type    = remoteMessage.data["type"] ?: ""
        val openUrl = remoteMessage.data["open_url"] ?: ""

        showNotification(title, body, type, openUrl)
    }

    // ------------------------------------------------------------------
    // Tampilkan notifikasi sistem
    // ------------------------------------------------------------------
    private fun showNotification(title: String, body: String, type: String, openUrl: String = "") {
        createNotificationChannel()

        // Tentukan halaman yang dibuka saat notif diklik
        // Prioritas: open_url dari FCM data → fallback berdasarkan type
        val targetUrl = if (openUrl.isNotEmpty()) openUrl else when (type) {
            "new_invoice"      -> "/tagihan"
            "reminder_invoice" -> "/tagihan"
            else               -> "/tagihan"
        }

        val intent = Intent(this, DashboardActivity::class.java).apply {
            putExtra("open_url", targetUrl)
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }
        val pendingIntent = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .build()

        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.notify(System.currentTimeMillis().toInt(), notification)
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                CHANNEL_NAME,
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Notifikasi tagihan dan status tiket"
                enableVibration(true)
            }
            val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            manager.createNotificationChannel(channel)
        }
    }
}
