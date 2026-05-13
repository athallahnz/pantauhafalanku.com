<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string|null $log_name
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property array|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $causer
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $subject
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereCauserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereCauserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereLogName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ActivityLog whereUserAgent($value)
 */
	class ActivityLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $santri_id
 * @property int $musyrif_id
 * @property \Illuminate\Support\Carbon|null $tanggal_setoran
 * @property int|null $hafalan_template_id
 * @property string|null $nilai_label
 * @property string $status
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $nilai_display
 * @property-read string $rentang_label
 * @property-read \App\Models\Musyrif $musyrif
 * @property-read \App\Models\Santri $santri
 * @property-read \App\Models\HafalanTemplate|null $template
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan query()
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereHafalanTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereNilaiLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereSantriId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereTanggalSetoran($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hafalan whereUpdatedAt($value)
 */
	class Hafalan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $juz
 * @property string $tahap
 * @property int $urutan
 * @property string|null $label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $display_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hafalan> $hafalans
 * @property-read int|null $hafalans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurahSegment> $segments
 * @property-read int|null $segments_count
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereJuz($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereTahap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HafalanTemplate whereUrutan($value)
 */
	class HafalanTemplate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $logo
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $head_name
 * @property string|null $established_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereEstablishedYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereHeadName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereLogo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|InstitutionSetting whereWebsite($value)
 */
	class InstitutionSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama_kelas
 * @property string|null $deskripsi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Santri> $santris
 * @property-read int|null $santris_count
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas query()
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas whereDeskripsi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas whereNamaKelas($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Kelas whereUpdatedAt($value)
 */
	class Kelas extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $kelas_id
 * @property string $nama
 * @property string|null $alamat
 * @property string|null $pendidikan_terakhir
 * @property string|null $domisili
 * @property string|null $halaqah
 * @property string|null $kode
 * @property string|null $keterangan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $lama_mengabdi
 * @property string|null $amanah_lain
 * @property string|null $metode_alquran
 * @property int $is_sertifikasi_ummi
 * @property int|null $tahun_sertifikasi
 * @property string|null $siap_sertifikasi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MusyrifAttendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hafalan> $hafalans
 * @property-read int|null $hafalans_count
 * @property-read \App\Models\Kelas|null $kelas
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Santri> $santri
 * @property-read int|null $santri_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Santri> $santris
 * @property-read int|null $santris_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif query()
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereAmanahLain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereDomisili($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereHalaqah($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereIsSertifikasiUmmi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereKelasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereKode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereLamaMengabdi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereMetodeAlquran($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif wherePendidikanTerakhir($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereSiapSertifikasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereTahunSertifikasi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Musyrif whereUserId($value)
 */
	class Musyrif extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $musyrif_id
 * @property string $type
 * @property \Illuminate\Support\Carbon $attendance_at
 * @property string $photo_path
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $accuracy
 * @property string|null $address_text
 * @property string|null $ip_address
 * @property string|null $device_info
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Musyrif $musyrif
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance query()
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereAccuracy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereAddressText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereAttendanceAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereDeviceInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance wherePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MusyrifAttendance whereUpdatedAt($value)
 */
	class MusyrifAttendance extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $santri_id
 * @property int $musyrif_id
 * @property int|null $hafalan_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property int $poin
 * @property string|null $keterangan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Hafalan|null $hafalan
 * @property-read \App\Models\Musyrif $musyrif
 * @property-read \App\Models\Santri $santri
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint query()
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereHafalanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereKeterangan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint wherePoin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereSantriId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PelanggaranPoint whereUpdatedAt($value)
 */
	class PelanggaranPoint extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $photo
 * @property string|null $full_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProfileSetting whereUserId($value)
 */
	class ProfileSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $kelas_id
 * @property string $nama
 * @property string|null $nis
 * @property \Illuminate\Support\Carbon|null $tanggal_lahir
 * @property string|null $jenis_kelamin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $musyrif_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hafalan> $hafalans
 * @property-read int|null $hafalans_count
 * @property-read \App\Models\Kelas|null $kelas
 * @property-read \App\Models\Musyrif|null $musyrif
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tahsin> $tahsins
 * @property-read int|null $tahsins_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tilawah> $tilawahs
 * @property-read int|null $tilawahs_count
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Santri newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Santri newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Santri query()
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereJenisKelamin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereKelasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereNis($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereTanggalLahir($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Santri whereUserId($value)
 */
	class Santri extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $santri_id
 * @property int $semester_id
 * @property int $kelas_id
 * @property int|null $musyrif_id
 * @property string $tipe
 * @property string|null $catatan
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereKelasId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereSantriId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereSemesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereTipe($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SantriKelasHistory whereUpdatedAt($value)
 */
	class SantriKelasHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tahun_ajaran_id
 * @property string $nama
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $tanggal_mulai
 * @property \Illuminate\Support\Carbon|null $tanggal_selesai
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Semester active()
 * @method static \Illuminate\Database\Eloquent\Builder|Semester newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Semester newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Semester query()
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereTahunAjaranId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereTanggalMulai($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereTanggalSelesai($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Semester whereUpdatedAt($value)
 */
	class Semester extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nama
 * @property int $jumlah_ayat
 * @property string|null $nama_latin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SurahSegment> $segments
 * @property-read int|null $segments_count
 * @method static \Illuminate\Database\Eloquent\Builder|Surah newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Surah newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Surah query()
 * @method static \Illuminate\Database\Eloquent\Builder|Surah whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Surah whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Surah whereJumlahAyat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Surah whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Surah whereNamaLatin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Surah whereUpdatedAt($value)
 */
	class Surah extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $hafalan_template_id
 * @property int $surah_id
 * @property int $ayat_awal
 * @property int $ayat_akhir
 * @property int $urutan_segmen
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $range_label
 * @property-read \App\Models\Surah $surah
 * @property-read \App\Models\HafalanTemplate $template
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment query()
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereAyatAkhir($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereAyatAwal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereHafalanTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereSurahId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SurahSegment whereUrutanSegmen($value)
 */
	class SurahSegment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $santri_id
 * @property int $musyrif_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string $status
 * @property string|null $buku
 * @property int|null $halaman
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $buku_label
 * @property-read \App\Models\Musyrif $musyrif
 * @property-read \App\Models\Santri $santri
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereBuku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereHalaman($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereSantriId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tahsin whereUpdatedAt($value)
 */
	class Tahsin extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $santri_id
 * @property int $musyrif_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property int $hafalan_template_id
 * @property string $status
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Musyrif $musyrif
 * @property-read \App\Models\Santri $santri
 * @property-read \App\Models\HafalanTemplate $template
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereCatatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereHafalanTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereMusyrifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereSantriId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereTanggal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tilawah whereUpdatedAt($value)
 */
	class Tilawah extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $nomor
 * @property string $role
 * @property bool $is_approved
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Musyrif|null $musyrif
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\ProfileSetting|null $profileSetting
 * @property-read \App\Models\Santri|null $santri
 * @property-read \App\Models\Santri|null $santriProfile
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNomor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

