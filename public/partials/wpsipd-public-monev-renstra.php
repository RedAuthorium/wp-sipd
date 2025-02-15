<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
global $wpdb;
$input = shortcode_atts( array(
	'id_skpd' => '',
	'tahun_anggaran' => '2022'
), $atts );

if(empty($input['id_skpd']))
{
	die('<h1>SKPD tidak ditemukan!</h1>');
}

$api_key = get_option('_crb_api_key_extension' );

function button_edit_monev($class=false)
{
	$ret = ' <span style="display: none;" data-id="'.$class.'" class="edit-monev"><i class="dashicons dashicons-edit"></i></span>';
	return $ret;
}

$rumus_indikator_db = $wpdb->get_results("SELECT * from data_rumus_indikator where active=1 and tahun_anggaran=".$input['tahun_anggaran'], ARRAY_A);
$rumus_indikator = '';
$keterangan_indikator_html = '';
foreach ($rumus_indikator_db as $k => $v)
{
	$rumus_indikator .= '<option value="'.$v['id'].'">'.$v['rumus'].'</option>';
	$keterangan_indikator_html .= '<li data-id="'.$v['id'].'" style="display: none;">'.$v['keterangan'].'</li>';
}
$sql = $wpdb->prepare("
	select 
		* 
	from data_unit 
	where tahun_anggaran=%d
		and id_skpd =".$input['id_skpd']."
		and active=1
	order by id_skpd ASC
", $input['tahun_anggaran']);
$unit = $wpdb->get_results($sql, ARRAY_A);
$pengaturan = $wpdb->get_results($wpdb->prepare("
	select 
		* 
	from data_pengaturan_sipd 
	where tahun_anggaran=%d
", $input['tahun_anggaran']), ARRAY_A);

$awal_rpjmd = 2018;
$tahun_anggaran_1 = 2019;
$tahun_anggaran_2 = 2020;
$tahun_anggaran_3 = 2021;
$tahun_anggaran_4 = 2022;
$tahun_anggaran_5 = 2023;
$akhir_rpjmd = 2023;
if(!empty($pengaturan))
{
	$awal_rpjmd = $pengaturan[0]['awal_rpjmd'];	
	$tahun_anggaran_1 = $awal_rpjmd+1;
	$tahun_anggaran_2 = $awal_rpjmd+2;
	$tahun_anggaran_3 = $awal_rpjmd+3;
	$tahun_anggaran_4 = $awal_rpjmd+4;
	$tahun_anggaran_5 = $awal_rpjmd+5;
	$akhir_rpjmd = $pengaturan[0]['akhir_rpjmd'];
}
$urut = $input['tahun_anggaran']-$awal_rpjmd;
$nama_pemda = $pengaturan[0]['daerah'];

$bulan = date('m');
$body_monev = '';
$data_all = array(
	'data' => array()
);
$current_user = wp_get_current_user();
$tujuan = $wpdb->get_results($wpdb->prepare("
			select 
				* 
			from data_renstra_tujuan 
			where 
				id_unit=%d and 
				active=1 and 
				tahun_anggaran=%d order by id",
				$input['id_skpd'], $input['tahun_anggaran']), ARRAY_A);
// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

if(!empty($tujuan)){
	foreach ($tujuan as $t => $tujuan_value) {
		$tujuan_key = $tujuan_value['id_bidang_urusan']."-".$tujuan_value['id_unik'];
		if(empty($data_all['data'][$tujuan_key])){

			$status_rpjmd='';
			if(!empty($tujuan_value['kode_sasaran_rpjm'])){
				$cek_status_rpjmd = $wpdb->get_results($wpdb->prepare("
					SELECT 
						id 
					FROM 
						data_rpjmd_sasaran 
					WHERE 
						active=1 AND
						id_unik=%s AND
						tahun_anggaran=%d",
						$tujuan_value['kode_sasaran_rpjm'],
						$input['tahun_anggaran']
					), ARRAY_A);

				if(!empty($cek_status_rpjmd)){
					$status_rpjmd='TERKONEKSI';
				}
			}

			$nama = explode("||", $tujuan_value['tujuan_teks']);
			$nama_bidang_urusan = explode("||", $tujuan_value['nama_bidang_urusan']);
			$data_all['data'][$tujuan_key] = array(
				'id_unit' => $tujuan_value['id_unit'],
				'status_rpjmd' => $status_rpjmd,
				'status' => '1',
				'nama' => $tujuan_value['tujuan_teks'],
				'nama_teks' => $nama[0],
				'id_unik' => $tujuan_value['id_unik'],
				'kode_sasaran_rpjm' => $tujuan_value['kode_sasaran_rpjm'],
				'kode_tujuan' => $tujuan_value['id_unik'],
				'urut_tujuan' => $tujuan_value['urut_tujuan'],
				'id_bidang_urusan' => $tujuan_value['id_bidang_urusan'],
				'kode_bidang_urusan' => $tujuan_value['kode_bidang_urusan'],
				'nama_bidang_urusan' => $tujuan_value['nama_bidang_urusan'],
				'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
				'indikator' => array(),
				'data' => array()
			);
		}
		
		if(!empty($tujuan_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['indikator'][$tujuan_value['id_unik_indikator']])){
			$data_all['data'][$tujuan_key]['indikator'][$tujuan_value['id_unik_indikator']] = array(
				'id' => $tujuan_value['id'],
				'id_unik_indikator' => $tujuan_value['id_unik_indikator'],
				'indikator_teks' => !empty($tujuan_value['indikator_teks']) ? $tujuan_value['indikator_teks'] : '-',
				'satuan' => !empty($tujuan_value['satuan']) ? $tujuan_value['satuan'] : "",
				'target_1' => !empty($tujuan_value['target_1']) ? $tujuan_value['target_1'] : "",
				'target_2' => !empty($tujuan_value['target_2']) ? $tujuan_value['target_2'] : "",
				'target_3' => !empty($tujuan_value['target_3']) ? $tujuan_value['target_3'] : "",
				'target_4' => !empty($tujuan_value['target_4']) ? $tujuan_value['target_4'] : "",
				'target_5' => !empty($tujuan_value['target_5']) ? $tujuan_value['target_5'] : "",
				'target_awal' => !empty($tujuan_value['target_awal']) ? $tujuan_value['target_awal'] : "",
				'target_akhir' => !empty($tujuan_value['target_akhir']) ? $tujuan_value['target_akhir'] : "",
			);
		}

		$sasaran = $wpdb->get_results($wpdb->prepare("
				select * from data_renstra_sasaran 
				where
					active=1 and
					id_unit=%d and
					tahun_anggaran=%d and
					kode_tujuan=%s and
					id_bidang_urusan=%d and
					urut_tujuan=%d order by id
			", $input['id_skpd'], $input['tahun_anggaran'], $tujuan_value['id_unik'], $tujuan_value['id_bidang_urusan'], $tujuan_value['urut_tujuan']), ARRAY_A);
		// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

		if(!empty($sasaran)){
			foreach ($sasaran as $s => $sasaran_value) {
				$nama = explode("||", $sasaran_value['sasaran_teks']);
				$sasaran_key = $nama[2];			

				if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key])){
					$nama_bidang_urusan = explode("||", $sasaran_value['nama_bidang_urusan']);
					$data_all['data'][$tujuan_key]['data'][$sasaran_key] = array(
						'id_unit' => $sasaran_value['id_unit'],
						'status' => '1',
						'nama' => $sasaran_value['sasaran_teks'],
						'nama_teks' => $nama[0],
						'id_unik' => $sasaran_value['id_unik'],
						'kode_sasaran' => $sasaran_key,
						'urut_sasaran' => $sasaran_value['urut_sasaran'],
						'kode_tujuan' => $sasaran_value['kode_tujuan'],
						'urut_tujuan' => $sasaran_value['urut_tujuan'],
						'id_bidang_urusan' => $sasaran_value['id_bidang_urusan'],
						'kode_bidang_urusan' => $sasaran_value['kode_bidang_urusan'],
						'nama_bidang_urusan' => $sasaran_value['nama_bidang_urusan'],
						'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
						'id_misi' => $sasaran_value['id_misi'],
						'id_visi' => $sasaran_value['id_visi'],
						'indikator' => array(),
						'data' => array()
					);

				}
					
				if(!empty($sasaran_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['indikator'][$sasaran_value['id_unik_indikator']])){
						$data_all['data'][$tujuan_key]['data'][$sasaran_key]['indikator'][$sasaran_value['id_unik_indikator']] = array(
					'id' => $sasaran_value['id'],
					'id_unik_indikator' => $sasaran_value['id_unik_indikator'],
					'indikator_teks' => !empty($sasaran_value['indikator_teks']) ? $sasaran_value['indikator_teks'] : '-',
					'satuan' => !empty($sasaran_value['satuan']) ? $sasaran_value['satuan'] : "",
					'target_1' => !empty($sasaran_value['target_1']) ? $sasaran_value['target_1'] : "",
					'target_2' => !empty($sasaran_value['target_2']) ? $sasaran_value['target_2'] : "",
					'target_3' => !empty($sasaran_value['target_3']) ? $sasaran_value['target_3'] : "",
					'target_4' => !empty($sasaran_value['target_4']) ? $sasaran_value['target_4'] : "",
					'target_5' => !empty($sasaran_value['target_5']) ? $sasaran_value['target_5'] : "",
					'target_awal' => !empty($sasaran_value['target_awal']) ? $sasaran_value['target_awal'] : "",
					'target_akhir' => !empty($sasaran_value['target_akhir']) ? $sasaran_value['target_akhir'] : "",
					);
				}

				$program = $wpdb->get_results($wpdb->prepare("
					select * from data_renstra_program 
					where
						active=1 and
						id_unit=%d and
						tahun_anggaran=%d and
						kode_sasaran=%s and
						urut_sasaran=%d and
						kode_tujuan=%s and 
						urut_tujuan=%d and 
						id_bidang_urusan=%d and
						id_misi=%d and
						id_visi=%d
						order by id
					", 
						$input['id_skpd'], 
						$input['tahun_anggaran'], 
						$nama[2], 
						$sasaran_value['urut_sasaran'],
						$sasaran_value['kode_tujuan'],
						$sasaran_value['urut_tujuan'],
						$sasaran_value['id_bidang_urusan'],
						$sasaran_value['id_misi'],
						$sasaran_value['id_visi']
				), ARRAY_A);
				// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

				if(!empty($program)){
					foreach ($program as $p => $program_value) {

						if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']])){
							$nama = explode("||", $program_value['nama_program']);
							$nama_bidang_urusan = explode("||", $program_value['nama_bidang_urusan']);
							$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']] = array(
								'id_unit' => $program_value['id_unit'],
								'status' => '1',
								'nama' => $program_value['nama_program'],
								'nama_teks' => $nama[0],
								'id_unik' => $program_value['id_unik'],
								'kode_program' => $program_value['kode_program'],
								'kode_sasaran' => $program_value['kode_sasaran'],
								'urut_sasaran' => $program_value['urut_sasaran'],
								'kode_tujuan' => $program_value['kode_tujuan'],
								'urut_tujuan' => $program_value['urut_tujuan'],
								'id_bidang_urusan' => $program_value['id_bidang_urusan'],
								'kode_bidang_urusan' => $program_value['kode_bidang_urusan'],
								'nama_bidang_urusan' => $program_value['nama_bidang_urusan'],
								'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
								'id_misi' => $program_value['id_misi'],
								'id_visi' => $program_value['id_visi'],
								'indikator' => array(),
								'data' => array()
							);
						}
							
						if(!empty($program_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']]['indikator'][$program_value['id_unik_indikator']])){

							$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']]['indikator'][$program_value['id_unik_indikator']] = array(
								'id' => $program_value['id'],
								'id_unik_indikator' => $program_value['id_unik_indikator'],
								'indikator_teks' => !empty($program_value['indikator']) ? $program_value['indikator'] : '-',
								'satuan' => !empty($program_value['satuan']) ? $program_value['satuan'] : "",
								'target_1' => !empty($program_value['target_1']) ? $program_value['target_1'] : "",
								'target_2' => !empty($program_value['target_2']) ? $program_value['target_2'] : "",
								'target_3' => !empty($program_value['target_3']) ? $program_value['target_3'] : "",
								'target_4' => !empty($program_value['target_4']) ? $program_value['target_4'] : "",
								'target_5' => !empty($program_value['target_5']) ? $program_value['target_5'] : "",
								'target_awal' => !empty($program_value['target_awal']) ? $program_value['target_awal'] : "",
								'target_akhir' => !empty($program_value['target_akhir']) ? $program_value['target_akhir'] : "",
								'pagu_1' => !empty($program_value['pagu_1']) ? $program_value['pagu_1'] : null,
								'pagu_2' => !empty($program_value['pagu_2']) ? $program_value['pagu_2'] : null,
								'pagu_3' => !empty($program_value['pagu_3']) ? $program_value['pagu_3'] : null,
								'pagu_4' => !empty($program_value['pagu_4']) ? $program_value['pagu_4'] : null,
								'pagu_5' => !empty($program_value['pagu_5']) ? $program_value['pagu_5'] : null
							);
						}

						$kegiatan = $wpdb->get_results($wpdb->prepare("
							select * from data_renstra_kegiatan 
							where
								active=1 and
								id_unit=%d and
								tahun_anggaran=%d and
								id_program=%d and
								kode_sasaran=%s and
								urut_sasaran=%d and
								kode_tujuan=%s and 
								urut_tujuan=%d and 
								id_bidang_urusan=%d and
								id_misi=%d and
								id_visi=%d
								order by id
							", 
								$input['id_skpd'], 
								$input['tahun_anggaran'], 
								$program_value['id_program'],
								$program_value['kode_sasaran'],
								$program_value['urut_sasaran'],
								$program_value['kode_tujuan'],
								$program_value['urut_tujuan'],
								$program_value['id_bidang_urusan'],
								$program_value['id_misi'],
								$program_value['id_visi']
						), ARRAY_A);
						// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

						if(!empty($kegiatan)){
							foreach ($kegiatan as $k => $kegiatan_value) {
								if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']]['data'][$kegiatan_value['kode_giat']])){
									$nama = explode("||", $kegiatan_value['nama_giat']);
									$nama_bidang_urusan = explode("||", $kegiatan_value['nama_bidang_urusan']);
									$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']]['data'][$kegiatan_value['kode_giat']] = array(
										'id_unit' => $kegiatan_value['id_unit'],
										'status' => '1',
										'nama' => $kegiatan_value['nama_giat'],
										'nama_teks' => $nama[0],
										'id_giat' => $kegiatan_value['id_giat'],
										'kode_giat' => $kegiatan_value['kode_giat'],
										'kode_program' => $kegiatan_value['kode_program'],
										'kode_sasaran' => $kegiatan_value['kode_sasaran'],
										'urut_sasaran' => $kegiatan_value['urut_sasaran'],
										'kode_tujuan' => $kegiatan_value['kode_tujuan'],
										'urut_tujuan' => $kegiatan_value['urut_tujuan'],
										'id_bidang_urusan' => $kegiatan_value['id_bidang_urusan'],
										'kode_bidang_urusan' => $kegiatan_value['kode_bidang_urusan'],
										'nama_bidang_urusan' => $kegiatan_value['nama_bidang_urusan'],
										'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
										'id_misi' => $kegiatan_value['id_misi'],
										'id_visi' => $kegiatan_value['id_visi'],
										'indikator' => array()
									);
								}

								if(!empty($kegiatan_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']]['data'][$kegiatan_value['kode_giat']]['indikator'][$kegiatan_value['id_unik_indikator']])){
									
									$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_value['kode_program']]['data'][$kegiatan_value['kode_giat']]['indikator'][$kegiatan_value['id_unik_indikator']] = array(
										'id' => $kegiatan_value['id'],
										'id_unik_indikator' => $kegiatan_value['id_unik_indikator'],
										'indikator_teks' => !empty($kegiatan_value['indikator']) ? $kegiatan_value['indikator'] : '-',
										'satuan' => !empty($kegiatan_value['satuan']) ? $kegiatan_value['satuan'] : "",
										'target_1' => !empty($kegiatan_value['target_1']) ? $kegiatan_value['target_1'] : "",
										'target_2' => !empty($kegiatan_value['target_2']) ? $kegiatan_value['target_2'] : "",
										'target_3' => !empty($kegiatan_value['target_3']) ? $kegiatan_value['target_3'] : "",
										'target_4' => !empty($kegiatan_value['target_4']) ? $kegiatan_value['target_4'] : "",
										'target_5' => !empty($kegiatan_value['target_5']) ? $kegiatan_value['target_5'] : "",
										'target_awal' => !empty($kegiatan_value['target_awal']) ? $kegiatan_value['target_awal'] : "",
										'target_akhir' => !empty($kegiatan_value['target_akhir']) ? $kegiatan_value['target_akhir'] : "",
										'pagu_1' => !empty($kegiatan_value['pagu_1']) ? $kegiatan_value['pagu_1'] : null,
										'pagu_2' => !empty($kegiatan_value['pagu_2']) ? $kegiatan_value['pagu_2'] : null,
										'pagu_3' => !empty($kegiatan_value['pagu_3']) ? $kegiatan_value['pagu_3'] : null,
										'pagu_4' => !empty($kegiatan_value['pagu_4']) ? $kegiatan_value['pagu_4'] : null,
										'pagu_5' => !empty($kegiatan_value['pagu_5']) ? $kegiatan_value['pagu_5'] : null
									);
								}
							}
						}
					}
				}
			}
		}
	} 
}

