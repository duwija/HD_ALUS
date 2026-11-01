<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \RouterOS\Client;
use \RouterOS\Query;
use Exception;

class Distrouter extends Model
{
	use SoftDeletes;
    //
	protected $fillable =['name','ip', 'port', 'web','user','password','created_at','updated_at','note','deleted_at'];

	// Password MikroTik dienkripsi at-rest (APP_KEY). Transparan untuk semua $distrouter->password
	// yang sudah ada di codebase — otomatis encrypt saat save, decrypt saat dibaca.
	// Data lama sudah dimigrasi lewat: php artisan distrouter:encrypt-passwords
	protected $casts = [
		'password' => 'encrypted',
	];
	public function customer()
	{
		return $this->hasmany('\App\Customer', 'id_distrouter');
	}

	// Pool & alamat untuk profile isolir yang dibuat otomatis bila belum ada di router
	const ISOLIR_POOL_NAME   = 'ISOLIR';
	const ISOLIR_POOL_RANGES = '172.20.0.2-172.20.255.254';
	const ISOLIR_LOCAL_ADDR  = '172.20.0.1';
	const ISOLIR_DNS         = '8.8.8.8,1.1.1.1';
	const ISOLIR_SUBNET      = '172.20.0.0/16';

	/**
	 * Metode isolir tenant (Settings → Metode Isolir), disimpan di isp_master.tenants.features:
	 *   isolir_method  : disable (default) | profile
	 *   isolir_profile : nama profile PPP isolir (default ISOLIR)
	 *
	 * @return array{0:string,1:string} [method, profileName]
	 */
	public static function isolirSetting(): array
	{
		$features = tenant('features') ?? [];
		$method   = ($features['isolir_method'] ?? 'disable') === 'profile' ? 'profile' : 'disable';
		$profile  = trim((string) ($features['isolir_profile'] ?? '')) ?: 'ISOLIR';

		return [$method, $profile];
	}

	/**
	 * Jalankan perintah API dan lempar exception bila router membalas !trap
	 * (library tidak melempar sendiri, pesan error ada di ['after']['message']).
	 */
	private static function apiWrite(Client $client, Query $query): array
	{
		$out = $client->query($query)->read();
		if (isset($out['after']['message'])) {
			throw new \RuntimeException($out['after']['message']);
		}
		return $out;
	}

	/**
	 * Pastikan profile isolir ada di router. Jika belum: buat pool ISOLIR (172.20.0.0/16)
	 * lalu profile isolir yang memakai pool tersebut dan memasukkan pelanggan ke address-list "isolir".
	 */
	private static function ensureIsolirProfile(Client $client, string $profile): void
	{
		$exists = $client->query((new Query('/ppp/profile/print'))->where('name', $profile))->read();
		if (!empty($exists[0]['.id'])) {
			return;
		}

		$pool = $client->query((new Query('/ip/pool/print'))->where('name', self::ISOLIR_POOL_NAME))->read();
		if (empty($pool[0]['.id'])) {
			static::apiWrite($client, (new Query('/ip/pool/add'))
				->equal('name', self::ISOLIR_POOL_NAME)
				->equal('ranges', self::ISOLIR_POOL_RANGES)
				->equal('comment', 'Pool isolir (dibuat otomatis oleh billing)'));
		}

		static::apiWrite($client, (new Query('/ppp/profile/add'))
			->equal('name', $profile)
			->equal('local-address', self::ISOLIR_LOCAL_ADDR)
			->equal('remote-address', self::ISOLIR_POOL_NAME)
			->equal('address-list', 'isolir')
			->equal('rate-limit', '64k/64k')
			->equal('dns-server', self::ISOLIR_DNS)
			->equal('comment', 'Profile isolir (dibuat otomatis oleh billing)'));

		\Log::channel('isolir')->info("[ISOLIR] Profile '{$profile}' + pool " . self::ISOLIR_POOL_NAME . " dibuat otomatis di router.");
	}

