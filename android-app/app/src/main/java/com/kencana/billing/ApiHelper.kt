package com.kencana.billing

import com.kencana.billing.BuildConfig
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.util.concurrent.TimeUnit

/**
 * Helper untuk semua komunikasi dengan REST API Laravel
 */
object ApiHelper {

    private val client = OkHttpClient.Builder()
        .connectTimeout(15, TimeUnit.SECONDS)
        .readTimeout(15, TimeUnit.SECONDS)
        .build()

    private val JSON = "application/json".toMediaType()
    private val BASE = BuildConfig.BASE_URL

    // ------------------------------------------------------------------
    // Login
    // Returns JSONObject dengan { success, data: { token, customers[] } }
    // ------------------------------------------------------------------
    fun login(email: String, password: String): JSONObject {
        val body = JSONObject().apply {
            put("email", email)
            put("password", password)
        }.toString().toRequestBody(JSON)

        val req = Request.Builder()
            .url("$BASE/api/customer/login")
            .post(body)
            .build()

        return executeRequest(req)
    }

    // ------------------------------------------------------------------
    // Daftarkan FCM token ke server
    // ------------------------------------------------------------------
    fun registerFcmToken(token: String, customerId: Int, fcmToken: String): JSONObject {
        val body = JSONObject().apply {
            put("customer_id", customerId)
            put("fcm_token", fcmToken)
        }.toString().toRequestBody(JSON)

        val req = Request.Builder()
            .url("$BASE/api/customer/register-token")
            .post(body)
            .addHeader("Authorization", "Bearer $token")
            .build()

        return executeRequest(req)
    }

    // ------------------------------------------------------------------
    // Logout (hapus FCM token di server)
    // ------------------------------------------------------------------
    fun logout(token: String): JSONObject {
        val body = "{}".toRequestBody(JSON)
        val req = Request.Builder()
            .url("$BASE/api/customer/logout")
            .post(body)
            .addHeader("Authorization", "Bearer $token")
            .build()

        return executeRequest(req)
    }

    // ------------------------------------------------------------------
    // Dashboard info
    // ------------------------------------------------------------------
    fun getDashboard(token: String, customerId: Int): JSONObject {
        val req = Request.Builder()
            .url("$BASE/api/customer/dashboard/$customerId")
            .get()
            .addHeader("Authorization", "Bearer $token")
            .build()

        return executeRequest(req)
    }

    // ------------------------------------------------------------------
    // Execute & parse
    // ------------------------------------------------------------------
    private fun executeRequest(req: Request): JSONObject {
        return try {
            client.newCall(req).execute().use { resp ->
                val body = resp.body?.string() ?: "{}"
                JSONObject(body)
            }
        } catch (e: Exception) {
            JSONObject().apply {
                put("success", false)
                put("message", e.message ?: "Network error")
            }
        }
    }
}