$sasaran = $wpdb->get_results($wpdb->prepare("
			select * from data_renstra_sasaran 
			where
				active=1 and
				id_unit=%d and
				tahun_anggaran=%d
			order by id
		", $input['id_skpd'], $input['tahun_anggaran']), ARRAY_A);

if(!empty($sasaran)){
	foreach ($sasaran as $s => $s_value) {
		$tujuan_key = $s_value['id_bidang_urusan'].'-'.$s_value['kode_tujuan'];
			
		$cek_tujuan = $wpdb->get_results($wpdb->prepare("
			select * from data_renstra_tujuan 
			where
				active=1 and
				id_unit=%d and
				tahun_anggaran=%d and
				id_bidang_urusan=%d and
				id_unik=%s and
				urut_tujuan=%d
			", 
			$input['id_skpd'],
			$input['tahun_anggaran'],
			$s_value['id_bidang_urusan'],
			$s_value['kode_tujuan'],
			$s_value['urut_tujuan']
		), ARRAY_A);

		if(!array_key_exists($tujuan_key, $data_all['data']) || empty($cek_tujuan)){

			$nama_bidang_urusan = explode("||", $s_value['nama_bidang_urusan']);
			$tujuan_key .= "-404"; // tambahkan kode 404 karena tidak ditemukannya data berdasarkan kode_tujuan dan urut_tujuan

			if(empty($data_all['data'][$tujuan_key])){

				$nama = explode("||", $s_value['tujuan_teks']);
				$data_all['data'][$tujuan_key] = array(
					'id_unit' => '',
					'status' => '0',
					'status_rpjmd' => '',
					'nama' => 'TUJUAN KOSONG => ' . $s_value['tujuan_teks'],
					'nama_teks' => 'TUJUAN KOSONG',
					'id_unik' => '',
					'kode_tujuan' => '',
					'urut_tujuan' => '',
					'id_bidang_urusan' => '',
					'kode_bidang_urusan' => '',
					'nama_bidang_urusan' => $s_value['nama_bidang_urusan'],
					'nama_bidang_urusan_teks' => '',
					'urut_tujuan' => '',
					'indikator' => array(),
						'data' => array()
				);
			}
		}

		$nama = explode("||", $s_value['sasaran_teks']);
		$sasaran_key = $nama[2];

		if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key])){
			$data_all['data'][$tujuan_key]['data'][$sasaran_key] = array(
				'id_unit' => $s_value['id_unit'],
				'status' => '0',
				'nama' => $s_value['sasaran_teks'],
				'nama_teks' => $nama[0],
				'id_unik' => $s_value['id_unik'],
				'kode_sasaran' => $sasaran_key,
				'urut_sasaran' => $s_value['urut_sasaran'],
				'kode_tujuan' => $s_value['kode_tujuan'],
				'urut_tujuan' => $s_value['urut_tujuan'],
				'id_bidang_urusan' => $s_value['id_bidang_urusan'],
				'kode_bidang_urusan' => $s_value['kode_bidang_urusan'],
				'nama_bidang_urusan' => $s_value['nama_bidang_urusan'],
				'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
				'id_misi' => $s_value['id_misi'],
				'id_visi' => $s_value['id_visi'],
				'indikator' => array(),
				'data' => array()
			);
		}

		if(!empty($s_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['indikator'][$s_value['id_unik_indikator']])){
			$data_all['data'][$tujuan_key]['data'][$sasaran_key]['indikator'][$s_value['id_unik_indikator']] = array(
				'id' => $s_value['id'],
				'id_unik_indikator' => $s_value['id_unik_indikator'],
				'indikator_teks' => !empty($s_value['indikator_teks']) ? $s_value['indikator_teks'].button_edit_monev($input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$keg['kode_tujuan'].'-'.$s_value['id_unik_indikator']) : '-',
				'satuan' => !empty($s_value['satuan']) ? $s_value['satuan'] : "",
				'target_1' => !empty($s_value['target_1']) ? $s_value['target_1'] : "",
				'target_2' => !empty($s_value['target_2']) ? $s_value['target_2'] : "",
				'target_3' => !empty($s_value['target_3']) ? $s_value['target_3'] : "",
				'target_4' => !empty($s_value['target_4']) ? $s_value['target_4'] : "",
				'target_5' => !empty($s_value['target_5']) ? $s_value['target_5'] : "",
				'target_awal' => !empty($s_value['target_awal']) ? $s_value['target_awal'] : "",
				'target_akhir' => !empty($s_value['target_akhir']) ? $s_value['target_akhir'] :"",
			);
		}

		$program = $wpdb->get_results($wpdb->prepare("
					select * from data_renstra_program 
					where
						active=1 and
						id_unit=%d and
						tahun_anggaran=%d and
						kode_sasaran=%s and
						urut_sasaran=%d and
						kode_tujuan=%s and 
						urut_tujuan=%d and 
						id_bidang_urusan=%d and
						id_misi=%d and
						id_visi=%d
						order by id
					", 
						$input['id_skpd'], 
						$input['tahun_anggaran'], 
						$sasaran_key, 
						$s_value['urut_sasaran'],
						$s_value['kode_tujuan'],
						$s_value['urut_tujuan'],
						$s_value['id_bidang_urusan'],
						$s_value['id_misi'],
						$s_value['id_visi']
			), ARRAY_A);
		// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

		if(!empty($program)){
			foreach ($program as $p => $p_value) {
		
				if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']])){
					$nama = explode("||", $p_value['nama_program']);
					$nama_bidang_urusan = explode("||", $p_value['nama_bidang_urusan']);
					$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']] = array(
						'id_unit' => $p_value['id_unit'],
						'status' => "0",
						'nama' => $p_value['nama_program'],
						'nama_teks' => $nama[0],
						'id_unik' => $p_value['id_unik'],
						'kode_program' => $p_value['kode_program'],
						'kode_sasaran' => $p_value['kode_sasaran'],
						'urut_sasaran' => $p_value['urut_sasaran'],
						'kode_tujuan' => $p_value['kode_tujuan'],
						'urut_tujuan' => $p_value['urut_tujuan'],
						'id_bidang_urusan' => $p_value['id_bidang_urusan'],
						'kode_bidang_urusan' => $p_value['kode_bidang_urusan'],
						'nama_bidang_urusan' => $p_value['nama_bidang_urusan'],
						'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
						'id_misi' => $p_value['id_misi'],
						'id_visi' => $p_value['id_visi'],
						'indikator' => array(),
						'data' => array()
					);
				}

				if(!empty($p_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['indikator'][$p_value['id_unik_indikator']])){
								
					$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['indikator'][$p_value['id_unik_indikator']] = array(
						'id' => $p_value['id'],
						'id_unik_indikator' => $p_value['id_unik_indikator'],
						'indikator_teks' => !empty($p_value['indikator']) ? $p_value['indikator'] : '-',
						'satuan' => !empty($p_value['satuan']) ? $p_value['satuan'] : "",
						'target_1' => !empty($p_value['target_1']) ? $p_value['target_1'] : "",
						'target_2' => !empty($p_value['target_2']) ? $p_value['target_2'] : "",
						'target_3' => !empty($p_value['target_3']) ? $p_value['target_3'] : "",
						'target_4' => !empty($p_value['target_4']) ? $p_value['target_4'] : "",
						'target_5' => !empty($p_value['target_5']) ? $p_value['target_5'] : "",
						'target_awal' => !empty($p_value['target_awal']) ? $p_value['target_awal'] : "",
						'target_akhir' => !empty($p_value['target_akhir']) ? $p_value['target_akhir'] : "",
						'pagu_1' => !empty($p_value['pagu_1']) ? $p_value['pagu_1'] : null,
						'pagu_2' => !empty($p_value['pagu_2']) ? $p_value['pagu_2'] : null,
						'pagu_3' => !empty($p_value['pagu_3']) ? $p_value['pagu_3'] : null,
						'pagu_4' => !empty($p_value['pagu_4']) ? $p_value['pagu_4'] : null,
						'pagu_5' => !empty($p_value['pagu_5']) ? $p_value['pagu_5'] : null
					);
				}

				$kegiatan = $wpdb->get_results($wpdb->prepare("
							select * from data_renstra_kegiatan 
							where
								active=1 and
								id_unit=%d and
								tahun_anggaran=%d and
								id_program=%d and
								kode_sasaran=%s and
								urut_sasaran=%d and
								kode_tujuan=%s and 
								urut_tujuan=%d and 
								id_bidang_urusan=%d and
								id_misi=%d and
								id_visi=%d
								order by id
							", 
								$input['id_skpd'], 
								$input['tahun_anggaran'], 
								$p_value['id_program'],
								$p_value['kode_sasaran'],
								$p_value['urut_sasaran'],
								$p_value['kode_tujuan'],
								$p_value['urut_tujuan'],
								$p_value['id_bidang_urusan'],
								$p_value['id_misi'],
								$p_value['id_visi']
				), ARRAY_A);
				// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

				if(!empty($kegiatan)){
					foreach ($kegiatan as $k => $k_value) {

						if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']])){
							
							$nama = explode("||", $k_value['nama_giat']);
							$nama_bidang_urusan = explode("||", $k_value['nama_bidang_urusan']);
							$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']] = array(
								'id_unit' => $k_value['id_unit'],
								'status' => "0",
								'nama' => $k_value['nama_giat'],
								'nama_teks' => $nama[0],
								'id_giat' => $k_value['id_giat'],
								'kode_giat' => $k_value['kode_giat'],
								'kode_program' => $k_value['kode_program'],
								'kode_sasaran' => $k_value['kode_sasaran'],
								'urut_sasaran' => $k_value['urut_sasaran'],
								'kode_tujuan' => $k_value['kode_tujuan'],
								'urut_tujuan' => $k_value['urut_tujuan'],
								'id_bidang_urusan' => $k_value['id_bidang_urusan'],
								'kode_bidang_urusan' => $k_value['kode_bidang_urusan'],
								'nama_bidang_urusan' => $k_value['nama_bidang_urusan'],
								'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
								'id_misi' => $k_value['id_misi'],
								'id_visi' => $k_value['id_visi'],
								'indikator' => array()
							);
						} 

						if(!empty($k_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']]['indikator'][$k_value['id_unik_indikator']])){

							$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']]['indikator'][$k_value['id_unik_indikator']] = array(
								'id' => $k_value['id'],
								'id_unik_indikator' => $k_value['id_unik_indikator'],
								'indikator_teks' => !empty($k_value['indikator']) ? $k_value['indikator'] : '-',
								'satuan' => !empty($k_value['satuan']) ? $k_value['satuan'] : "",
								'target_1' => !empty($k_value['target_1']) ? $k_value['target_1'] : "",
								'target_2' => !empty($k_value['target_2']) ? $k_value['target_2'] : "",
								'target_3' => !empty($k_value['target_3']) ? $k_value['target_3'] : "",
								'target_4' => !empty($k_value['target_4']) ? $k_value['target_4'] : "",
								'target_5' => !empty($k_value['target_5']) ? $k_value['target_5'] : "",
								'target_awal' => !empty($k_value['target_awal']) ? $k_value['target_awal'] : "",
								'target_akhir' => !empty($k_value['target_akhir']) ? $k_value['target_akhir'] : "",
								'pagu_1' => !empty($k_value['pagu_1']) ? $k_value['pagu_1'] : null,
								'pagu_2' => !empty($k_value['pagu_2']) ? $k_value['pagu_2'] : null,
								'pagu_3' => !empty($k_value['pagu_3']) ? $k_value['pagu_3'] : null,
								'pagu_4' => !empty($k_value['pagu_4']) ? $k_value['pagu_4'] : null,
								'pagu_5' => !empty($k_value['pagu_5']) ? $k_value['pagu_5'] : null
							);
						} 
					} 
				} 
			} 
		} 
	} 
} 