	/**
	 * Isolir pelanggan (status Blocked) sesuai metode tenant:
	 *  - disable : disable secret PPPoE (sama dengan mikrotik_disable)
	 *  - profile : ganti profile secret ke profile isolir, secret tetap enable, sesi aktif diputus
	 * Pelanggan Inactive/terminate tetap memakai mikrotik_disable, bukan fungsi ini.
	 */
	public static function mikrotik_isolir($ip, $user, $pass, $port, $cid)
	{
		[$method, $isolirProfile] = static::isolirSetting();

		if ($method !== 'profile') {
			return static::mikrotik_disable($ip, $user, $pass, $port, $cid);
		}

		try {
			$client = new Client([
				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);

			static::ensureIsolirProfile($client, $isolirProfile);

			$secrets = $client->query((new Query('/ppp/secret/print'))->where('name', $cid))->read();

			$wasDisabled = null;
			$oldProfile  = null;
			foreach ($secrets as $secret) {
				$wasDisabled = $secret['disabled'] ?? 'false';
				$oldProfile  = $secret['profile'] ?? null;

				static::apiWrite($client, (new Query('/ppp/secret/set'))
					->equal('.id', $secret['.id'])
					->equal('profile', $isolirProfile)
					->equal('disabled', 'false'));
			}

			// Putus sesi aktif supaya modem login ulang dengan profile isolir
			if (!empty($secrets)) {
				$actives = $client->query((new Query('/ppp/active/print'))->where('name', $cid))->read();
				foreach ($actives as $active) {
					$client->query((new Query('/ppp/active/remove'))->equal('.id', $active['.id']))->read();
				}
			}

			static::logPppoeStatusChange($cid, $wasDisabled, 'false');
			static::logPppoeProfileChange($cid, $oldProfile, $isolirProfile);

		} catch (Exception $ex) {
			throw new \RuntimeException('MikroTik isolir (profile) failed for ' . $cid . ': ' . $ex->getMessage(), 0, $ex);
		}
	}

	/**
	 * Profile secret sebelum diisolir, diambil dari Customerlog yang ditulis mikrotik_isolir()
	 * (topic pppoe_status, "PPPoE Profile" old → profile isolir). Banyak secret di router memakai
	 * profile yang namanya beda dari nama plan billing (dengan pool IP sendiri), jadi kembali ke
	 * nama plan bisa mengubah kecepatan/pool. Return null bila tidak ada catatan atau profile
	 * tersebut sudah tidak ada di router.
	 */
	private static function profileBeforeIsolir(Client $client, string $cid, string $isolirProfile): ?string
	{
		$customer = \App\Customer::withTrashed()->where('pppoe', $cid)->first();
		if (!$customer) {
			return null;
		}

		$logs = \App\Customerlog::where('id_customer', $customer->id)
			->where('topic', 'pppoe_status')
			->orderByDesc('id')
			->limit(50)
			->get(['updates']);

		$candidate = null;
		foreach ($logs as $log) {
			$change = json_decode((string) $log->updates, true)['PPPoE Profile'] ?? null;
			if (($change['new'] ?? null) === $isolirProfile && !empty($change['old']) && $change['old'] !== $isolirProfile) {
				$candidate = $change['old'];
				break;
			}
		}
		if ($candidate === null) {
			return null;
		}

		$exists = $client->query((new Query('/ppp/profile/print'))->where('name', $candidate))->read();
		return !empty($exists[0]['.id']) ? $candidate : null;
	}

	/**
	 * Kembalikan profile secret yang sedang di profile isolir ke profile sebelum diisolir;
	 * bila tidak tercatat, ke profile paket (nama plan pelanggan).
	 * Return true bila profile diubah (sesi perlu diputus agar kecepatan paket berlaku).
	 */
	private static function restoreProfileFromIsolir(Client $client, array $secret, string $cid): bool
	{
		[, $isolirProfile] = static::isolirSetting();

		if (($secret['profile'] ?? null) !== $isolirProfile) {
			return false;
		}

		$target = static::profileBeforeIsolir($client, $cid, $isolirProfile);

		if ($target === null) {
			$customer = \App\Customer::withTrashed()->where('pppoe', $cid)->first();
			$plan     = $customer ? \App\Plan::withTrashed()->find($customer->id_plan) : null;
			if (!$plan || trim((string) $plan->name) === '') {
				throw new \RuntimeException("Plan pelanggan {$cid} tidak ditemukan, profile tidak bisa dikembalikan dari {$isolirProfile}");
			}

			// Pastikan profile paket ada di router (sama seperti saat pelanggan dibuat)
			static::mikrotik_addprofile_client($client, $plan->name, $plan->speed, $plan->description);
			$target = $plan->name;
		}

		static::apiWrite($client, (new Query('/ppp/secret/set'))
			->equal('.id', $secret['.id'])
			->equal('profile', $target));

		static::logPppoeProfileChange($cid, $isolirProfile, $target);

		return true;
	}

	/** Versi mikrotik_addprofile yang memakai koneksi yang sudah terbuka. */
	private static function mikrotik_addprofile_client(Client $client, $name, $limit, $comment): void
	{
		$profiles = $client->query((new Query('/ppp/profile/print'))->where('name', $name))->read();
		if (!empty($profiles[0]['.id'])) {
			return;
		}

		static::apiWrite($client, (new Query('/ppp/profile/add'))
			->equal('name', $name)
			->equal('rate-limit', $limit)
			->equal('comment', $comment));
	}

	public static function mikrotik_connection ($host,$user,$pass,$port)
	{
		try {

			$client = new Client([
            //to login to api
				'host' => $host,
				'user' => $user,
				'pass' => $pass,
				'port' => $port,
            //data
				

			]);
		}
		catch (Exception $e)
		{
			return false;
		}
		finally {
			return true;
		}
	}

// 	public static function mikrotik_addsecreate($host,$user,$pass,$port,$cid,$cidpass,$profile,$comment)
	
// 	{

// 		try {

// 			$client = new Client([
//             //to login to api
// 				'host' => $host,
// 				'user' => $user,
// 				'pass' => $pass,
// 				'port' => $port,
//             //data
	

// 			]);
	




// // check user exist 
// 			$query_check =

// 			(new Query('/ppp/secret/print'))

// 			->where('name',$cid);

// 			$users = $client->query($query_check)->read();


// //var_dump($users);
//             // if user exist
// 			if (!empty($users[0]['.id'])) {
//             // set the user enable
// 				foreach ($users as $user) {

//     // enable
// 					$query_enable = (new Query('/ppp/secret/set'))
// 					->equal('.id', $user['.id'])
// 					->equal('disabled', 'false');
	


// 					$result = $client->query($query_enable)->read();

// // echo $result;

// 				}
// 			}

// 			else
// 			{

// 				$query_add =

// 				(new Query('/ppp/secret/add '))
// 				->equal('name', $cid)
// 				->equal('password', $cidpass)
// 				->equal('comment', $comment)
// 				->equal('profile', $profile);


// 				$response = $client->query($query_add)->read();

// 			}

// 		} catch (Exception $ex) {
// 			return('field connecting to router');
// 		}
// 	}



	public static function mikrotik_addsecreate($host, $user, $pass, $port, $cid, $cidpass, $profile, $comment, $ip = null)
	{
		try {
			$client = new Client([
				'host' => $host,
				'user' => $user,
				'pass' => $pass,
				'port' => $port,
			]);

        // Cek apakah user PPPoE sudah ada
			$query_check = (new Query('/ppp/secret/print'))
			->where('name', $cid);

			$users = $client->query($query_check)->read();

			if (!empty($users[0]['.id'])) {
				foreach ($users as $user) {
					$query_enable = (new Query('/ppp/secret/set'))
					->equal('.id', $user['.id'])
					->equal('disabled', 'false');
					// Secret yang sedang di profile isolir dikembalikan ke profile paket
					[, $isolirProfile] = static::isolirSetting();
					$restoreProfile = ($user['profile'] ?? null) === $isolirProfile && !empty($profile);
					if ($restoreProfile) {
						// Utamakan profile sebelum diisolir (bisa beda nama dengan plan)
						$profile = static::profileBeforeIsolir($client, $cid, $isolirProfile) ?? $profile;
						$query_enable->equal('profile', $profile);
					}
					$client->query($query_enable)->read();

					if ($restoreProfile) {
						static::logPppoeProfileChange($cid, $isolirProfile, $profile);
						$actives = $client->query((new Query('/ppp/active/print'))->where('name', $cid))->read();
						foreach ($actives as $active) {
							$client->query((new Query('/ppp/active/remove'))->equal('.id', $active['.id']))->read();
						}
					}
				}
			} else {
				$query_add = (new Query('/ppp/secret/add'))
				->equal('name', $cid)
				->equal('password', $cidpass)
				->equal('comment', $comment)
				->equal('profile', $profile);

            // Tambahkan remote-address hanya jika IP tersedia
				if (!empty($ip)) {
					$query_add->equal('remote-address', $ip);
				}

				$client->query($query_add)->read();
			}

		} catch (Exception $ex) {
			return 'failed connecting to router';
		}
	}


	public static function mikrotik_is_secret_disabled($ip, $user, $pass, $port, $pppoe)
	{
		$client = new \RouterOS\Client([
			'host' => $ip,
			'user' => $user,
			'pass' => $pass,
			'port' => $port,
		]);

		$query = (new \RouterOS\Query('/ppp/secret/print'))
		->where('name', $pppoe);

		$secrets = $client->query($query)->read();

		return isset($secrets[0]['disabled']) && $secrets[0]['disabled'] === 'true';
	}



	public static function mikrotik_addprofile($host,$user,$pass,$port,$name,$limit,$comment)
	{

		try {

			$client = new Client([
            //to login to api
				'host' => $host,
				'user' => $user,
				'pass' => $pass,
				'port' => $port,
            //data
				

			]);
			$name      = $name;
			$limit   = $limit;
			$comment   = $comment;
			




// check user exist 
			$query_check =

			(new Query('/ppp/profile/print'))

			->where('name',$name);

			$profiles = $client->query($query_check)->read();




//var_dump($users);
            // if user exist
			if (!empty($profiles[0]['.id'])) {
            // set the user enable
				foreach ($profiles as $profile) {

    // enable
					$query_enable = (new Query('/ppp/profile/set'))
					->equal('.id', $profile['.id'])
					->equal('disabled', 'false');


					$result = $client->query($query_enable)->read();
					

// echo $result;

				}
			}

			else
			{

				$query_add =

				(new Query('/ppp/profile/add '))
				->equal('name',$name)
				->equal('rate-limit', $limit)
				->equal('comment', $comment);


				$response = $client->query($query_add)->read();
				

			}


			

		} catch (Exception $ex) {
			return "field connecting to router";
		}


	}








	public static function mikrotik_enable($ip,$user,$pass,$port,$cid)
	{

		try {

			$client = new Client([


				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);



			$query =

			(new Query('/ppp/secret/print'))

			->where('name', $cid);


			$secrets = $client->query($query)->read();

			$wasDisabled = null;
			$profileRestored = false;
			foreach ($secrets as $secret) {

				$wasDisabled = $secret['disabled'] ?? 'false';

				// enable
				$query = (new Query('/ppp/secret/set'))
				->equal('.id', $secret['.id'])
				->equal('disabled', 'false');

				$client->query($query)->read();

				// Jika diisolir dengan metode profile → kembalikan ke profile paket (nama plan)
				if (static::restoreProfileFromIsolir($client, $secret, $cid)) {
					$profileRestored = true;
				}
			}

			// Putus sesi yang masih memakai profile isolir agar login ulang dengan profile paket
			if ($profileRestored) {
				$actives = $client->query((new Query('/ppp/active/print'))->where('name', $cid))->read();
				foreach ($actives as $active) {
					$client->query((new Query('/ppp/active/remove'))->equal('.id', $active['.id']))->read();
				}
			}

			static::logPppoeStatusChange($cid, $wasDisabled, 'false');

		} catch (Exception $ex) {
			throw new \RuntimeException('MikroTik enable failed for ' . $cid . ': ' . $ex->getMessage(), 0, $ex);
		}

	}



	public static function mikrotik_disable($ip,$user,$pass,$port,$cid)
	{

		try {

			$client = new Client([


				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);



			$query =

			(new Query('/ppp/secret/print'))

			->where('name', $cid);


			$secrets = $client->query($query)->read();

			$wasDisabled = null;
			foreach ($secrets as $secret) {

				$wasDisabled = $secret['disabled'] ?? 'false';

				// disable
				$query = (new Query('/ppp/secret/set'))
				->equal('.id', $secret['.id'])
				->equal('disabled', 'true');

				$client->query($query)->read();

				// Putus koneksi aktif jika ada
				$query_status = (new Query('/ppp/active/print'))->where('name', $cid);
				$response_status = $client->query($query_status)->read();

				if (!empty($response_status)) {
					foreach ($response_status as $active) {
						$query = (new Query('/ppp/active/remove'))
						->equal('.id', $active['.id']);
						$client->query($query)->read();
					}
				}
			}

			static::logPppoeStatusChange($cid, $wasDisabled, 'true');

		} catch (Exception $ex) {
			throw new \RuntimeException('MikroTik disable failed for ' . $cid . ': ' . $ex->getMessage(), 0, $ex);
		}

	}

	/**
	 * Catat perubahan status PPPoE (enable/disable) ke Customerlog, dipanggil dari
	 * mikrotik_enable()/mikrotik_disable() supaya semua caller otomatis tercatat
	 * tanpa perlu ubah tiap titik panggil. $cid = nama PPP secret (= customers.pppoe).
	 * $fromDisabledRaw/$toDisabledRaw = nilai property 'disabled' RouterOS ('true'/'false').
	 */
	private static function logPppoeStatusChange($cid, $fromDisabledRaw, $toDisabledRaw)
	{
		if ($fromDisabledRaw === null) return; // tidak ada secret yang diubah

		$from = ($fromDisabledRaw === 'true') ? 'Disabled' : 'Enabled';
		$to   = ($toDisabledRaw === 'true') ? 'Disabled' : 'Enabled';

		if ($from === $to) return; // tidak ada perubahan status nyata

		try {
			$customer = \App\Customer::where('pppoe', $cid)->first();
			if (!$customer) return;

			\App\Customerlog::create([
				'id_customer' => $customer->id,
				'date'        => now(),
				'updated_by'  => 'System (MikroTik)',
				'topic'       => 'pppoe_status',
				'updates'     => json_encode([
					'PPPoE Status' => ['old' => $from, 'new' => $to],
				]),
			]);
		} catch (\Exception $e) {
			\Log::warning("Gagal mencatat Customerlog pppoe_status untuk {$cid}: " . $e->getMessage());
		}
	}


	/** Catat perubahan profile PPPoE (isolir ↔ paket) ke Customerlog. */
	private static function logPppoeProfileChange($cid, $fromProfile, $toProfile)
	{
		if ($fromProfile === null || $fromProfile === $toProfile) return;

		try {
			$customer = \App\Customer::where('pppoe', $cid)->first();
			if (!$customer) return;

			\App\Customerlog::create([
				'id_customer' => $customer->id,
				'date'        => now(),
				'updated_by'  => 'System (MikroTik)',
				'topic'       => 'pppoe_status',
				'updates'     => json_encode([
					'PPPoE Profile' => ['old' => $fromProfile, 'new' => $toProfile],
				]),
			]);
		} catch (\Exception $e) {
			\Log::warning("Gagal mencatat Customerlog pppoe_profile untuk {$cid}: " . $e->getMessage());
		}
	}


	public static function mikrotik_remove($ip,$user,$pass,$port,$cid)
	{

		try {

			$client = new Client([


				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);



			$query =

			(new Query('/ppp/secret/print'))

			->where('name', $cid);


			$secrets = $client->query($query)->read();


			echo "Before update" . PHP_EOL;


			foreach ($secrets as $secret) {

    // enable
				$query = (new Query('/ppp/secret/remove'))
				->equal('.id', $secret['.id']);
				
				
				// ->equal('comment', 'enable by');

    // Update query ordinary have no return
				$client->query($query)->read();
				
    //print_r($secret['disabled']);



			}


		} catch (Exception $ex) {
			abort(404, 'Github Repository not found');
		}

	}



	public static function mikrotik_remove_active_connection($ip, $user, $pass, $port, $cid)
	{
		try {
			$client = new Client([
				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);

        // Ambil daftar koneksi aktif berdasarkan nama pengguna (cid)
			$query = (new Query('/ppp/active/print'))->where('name', $cid);
			$activeConnections = $client->query($query)->read();

			if (empty($activeConnections)) {
          //  echo "Tidak ada koneksi aktif untuk user: $cid" . PHP_EOL;
				return;
			}

        //echo "Sebelum penghapusan koneksi..." . PHP_EOL;

			foreach ($activeConnections as $connection) {
				echo "Menghapus koneksi aktif dengan ID: " . $connection['.id'] . PHP_EOL;

            // Hapus koneksi aktif berdasarkan ID
				$deleteQuery = (new Query('/ppp/active/remove'))
				->equal('.id', $connection['.id']);

				$client->query($deleteQuery)->read();
			}

       // echo "Koneksi aktif berhasil dihapus untuk user: $cid" . PHP_EOL;

		} catch (Exception $ex) {
       // echo "Terjadi kesalahan: " . $ex->getMessage();
		}
	}







	public function mikrotik_status($ip,$user,$pass,$port,$cid)
	{
		$result = 'unknow';

		$status['online']= 'Unknow';
		$status['ip'] = 'Unknow';
		$status['uptime']  = 'Unknow';
		$status['user']  = 'User not Found';
		$status['ip_count']  = 0;
		$status['profile']   = null;

		
		

		try {

			$client = new Client([
				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);
			$secret = $client->query((new Query('/ppp/secret/print'))->where('name', $cid))->read();
			if (!empty($secret)) {
				$disabled = $secret[0]['disabled'] ?? null;
				$status['user'] = match ($disabled) {
					'true'  => 'Disable',
					'false' => 'Enable',
					default => 'Unknown',
				};
				$status['profile'] = $secret[0]['profile'] ?? null;

            // 2. Get PPP active
				$active = $client->query((new Query('/ppp/active/print'))->where('name', $cid))->read();
				if (!empty($active)) {
					$status['online'] = 'Online';
					$status['ip']     = $active[0]['address'] ?? '-';
					$status['uptime'] = $active[0]['uptime'] ?? '-';

                // 3. Only check IP conflict if online
					if (!empty($status['ip'])) {
						$ips = $client->query((new Query('/ip/address/print'))->where('network', $status['ip']))->read();
						$status['ip_count'] = count($ips);
					}
				} else {
					$status['online'] = 'Offline';
				}

			}
		} catch (Exception $ex) {
			$result = 'Unknow';
		}



		return $status;


	}




	public function mikrotik_status_ip($ip,$user,$pass,$port,$ipc)
	{
		$result = 'unknow';

		$status['network'] = 'unknow';
		$status['interface']  = 'unknow';

		try {

			$client = new Client([
				'host' => $ip,
				'user' => $user,
				'pass' => $pass,
				'port' => $port
			]);

			$query =
			(new Query('/ip/address/print'))
			->where('network', '10.5.51.175');


			$response = $client->query($query)->read();
			$status = count($response);
// 			if (!empty($response)){

// 			// foreach ($response as $response) {
// 			// 	$status['network'] = ($response['network']);
// 			// 	$status['interface']  = ($response['interface']);
// 			// }
// $status=$response;


// }


		} catch (Exception $ex) {
			$result = 'Unknow';
		}



		return $status;


	}


}
