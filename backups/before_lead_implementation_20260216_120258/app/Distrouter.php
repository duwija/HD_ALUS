<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;
use \RouterOS\Client;
use \RouterOS\Query;
use Exception;

class Distrouter extends Model
{
	use softDeletes;
    //
	protected $fillable =['name','ip', 'port', 'web','user','password','created_at','updated_at','note','deleted_at'];
	public function customer()
	{
		return $this->hasmany('\App\Customer', 'id_distrouter');
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
					$client->query($query_enable)->read();
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


			echo "Before update" . PHP_EOL;


			foreach ($secrets as $secret) {

    // enable
				$query = (new Query('/ppp/secret/set'))
				->equal('.id', $secret['.id'])
				->equal('disabled', 'false');
				// ->equal('comment', 'enable by');

    // Update query ordinary have no return
				$client->query($query)->read();
				
    //print_r($secret['disabled']);



			}


		} catch (Exception $ex) {
			abort(404, 'Github Repository not found');
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


			echo "Before update" . PHP_EOL;


			foreach ($secrets as $secret) {

    // enable
				$query = (new Query('/ppp/secret/set'))
				->equal('.id', $secret['.id'])
				->equal('disabled', 'true');
				// ->equal('comment', 'enable by');

    // Update query ordinary have no return
				$client->query($query)->read();
				
    //print_r($secret['disabled']);




				$query_status =
				(new Query('/ppp/active/print'))
				->where('name', $cid);

				$response_status = $client->query($query_status)->read();
				if (!empty($response_status ))
				{


					foreach ($response_status as $response_status) {
						$query = (new Query('/ppp/active/remove'))
						->equal('.id', $response_status['.id']);
						$client->query($query)->read();
						
					}
				}
			}
			




			


		} catch (Exception $ex) {
			abort(404, 'Github Repository not found');
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