$program = $wpdb->get_results($wpdb->prepare("
					select * from data_renstra_program 
					where
						active=1 and
						id_unit=%d and
						tahun_anggaran=%d
					order by id
					", 
						$input['id_skpd'], 
						$input['tahun_anggaran']
			), ARRAY_A);

if(!empty($program)){
	foreach ($program as $p => $p_value) {
			
		$tujuan_key = $p_value['id_bidang_urusan'].'-'.$p_value['kode_tujuan'];
		$sasaran_key = !empty($p_value['kode_sasaran']) ? $p_value['kode_sasaran'] : 'KODE-KOSONG';
		$cek_sasaran = $wpdb->get_results($wpdb->prepare("
				select * from data_renstra_sasaran where
				active=1 and
				id_unit=%d and 
				tahun_anggaran=%d and
				id_bidang_urusan=%d and
				id_unik=%s and
				urut_sasaran=%d and
				kode_tujuan=%s and
				urut_tujuan=%d
				",
				$input['id_skpd'],
				$input['tahun_anggaran'],
				$p_value['id_bidang_urusan'],
				$p_value['kode_sasaran'],
				$p_value['urut_sasaran'],
				$p_value['kode_tujuan'],
				$p_value['urut_tujuan']
			), ARRAY_A);
		// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';die();

		if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]) || empty($cek_sasaran)){
				
			$nama_bidang_urusan = explode("||", $p_value['nama_bidang_urusan']);
			$tujuan_key .= "-404"; // tambahkan kode 404, asumsi tujuan kosong karena sasaran tidak ditemukan
			if(empty($data_all['data'][$tujuan_key])){
				$nama = explode("||", $p_value['tujuan_teks']);
				$data_all['data'][$tujuan_key] = array(
					'id_unit' => '',
					'status_rpjmd' => '',
					'status' => '0',
					'nama' => 'TUJUAN KOSONG => '.$p_value['tujuan_teks'],
					'nama_teks' => 'TUJUAN KOSONG',
					'id_unik' => '',
					'kode_tujuan' => '',
					'urut_tujuan' => '',
					'id_bidang_urusan' => '',
					'kode_bidang_urusan' => '',
					'nama_bidang_urusan' => $p_value['nama_bidang_urusan'],
					'nama_bidang_urusan_teks' => '',
					'urut_tujuan' => '',
					'indikator' => array(),
					'data' => array()
				);
			}

			if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key])){
				$nama = explode("||", $p_value['sasaran_teks']);
				$data_all['data'][$tujuan_key]['data'][$sasaran_key] = array(
					'id_unit' => '',
					'status' => '0',
					'nama' => 'SASARAN KOSONG => '.$p_value['sasaran_teks'],
					'nama_teks' => 'SASARAN KOSONG',
					'id_unik' => '',
					'kode_sasaran' => '',
					'urut_sasaran' => '',
					'kode_tujuan' => '',
					'urut_tujuan' => '',
					'id_bidang_urusan' => '',
					'kode_bidang_urusan' => '',
					'nama_bidang_urusan' => $p_value['nama_bidang_urusan'],
					'nama_bidang_urusan_teks' => '',
					'id_misi' => '',
					'id_visi' => '',
					'indikator' => array(),
					'data' => array()
				);
			}
		}

		$nama = explode("||", $p_value['nama_program']);
		if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']])){
			$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']] = array(
				'id_unit' => $p_value['id_unit'],
				'status' => "0",
				'nama' => $p_value['nama_program'],
				'nama_teks' => $nama[0],
				'id_unik' => $p_value['id_unik'],
				'kode_program' => $p_value['kode_program'],
				'urut_sasaran' => $p_value['urut_sasaran'],
				'kode_sasaran' => $sasaran_key,
				'kode_tujuan' => $p_value['kode_tujuan'],
				'urut_tujuan' => $p_value['urut_tujuan'],
				'id_bidang_urusan' => $p_value['id_bidang_urusan'],
				'kode_bidang_urusan' => $p_value['kode_bidang_urusan'],
				'nama_bidang_urusan' => $p_value['nama_bidang_urusan'],
				'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
				'id_misi' => $p_value['id_misi'],
				'id_visi' => $p_value['id_visi'],
				'indikator' => array(),
				'data' => array()
			);
		}

		if(!empty($p_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['indikator'][$p_value['id_unik_indikator']])){
								
			$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['indikator'][$p_value['id_unik_indikator']] = array(
				'id' => $p_value['id'],
				'id_unik_indikator' => $p_value['id_unik_indikator'],
				'indikator_teks' => !empty($p_value['indikator']) ? $p_value['indikator'] : '-',
				'satuan' => !empty($p_value['satuan']) ? $p_value['satuan'] : "",
				'target_1' => !empty($p_value['target_1']) ? $p_value['target_1'] : "",
				'target_2' => !empty($p_value['target_2']) ? $p_value['target_2'] : "",
				'target_3' => !empty($p_value['target_3']) ? $p_value['target_3'] : "",
				'target_4' => !empty($p_value['target_4']) ? $p_value['target_4'] : "",
				'target_5' => !empty($p_value['target_5']) ? $p_value['target_5'] : "",
				'target_awal' => !empty($p_value['target_awal']) ? $p_value['target_awal'] : "",
				'target_akhir' => !empty($p_value['target_akhir']) ? $p_value['target_akhir'] : "",
				'pagu_1' => !empty($p_value['pagu_1']) ? $p_value['pagu_1'] : null,
				'pagu_2' => !empty($p_value['pagu_2']) ? $p_value['pagu_2'] : null,
				'pagu_3' => !empty($p_value['pagu_3']) ? $p_value['pagu_3'] : null,
				'pagu_4' => !empty($p_value['pagu_4']) ? $p_value['pagu_4'] : null,
				'pagu_5' => !empty($p_value['pagu_5']) ? $p_value['pagu_5'] : null
			);
		}

		$kegiatan = $wpdb->get_results($wpdb->prepare("
					select * from data_renstra_kegiatan 
					where
						active=1 and
						id_unit=%d and
						tahun_anggaran=%d and
						id_program=%d and
						kode_sasaran=%s and
						urut_sasaran=%d and
						kode_tujuan=%s and 
						urut_tujuan=%d and 
						id_bidang_urusan=%d and
						id_misi=%d and
						id_visi=%d
						order by id
					", 
						$input['id_skpd'], 
						$input['tahun_anggaran'], 
						$p_value['id_program'],
						$p_value['kode_sasaran'],
						$p_value['urut_sasaran'],
						$p_value['kode_tujuan'],
						$p_value['urut_tujuan'],
						$p_value['id_bidang_urusan'],
						$p_value['id_misi'],
						$p_value['id_visi']
				), ARRAY_A);
		// echo '<pre>';print_r($wpdb->last_query);echo '</pre>';

		if(!empty($kegiatan)){
			foreach ($kegiatan as $k => $k_value) {
				
				if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']])){

					$nama = explode("||", $k_value['nama_giat']);
					$nama_bidang_urusan = explode("||", $k_value['nama_bidang_urusan']);
					$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']] = array(
						'id_unit' => $k_value['id_unit'],
						'status' => "0",
						'nama' => $k_value['nama_giat'],
						'nama_teks' => $nama[0],
						'id_giat' => $k_value['id_giat'],
						'kode_giat' => $k_value['kode_giat'],
						'kode_program' => $k_value['kode_program'],
						'kode_sasaran' => $k_value['kode_sasaran'],
						'urut_sasaran' => $k_value['urut_sasaran'],
						'kode_tujuan' => $k_value['kode_tujuan'],
						'urut_tujuan' => $k_value['urut_tujuan'],
						'id_bidang_urusan' => $k_value['id_bidang_urusan'],
						'kode_bidang_urusan' => $k_value['kode_bidang_urusan'],
						'nama_bidang_urusan' => $k_value['nama_bidang_urusan'],
						'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
						'id_misi' => $k_value['id_misi'],
						'id_visi' => $k_value['id_visi'],
						'indikator' => array()
					);
				}

				if(!empty($k_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']]['indikator'][$k_value['id_unik_indikator']])){

					$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$p_value['kode_program']]['data'][$k_value['kode_giat']]['indikator'][$k_value['id_unik_indikator']] = array(
						'id' => $k_value['id'],
						'id_unik_indikator' => $k_value['id_unik_indikator'],
						'indikator_teks' => !empty($k_value['indikator']) ? $k_value['indikator'] : '-',
						'satuan' => !empty($k_value['satuan']) ? $k_value['satuan'] : "",
						'target_1' => !empty($k_value['target_1']) ? $k_value['target_1'] : "",
						'target_2' => !empty($k_value['target_2']) ? $k_value['target_2'] : "",
						'target_3' => !empty($k_value['target_3']) ? $k_value['target_3'] : "",
						'target_4' => !empty($k_value['target_4']) ? $k_value['target_4'] : "",
						'target_5' => !empty($k_value['target_5']) ? $k_value['target_5'] : "",
						'target_awal' => !empty($k_value['target_awal']) ? $k_value['target_awal'] : "",
						'target_akhir' => !empty($k_value['target_akhir']) ? $k_value['target_akhir'] : "",
						'pagu_1' => !empty($k_value['pagu_1']) ? $k_value['pagu_1'] : null,
						'pagu_2' => !empty($k_value['pagu_2']) ? $k_value['pagu_2'] : null,
						'pagu_3' => !empty($k_value['pagu_3']) ? $k_value['pagu_3'] : null,
						'pagu_4' => !empty($k_value['pagu_4']) ? $k_value['pagu_4'] : null,
						'pagu_5' => !empty($k_value['pagu_5']) ? $k_value['pagu_5'] : null	
					);
				}
			}
		}
	}	
}

