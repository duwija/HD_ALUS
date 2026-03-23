package com.kencana.billing

import android.content.Context
import android.content.SharedPreferences
import org.json.JSONArray

/**
 * Manages login token and customer data in SharedPreferences
 */
class SessionManager(context: Context) {

    private val prefs: SharedPreferences =
        context.getSharedPreferences("kencana_session", Context.MODE_PRIVATE)

    companion object {
        private const val KEY_TOKEN      = "auth_token"
        private const val KEY_EMAIL      = "email"
        private const val KEY_CUSTOMERS  = "customers_json"
    }

    val isLoggedIn: Boolean
        get() = prefs.getString(KEY_TOKEN, null) != null

    var token: String?
        get() = prefs.getString(KEY_TOKEN, null)
        set(v) = prefs.edit().putString(KEY_TOKEN, v).apply()

    var email: String?
        get() = prefs.getString(KEY_EMAIL, null)
        set(v) = prefs.edit().putString(KEY_EMAIL, v).apply()

    var customersJson: String?
        get() = prefs.getString(KEY_CUSTOMERS, null)
        set(v) = prefs.edit().putString(KEY_CUSTOMERS, v).apply()

    fun saveSession(token: String, email: String, customersJson: String) {
        prefs.edit()
            .putString(KEY_TOKEN, token)
            .putString(KEY_EMAIL, email)
            .putString(KEY_CUSTOMERS, customersJson)
            .apply()
    }

    fun clear() {
        prefs.edit().clear().apply()
    }
}
