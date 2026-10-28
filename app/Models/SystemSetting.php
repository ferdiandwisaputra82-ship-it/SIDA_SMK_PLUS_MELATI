<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /**
     * Get all settings as key=>value array
     */
    public static function getAll(): array
    {
        return self::pluck('value', 'key')->toArray();
    }

    /**
     * Get single setting value
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        $value = $setting ? $setting->value : null;

        return ($value !== null && $value !== '') ? $value : $default;
    }

    /**
     * Set / upsert a setting value
     */
    public static function setValue(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Set multiple settings at once
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            self::setValue($key, $value);
        }
    }

    /**
     * Get default settings array
     */
    public static function defaults(): array
    {
        return [
            'fabric_channel'  => 'documentchannel',
            'chaincode_name'  => 'documentCC',
            'peer_endpoint'   => 'grpcs://peer0.org1.smkmelati.sch.id:7051',
            'msp_id'          => 'SMKMelatiMSP',
            'ocr_engine'      => 'EasyOCR AI Engine',
            'ocr_lang'        => 'ind+eng',
            'ocr_threshold'   => '85',
            'max_file_size'   => '20',
            'sekolah_nama'    => 'SMK Plus Melati',
            'sekolah_alamat'  => 'Jl. Melati No.1, Jakarta Selatan',
            'sekolah_email'   => 'info@smkplusmelati.sch.id',
            'sekolah_npsn'    => '20100123',
            'gemini_api_key'  => '',
        ];
    }
}