$kegiatan = $wpdb->get_results($wpdb->prepare("
							select * from data_renstra_kegiatan 
							where
								active=1 and
								id_unit=%d and
								tahun_anggaran=%d
								order by id
							", 
								$input['id_skpd'], 
								$input['tahun_anggaran']
						), ARRAY_A);

if(!empty($kegiatan)){
	foreach ($kegiatan as $k => $k_value) {

		$nama_bidang_urusan = explode("||", $k_value['nama_bidang_urusan']);
		$tujuan_key = $k_value['id_bidang_urusan'].'-'.$k_value['kode_tujuan'];
		$sasaran_key = !empty($k_value['kode_sasaran']) ? $k_value['kode_sasaran'] : 'KODE-KOSONG';
		$program_key = !empty($k_value['kode_program']) ? $k_value['kode_program'] : 'KODE-KOSONG';

		$cek_program = $wpdb->get_results($wpdb->prepare("
				select * from data_renstra_program 
				where
					active=1 and
					id_unit=%d and
					tahun_anggaran=%d and
					id_program=%d and
					kode_sasaran=%s and
					urut_sasaran=%d and
					kode_tujuan=%s and 
					urut_tujuan=%d and 
					id_bidang_urusan=%d and
					id_misi=%d and
					id_visi=%d
					order by id
				", 
					$input['id_skpd'], 
					$input['tahun_anggaran'], 
					$k_value['id_program'],
					$k_value['kode_sasaran'],
					$k_value['urut_sasaran'],
					$k_value['kode_tujuan'],
					$k_value['urut_tujuan'],
					$k_value['id_bidang_urusan'],
					$k_value['id_misi'],
					$k_value['id_visi']
			), ARRAY_A);

		if(!array_key_exists($program_key, $data_all['data'][$tujuan_key]['data'][$sasaran_key]['data']) || empty($cek_program)){

			if(empty($data_all['data'][$tujuan_key])){
				$tujuan_key .= "-404";
				$nama = explode("||", $k_value['tujuan_teks']);
				$data_all['data'][$tujuan_key] = array(
					'id_unit' => '',
					'status_rpjmd' => '',
					'status' => '0',
					'nama' => 'TUJUAN KOSONG => '.$k_value['tujuan_teks'],
					'nama_teks' => 'TUJUAN KOSONG',
					'id_unik' => '',
					'kode_tujuan' => '',
					'urut_tujuan' => '',
					'id_bidang_urusan' => '',
					'kode_bidang_urusan' => '',
					'nama_bidang_urusan' => $k_value['nama_bidang_urusan'],
					'nama_bidang_urusan_teks' => '',
					'urut_tujuan' => '',
					'indikator' => array(),
					'data' => array()
				);
			}

			if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key])){
				$sasaran_key .= "-404";
				$nama = explode("||", $k_value['sasaran_teks']);
				$data_all['data'][$tujuan_key]['data'][$sasaran_key] = array(
					'id_unit' => '',
					'status' => '0',
					'nama' => 'SASARAN KOSONG => '.$k_value['sasaran_teks'],
					'nama_teks' => 'SASARAN KOSONG',
					'id_unik' => '',
					'kode_sasaran' => '',
					'urut_sasaran' => '',
					'kode_tujuan' => '',
					'urut_tujuan' => '',
					'id_bidang_urusan' => '',
					'kode_bidang_urusan' => '',
					'nama_bidang_urusan' => $k_value['nama_bidang_urusan'],
					'nama_bidang_urusan_teks' => '',
					'id_misi' => '',
					'id_visi' => '',
					'indikator' => array(),
					'data' => array()
				);
			}

			if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_key])){
				$program_key .= "-404";
				$nama = explode("||", $k_value['nama_program']);
				$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_key] = array(
					'id_unit' => "",
					'status' => "0",
					'nama' => "PROGRAM KOSONG => ".$k_value['nama_program'],
					'nama_teks' => "PROGRAM KOSONG",
					'id_unik' => "",
					'kode_program' => "",
					'kode_sasaran' => "",
					'kode_tujuan' => "",
					'urut_tujuan' => "",
					'id_bidang_urusan' => "",
					'kode_bidang_urusan' => "",
					'nama_bidang_urusan' => $k_value['nama_bidang_urusan'],
					'nama_bidang_urusan_teks' => '',
					'id_misi' => "",
					'id_visi' => "",
					'indikator' => array(),
					'data' => array()
				);
			}
		}

		if(empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_key]['data'][$k_value['kode_giat']])){

			$nama = explode("||", $k_value['nama_giat']);
			$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_key]['data'][$k_value['kode_giat']] = array(
				'id_unit' => $k_value['id_unit'],
				'status' => "0",
				'nama' => $k_value['nama_giat'],
				'nama_teks' => $nama[0],
				'id_giat' => $k_value['id_giat'],
				'kode_giat' => $k_value['kode_giat'],
				'kode_program' => $k_value['kode_program'],
				'kode_sasaran' => $k_value['kode_sasaran'],
				'urut_sasaran' => $k_value['urut_sasaran'],
				'kode_tujuan' => $k_value['kode_tujuan'],
				'urut_tujuan' => $k_value['urut_tujuan'],
				'id_bidang_urusan' => $k_value['id_bidang_urusan'],
				'kode_bidang_urusan' => $k_value['kode_bidang_urusan'],
				'nama_bidang_urusan' => $k_value['nama_bidang_urusan'],
				'nama_bidang_urusan_teks' => $nama_bidang_urusan[0],
				'id_misi' => $k_value['id_misi'],
				'id_visi' => $k_value['id_visi'],
				'indikator' => array()
			);
		}

		if(!empty($k_value['id_unik_indikator']) && empty($data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_key]['data'][$k_value['kode_giat']]['indikator'][$k_value['id_unik_indikator']])){

			$data_all['data'][$tujuan_key]['data'][$sasaran_key]['data'][$program_key]['data'][$k_value['kode_giat']]['indikator'][$k_value['id_unik_indikator']] = array(
				'id' => $k_value['id'],
				'id_unik_indikator' => $k_value['id_unik_indikator'],
				'indikator_teks' => !empty($k_value['indikator']) ? $k_value['indikator'] : '-',
				'satuan' => !empty($k_value['satuan']) ? $k_value['satuan'] : "",
				'target_1' => !empty($k_value['target_1']) ? $k_value['target_1'] : "",
				'target_2' => !empty($k_value['target_2']) ? $k_value['target_2'] : "",
				'target_3' => !empty($k_value['target_3']) ? $k_value['target_3'] : "",
				'target_4' => !empty($k_value['target_4']) ? $k_value['target_4'] : "",
				'target_5' => !empty($k_value['target_5']) ? $k_value['target_5'] : "",
				'target_awal' => !empty($k_value['target_awal']) ? $k_value['target_awal'] : "",
				'target_akhir' => !empty($k_value['target_akhir']) ? $k_value['target_akhir'] : "",
				'pagu_1' => !empty($k_value['pagu_1']) ? $k_value['pagu_1'] : null,
				'pagu_2' => !empty($k_value['pagu_2']) ? $k_value['pagu_2'] : null,
				'pagu_3' => !empty($k_value['pagu_3']) ? $k_value['pagu_3'] : null,
				'pagu_4' => !empty($k_value['pagu_4']) ? $k_value['pagu_4'] : null,
				'pagu_5' => !empty($k_value['pagu_5']) ? $k_value['pagu_5'] : null
			);
		}
	}
}

// echo '<pre>';print_r($data_all['data']);echo '</pre>'; die();
foreach ($data_all['data'] as $key => $tujuan) 
	{

			$indikator = array(
					'indikator_teks' => array(),
					'target_akhir' => array(),
					'target_'.$urut => array(),
					'satuan' => array(),
				);
			foreach ($tujuan['indikator'] as $k => $v) {
				$indikator_teks = $v['indikator_teks'].button_edit_monev($input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$v['id'].'-'.$urut.'-1'.'-'.$bulan);

				$indikator['indikator_teks'][]=$indikator_teks;
				$indikator['target_akhir'][]=$v['target_akhir'];
				$indikator['target_'.$urut][]=$v['target_'.$urut];
				$indikator['satuan'][]=$v['satuan'];
			}

			$status_rpjmd = !empty($tujuan['status_rpjmd']) ? '<a href="javascript:void(0)" onclick="show_rpjm(\''.$input['tahun_anggaran'].'\', \''.$input['id_skpd'].'\', \''.$tujuan['kode_sasaran_rpjm'].'\')">
			            	'.$tujuan['status_rpjmd'].'
			            	</a>' : $tujuan['status_rpjmd'];

			$backgroundColor = !empty($tujuan['status']) ? '' : '#ffdbdb';
			$backgroundColor = !empty($tujuan['status_rpjmd']) ? '' : '#f7d2a1';
			$body_monev .= '
				<tr class="tujuan tr-tujuan" data-kode="" style="background-color:'.$backgroundColor.'">
		            <td class="kiri kanan bawah text_blok">'.$status_rpjmd.'</td>
		            <td class="kiri kanan bawah text_blok">
		            	<span class="debug-renstra">'.$tujuan['nama_bidang_urusan'].'</span>
		            	<span class="nondebug-renstra">'.$tujuan['nama_bidang_urusan_teks'].'</span>
		            </td>
		            <td class="text_kiri kanan bawah text_blok">
		            	<span class="debug-renstra">'.$tujuan['nama'].'</span>
		            	<span class="nondebug-renstra">'.$tujuan['nama_teks'].'</span>
		            </td>
		            <td class="text_tengah kanan bawah text_blok"></td>
		            <td class="kanan bawah text_blok"></td>
		            <td class="kanan bawah text_blok"></td>
		            <td class="kanan bawah text_blok indikator rumus_indikator">'.implode(' </br><br> ', $indikator['indikator_teks']).'</td>
		            <td class="text_tengah kanan bawah text_blok total_renstra">'.implode(' </br><br> ', $indikator['target_akhir']).'</td>
		            <td class="text_tengah kanan bawah text_blok total_renstra">'.implode(' </br><br> ', $indikator['satuan']).'</td>
		            <td class="text_kanan kanan bawah text_blok total_renstra"></td>
		            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
		            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
		            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
		            <td class="text_tengah kanan bawah text_blok total_renstra target_indikator">'.implode(' </br><br> ', $indikator['target_'.$urut]).'</td>
		            <td class="text_tengah kanan bawah text_blok total_renstra satuan_indikator">'.implode(' </br><br> ', $indikator['satuan']).'</td>
		            <td class="text_kanan kanan bawah text_blok total_renstra pagu_renstra" data-pagu=""></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
		            <td class="text_kanan kanan bawah text_blok triwulan_1"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
		            <td class="text_kanan kanan bawah text_blok triwulan_2"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
		            <td class="text_kanan kanan bawah text_blok triwulan_3"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
		            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
		            <td class="text_kanan kanan bawah text_blok triwulan_4"></td>
		            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
		            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
		            <td class="text_kanan kanan bawah text_blok realisasi_renstra pagu_renstra_realisasi" data-pagu=""></td>
		            <td class="text_tengah kanan bawah text_blok capaian_renstra"></td>
		            <td class="text_kanan kanan bawah text_blok capaian_renstra"></td>
		            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
		            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
		            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
		            <td class="text_tengah kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
		            <td class="text_kanan kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
	        		<td class="kanan bawah text_blok">'.$unit[0]['nama_skpd'].'</td>
		        </tr>';		        

		foreach ($tujuan['data'] as $key => $sasaran) 
		{
				$indikator = array(
					'indikator_teks' => array(),
					'target_akhir' => array(),
					'target_'.$urut => array(),
					'satuan' => array(),
				);
				foreach ($sasaran['indikator'] as $k => $v) {
					$indikator_teks = $v['indikator_teks'].button_edit_monev($input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$v['id'].'-'.$urut.'-2'.'-'.$bulan);

					$indikator['indikator_teks'][]=$indikator_teks;
					$indikator['target_akhir'][]=$v['target_akhir'];
					$indikator['target_'.$urut][]=$v['target_'.$urut];
					$indikator['satuan'][]=$v['satuan'];
				}

				$backgroundColor = !empty($sasaran['status']) ? '' : '#ffdbdb';
				$body_monev .= '
					<tr class="sasaran tr-sasaran" data-kode="" style="background-color:'.$backgroundColor.'">
			            <td class="kiri kanan bawah text_blok"></td>
			            <td class="kiri kanan bawah text_blok">
			            	<span class="debug-renstra">'.$sasaran['nama_bidang_urusan'].'</span>
			            </td>
			            <td class="text_kiri kanan bawah text_blok">
			            	<span class="debug-renstra">'.$tujuan['nama'].'</span>
			            </td>
			            <td class="text_kiri kanan bawah text_blok">
			            	<span class="debug-renstra">'.$sasaran['nama'].'</span>
			            	<span class="nondebug-renstra">'.$sasaran['nama_teks'].'</span>
			            </td>
			            <td class="kanan bawah text_blok"></td>
			            <td class="kanan bawah text_blok nama"></td>
			            <td class="kanan bawah text_blok indikator rumus_indikator">'.implode(' </br><br> ', $indikator['indikator_teks']).'</td>
			            <td class="text_tengah kanan bawah text_blok total_renstra">'.implode(' </br><br> ', $indikator['target_akhir']).'</td>
			            <td class="text_tengah kanan bawah text_blok total_renstra">'.implode(' </br><br> ', $indikator['satuan']).'</td>
			            <td class="text_kanan kanan bawah text_blok total_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
			            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
			            <td class="text_tengah kanan bawah text_blok total_renstra target_indikator">'.implode(' </br><br> ', $indikator['target_'.$urut]).'</td>
			            <td class="text_tengah kanan bawah text_blok total_renstra satuan_indikator">'.implode(' </br><br> ', $indikator['satuan']).'</td>
			            <td class="text_kanan kanan bawah text_blok total_renstra pagu_renstra" data-pagu=""></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_1"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_2"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_3"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_4"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
			            <td class="text_kanan kanan bawah text_blok realisasi_renstra pagu_renstra_realisasi" data-pagu=""></td>
			            <td class="text_tengah kanan bawah text_blok capaian_renstra"></td>
			            <td class="text_kanan kanan bawah text_blok capaian_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
			            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
			            <td class="text_tengah kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
			            <td class="text_kanan kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
		        		<td class="kanan bawah text_blok">'.$unit[0]['nama_skpd'].'</td>
			        </tr>';

			foreach ($sasaran['data'] as $key => $program) 
			{
				$backgroundColor = !empty($program['status']) ? '' : '#ffdbdb';
				$body_monev .= '
					<tr class="program tr-program" data-kode="'.$input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$program['kode_tujuan'].'-'.$program['kode_sasaran'].'-'.$program['kode_program'].'" style="background-color:'.$backgroundColor.'">
			            <td class="kiri kanan bawah text_blok"></td>
			            <td class="text_kiri kanan bawah text_blok">
			            	<span class="debug-renstra">'.$program['nama_bidang_urusan'].'</span>
			            </td>
			            <td class="text_kiri kanan bawah text_blok">
			            	<span class="debug-renstra">'.$tujuan['nama'].'</span>
			            </td>
			            <td class="text_kiri kanan bawah text_blok">
			            	<span class="debug-renstra">'.$sasaran['nama'].'</span>
			            </td>
			            <td class="kanan bawah text_blok" nama>
			            	<span class="debug-renstra">'.$program['nama'].'</span>
			            	<span class="nondebug-renstra">'.$program['nama_teks'].'</span>
			            </td>
			            <td class="kanan bawah text_blok"></td>
			            <td class="kanan bawah text_blok indikator rumus_indikator"></td>
			            <td class="text_tengah kanan bawah text_blok total_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok total_renstra"></td>
			            <td class="text_kanan kanan bawah text_blok total_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
			            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
			            <td class="text_tengah kanan bawah text_blok total_renstra target_indikator"></td>
			            <td class="text_tengah kanan bawah text_blok total_renstra satuan_indikator"></td>
			            <td class="text_kanan kanan bawah text_blok total_renstra pagu_renstra" data-pagu=""></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_1"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_2"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_3"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
			            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
			            <td class="text_kanan kanan bawah text_blok triwulan_4"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
			            <td class="text_kanan kanan bawah text_blok realisasi_renstra pagu_renstra_realisasi" data-pagu=""></td>
			            <td class="text_tengah kanan bawah text_blok capaian_renstra"></td>
			            <td class="text_kanan kanan bawah text_blok capaian_renstra"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
			            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
			            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
			            <td class="text_tengah kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
			            <td class="text_kanan kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
		        		<td class="kanan bawah text_blok">'.$unit[0]['nama_skpd'].'</td>
			        </tr>';

				foreach ($program['indikator'] as $k => $v) {
					$indikator_teks = $v['indikator_teks'].button_edit_monev($input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$v['id'].'-'.$urut.'-3'.'-'.$bulan);

					$body_monev .= '
						<tr class="program tr-ind-program" data-kode="'.$input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$program['kode_tujuan'].'-'.$program['kode_sasaran'].'-'.$program['kode_program'].'" style="background-color:'.$backgroundColor.'">
				            <td class="kiri kanan bawah text_blok"></td>
				            <td class="text_kiri kanan bawah text_blok">
				            	<span class="debug-renstra">'.$program['nama_bidang_urusan'].'</span>
				            </td>
				            <td class="text_kiri kanan bawah text_blok">
				            	<span class="debug-renstra">'.$tujuan['nama'].'</span>
				            </td>
				            <td class="text_kiri kanan bawah text_blok">
				            	<span class="debug-renstra">'.$sasaran['nama'].'</span>
				            </td>
				            <td class="kanan bawah text_blok" nama>
				            	<span class="data-renstra">'.$program['nama_teks'].'</span>
				            	<span class="debug-renstra">'.$program['nama'].'</span>
				            </td>
				            <td class="kanan bawah text_blok"></td>
				            <td class="kanan bawah text_blok indikator rumus_indikator">'.$indikator_teks.'</td>
				            <td class="text_tengah kanan bawah text_blok total_renstra">'.$v['target_akhir'].'</td>
				            <td class="text_tengah kanan bawah text_blok total_renstra">'.$v['satuan'].'</td>
				            <td class="text_kanan kanan bawah text_blok total_renstra"></td>
				            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
				            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
				            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_lalu"></td>
				            <td class="text_tengah kanan bawah text_blok total_renstra target_indikator">'.$v['target_'.$urut].'</td>
				            <td class="text_tengah kanan bawah text_blok total_renstra satuan_indikator">'.$v['satuan'].'</td>
				            <td class="text_kanan kanan bawah text_blok total_renstra pagu_renstra" data-pagu="'.$v['pagu_'.$urut].'">'.number_format($v['pagu_'.$urut],2,',','.').'</td>
				            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_1"></td>
				            <td class="text_kanan kanan bawah text_blok triwulan_1"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_2"></td>
				            <td class="text_kanan kanan bawah text_blok triwulan_2"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_3"></td>
				            <td class="text_kanan kanan bawah text_blok triwulan_3"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
				            <td class="text_tengah kanan bawah text_blok triwulan_4"></td>
				            <td class="text_kanan kanan bawah text_blok triwulan_4"></td>
				            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
				            <td class="text_tengah kanan bawah text_blok realisasi_renstra"></td>
				            <td class="text_kanan kanan bawah text_blok realisasi_renstra pagu_renstra_realisasi" data-pagu=""></td>
				            <td class="text_tengah kanan bawah text_blok capaian_renstra"></td>
				            <td class="text_kanan kanan bawah text_blok capaian_renstra"></td>
				            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
				            <td class="text_tengah kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
				            <td class="text_kanan kanan bawah text_blok realisasi_renstra_tahun_berjalan"></td>
				            <td class="text_tengah kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
				            <td class="text_kanan kanan bawah text_blok capaian_renstra_tahun_berjalan"></td>
			        		<td class="kanan bawah text_blok">'.$unit[0]['nama_skpd'].'</td>
				        </tr>';
				}


				foreach ($program['data'] as $key => $kegiatan) 
				{

					$backgroundColor = !empty($kegiatan['status']) ? '' : '#ffdbdb';
					$body_monev .= '
						<tr class="kegiatan tr-kegiatan" data-kode="" style="background-color:'.$backgroundColor.'">
				            <td class="kiri kanan bawah"></td>
				            <td class="kiri kanan bawah">
				            	<span class="debug-renstra">'.$kegiatan['nama_bidang_urusan'].'</span>
				            </td>
				            <td class="text_kiri kanan bawah">
				            	<span class="debug-renstra">'.$tujuan['nama'].'</span>
				            </td>
				            <td class="text_kiri kanan bawah">
				            	<span class="debug-renstra">'.$sasaran['nama'].'</span>
				            </td>
				            <td class="kanan bawah">
				            	<span class="debug-renstra">'.$program['nama'].'</span>
				            </td>
				            <td class="kanan bawah nama">
				            	<span class="debug-renstra">'.$kegiatan['nama'].'</span>
				            	<span class="nondebug-renstra">'.$kegiatan['nama_teks'].'</span>
				            </td>
				            <td class="kanan bawah indikator rumus_indikator"></td>
				            <td class="text_tengah kanan bawah total_renstra"></td>
				            <td class="text_tengah kanan bawah total_renstra"></td>
				            <td class="text_kanan kanan bawah total_renstra"></td>
				            <td class="text_tengah kanan bawah realisasi_renstra_tahun_lalu"></td>
				            <td class="text_tengah kanan bawah realisasi_renstra_tahun_lalu"></td>
				            <td class="text_kanan kanan bawah realisasi_renstra_tahun_lalu"></td>
				            <td class="text_tengah kanan bawah total_renstra target_indikator"></td>
				            <td class="text_tengah kanan bawah total_renstra satuan_indikator"></td>
				            <td class="text_kanan kanan bawah total_renstra pagu_renstra" data-pagu=""></td>
				            <td class="text_tengah kanan bawah triwulan_1"></td>
				            <td class="text_tengah kanan bawah triwulan_1"></td>
				            <td class="text_kanan kanan bawah triwulan_1"></td>
				            <td class="text_tengah kanan bawah triwulan_2"></td>
				            <td class="text_tengah kanan bawah triwulan_2"></td>
				            <td class="text_kanan kanan bawah triwulan_2"></td>
				            <td class="text_tengah kanan bawah triwulan_3"></td>
				            <td class="text_tengah kanan bawah triwulan_3"></td>
				            <td class="text_kanan kanan bawah triwulan_3"></td>
				            <td class="text_tengah kanan bawah triwulan_4"></td>
				            <td class="text_tengah kanan bawah triwulan_4"></td>
				            <td class="text_kanan kanan bawah triwulan_4"></td>
				            <td class="text_tengah kanan bawah realisasi_renstra"></td>
				            <td class="text_tengah kanan bawah realisasi_renstra"></td>
				            <td class="text_kanan kanan bawah realisasi_renstra pagu_renstra_realisasi" data-pagu=""></td>
				            <td class="text_tengah kanan bawah capaian_renstra"></td>
				            <td class="text_kanan kanan bawah capaian_renstra"></td>
				            <td class="text_tengah kanan bawah realisasi_renstra_tahun_berjalan"></td>
				            <td class="text_tengah kanan bawah realisasi_renstra_tahun_berjalan"></td>
				            <td class="text_kanan kanan bawah realisasi_renstra_tahun_berjalan"></td>
				            <td class="text_tengah kanan bawah capaian_renstra_tahun_berjalan"></td>
				            <td class="text_kanan kanan bawah capaian_renstra_tahun_berjalan"></td>
			        		<td class="kanan bawah">'.$unit[0]['nama_skpd'].'</td>
				        </tr>';

				    foreach ($kegiatan['indikator'] as $k => $v) {
						$indikator_teks = $v['indikator_teks'].button_edit_monev($input['tahun_anggaran'].'-'.$input['id_skpd'].'-'.$v['id'].'-'.$urut.'-4'.'-'.$bulan);
						
						$backgroundColor = !empty($kegiatan['status']) ? '' : '#ffdbdb';
						$body_monev .= '
							<tr class="kegiatan tr-ind-kegiatan" data-kode="" style="background-color:'.$backgroundColor.'">
					            <td class="kiri kanan bawah"></td>
					            <td class="kiri kanan bawah">
					            	<span class="debug-renstra">'.$kegiatan['nama_bidang_urusan'].'</span>
					            </td>
					            <td class="text_kiri kanan bawah">
					            	<span class="debug-renstra">'.$tujuan['nama'].'</span>
					            </td>
					            <td class="text_kiri kanan bawah">
					            	<span class="debug-renstra">'.$sasaran['nama'].'</span>
					            </td>
					            <td class="kanan bawah">
					            	<span class="debug-renstra">'.$program['nama'].'</span>
					            </td>
					            <td class="kanan bawah nama">
					            	<span class="data-renstra">'.$kegiatan['nama_teks'].'</span>
					            	<span class="debug-renstra">'.$kegiatan['nama'].'</span>
					            </td>
					            <td class="kanan bawah indikator rumus_indikator">'.$indikator_teks.'</td>
					            <td class="text_tengah kanan bawah total_renstra">'.$v['target_akhir'].'</td>
					            <td class="text_tengah kanan bawah total_renstra">'.$v['satuan'].'</td>
					            <td class="text_kanan kanan bawah total_renstra"></td>
					            <td class="text_tengah kanan bawah realisasi_renstra_tahun_lalu"></td>
					            <td class="text_tengah kanan bawah realisasi_renstra_tahun_lalu"></td>
					            <td class="text_kanan kanan bawah realisasi_renstra_tahun_lalu"></td>
					            <td class="text_tengah kanan bawah total_renstra target_indikator">'.$v['target_'.$urut].'</td>
					            <td class="text_tengah kanan bawah total_renstra satuan_indikator">'.$v['satuan'].'</td>
					            <td class="text_kanan kanan bawah total_renstra pagu_renstra" data-pagu="">'.number_format($v['pagu_'.$urut], 2,',','.').'</td>
					            <td class="text_tengah kanan bawah triwulan_1"></td>
					            <td class="text_tengah kanan bawah triwulan_1"></td>
					            <td class="text_kanan kanan bawah triwulan_1"></td>
					            <td class="text_tengah kanan bawah triwulan_2"></td>
					            <td class="text_tengah kanan bawah triwulan_2"></td>
					            <td class="text_kanan kanan bawah triwulan_2"></td>
					            <td class="text_tengah kanan bawah triwulan_3"></td>
					            <td class="text_tengah kanan bawah triwulan_3"></td>
					            <td class="text_kanan kanan bawah triwulan_3"></td>
					            <td class="text_tengah kanan bawah triwulan_4"></td>
					            <td class="text_tengah kanan bawah triwulan_4"></td>
					            <td class="text_kanan kanan bawah triwulan_4"></td>
					            <td class="text_tengah kanan bawah realisasi_renstra"></td>
					            <td class="text_tengah kanan bawah realisasi_renstra"></td>
					            <td class="text_kanan kanan bawah realisasi_renstra pagu_renstra_realisasi" data-pagu=""></td>
					            <td class="text_tengah kanan bawah capaian_renstra"></td>
					            <td class="text_kanan kanan bawah capaian_renstra"></td>
					            <td class="text_tengah kanan bawah realisasi_renstra_tahun_berjalan"></td>
					            <td class="text_tengah kanan bawah realisasi_renstra_tahun_berjalan"></td>
					            <td class="text_kanan kanan bawah realisasi_renstra_tahun_berjalan"></td>
					            <td class="text_tengah kanan bawah capaian_renstra_tahun_berjalan"></td>
					            <td class="text_kanan kanan bawah capaian_renstra_tahun_berjalan"></td>
				        		<td class="kanan bawah">'.$unit[0]['nama_skpd'].'</td>
					        </tr>';
					}
				}
			}
		}
	}

?>

<style type="text/css">
	table th, #modal-monev th {
		vertical-align: middle;
	}
	body {
		overflow: auto;
	}
	td[contenteditable="true"] {
	    background: #ff00002e;
	}
	.terkoneksi_rpjmd {
		background-color: aqua;
	}
	.debug-renstra, .data-renstra{
		display: none;
	}
	.action-checkbox {
		margin-left: 20px;
	}
</style>
<input type="hidden" value="<?php echo get_option('_crb_api_key_extension' ); ?>" id="api_key">
<input type="hidden" value="<?php echo $input['tahun_anggaran']; ?>" id="tahun_anggaran">
<input type="hidden" value="<?php echo $unit[0]['id_skpd']; ?>" id="id_skpd">
<h4 style="text-align: center; margin: 0; font-weight: bold;">Monitoring dan Evaluasi Rencana Strategis <br><?php echo $unit[0]['kode_skpd'].'&nbsp;'.$unit[0]['nama_skpd'].'<br>Tahun '.$input['tahun_anggaran'].' '.$nama_pemda; ?></h4>
<div id="cetak" title="Laporan MONEV RENJA" style="padding: 5px; overflow: auto; height: 80vh;">
	<table cellpadding="2" cellspacing="0" style="font-family:\'Open Sans\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif; border-collapse: collapse; font-size: 70%; border: 0; table-layout: fixed;" contenteditable="false">
		<thead>
			<tr>
				<th rowspan="5" style="width: 100px;" class='atas kiri kanan bawah text_tengah text_blok'>Status Koneksi RPJM</th>
				<th rowspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Bidang Urusan</th>
				<th rowspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Tujuan</th>
				<th rowspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Sasaran</th>
				<th rowspan="2" style="width: 250px;" class='atas kanan bawah text_tengah text_blok'>Program</th>
				<th rowspan="2" style="width: 250px;" class='atas kanan bawah text_tengah text_blok'>Kegiatan</th>
				<th rowspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Indikator Kinerja Tujuan, Sasaran, Program(outcome) dan Kegiatan (output)</th>
				<th rowspan="2" colspan="3" style="width: 300px;" class='atas kanan bawah text_tengah text_blok'>Target Renstra SKPD pada Tahun <?php echo $awal_rpjmd; ?> s/d <?php echo $akhir_rpjmd; ?> (periode Renstra SKPD)</th>
				<th rowspan="2" colspan="3" style="width: 300px;" class='atas kanan bawah text_tengah text_blok'>Realisasi Capaian Kinerja Renstra SKPD sampai dengan Renja SKPD Tahun Lalu</th>
				<th rowspan="2" colspan="3" style="width: 300px;" class='atas kanan bawah text_tengah text_blok'>Target kinerja dan anggaran Renja SKPD Tahun Berjalan Tahun <?php echo $input['tahun_anggaran']; ?> yang dievaluasi</th>
				<th colspan="12" style="width: 1200px;" class='atas kanan bawah text_tengah text_blok'>Realisasi Kinerja Pada Triwulan</th>
				<th rowspan="2" colspan="3" style="width: 300px;" class='atas kanan bawah text_tengah text_blok'>Realisasi Capaian Kinerja dan Anggaran Renja SKPD yang dievaluasi</th>
				<th rowspan="2" colspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Tingkat Capaian Kinerja dan Realisasi Anggaran Renja yang dievaluasi (%)</th>
				<th rowspan="2" colspan="3" style="width: 300px;" class='atas kanan bawah text_tengah text_blok'>Realisasi Kinerja dan Anggaran Renstra SKPD s/d Tahun <?php echo $input['tahun_anggaran']; ?> (Akhir Tahun Pelaksanaan Renja SKPD)</th>
				<th rowspan="2" colspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Tingkat Capaian Kinerja dan Realisasi Anggaran Renstra SKPD s/d tahun <?php echo $input['tahun_anggaran']; ?> (%)</th>
				<th rowspan="2" style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Unit OPD Penanggung Jawab</th>
			</tr>
			<tr>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>I</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>II</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>III</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>VI</th>
			</tr>
			<tr>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>0</th>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>1</th>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>2</th>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>3</th>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>4</th>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>5</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>6</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>7</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>8</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>9</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>10</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>11</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>12</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>13 = 8+9+10+11</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>14 = 12/7x100</th>
				<th colspan="3" class='atas kanan bawah text_tengah text_blok'>15 = 6 + 12</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>16 = 14/5 x100</th>
				<th rowspan="3" class='atas kanan bawah text_tengah text_blok'>17</th>
			</tr>
			<tr>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th colspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>K</th>
				<th rowspan="2" class='atas kanan bawah text_tengah text_blok'>Rp</th>
			</tr>
			<tr>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
				<th class='atas kanan bawah text_tengah text_blok'>Volume</th>
				<th class='atas kanan bawah text_tengah text_blok'>Satuan</th>
			</tr>
		</thead>
		<tbody>
			<?php echo $body_monev; ?>
		</tbody>
	</table>
</div>

<div class="hide-print" id="catatan_dokumentasi" style="max-width: 1200px; margin: auto;">
	<h4 style="margin: 30px 0 10px; font-weight: bold;">Catatan Dokumentasi:</h4>
	<ul>
		<li>Data dengan latar belakang warna orange menandakan Tujuan Renstra tidak terhubung dengan sasaran RPJM.</li>
		<li>Data dengan latar belakang warna merah menandakan Tujuan Renstra atau Sasaran Renstra tidak terhubung.</li>
		<li>Debug Cascading Renstra digunakan untuk menampilkan detail data dari TUJUAN hingga KEGIATAN.</li>
		<li>Status Koneksi RPJM menunjukan keterkaitan antara TUJUAN RENSTRA dengan SASARAN RPJM dan dapat diakses untuk melihat detail hierarkinya.</li>
	</ul>
</div>

<div class="modal fade" id="modal-monev" tabindex="-1" role="dialog" data-backdrop="static" aria-hidden="true">'
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content"style="min-width: 850px";>
            <div class="modal-header bgpanel-theme">
                <h4 style="margin: 0;" class="modal-title" id="">Edit MONEV Indikator Renstra Per Bulan</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span><i class="dashicons dashicons-dismiss"></i></span></button>
            </div>
            <div class="modal-body">
            	<form>
            		<input type="hidden" id="id_indikator">
            		<input type="hidden" id="type_indikator">
            		<input type="hidden" id="target_indikator">
                  	<div class="form-group">
                  		<table class="table table-bordered">
                  			<tbody>
                  				<tr>
                  					<th style="width: 200px;">Tujuan / Sasaran / Program / Kegiatan</th>
                  					<td id="monev-nama"></td>
                  				</tr>
                  				<tr>
                  					<td colspan="2">
                  						<table class="display-indikator-renstra">
                  							<thead>
                  								<tr>
					              					<th class="text_tengah" colspan="2" rowspan="2">Indikator</th>
					              					<th class="text_tengah" style="width: 100px;" colspan="5">Target</th>
					              					<th class="text_tengah" style="width: 100px;" rowspan="2">Satuan</th>
					              					<th class="text_tengah" style="width: 140px;" rowspan="2" colspan="2">Pagu (Rp)<br>Tahun <?php echo $input['tahun_anggaran']; ?></th>
												</tr>
												<tr>
													<th><?php echo $tahun_anggaran_1; ?></th>
													<th><?php echo $tahun_anggaran_2; ?></th>
													<th><?php echo $tahun_anggaran_3; ?></th>
													<th><?php echo $tahun_anggaran_4; ?></th>
													<th><?php echo $tahun_anggaran_5; ?></th>
												</tr>
                  							</thead>
                  							<tbody id="monev-body-renstra">
                  							</tbody>
                  						</table>
                  					</td>
                  				</tr>
                  				<tr>
                  					<td colspan="2">
                  						<table>
                  							<thead>
                  								<tr>
                  									<th class="text_tengah">Hitung Capaian Indikator</th>
                  								</tr>
                  							</thead>
                  							<tbody>
                  								<tr>
                  									<td>
				                  						<select style="width: 100%;" id="hitung_indikator">
				                  							<option value="1">Automatis</option>
				                  							<option value="0">Manual</option>
				                  						</select>
				                  						<ul id="helptext_hitung_indikator" style="margin: 10px 0 0 30px;">
				                  							<li data-id="1" style="display: none;">Capaian indikator dihitung secara otomatis oleh system</li>
				                  							<li data-id="0" style="display: none;">Capaian indikator diisi manual</li>
				                  						</ul>
				                  					</td>
                  								</tr>
                  							</tbody>
                  						</table>
                  					</td>
                  				</tr>
                  				<tr>
                  					<td colspan="2">
                  						<table id="table_rumus_indikator">
                  							<thead>
                  								<tr>
                  									<th class="text_tengah">Pilih Rumus Indikator</th>
                  								</tr>
                  							</thead>
                  							<tbody>
                  								<tr>
                  									<td>
				                  						<select style="width: 100%;" id="rumus_indikator">
				                  							<?php echo $rumus_indikator; ?>
				                  						</select>
				                  						<ul id="helptext_rumus_indikator" style="margin: 10px 0 0 30px;">
				                  							<?php echo $keterangan_indikator_html; ?>
				                  						</ul>
				                  					</td>
                  								</tr>
                  							</tbody>
                  						</table>
                  					</td>
                  				</tr>
                  				<tr>
                  					<td colspan="2">
                  						<table>
                  							<thead>
                  								<tr>
		              								<th class="text_tengah">Bulan</th>
		              								<th class="text_tengah" style="width: 300px;">Realisasi (Rp.)</th>
		              								<th class="text_tengah" style="width: 200px;">Realisasi Target</th>
		              								<th class="text_tengah" style="width: 200px;">Capaian Target (%)</th>
		              								<th class="text_tengah" style="width: 200px;">Keterangan / Permasalahan / Saran</th>
		              							</tr>
                  								<tr>
		              								<th class="text_tengah">1</th>
		              								<th class="text_tengah">2</th>
		              								<th class="text_tengah">3</th>
		              								<th class="text_tengah">4</th>
		              								<th class="text_tengah">5</th>
		              							</tr>
                  							</thead>
                  							<tbody id="monev-body-realisasi-renstra"></tbody>
                  						</table>
                  					</td>
                  				</tr>
                  			</tbody>
                  		</table>
                  	</div>
                </form>
            </div>
            <div class="modal-footer">
            	<button type="button" class="btn btn-success simpan-monev-renstra">Simpan</button>
				<button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-rpjmd" tabindex="-1" role="dialog" data-backdrop="static" aria-hidden="true">'
    <div class="modal-dialog" style="min-width:1200px" role="document">
        <div class="modal-content">
            <div class="modal-header bgpanel-theme">
                <h5 class="modal-title" id="exampleModalLabel" style="margin: 0 auto; text-align:center; font-weight: bold"></h5>
            </div>
            <div class="modal-body">
            	<table cellpadding="2" cellspacing="0" style="font-family:\'Open Sans\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif; border-collapse: collapse; font-size: 70%; border: 0; table-layout: fixed;" contenteditable="false">
						<thead>
							<tr>
								<th style="width: 100px;" class='atas kanan bawah text_tengah text_blok'>Visi</th>
								<th style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Misi</th>
								<th style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Tujuan</th>
								<th style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Sasaran</th>
								<th style="width: 250px;" class='atas kanan bawah text_tengah text_blok'>Program</th>
								<th style="width: 200px;" class='atas kanan bawah text_tengah text_blok'>Indikator RPJMD (Tujuan, Sasaran, Program)</th>
							</tr>
						</thead>
						<tbody id="body-rpjmd">
						</tbody>
					</table>
            </div>
            <div class="modal-footer">
            	<button type="button" class="btn btn-danger" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
	run_download_excel();
	var data_all = <?php echo json_encode($data_all); ?>;
	console.log(data_all);

	jQuery(document).on('ready', function(){
		var aksi = ''
			+'<h3 style="margin-top: 20px;">SETTING</h3>'
			+'<label class="action-checkbox"><input type="checkbox" onclick="edit_monev_indikator(this);"> Edit Monev indikator</label>&nbsp;'
			+'<label class="action-checkbox"><input type="checkbox" onclick="debug_renstra(this);"> Debug Cascading Renstra</label>'
			+'<label  class="action-checkbox"><input type="checkbox" onclick="setting_sakip(this);"> Setting SAKIP</label>'
			+'<label class="action-checkbox">'
				+'Sembunyikan Baris '
				+'<select id="sembunyikan-baris" onchange="sembunyikan_baris(this);" style="padding: 5px 10px; min-width: 200px;">'
					+'<option value="">Pilih Baris</option>'
					+'<option value="tr-tujuan">Tujuan</option>'
					+'<option value="tr-sasaran">Sasaran</option>'
					+'<option value="tr-program">Program</option>'
					+'<option value="tr-kegiatan">Kegiatan</option>'
				+'</select>'
			+'</label>'
		jQuery('#action-sipd').append(aksi);
		jQuery('#rumus_indikator').on('click', function(){
			setRumus(jQuery(this).val());
		});
		jQuery('#hitung_indikator').on('click', function(){
			setHitungCapaian(jQuery(this).val());
			if(jQuery(this).val()==1){
				jQuery("#table_rumus_indikator").show();
				for (var i = 1; i <= <?php echo $bulan ?>; i++) {
					jQuery("#realisasi_target_bulan_"+i).attr('onkeypress','onlyNumber(event)');
					jQuery("#capaian_target_bulan_"+i).attr('contenteditable',"false");
				}
			}else if(jQuery(this).val()==0){
				jQuery("#table_rumus_indikator").hide();
				for (var i = 1; i <= <?php echo $bulan ?>; i++) {
					jQuery("#realisasi_target_bulan_"+i).attr('onkeypress','');
					jQuery("#capaian_target_bulan_"+i).attr('contenteditable',"true");
				}
			}
		});
		jQuery('.edit-monev').on('click', function(){
			jQuery('#wrap-loading').show();
			var tr = jQuery(this).closest('tr');
			var nama = tr.find('span.data-renstra').text();
			var kode = jQuery(this).attr("data-id");
			var rinc_kode = kode.split('-');

			jQuery("#table_rumus_indikator").show();
			jQuery("#monev-body-renstra").html('');
			jQuery.ajax({
				url: '<?php echo admin_url("admin-ajax.php") ?>',
				type: 'post',
				data:{
					'action' : 'get_monev_renstra',
	          		"api_key": "<?php echo $api_key; ?>",
					'tahun_anggaran' : rinc_kode[0],
					'id_skpd': rinc_kode[1],
					'id': rinc_kode[2],
					'urut': rinc_kode[3],
					'type_indikator': rinc_kode[4],
					'bulan': rinc_kode[5],
				},
				dataType:'json',
				success:function(res){
					jQuery("#monev-nama").html(nama);
					jQuery("#monev-body-renstra").html(res.body_renstra);
					jQuery("#monev-body-realisasi-renstra").html(res.body_realisasi_renstra);
					jQuery("#id_indikator").val(rinc_kode[2]);
					jQuery("#type_indikator").val(rinc_kode[4]);
					jQuery("#target_indikator").val(res.target_indikator);
					jQuery("#sum_realisasi_anggaran").text(res.sum_realisasi_anggaran);

					if(res.hitung_indikator==0){
						jQuery("#table_rumus_indikator").hide();
						for (var i = 1; i <= <?php echo $bulan ?>; i++) {
							jQuery("#realisasi_target_bulan_"+i).attr('onkeypress','');
							jQuery("#capaian_target_bulan_"+i).attr('contenteditable',"true");
						}
					} else if (res.hitung_indikator==1){
						jQuery("#table_rumus_indikator").show();
						for (var i = 1; i <= <?php echo $bulan ?>; i++) {
							jQuery("#realisasi_target_bulan_"+i).attr('onkeypress','onlyNumber(event)');
							jQuery("#capaian_target_bulan_"+i).attr('contenteditable',"false");
						}
					}

					setHitungCapaian(res.hitung_indikator);
					setRumus(res.rumus_indikator);
					jQuery('#modal-monev').modal('show');
					jQuery('#wrap-loading').hide();
				}
			})

		});

		jQuery(".simpan-monev-renstra").on('click', function(){
			jQuery('#wrap-loading').show();
			var realisasi_anggaran = {};
			var realisasi_target = {};
			var capaian_indikator = {};
			var keterangan = {};

			for (var i = 1; i <= 12; i++) {
				realisasi_anggaran['realisasi_anggaran_bulan_'+i] = jQuery("#realisasi_anggaran_bulan_"+i).text().trim();
				realisasi_target['realisasi_target_bulan_'+i] = jQuery("#realisasi_target_bulan_"+i).text().trim();
				capaian_indikator['capaian_bulan_'+i] = jQuery("#capaian_target_bulan_"+i).text().trim();
				keterangan['keterangan_bulan_'+i] = jQuery("#keterangan_bulan_"+i).text().trim();
			}
			
			jQuery.ajax({
				url:'<?php echo admin_url("admin-ajax.php") ?>',
				type:'post',
				data:{
					'action' : 'save_monev_renstra',
			        "api_key" : "<?php echo $api_key; ?>",
					'id_indikator' : jQuery("#id_indikator").val(),
					'id_rumus_indikator' : jQuery("#rumus_indikator").val(),
					'type_indikator' : jQuery("#type_indikator").val(),
					'hitung_indikator' : jQuery("#hitung_indikator").val(),
					'realisasi_anggaran':realisasi_anggaran,
					'realisasi_target':realisasi_target,
					'capaian_indikator':capaian_indikator,
					'keterangan':keterangan,
					'tahun_anggaran' : jQuery("#tahun_anggaran").val()
				},
				dataType:'json',
				success:function(result){
					alert(result.message);
					jQuery('#wrap-loading').hide();
				}
			});
		})
	});

	jQuery("#rumus_indikator").on('change', function(){
		var rumus = jQuery(this).val();

		var total_realisasi_anggaran=0;
		for (var i = 1; i <= <?php echo $bulan; ?>; i++) {
			total_realisasi_anggaran += parseInt(jQuery("#realisasi_anggaran_bulan_"+i).text());
		}
	})
	function edit_monev_indikator(that){
		if(jQuery(that).is(':checked')){
			jQuery('.edit-monev').show();
		}else{
			jQuery('.edit-monev').hide();
		}
	}
	function setRumus(id){
		jQuery('#tipe_indikator').val(id);
		jQuery('#helptext_rumus_indikator li').hide();
		jQuery('#helptext_rumus_indikator li[data-id="'+id+'"]').show();
	}
	function setHitungCapaian(id){
		jQuery('#hitung_indikator').val(id);
		jQuery('#helptext_hitung_indikator li').hide();
		jQuery('#helptext_hitung_indikator li[data-id="'+id+'"]').show();
	}
	function setTotalMonev(){
		var i = 1;
		var total = 0;
		jQuery("#monev-body-realisasi-renstra .realisasi_anggaran").map(function(){
			if(i <= <?php echo $bulan; ?>){
				total += parseInt(jQuery("#realisasi_anggaran_bulan_"+i).text());
			}
			i++;
		})
		jQuery("#sum_realisasi_anggaran").text(total);
	}
	function setCapaianMonev(that){

		var tag = jQuery(this).attr('id');
		var hitung = jQuery("#hitung_indikator").val();
		var target_indikator = +jQuery('#target_indikator').text();
		var rumus_indikator = jQuery('#rumus_indikator').val();
		var target_indikator = jQuery("#target_indikator").val();
		var target = target_indikator.split(" ");

		if(hitung==1){
			if(rumus_indikator == 3 && that){
				var id = jQuery(that).attr('id');
				var bulan = +id.replace('realisasi_target_bulan_', '');
				if(bulan > 1){
					var val_bulan_sebelumnya = +jQuery('#realisasi_target_bulan_'+(bulan-1)).text();
					var val = jQuery(that).text();
					if(val < val_bulan_sebelumnya && val > 0){
						jQuery(that).text(val_bulan_sebelumnya);
						alert('Untuk rumus indikator persentasi, nilai target tidak boleh lebih kecil dari bulan sebelumnya!');
					}
				}
			}
			var realisasi = 0;
			var target_batas_bulan_input = 0;
			var bulan = 0;
			jQuery('#monev-body-realisasi-renstra .realisasi_target_bulanan').map(function(){
				bulan++;
				var realisasi_target_bulanan = +jQuery(this).text();
				realisasi += realisasi_target_bulanan;
				if(<?php echo $bulan; ?> == bulan){
					target_batas_bulan_input = realisasi_target_bulanan;
				}
			});
			
			var capaian = 0;
			if(rumus_indikator == 1){
				if(target[0] > 0){
					capaian = Math.round((realisasi/target[0])*10000)/100;
				}
			}else if(rumus_indikator == 2){
				realisasi = target_batas_bulan_input;
				if(realisasi > 0){
					capaian = Math.round((target[0]/realisasi)*10000)/100;
				}
			}else if(rumus_indikator == 3){
				realisasi = target_batas_bulan_input;
				if(target[0] > 0){
					capaian = Math.round((realisasi/target[0])*10000)/100;
				}
			}
			jQuery("#capaian_indikator").text(capaian);
		}
	}
	function sembunyikan_baris(that){
		var val = jQuery(that).val();
		var tr_tujuan = jQuery('.tr-tujuan');
		var tr_sasaran = jQuery('.tr-sasaran');
		var tr_program = jQuery('.tr-program');
		var tr_kegiatan = jQuery('.tr-kegiatan');
		tr_tujuan.show();
		tr_sasaran.show();
		tr_program.show();
		tr_kegiatan.show();
		if(val == 'tr-tujuan'){
			tr_tujuan.hide();
			tr_sasaran.hide();
			tr_program.hide();
			tr_kegiatan.hide();
		}else if(val == 'tr-sasaran'){
			tr_sasaran.hide();
			tr_program.hide();
			tr_kegiatan.hide();
		}else if(val == 'tr-program'){
			tr_program.hide();
			tr_kegiatan.hide();
		}else if(val == 'tr-kegiatan'){
			tr_kegiatan.hide();
		} 
	}
	function debug_renstra(that){
		if(jQuery(that).is(':checked')){
			jQuery('.debug-renstra').show();
			jQuery('.nondebug-renstra').hide();
		}else{
			jQuery('.debug-renstra').hide();
			jQuery('.nondebug-renstra').show();
		}
	}

	function show_rpjm(tahun_anggaran, id_unit, kode_sasaran_rpjm){
		jQuery('#wrap-loading').show();
		var modal = jQuery("#modal-rpjmd");
		jQuery.ajax({

			url:"<?php echo admin_url("admin-ajax.php") ?>",
			type:"post",
			data:{
				"action": "get_data_rpjm",
	          	"api_key": "<?php echo $api_key; ?>",
				"tahun_anggaran": tahun_anggaran,
				"id_unit": id_unit,
				"kode_sasaran_rpjm" : kode_sasaran_rpjm
			},
			dataType:"json",
			success: function(response){
				if(response.status==1){
					modal.find("#body-rpjmd").html('');
			  		modal.find("#body-rpjmd").html(response.body_rpjm);
					modal.find('.modal-title').html('RPJMD <br> <?php echo $unit[0]['kode_skpd'].'&nbsp;'.$unit[0]['nama_skpd'].'<br>Tahun '.$input['tahun_anggaran'].' <br> '.$nama_pemda; ?>');
				}		
				modal.modal('show');
				jQuery('#wrap-loading').hide();
			}
		});

	}
</script>