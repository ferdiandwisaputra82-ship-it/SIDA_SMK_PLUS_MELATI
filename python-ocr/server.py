import base64
import os
import re
import json
import hashlib
import zipfile
import io
import time
import uuid
import numpy as np
import cv2
import requests
from datetime import datetime
from fastapi import FastAPI, HTTPException, UploadFile, File
from pydantic import BaseModel
from typing import Optional, List
import logging

# Pillow untuk konversi gambar
try:
    from PIL import Image as PILImage
    PILLOW_AVAILABLE = True
except ImportError:
    PILLOW_AVAILABLE = False

# PyMuPDF & pypdf untuk pemrosesan file PDF
try:
    import fitz
    PYMUPDF_AVAILABLE = True
except ImportError:
    PYMUPDF_AVAILABLE = False

try:
    import pypdf
    PYPDF_AVAILABLE = True
except ImportError:
    PYPDF_AVAILABLE = False

# Groq SDK
try:
    from groq import Groq
    GROQ_AVAILABLE = True
except ImportError:
    GROQ_AVAILABLE = False

# Google Generative AI SDK (Legacy & New)
try:
    import google.generativeai as genai
    GEMINI_SDK_AVAILABLE = True
except ImportError:
    GEMINI_SDK_AVAILABLE = False

try:
    from google import genai as google_genai
    GENAI_NEW_SDK_AVAILABLE = True
except ImportError:
    GENAI_NEW_SDK_AVAILABLE = False

try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    pass

# Model Vision Groq (multimodal) - Kosongkan jika model vision diblokir level org
GROQ_VISION_MODELS = []

# Model teks Groq yang aktif dan terbukti super cepat & presisi untuk ekstraksi JSON
GROQ_TEXT_MODELS = [
    "openai/gpt-oss-20b",    # Ultra-cepat (~1.2s - 1.8s) & kuota TPM jauh lebih besar
    "openai/gpt-oss-120b",   # Model besar presisi tinggi jika 20b butuh fallback
]

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Logger
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")
logger = logging.getLogger("OCR-Service")

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Baca .env (python-ocr/.env) dan .env Laravel
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def _read_env_file(path: str) -> dict:
    env_vars = {}
    try:
        with open(path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    key, _, val = line.partition("=")
                    env_vars[key.strip()] = val.strip().strip('"').strip("'")
        logger.info(f"âœ“ .env terbaca: {path} ({len(env_vars)} variabel)")
    except Exception as e:
        logger.warning(f"âš ï¸ Tidak dapat membaca .env ({path}): {e}")
    return env_vars

# Baca dua file .env: lokal (python-ocr/.env) dan Laravel (../.env)
_LOCAL_ENV   = _read_env_file(os.path.join(os.path.dirname(__file__), ".env"))
_LARAVEL_ENV = _read_env_file(os.path.join(os.path.dirname(__file__), "..", ".env"))

def _cfg(key: str, default: str = "") -> str:
    """Ambil nilai konfigurasi: lokal .env â†’ Laravel .env â†’ os.environ â†’ default."""
    return (
        _LOCAL_ENV.get(key)
        or _LARAVEL_ENV.get(key)
        or os.environ.get(key, default)
    )

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Konfigurasi Ollama (bisa diubah via .env)
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
OLLAMA_HOST    = _cfg("OLLAMA_HOST",    "http://localhost:11434")
OLLAMA_MODEL   = _cfg("OLLAMA_MODEL",   "qwen2.5")
OLLAMA_TIMEOUT = int(_cfg("OLLAMA_TIMEOUT", "3"))
OLLAMA_API_KEY = _cfg("OLLAMA_API_KEY", "")

logger.info(f"ðŸ¤– Ollama Config â†’ host={OLLAMA_HOST} | model={OLLAMA_MODEL} | timeout={OLLAMA_TIMEOUT}s | key_loaded={bool(OLLAMA_API_KEY)}")

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# PROMPT AI â€” Upgrade v2.0 (Juli 2026)
# Mencakup: Ijazah, Rapor, Sertifikat, Transkrip,
# Surat Keterangan, Surat Tugas, Surat Keputusan
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
OCR_STRUCT_PROMPT = """Kamu adalah sistem AI OCR Dokumen Resmi Indonesia yang sangat presisi dan cerdas.
Tugasmu: baca teks OCR (yang mungkin mengandung noise/typo/karakter rusak), lalu ekstrak data ke 6 field JSON.
Kamu BOLEH dan WAJIB mengoreksi typo OCR sebelum mengisi field, tapi JANGAN mengarang data yang tidak ada.
Jika suatu data tidak ditemukan dalam dokumen, isi field tersebut dengan tanda "-" (strip), BUKAN null, BUKAN string kosong, BUKAN teks sembarang.

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ A. KOREKSI NOISE OCR (lakukan sebelum ekstraksi)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Noise OCR umum yang HARUS diperbaiki saat membaca nama/teks:
  â€¢ Angka mirip huruf: 0â†’O, 1â†’I atau L, 3â†’B atau E, 5â†’S, 8â†’B, 6â†’G
  â€¢ Karakter noise: $ # @ % ^ & * _ = + < > \ ~ â†’ hapus dari nama
  â€¢ Huruf berspasi: "I J A Z A H" â†’ "IJAZAH" | "S E R T I F I K A T" â†’ "SERTIFIKAT"
  â€¢ Sambungan salah: "lQBAL" â†’ "IQBAL" (l menjadi I)
  â€¢ Karakter tambahan aneh di tengah nama: "IQBAL F4CHROZI" â†’ "IQBAL FACHROZI"

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ B. PANDUAN FIELD 1 â€” "jenis_dokumen"
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Pilih TEPAT SATU dari 8 nilai berikut (harus PERSIS SAMA huruf besar/kecilnya):
  "Ijazah" | "Rapor" | "Transkrip Nilai" | "Surat Keterangan" |
  "Sertifikat" | "Surat Tugas" | "Surat Keputusan" | "Dokumen Lainnya"

Aturan penentuan:
  â†’ "Ijazah"           : ada kata IJAZAH, STTB, LULUS dari satuan pendidikan
  â†’ "Rapor"            : ada RAPOR, RAPORT, LAPORAN HASIL BELAJAR, CAPAIAN HASIL BELAJAR,
                         LEMBAR HASIL BELAJAR, EVALUASI BELAJAR, NILAI SEMESTER, NILAI RAPOR
  â†’ "Transkrip Nilai"  : ada TRANSKRIP, DAFTAR NILAI, REKAP NILAI, NILAI AKHIR SEMESTER
  â†’ "Sertifikat"       : ada SERTIFIKAT, PIAGAM, CERTIFICATE, PENGHARGAAN, KOMPETENSI
  â†’ "Surat Tugas"      : ada SURAT TUGAS, TUGAS KEDINASAN, PERINTAH TUGAS
  â†’ "Surat Keputusan"  : ada SURAT KEPUTUSAN, SK, KEPUTUSAN KEPALA, KEPUTUSAN DIREKTUR
  â†’ "Surat Keterangan" : ada SURAT KETERANGAN, KETERANGAN AKTIF, KETERANGAN LULUS,
                         KETERANGAN KERJA, KETERANGAN TIDAK MAMPU
  â†’ "Dokumen Lainnya"  : tidak cocok satupun di atas

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ C. PANDUAN FIELD 2 â€” "nama_pemilik"
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Ini adalah NAMA SISWA/MAHASISWA/PESERTA/PENERIMA dokumen â€” BUKAN nama orang tua, kepala sekolah, pejabat, atau penandatangan.

[ CARA MENEMUKAN NAMA sesuai jenis dokumen ]

â–¸ IJAZAH / STTB:
  - Cari baris dengan label: "nama", "nama :", "nama:"
  - Urutan khas Ijazah: nama â†’ tempat/tgl lahir â†’ nama orang tua â†’ nomor induk
  - AMBIL HANYA nilai dari baris "nama" PERTAMA
  - "nama orang tua" = nama ayah/ibu â†’ BUKAN nama_pemilik, JANGAN AMBIL
  - Penandatangan ("Kepala Sekolah", NIP, TTD) â†’ BUKAN nama_pemilik, JANGAN AMBIL
  - Contoh BENAR: label "nama" â†’ "IQBAL FACHROZI" â†’ nama_pemilik = "IQBAL FACHROZI"

â–¸ RAPOR:
  - Cari label: "Nama Siswa", "Nama Peserta Didik", "Nama Lengkap", "Nama:"
  - Hindari: Nama Wali Kelas, Nama Kepala Sekolah, Nama Guru
  - Contoh: "Nama Siswa : Alief Nuraizzy" â†’ nama_pemilik = "Alief Nuraizzy"

â–¸ SERTIFIKAT / PIAGAM:
  - Cari frasa: "Diberikan kepada", "Diperoleh oleh", "Diberikan atas nama", "Kepada:"
  - Potong tepat sebelum kata: "pada", "atas", "yang", "telah", "dalam", "sebagai"
  - Contoh: "Diberikan Kepada Ahmad Fauzi S.Kom pada tanggal" â†’ "Ahmad Fauzi S.Kom"

â–¸ SURAT (Keterangan/Tugas/Keputusan):
  - Cari: "Yang bertanda tangan", "menerangkan bahwa", "menugaskan", "Nama:", "Yang bersangkutan"
  - PENERIMA surat = nama_pemilik, BUKAN pengirim

[ ATURAN UMUM NAMA ]
  âœ“ Ambil nama LENGKAP termasuk gelar akademik (S.Pd, S.T, M.Kom, Drs, Hj, dst.)
  âœ“ Perbaiki typo OCR pada nama (contoh: "FACHRDZI" â†’ "FACHROZI")
  âœ— Jangan sertakan kata setelah nama: tempat, tanggal, lahir, orang, tua, nomor, pada, telah
  âœ— Jangan sertakan jabatan, instansi, atau kalimat penjelas setelah nama

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ D. PANDUAN FIELD 3 â€” "nomor_dokumen"
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Ambil kode/nomor resmi dokumen persis sesuai format asli.

  â–¸ Ijazah nasional    : format "DN-XX DI XXXXXXX" (di kotak bawah dokumen)
                         atau "M-SMA/XX/XXXXXX", "M-SMK/XX/XXXXXX", "M-SD/XX/XXXXXX"
  â–¸ Sertifikat         : nomor sertifikat (misal: "CERT/2024/001")
  â–¸ Surat resmi        : nomor surat (misal: "421.5/ST-089/SMK/2026", "003/PD-1509/F")
  â–¸ Rapor              : nomor rapor jika ada
  â–¸ "nomor induk" (NIS): BUKAN nomor_dokumen â†’ masukkan ke nisn_nim!
  â–¸ Jika tidak ada nomor dokumen â†’ isi "-"

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ E. PANDUAN FIELD 4 â€” "nisn_nim"
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  â–¸ NISN = 10 digit (Nomor Induk Siswa Nasional) â†’ prioritas utama
  â–¸ NIS  = 4-8 digit (Nomor Induk Siswa di sekolah tersebut)
  â–¸ NIM  = nomor mahasiswa
  â–¸ "nomor induk" pada Ijazah biasanya NIS (bukan NISN)
  â–¸ Isi hanya ANGKA, tanpa spasi/strip/huruf
  â–¸ Jika tidak ada â†’ isi "-"
  â–¸ Contoh: "nomor induk : 1047" â†’ nisn_nim = "1047"
  â–¸ Contoh: "NISN : 0078234512" â†’ nisn_nim = "0078234512"

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ F. PANDUAN FIELD 5 â€” "tahun_dokumen"
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  â–¸ Cari tahun dalam konteks: Tahun Ajaran, Tahun Pelajaran, Tanggal Penerbitan, Tahun Kelulusan
  â–¸ Format "XXXX/XXXX" (Tahun Pelajaran) â†’ ambil tahun KEDUA (tahun kelulusan)
    Contoh: "Tahun Pelajaran 2012/2013" â†’ tahun_dokumen = "2013"
  â–¸ Format tanggal pada tanda tangan/penerbitan â†’ ambil tahunnya
    Contoh: "Rokan Hilir, 1 Juni 2013" â†’ tahun_dokumen = "2013"
  â–¸ Jika ada konflik, prioritaskan tahun pada TANGGAL PENERBITAN surat/dokumen
  â–¸ Isi hanya 4 digit angka tahun (misal: "2013", "2025")

â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Â§ G. PANDUAN FIELD 6 â€” "deskripsi_keterangan"
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
Buat deskripsi INFORMATIF dan KONTEKSTUAL sesuai jenis dokumen.
Gunakan template berikut (isi dengan data yang ada, lewati bagian kosong):

  ▸ IJAZAH:
    "Ijazah [jenjang: SD/SMP/SMA/SMK] atas nama [nama_pemilik] dari [nama sekolah], "
    "Tahun Pelajaran [tahun pelajaran]. Nomor Ijazah: [nomor]. NIS: [nisn_nim]. "
    "Dinyatakan LULUS berdasarkan hasil Ujian Nasional."
    Contoh: "Ijazah SMP atas nama IQBAL FACHROZI dari SMP Negeri 3 Kubu, Tahun Pelajaran 2012/2013. Nomor Ijazah: DN-09 DI 0067400. NIS: 1047. Dinyatakan LULUS berdasarkan hasil Ujian Nasional."

  ▸ RAPOR:
    "Rapor [Semester X / Kelas X] atas nama [nama_pemilik] dari [nama sekolah], "
    "Tahun Pelajaran [tahun pelajaran]. [Catatan penting jika ada.]"
    Contoh: "Rapor Semester 1 Kelas X atas nama Alief Nuraizzy dari SMK Plus Melati, Tahun Pelajaran 2024/2025."

  ▸ SERTIFIKAT:
    "Sertifikat [nama kegiatan/kompetensi] atas nama [nama_pemilik], "
    "diterbitkan oleh [lembaga penerbit] pada [tanggal, tahun]."
    Contoh: "Sertifikat Pelatihan Canva untuk Pendidik atas nama Ahmad Fauzi, diterbitkan oleh Kemdikbud pada 15 Juli 2025."

  ▸ SURAT TUGAS:
    "Surat Tugas No. [nomor] dari [instansi pengirim] kepada [nama_pemilik], "
    "untuk melaksanakan [isi tugas]. Tanggal: [tanggal]."

  ▸ SURAT KEPUTUSAN:
    "SK No. [nomor] tentang [perihal] dari [pejabat/instansi] kepada [penerima]. Tanggal: [tanggal]."

  ▸ SURAT KETERANGAN:
    "Surat Keterangan No. [nomor] menerangkan bahwa [nama_pemilik] adalah [keterangan]. "
    "Diterbitkan oleh [instansi] pada [tanggal]."

  ▸ TRANSKRIP NILAI:
    "Transkrip Nilai atas nama [nama_pemilik] dari [institusi], Program Studi [prodi] (jika ada). "
    "Periode: [tahun]. Total SKS/Mata Pelajaran: [jumlah jika ada]."

  ▸ DOKUMEN LAINNYA:
    Buat ringkasan 1-2 kalimat yang menjelaskan isi utama dokumen.

──────────────────────────────────────────────────────
§ H. FORMAT OUTPUT
──────────────────────────────────────────────────────
⚠ Output HANYA JSON valid.
⚠ TANPA teks pembuka/penutup apapun.
⚠ TANPA markdown code block (tanpa ```json atau ```).
⚠ Langsung mulai dengan karakter "{" dan akhiri dengan "}".
⚠ Jangan tambahkan komentar, penjelasan, atau teks di luar JSON.

Skema JSON yang WAJIB diikuti:
{
  "jenis_dokumen": "<salah satu dari 8 pilihan di atas>",
  "nama_pemilik": "<nama lengkap pemilik/penerima> atau \"-\" jika tidak ditemukan",
  "nomor_dokumen": "<nomor resmi dokumen> atau \"-\" jika tidak ditemukan",
  "nisn_nim": "<angka NISN/NIS/NIM saja> atau \"-\" jika tidak ditemukan",
  "tahun_dokumen": "<4 digit tahun> atau \"-\" jika tidak ditemukan",
  "deskripsi_keterangan": "<deskripsi informatif sesuai template di atas> atau \"-\" jika tidak ada informasi cukup"
}"""

OCR_VISION_TEXT_PROMPT = OCR_STRUCT_PROMPT

app = FastAPI(
    title="Python OCR API Service",
    description="Microservice OCR: Fast Preprocessing + Groq AI + EasyOCR Optimized",
    version="6.1.0"
)

@app.on_event("startup")
async def startup_prewarm():
    logger.info("⚡ Inisialisasi awal server: pre-warming EasyOCR reader...")
    try:
        get_easyocr_reader()
        logger.info("✓ Pre-warming EasyOCR selesai. Server siap melayani request secara instan!")
    except Exception as e:
        logger.warning(f"⚠️ Pre-warm EasyOCR warning: {e}")
async def startup_prewarm():
    logger.info("⚡ Inisialisasi awal server: pre-warming EasyOCR reader...")
    try:
        get_easyocr_reader()
        logger.info("✓ Pre-warming EasyOCR selesai. Server siap melayani request secara instan!")
    except Exception as e:
        logger.warning(f"⚠️ Pre-warm EasyOCR warning: {e}")

# ──────────────────────────────────────────
# EasyOCR Loader
# ──────────────────────────────────────────
def get_easyocr_reader():
    global easyocr_reader
    if easyocr_reader is None:
        import easyocr
        logger.info("⏳ Memuat model EasyOCR (id + en)...")
        easyocr_reader = easyocr.Reader(
            ['id', 'en'],
            gpu=False,
            quantize=True,          # Lebih cepat dan hemat RAM pada CPU
        )
        logger.info("✓ EasyOCR siap!")
    return easyocr_reader


def run_easyocr(img: np.ndarray) -> str:
    """
    Jalankan EasyOCR dengan confidence filtering dan pengelompokan baris.
    """
    reader = get_easyocr_reader()

    # detail=1: [(bbox, teks, confidence), ...]
    results = reader.readtext(img, detail=1, paragraph=False, batch_size=4)

    if not results:
        return ""

    # Urutkan berdasarkan posisi Y (baris atas ke bawah)
    results.sort(key=lambda r: r[0][0][1])

    # Filter confidence dan kelompokkan baris
    MIN_CONFIDENCE = 0.25
    lines = []
    current_y = -1
    current_line_texts = []
    LINE_THRESHOLD = 20

    for (bbox, text, conf) in results:
        if conf < MIN_CONFIDENCE:
            continue

        y_top = bbox[0][1]

        if current_y == -1:
            current_y = y_top

        if abs(y_top - current_y) <= LINE_THRESHOLD:
            current_line_texts.append(text.strip())
        else:
            if current_line_texts:
                lines.append(" ".join(current_line_texts))
            current_line_texts = [text.strip()]
            current_y = y_top

    if current_line_texts:
        lines.append(" ".join(current_line_texts))

    result_text = "\n".join(lines)
    valid_count = len([r for r in results if r[2] >= MIN_CONFIDENCE])
    logger.info(f"   ↳ EasyOCR: {len(results)} deteksi, {valid_count} valid (conf≥{MIN_CONFIDENCE})")
    return result_text

# ──────────────────────────────────────────
# OpenCV Preprocessing (Optimized Fast)
# ──────────────────────────────────────────
def apply_preprocessing(img: np.ndarray, engine: str = "easyocr") -> np.ndarray:
    """
    Preprocessing cerdas & cepat untuk memaksimalkan akurasi tanpa membebani CPU:
    1. Smart Scaling: Jika gambar raksasa (>1600px), downscale ke max 1600px.
       Ini mempercepat inferensi EasyOCR hingga 8x lipat tanpa mengurangi akurasi teks.
       Jika gambar terlalu kecil (<700px), upscale ke 900px.
    2. Grayscale + CLAHE adaptif ringan untuk mengangkat teks pudar tanpa noise.
    """
    h, w = img.shape[:2]
    max_dim = max(h, w)
    min_dim = min(h, w)

    # 1. Smart scaling
    if max_dim > 1280:
        scale = 1280.0 / max_dim
        img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
        logger.info(f"  ⚡ Smart scale down: {w}x{h} → {img.shape[1]}x{img.shape[0]}")
    elif min_dim < 700:
        scale = 900.0 / min_dim
        img = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_CUBIC)
        logger.info(f"  ⚡ Smart scale up: {w}x{h} → {img.shape[1]}x{img.shape[0]}")

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY) if len(img.shape) == 3 else img

    # 2. CLAHE adaptif ringan (meningkatkan keterbacaan teks tanpa distorsi karakter)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    enhanced = clahe.apply(gray)

    return enhanced
# Global State
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
easyocr_reader      = None
tesseract_available = False

import pytesseract
for path in [
    r"C:\Program Files\Tesseract-OCR\tesseract.exe",
    r"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe",
    os.environ.get("TESSERACT_CMD", "")
]:
    if path and os.path.exists(path):
        pytesseract.pytesseract.tesseract_cmd = path
        tesseract_available = True
        logger.info(f"âœ“ Tesseract: {path}")
        break

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Request Model
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
class OCRRequest(BaseModel):
    image: str
    mimeType: str = "image/jpeg"
    engine: str = "easyocr"
    preprocess: bool = True
    fileName: str = ""
    docType: Optional[str] = None
    groqKey: Optional[str] = None   # field lama, diabaikan (tidak ada Groq lagi)
    geminiKey: Optional[str] = None # field lama, diabaikan

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# EasyOCR Loader
JENIS_DOKUMEN_VALID = [
    "Ijazah", "Rapor", "Transkrip Nilai", "Surat Keterangan",
    "Sertifikat", "Surat Tugas", "Surat Keputusan", "Dokumen Lainnya"
]

def validasi_hasil(hasil_json: dict) -> dict:
    """Validasi agar jenis_dokumen tidak pernah keluar dari 8 pilihan valid.
    Juga normalisasi semua field: nilai null/None/kosong diganti dengan "-".
    """
    if not isinstance(hasil_json, dict):
        return {"jenis_dokumen": "Dokumen Lainnya"}

    # Validasi jenis_dokumen
    if hasil_json.get("jenis_dokumen") not in JENIS_DOKUMEN_VALID:
        hasil_json["jenis_dokumen"] = "Dokumen Lainnya"

    # Normalisasi: field null/None/string kosong â†’ "-"
    for field in ["nama_pemilik", "nomor_dokumen", "nisn_nim", "tahun_dokumen", "deskripsi_keterangan"]:
        val = hasil_json.get(field)
        if val is None or (isinstance(val, str) and val.strip() in ("", "null", "none", "N/A", "n/a", "-")):
            hasil_json[field] = "-"
        elif isinstance(val, str):
            hasil_json[field] = val.strip()

    return hasil_json

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Helper: Bersihkan & Extract JSON dari respons Ollama
# (model lokal kadang mengembalikan teks pembuka sebelum JSON)
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def extract_json_from_response(raw: str) -> Optional[dict]:
    """
    Coba ekstrak JSON yang valid dari string respons AI (Ollama/Groq/Gemini).
    Mendukung pembersihan tag reasoning <think>...</think> dari model Qwen/DeepSeek.
    """
    if not raw:
        return None

    # Hapus tag <think>...</think> dari model reasoning (seperti Qwen / DeepSeek)
    cleaned = re.sub(r"<think>.*?</think>", "", raw, flags=re.DOTALL).strip()
    # Jika tag <think> terpotong karena token limit, bersihkan jika ada JSON setelahnya atau buang <think>
    if "<think>" in cleaned and "</think>" not in cleaned:
        cleaned = re.sub(r"^<think>.*?(?=\{)", "", cleaned, flags=re.DOTALL).strip()

    # Langkah 1: Hapus fence markdown
    cleaned = re.sub(r"```(?:json)?\s*", "", cleaned, flags=re.IGNORECASE)
    cleaned = re.sub(r"```\s*$", "", cleaned, flags=re.MULTILINE).strip()

    # Langkah 2: Cari blok JSON terluar { ... }
    start = cleaned.find("{")
    end   = cleaned.rfind("}")
    if start != -1 and end != -1 and end > start:
        json_str = cleaned[start:end + 1]
        try:
            return json.loads(json_str)
        except json.JSONDecodeError:
            pass

    # Langkah 3: Coba parse langsung
    try:
        return json.loads(cleaned)
    except json.JSONDecodeError:
        return None

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Helper: Validasi skema JSON hasil Ollama
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
SURAT_SCHEMA_KEYS = {
    "nomor_surat", "tanggal", "pengirim", "penerima",
    "perihal", "isi_ringkas", "lampiran"
}

DOKUMEN_SCHEMA_KEYS = {
    "jenis_dokumen", "nama_pemilik", "nomor_dokumen",
    "nisn_nim", "tahun_dokumen", "deskripsi_keterangan"
}

def validate_surat_schema(data: dict) -> bool:
    """Periksa apakah semua key skema surat ada di data."""
    return bool(data) and SURAT_SCHEMA_KEYS.issubset(data.keys())

def validate_dokumen_schema(data: dict) -> bool:
    """Periksa apakah semua key skema dokumen (ijazah/sertifikat) ada di data."""
    return bool(data) and DOKUMEN_SCHEMA_KEYS.issubset(data.keys())

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Ollama REST API â€” Core Call (dengan retry)
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def call_ollama(prompt: str, max_retries: int = 1) -> Optional[dict]:
    """
    Kirim prompt teks ke Ollama /api/generate dan kembalikan dict JSON hasil parsing.
    - max_retries: jumlah percobaan ulang jika JSON gagal di-parse (default 2x)
    - Error handling: jika Ollama tidak jalan / timeout â†’ log error, return None
    """
    url = f"{OLLAMA_HOST.rstrip('/')}/api/generate"
    payload = {
        "model":  OLLAMA_MODEL,
        "prompt": prompt,
        "stream": False,
        "options": {
            "temperature": 0.0,
            "top_p": 0.1,
            "num_predict": 256,
        }
    }

    for attempt in range(1, max_retries + 1):
        logger.info(f"ðŸ¤– Ollama request (percobaan {attempt}/{max_retries}) â†’ model={OLLAMA_MODEL}")
        try:
            headers = {}
            if OLLAMA_API_KEY:
                headers["Authorization"] = f"Bearer {OLLAMA_API_KEY}"
            response = requests.post(
                url,
                json=payload,
                headers=headers,
                timeout=OLLAMA_TIMEOUT
            )
            response.raise_for_status()

            resp_json = response.json()
            raw_text  = resp_json.get("response", "").strip()

            logger.debug(f"   Raw Ollama response [{len(raw_text)} chars]: {raw_text[:200]}")

            parsed = extract_json_from_response(raw_text)
            if parsed:
                logger.info(f"âœ… Ollama JSON berhasil di-parse | keys: {list(parsed.keys())}")
                return validasi_hasil(parsed)

            logger.warning(f"âš ï¸ Percobaan {attempt}: Gagal parse JSON dari Ollama. Raw: {raw_text[:300]}")

        except requests.exceptions.ConnectionError:
            logger.error(
                f"âŒ Ollama tidak dapat dijangkau di {OLLAMA_HOST}. "
                "Pastikan Ollama sudah berjalan: jalankan `ollama serve` di terminal."
            )
            return None  # Tidak perlu retry jika server mati

        except requests.exceptions.Timeout:
            logger.error(
                f"âŒ Ollama timeout setelah {OLLAMA_TIMEOUT} detik "
                f"(percobaan {attempt}/{max_retries})."
            )

        except Exception as e:
            logger.error(f"âŒ Ollama error (percobaan {attempt}/{max_retries}): {e}")

        if attempt < max_retries:
            wait_sec = 2 * attempt
            logger.info(f"   â³ Menunggu {wait_sec} detik sebelum retry...")
            time.sleep(wait_sec)

    logger.error(f"âŒ Ollama gagal setelah {max_retries} percobaan.")
    return None

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Ollama Vision AI (OCR langsung dari gambar)
# Karena Ollama model teks tidak mendukung vision,
# kita gunakan EasyOCR untuk ekstrak teks dulu,
# lalu kirim teks ke Ollama untuk distrukturkan.
# (Jika pakai model vision seperti llava, endpoint sama)
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def call_ollama_structure_text(raw_text: str) -> Optional[dict]:
    """
    Kirim teks mentah OCR ke Ollama untuk distrukturkan ke format JSON dokumen.
    Menggunakan skema: jenis_dokumen, nama_pemilik, nomor_dokumen, dst.
    Ini menggantikan call_groq_text_ocr().
    """
    if not raw_text or not raw_text.strip():
        return None

    prompt = (
        OCR_VISION_TEXT_PROMPT
        + "\n\nTeks OCR yang perlu dianalisis:\n"
        + raw_text.strip()
    )

    result = call_ollama(prompt, max_retries=1)
    if result and validate_dokumen_schema(result):
        return result

    # Jika schema tidak lengkap, tetap kembalikan (mungkin sebagian field ada)
    if result:
        logger.warning(f"âš ï¸ Schema dokumen tidak lengkap. Keys: {list(result.keys())}")
        return result

    return None


def preprocess_ocr_text_for_ai(text: str) -> str:
    """
    Bersihkan dan normalisasi teks OCR kasar sebelum dikirim ke AI.

    Pipeline pembersihan:
    1. Perbaiki huruf kapital berspasi (S E R T I F I K A T â†’ SERTIFIKAT)
    2. Perbaiki karakter noise OCR umum (|I, l1, O0)
    3. Normalisasi label-label dokumen umum
    4. Normalisasi spasi dan tanda baca
    5. Hapus baris kosong/noise berlebihan
    6. Pisahkan label dari nilainya (nomor : nilai â†’ baris terpisah)
    """
    if not text:
        return ""

    # â”€ STEP 1: Perbaiki huruf kapital berspasi (2â€“15 huruf) â”€
    # Contoh: "I J A Z A H" â†’ "IJAZAH", "S M K" â†’ "SMK"
    for _ in range(6):
        prev = text
        text = re.sub(
            r'(?<![A-Za-z])([A-Z])(?:\s+([A-Z])){1,14}(?![a-z])',
            lambda m: m.group(0).replace(' ', ''),
            text
        )
        if text == prev:
            break

    # â”€ STEP 2: Perbaiki karakter noise OCR yang umum â”€
    text = re.sub(r'(?<=[A-Za-z])[\|l]{1,2}(?=[A-Za-z])', 'I', text)  # l| di tengah kata â†’ I
    text = re.sub(r'(?<=[A-Z])[0](?=[A-Z])', 'O', text)               # X0X di nama kapital â†’ XOX
    text = re.sub(r'(?<=[a-zA-Z])[1](?=[a-zA-Z])', 'l', text)         # a1b â†’ alb

    # â”€ STEP 3: Normalisasi label dokumen Indonesia yang sering salah dibaca â”€
    label_fixes = [
        (r'\bN[Aa][Mm][Aa]\b', 'nama'),
        (r'\bN[Ii][Ss][Ww][Aa]\b', 'niswa'),
        (r'\bN[Ii][Ss][Nn]\b', 'nisn'),
        (r'\bT[Aa][Hh][Uu][Nn]\b', 'Tahun'),
        (r'\bN[Oo][Mm][Oo][Rr]\b', 'nomor'),
        (r'\bT[Ee][Mm][Pp][Aa][Tt]\b', 'tempat'),
        (r'\bT[Aa][Nn][Gg][Gg][Aa][Ll]\b', 'tanggal'),
        (r'\bLULUS\b', 'LULUS'),
        (r'\bIJAZAH\b', 'IJAZAH'),
        (r'\bRAPOR\b', 'RAPOR'),
        (r'\bSERTIFIKAT\b', 'SERTIFIKAT'),
    ]
    for pattern, replacement in label_fixes:
        text = re.sub(pattern, replacement, text)

    # â”€ STEP 4: Normalisasi tanda baca â”€
    text = re.sub(r'[ \t]*:[ \t]*', ': ', text)    # spasi seragam di sekitar colon
    text = re.sub(r'[ \t]*/[ \t]*', '/', text)     # hapus spasi di sekitar slash
    text = re.sub(r'\.{3,}', ' ', text)            # "..........." â†’ spasi (garis titik-titik Ijazah)
    text = re.sub(r'-{4,}', ' ', text)             # "--------" â†’ spasi
    text = re.sub(r'_{3,}', ' ', text)             # "_______" â†’ spasi

    # â”€ STEP 5: Bersihkan tiap baris â”€
    cleaned_lines = []
    for line in text.splitlines():
        line = line.strip()
        # Buang baris yang hanya berisi karakter noise (tanda baca/simbol tanpa huruf/angka)
        if not line:
            continue
        if len(line) < 2:
            continue
        if re.match(r'^[^a-zA-Z0-9]+$', line):  # Baris tanpa huruf/angka = noise
            continue
        cleaned_lines.append(line)

    return "\n".join(cleaned_lines)


def call_ollama_extract_surat(raw_text: str) -> Optional[dict]:
    """
    Ekstrak metadata dari teks OCR menggunakan prompt ketat pengguna.
    """
    if not raw_text or not raw_text.strip():
        return None

    cleaned = preprocess_ocr_text_for_ai(raw_text)

    prompt = (
        OCR_STRUCT_PROMPT
        + "\n\nTeks OCR:\n\"\"\"\n"
        + cleaned
        + "\n\"\"\""
    )

    result = call_ollama(prompt, max_retries=1)
    if result:
        return validasi_hasil(result)

    return None

def get_groq_key(payload_key: Optional[str] = None) -> str:
    key = (payload_key or "").strip()
    if key and key.startswith("gsk_"):
        return key
    env_key = _cfg("GROQ_API_KEY", "").strip()
    if env_key and env_key.startswith("gsk_"):
        return env_key
    return key or env_key

def call_groq_ocr(b64_image: str, mime_type: str, api_key: str) -> Optional[dict]:
    """
    Kirim gambar ke Groq Vision API dan kembalikan hasil JSON terstruktur.
    Menggunakan system+user role dan meminta JSON object langsung.
    """
    if not api_key or not api_key.strip() or not GROQ_AVAILABLE:
        return None

    if not GROQ_VISION_MODELS:
        logger.info("â„¹ï¸ Tidak ada GROQ_VISION_MODELS yang dikonfigurasi, skip vision.")
        return None

    client = Groq(api_key=api_key)

    for model in GROQ_VISION_MODELS:
        logger.info(f"ðŸš€ Mencoba Groq Vision model: [{model}]")
        try:
            # Params khusus untuk model reasoning (qwen/qwen3.6-27b):
            # - reasoning_effort="none" â†’ mematikan token <think> sehingga hemat TPM
            # - image detail="low"     â†’ ~256 token/gambar, cukup untuk OCR teks dokumen
            extra_params = {}
            if "qwen" in model.lower():
                extra_params["reasoning_effort"] = "none"

            response = client.chat.completions.create(
                model=model,
                messages=[
                    {
                        "role": "system",
                        "content": (
                            "Kamu adalah sistem OCR dokumen resmi Indonesia yang sangat presisi. "
                            "Output HANYA berupa JSON valid mentah, tanpa markdown, tanpa teks lain, langsung dimulai dengan '{'. "
                            "Jangan mengarang data. Isi null jika tidak ditemukan."
                        )
                    },
                    {
                        "role": "user",
                        "content": [
                            {
                                "type": "image_url",
                                "image_url": {
                                    "url": f"data:{mime_type};base64,{b64_image}",
                                    "detail": "low"  # low = ~256 token/gambar, cukup untuk OCR teks dokumen
                                }
                            },
                            {
                                "type": "text",
                                "text": OCR_STRUCT_PROMPT
                            }
                        ]
                    }
                ],
                temperature=0.0,
                max_tokens=1024,
                **extra_params,
            )

            raw = response.choices[0].message.content or "{}"
            parsed = extract_json_from_response(raw)
            if parsed:
                logger.info(f"âœ… Groq Vision [{model}] berhasil! Keys: {list(parsed.keys())}")
                return validasi_hasil(parsed)
            logger.warning(f"âš ï¸ Groq Vision [{model}] response tidak valid JSON: {raw[:200]}")

        except Exception as e:
            err_msg = str(e)
            if "does not support" in err_msg or "vision" in err_msg.lower() or "image" in err_msg.lower():
                logger.warning(f"âš ï¸ Groq Vision [{model}] tidak support vision: {err_msg[:120]}")
            elif "model" in err_msg.lower() and ("not found" in err_msg.lower() or "deactivated" in err_msg.lower()):
                logger.warning(f"âš ï¸ Groq Vision [{model}] model tidak aktif, skip: {err_msg[:120]}")
            else:
                logger.error(f"âŒ Groq Vision [{model}] error: {err_msg[:200]}")

    return None

def call_groq_text_ocr(raw_text: str, api_key: str) -> Optional[dict]:
    """
    Kirim teks mentah OCR ke Groq untuk distrukturkan.
    - Teks di-preprocess sebelum dikirim ke AI
    - Menggunakan sistem prompt + user prompt terpisah untuk hasil lebih akurat
    - Fallback ke model berikutnya jika gagal
    """
    if not api_key or not api_key.strip() or not GROQ_AVAILABLE:
        return None

    # Bersihkan teks OCR dulu sebelum dikirim ke Groq
    cleaned_text = preprocess_ocr_text_for_ai(raw_text)
    if not cleaned_text.strip():
        return None

    client = Groq(api_key=api_key.strip(), max_retries=0)

    # Gunakan system + user role agar model lebih patuh pada instruksi
    messages = [
        {
            "role": "system",
            "content": (
                "Kamu adalah sistem ekstraksi data dokumen resmi Indonesia yang sangat presisi. "
                "PENTING: DILARANG KERAS mengeluarkan teks proses berpikir, penjelas, atau tag <think>. "
                "Output kamu HANYA berupa JSON valid mentah, tanpa teks pembuka/penutup, "
                "tanpa markdown code block, langsung dimulai dengan '{'. "
                "Jangan mengarang data. Isi null jika field tidak ditemukan."
            )
        },
        {
            "role": "user",
            "content": (
                OCR_STRUCT_PROMPT
                + "\n\n=== TEKS OCR YANG HARUS DIPROSES ===\n\"\"\"\n"
                + cleaned_text
                + "\n\"\"\""
            )
        }
    ]

    for model in GROQ_TEXT_MODELS:
        try:
            logger.info(f"ðŸ¤– Mencoba Groq Text LLM model: [{model}]...")
            response = client.chat.completions.create(
                model=model,
                messages=messages,
                temperature=0.0,
                top_p=0.1,
                max_tokens=1024,
                response_format={"type": "json_object"},  # paksa JSON output
            )
            raw = response.choices[0].message.content or "{}"
            parsed = extract_json_from_response(raw)
            if parsed:
                logger.info(f"âœ… Groq Text LLM [{model}] berhasil! Keys: {list(parsed.keys())}")
                return validasi_hasil(parsed)
            logger.warning(f"âš ï¸ Groq [{model}] response tidak valid JSON: {raw[:200]}")
        except Exception as e:
            err_str = str(e)
            if "429" in err_str or "rate limit" in err_str.lower() or "too many requests" in err_str.lower():
                logger.warning(f"⚠️ Groq [{model}] rate limit (429) tercapai. Mencoba model berikutnya...")
                continue
            # Jika model tidak support response_format JSON, coba tanpa parameter itu
            if "response_format" in err_str or "json_object" in err_str:
                try:
                    logger.info(f"  â†³ Retry [{model}] tanpa response_format...")
                    response = client.chat.completions.create(
                        model=model,
                        messages=messages,
                        temperature=0.0,
                        top_p=0.1,
                        max_tokens=1536,
                    )
                    raw = response.choices[0].message.content or "{}"
                    parsed = extract_json_from_response(raw)
                    if parsed:
                        logger.info(f"âœ… Groq Text LLM [{model}] berhasil (no response_format)!")
                        return validasi_hasil(parsed)
                except Exception as e2:
                    logger.error(f"âŒ Groq [{model}] retry juga gagal: {e2}")
            else:
                logger.error(f"âŒ Groq text structuring [{model}] gagal: {e}")

    return None

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Google Gemini API Integration (Disabled for fast verification)
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def get_gemini_key(payload_key: Optional[str] = None) -> str:
    return ""  # Gemini model disabled for fast verification

def call_gemini_vision_ocr(b64_image: str, mime_type: str, api_key: str) -> Optional[dict]:
    return None  # Gemini model disabled for fast verification

def call_gemini_text_ocr(raw_text: str, api_key: str) -> Optional[dict]:
    return None  # Gemini model disabled for fast verification

    models = ["gemini-2.5-flash", "gemini-2.5-flash-lite", "gemini-1.5-flash", "gemini-2.0-flash"]

    # 1. Coba via google-genai SDK terbaru (2025/2026)
    if GENAI_NEW_SDK_AVAILABLE:
        try:
            client = google_genai.Client(api_key=api_key.strip())
            img_bytes = base64.b64decode(b64_image)
            for m in models:
                try:
                    logger.info(f"âœ¨ [google-genai SDK] Mencoba Gemini Vision Model: [{m}]...")
                    res = client.models.generate_content(
                        model=m,
                        contents=[
                            google_genai.types.Part.from_bytes(data=img_bytes, mime_type=mime_type),
                            OCR_STRUCT_PROMPT
                        ],
                        config={
                            "temperature": 0.0,
                            "top_p": 0.1,
                            "max_output_tokens": 1024
                        }
                    )
                    raw_res = res.text or ""
                    parsed = extract_json_from_response(raw_res)
                    if parsed:
                        logger.info(f"âœ… Google GenAI SDK Vision [{m}] berhasil!")
                        return validasi_hasil(parsed)
                except Exception as em:
                    logger.warning(f"âš ï¸ Google GenAI SDK Vision [{m}] error: {em}")
        except Exception as e:
            logger.warning(f"âš ï¸ Google GenAI Client error: {e}")

    # 2. Coba via SDK legacy google.generativeai
    if GEMINI_SDK_AVAILABLE:
        try:
            genai.configure(api_key=api_key.strip())
            img_data = base64.b64decode(b64_image)
            image_part = {"mime_type": mime_type, "data": img_data}
            for model_name in models:
                try:
                    logger.info(f"âœ¨ [google.generativeai SDK] Mencoba Gemini Vision Model: [{model_name}]...")
                    g_model = genai.GenerativeModel(model_name)
                    res = g_model.generate_content(
                        [image_part, OCR_STRUCT_PROMPT],
                        generation_config=genai.types.GenerationConfig(
                            temperature=0.0,
                            top_p=0.1,
                            max_output_tokens=1024
                        )
                    )
                    raw_res = res.text or ""
                    parsed = extract_json_from_response(raw_res)
                    if parsed:
                        logger.info(f"âœ… Google Gemini SDK Vision [{model_name}] berhasil!")
                        return validasi_hasil(parsed)
                except Exception as em:
                    logger.warning(f"âš ï¸ Gemini SDK Vision [{model_name}] error: {em}")
        except Exception as e:
            logger.warning(f"âš ï¸ Gemini SDK configure error: {e}")

    # 3. Fallback via REST API
    headers = {"Content-Type": "application/json"}
    for model in models:
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key.strip()}"
        payload = {
            "contents": [
                {
                    "parts": [
                        {
                            "inline_data": {
                                "mime_type": mime_type,
                                "data": b64_image
                            }
                        },
                        {"text": OCR_STRUCT_PROMPT}
                    ]
                }
            ],
            "generationConfig": {
                "temperature": 0.0,
                "topP": 0.1,
                "maxOutputTokens": 1024
            }
        }
        try:
            logger.info(f"âœ¨ [REST] Mencoba Google Gemini Vision Model: [{model}]...")
            resp = requests.post(url, json=payload, headers=headers, timeout=60)
            resp.raise_for_status()
            data = resp.json()
            candidates = data.get("candidates", [])
            if candidates:
                parts = candidates[0].get("content", {}).get("parts", [])
                if parts:
                    raw_res = parts[0].get("text", "")
                    parsed = extract_json_from_response(raw_res)
                    if parsed:
                        logger.info(f"âœ… Google Gemini REST Vision [{model}] berhasil!")
                        return validasi_hasil(parsed)
        except Exception as e:
            logger.warning(f"âš ï¸ Gemini REST Vision [{model}] error: {e}")

    return None

def call_gemini_text_ocr(raw_text: str, api_key: str) -> Optional[dict]:
    """Kirim teks mentah EasyOCR ke Google Gemini untuk distrukturkan."""
    if not api_key or not api_key.strip():
        return None

    cleaned = preprocess_ocr_text_for_ai(raw_text)
    prompt_text = (
        OCR_STRUCT_PROMPT
        + "\n\n=== TEKS OCR YANG HARUS DIPROSES ===\n\"\"\"\n"
        + cleaned
        + "\n\"\"\""
    )

    models = ["gemini-2.5-flash", "gemini-2.5-flash-lite", "gemini-1.5-flash", "gemini-2.0-flash"]

    # 1. Coba via google-genai SDK terbaru (2025/2026)
    if GENAI_NEW_SDK_AVAILABLE:
        try:
            client = google_genai.Client(api_key=api_key.strip())
            for m in models:
                try:
                    logger.info(f"âœ¨ [google-genai SDK] Mencoba Gemini Text Model: [{m}]...")
                    res = client.models.generate_content(
                        model=m,
                        contents=prompt_text,
                        config={
                            "temperature": 0.0,
                            "top_p": 0.1,
                            "max_output_tokens": 1024
                        }
                    )
                    raw_res = res.text or ""
                    parsed = extract_json_from_response(raw_res)
                    if parsed:
                        logger.info(f"âœ… Google GenAI SDK Text [{m}] berhasil!")
                        return validasi_hasil(parsed)
                except Exception as em:
                    logger.warning(f"âš ï¸ Google GenAI SDK Text [{m}] error: {em}")
        except Exception as e:
            logger.warning(f"âš ï¸ Google GenAI Client error: {e}")

    # 2. Coba via SDK legacy google.generativeai
    if GEMINI_SDK_AVAILABLE:
        try:
            genai.configure(api_key=api_key.strip())
            for model_name in models:
                try:
                    logger.info(f"âœ¨ [google.generativeai SDK] Mencoba Gemini Text Model: [{model_name}]...")
                    g_model = genai.GenerativeModel(model_name)
                    res = g_model.generate_content(
                        prompt_text,
                        generation_config=genai.types.GenerationConfig(
                            temperature=0.0,
                            top_p=0.1,
                            max_output_tokens=1024
                        )
                    )
                    raw_res = res.text or ""
                    parsed = extract_json_from_response(raw_res)
                    if parsed:
                        logger.info(f"âœ… Google Gemini SDK Text [{model_name}] berhasil!")
                        return validasi_hasil(parsed)
                except Exception as em:
                    logger.warning(f"âš ï¸ Gemini SDK Text [{model_name}] error: {em}")
        except Exception as e:
            logger.warning(f"âš ï¸ Gemini SDK configure error: {e}")

    # 3. Fallback via REST API
    headers = {"Content-Type": "application/json"}
    for model in models:
        url = f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key.strip()}"
        payload = {
            "contents": [
                {
                    "parts": [
                        {"text": prompt_text}
                    ]
                }
            ],
            "generationConfig": {
                "temperature": 0.0,
                "topP": 0.1,
                "maxOutputTokens": 1024
            }
        }
        try:
            logger.info(f"âœ¨ [REST] Mencoba Google Gemini Text Model: [{model}]...")
            resp = requests.post(url, json=payload, headers=headers, timeout=60)
            resp.raise_for_status()
            data = resp.json()
            candidates = data.get("candidates", [])
            if candidates:
                parts = candidates[0].get("content", {}).get("parts", [])
                if parts:
                    raw_res = parts[0].get("text", "")
                    parsed = extract_json_from_response(raw_res)
                    if parsed:
                        logger.info(f"âœ… Google Gemini REST Text [{model}] berhasil!")
                        return validasi_hasil(parsed)
        except Exception as e:
            logger.warning(f"âš ï¸ Gemini REST Text [{model}] error: {e}")

    return None

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Map hasil Ollama ke metadata Laravel
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Kata-kata yang jika ditemukan dalam nama menandakan akhir dari nama sebenarnya
_NAME_STOP_WORDS = {
    "pada", "dan", "dalam", "dengan", "sebagai", "untuk", "telah", "yang",
    "adalah", "di", "dari", "kepada", "atas", "oleh", "serta", "bahwa",
    "tentang", "peserta", "pelatihan", "canva", "quizizz", "menyatakan",
    "diberikan", "setelah", "sebelum", "berdasarkan", "bahwasanya",
    "tanggal", "bulan", "tahun", "nomor", "nomor:", "no.", "telah",
    "berhasil", "mengikuti", "menjadi", "kelas", "semester",
    # Tambahan untuk mencegah pengambilan salah pada Ijazah
    "tempat", "orang", "tua", "lahir", "kepala", "lulus", "satuan",
    "berdasarkan", "memenuhi", "peraturan", "pendidikan",
}

def clean_human_name(name: str) -> str:
    """Membersihkan nama manusia dari noise OCR tanpa memotong gelar (S.Pd, S.T, M.Kom, Drs, Hj, dsb)."""
    if not name:
        return ""
    # Hapus simbol aneh seperti $, #, @, %, *, =, +, <, >, /, \, _, {, }, [, ], ^, ~ (JANGAN hapus titik dan koma gelar!)
    n = re.sub(r'[\$\#\@\%\*\=\+\<\>\/\\\_\{\}\[\]\^\~]', '', name)
    words = n.split()
    clean_words = []
    for w in words:
        # Jika menemukan stop word, hentikan pengambilan kata (nama sudah selesai)
        if w.lower().rstrip('.,;:') in _NAME_STOP_WORDS:
            break
        clean_words.append(w)
    result = " ".join(clean_words).strip(" -,")
    return result if result else name.strip()

def map_ai_to_metadata(ai_data: dict) -> dict:
    return map_surat_to_metadata(ai_data)

def map_surat_to_metadata(surat_data: dict, raw_text: str = "") -> dict:
    """
    Konversi hasil JSON Ollama/Groq/Gemini ke format metadata Laravel.
    """
    doc_type   = (surat_data.get("jenis_dokumen") or "Dokumen Lainnya").strip()
    owner_name = clean_human_name((surat_data.get("nama_pemilik") or "").strip())
    doc_num    = (surat_data.get("nomor_dokumen") or "").strip()
    nisn       = (surat_data.get("nisn_nim") or "").strip()
    year       = (surat_data.get("tahun_dokumen") or "").strip()
    desc       = (surat_data.get("deskripsi_keterangan") or "").strip()

    # Bersihkan nilai jika AI mengembalikan string "null" atau "None"
    if str(owner_name).lower() in ["null", "none"]: owner_name = ""
    if str(doc_num).lower() in ["null", "none"]: doc_num = ""
    if str(nisn).lower() in ["null", "none"]: nisn = ""
    if str(year).lower() in ["null", "none"]: year = ""
    if str(doc_type).lower() in ["null", "none"]: doc_type = "Dokumen Lainnya"

    # Sanitasi NISN/NIM agar hanya berisi angka
    if nisn:
        nisn = re.sub(r"[^\d]", "", nisn)

    # 1. Fallback Tahun jika belum terisi dari AI
    if not year:
        if raw_text:
            yrs = [int(y) for y in re.findall(r"\b(20\d{2}|19\d{2})\b", raw_text) if 1950 <= int(y) <= datetime.now().year + 1]
            if yrs:
                year = str(max(yrs))
        if not year:
            year = str(datetime.now().year)

    # 2. Fallback Jenis Dokumen jika "Dokumen Lainnya" tapi dapat diklasifikasikan dari teks
    if (doc_type == "Dokumen Lainnya" or not doc_type) and raw_text:
        classified = classify_document(raw_text, "")
        if classified != "Dokumen Lainnya":
            doc_type = classified

    # 3. Fallback & Penyeragaman Nama Pemilik
    invalid_words = ["pelatihan", "pemanfaatan", "workshop", "seminar", "kementerian", "sekolah",
                     "ijazah", "sertifikat", "pembelajaran", "kelas", "semester", "republik",
                     "pendidikan", "kebudayaan", "menengah", "pertama", "atas", "menyatakan"]
    is_invalid_owner = not owner_name or any(w in owner_name.lower() for w in invalid_words)

    # â”€â”€ POLA EKSKLUSIF: nama orang tua, kepala sekolah, penandatangan â”€â”€
    # Regex untuk mendeteksi apakah nama yang dipilih AI sebenarnya bukan nama siswa
    _is_orang_tua = bool(raw_text and owner_name and re.search(
        r"(?:nama\s+orang\s+tua|nama\s+ayah|nama\s+ibu|wali)\s*[:\-\.]?\s*" + re.escape(owner_name),
        raw_text, re.IGNORECASE
    ))
    _is_kepala = bool(raw_text and owner_name and re.search(
        r"(?:kepala\s+sekolah|nip\s*[:\-\.]?|ttd|tanda\s+tangan)\s*[\n\r,]*\s*" + re.escape(owner_name),
        raw_text, re.IGNORECASE
    ))
    if _is_orang_tua or _is_kepala:
        logger.warning(f"âš ï¸ Nama pemilik dari AI terdeteksi sebagai orang tua/penandatangan: '{owner_name}' â†’ di-reset")
        owner_name = ""
        is_invalid_owner = True

    # â”€â”€ LOGIKA EKSKLUSIF NAMA PEMILIK â”€â”€
    # Jika AI (Groq/Gemini/Ollama) SUDAH memberikan nama_pemilik yang valid,
    # JANGAN PERNAH menimpa/merusak nama tersebut dengan regex kasar EasyOCR!
    if not is_invalid_owner and owner_name:
        logger.info(f"âœ… Menggunakan nama pemilik valid dari AI: '{owner_name}'")
    else:
        # 1. Coba ambil dari deskripsi_keterangan buatan AI (jika AI meletakkan nama bersih di situ)
        if desc:
            m_desc_name = re.search(
                r"atas\s+nama\s+([A-Za-z \.\,\'\-]{3,50})(?=\s+dari|\s+Tahun|\s+Nomor|\s+NIS|\.|$)",
                desc, re.IGNORECASE
            )
            if m_desc_name:
                cand_from_desc = clean_human_name(m_desc_name.group(1).strip())
                if cand_from_desc and len(cand_from_desc) > 2 and not any(w in cand_from_desc.lower() for w in invalid_words):
                    logger.info(f"âœ… Nama pemilik diambil dari deskripsi AI: '{cand_from_desc}'")
                    owner_name = cand_from_desc
                    is_invalid_owner = False

        # 2. Hanya jika AI tidak memberikan nama valid DAN deskripsi tidak punya nama, baru fallback ke Regex raw_text
        if (is_invalid_owner or not owner_name) and raw_text:
            # â”€â”€ PRIORITAS 1: Ijazah â€” baris "nama" SAJA (bukan 'nama orang tua') â”€â”€
            m_ijazah = re.search(
                r"^[ \t]*nama[ \t]*[:\-\.\s][ \t]*(?!orang|kepala|ayah|ibu|wali|pejabat)([A-Z][A-Za-z \.']{2,55})[ \t]*$",
                raw_text, re.MULTILINE
            )
            if m_ijazah:
                cand_ijazah = m_ijazah.group(1).strip()
                cand_ijazah = re.sub(
                    r"\s+(tempat|tanggal|lahir|orang|tua|nomor|induk|peserta|kelas|semester|nisn|nis|\d{4}).*",
                    "", cand_ijazah, flags=re.IGNORECASE
                ).strip()
                cand_ijazah = clean_human_name(cand_ijazah)
                if cand_ijazah and len(cand_ijazah) > 2 and not any(w in cand_ijazah.lower() for w in invalid_words):
                    logger.info(f"âœ… Nama Ijazah (regex raw_text) ditemukan: '{cand_ijazah}'")
                    owner_name = cand_ijazah
                    is_invalid_owner = False

            # â”€â”€ PRIORITAS 2: Rapor â€” "Nama Peserta Didik" / "Nama Siswa" / "Nama Lengkap" â”€â”€
            if is_invalid_owner or not owner_name:
                m_rapor_name = re.search(
                    r"(?:nama\s+peserta\s+didik|nama\s+siswa|nama\s+lengkap)\s*[:\-\.]?\s*([A-Za-z \t\.,\'\-]{3,60})",
                    raw_text, re.IGNORECASE
                )
                if m_rapor_name:
                    cand_rapor = m_rapor_name.group(1).strip()
                    cand_rapor = re.sub(
                        r"\s+(kelas|nisn|nis|nim|fase|semester|sekolah|alamat|tahun|orang|tempat|lahir).*",
                        "", cand_rapor, flags=re.IGNORECASE
                    ).strip()
                    cand_rapor = clean_human_name(cand_rapor)
                    if cand_rapor and len(cand_rapor) > 2 and not any(w in cand_rapor.lower() for w in invalid_words):
                        owner_name = cand_rapor
                        is_invalid_owner = False

            # â”€â”€ PRIORITAS 3: Pola umum nama dari teks â”€â”€
            if is_invalid_owner or not owner_name:
                m_name = re.search(
                    r"(?:"
                    r"diberikan\s+kepada\s*[:\-\.]?\s*"
                    r"|nama(?:\s+siswa|\s+lengkap|\s+peserta|\s+pemilik)?\s*[:\-\.]\s*"
                    r"|nama\s+[:\-]?\s*(?!orang|tua|kepala|ayah|ibu)"
                    r"|menyatakan\s+bahwa\s*[:\-\.]?\s*"
                    r")([A-Za-z \t.,\'\-]{3,60})",
                    raw_text, re.IGNORECASE
                )
                if m_name:
                    candidate = m_name.group(1).strip()
                    candidate = re.sub(
                        r"\s+(sebagai|tempat|ttl|nisn|nis|nim|lahir|nomor|telah|lulus|satuan|"
                        r"pendidikan|berdasarkan|pada|dan|dalam|dengan|untuk|yang|adalah|di|"
                        r"dari|kepada|atas|oleh|serta|bahwa|tentang|tanggal|bulan|orang|tua).*",
                        "", candidate, flags=re.IGNORECASE
                    ).strip()
                    candidate = clean_human_name(candidate)
                    if candidate and len(candidate) > 2 and not any(w in candidate.lower() for w in invalid_words):
                        owner_name = candidate

    owner_name = clean_human_name(owner_name)

    # 4. Fallback & Sanitasi Nomor Dokumen
    invalid_doc_nums = ["induk", "nomor", "no", "surat", "dokumen", "ijazah", "sertifikat", "rapor"]
    if doc_num.lower() in invalid_doc_nums or not re.search(r"[0-9A-Za-z]{2,}", doc_num):
        doc_num = ""

    if not doc_num and raw_text:
        # Prioritas 1: Format nomor Ijazah nasional "DN-XX DI XXXXXXX" atau "M-SMK/..."
        m_dn = re.search(
            r"\b(DN[-\s]\d{2}[-\s]DI[-\s]?\d{5,10})\b"
            r"|\b(M[-/][A-Z]{2,5}[-/]\d{2}[-/]\d{4,10})\b",
            raw_text, re.IGNORECASE
        )
        if m_dn:
            doc_num = (m_dn.group(1) or m_dn.group(2) or "").strip().upper()
            logger.info(f"âœ… Nomor Ijazah (DN-format) ditemukan: {doc_num}")

        # Prioritas 2: Nomor umum dari label
        if not doc_num:
            m_num = re.search(
                r"(?:nomor|no\.?|number)(?:\s+dokumen|\s+peserta|\s+ijazah|\s+surat)?\s*[:\-\.]?\s*([A-Za-z0-9\/\-\.]{3,35})",
                raw_text, re.IGNORECASE
            )
            if m_num:
                found_num = m_num.group(1).strip()
                if found_num.lower() not in invalid_doc_nums:
                    doc_num = found_num

    # 5. Fallback NISN / NIS jika tidak terisi
    if not nisn and raw_text:
        # NISN = 10 digit, NIS = 4-8 digit
        m_nisn = re.search(
            r"(?:nisn|nomor\s+induk\s+siswa\s+nasional)\s*[:\-\.]?\s*([0-9]{10})"
            r"|(?:\bnis\b|nomor\s+induk)\s*[:\-\.]?\s*([0-9]{4,8})",
            raw_text, re.IGNORECASE
        )
        if m_nisn:
            raw_nisn = (m_nisn.group(1) or m_nisn.group(2) or "").strip()
            nisn = re.sub(r"[^\d]", "", raw_nisn)

    # Jika NISN terdeteksi dan nomor dokumen masih kosong/invalid, isi dengan NISN
    if nisn and (not doc_num or doc_num.startswith("DOC-") or doc_num.lower() in invalid_doc_nums):
        doc_num = f"NISN-{nisn}"

    if doc_num and doc_num.startswith("DOC-"):
        doc_num = ""

    # Log hasil akhir untuk debugging
    logger.info(
        f"ðŸ“‹ Hasil Metadata: jenis={doc_type} | nama='{owner_name}' | "
        f"valid={not is_invalid_owner} | no={doc_num} | nisn={nisn} | tahun={year}"
    )

    return {
        "documentType":   doc_type,
        "ownerName":      owner_name if not is_invalid_owner else "",
        "documentNumber": doc_num,
        "nisn":           nisn,
        "year":           year,
        "institution":    "",
        "description":    desc
    }

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Regex Fallback
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def classify_document(text: str, file_name: str) -> str:
    t = (text + " " + file_name).lower()
    if "ijazah"          in t or "lulus"       in t: return "Ijazah"
    if "rapor"           in t or "raport"      in t: return "Rapor"
    if "transkrip"       in t:                        return "Transkrip Nilai"
    if "sertifikat"      in t or "certificate" in t:  return "Sertifikat"
    if "surat tugas"     in t:                        return "Surat Tugas"
    if "surat keputusan" in t or " sk "        in t:  return "Surat Keputusan"
    if "surat keterangan" in t:                       return "Surat Keterangan"
    return "Dokumen Lainnya"

def extract_metadata_regex(text: str, file_name: str, doc_type: str) -> dict:
    """
    Fallback ekstraksi metadata menggunakan regex murni.
    Dipanggil jika semua AI (Groq, Gemini, Ollama) gagal.
    """
    owner_name = doc_number = nisn = year = sender = desc = ""

    # Nama pemilik â€” prioritas Ijazah: baris "nama" tanpa "orang tua"
    m_ijazah = re.search(
        r"^[ \t]*nama[ \t]*[:\-\.\s][ \t]*(?!orang|kepala|ayah|ibu)([A-Z][A-Za-z \.']{2,50})[ \t]*$",
        text, re.MULTILINE
    )
    if m_ijazah:
        owner_name = m_ijazah.group(1).strip()
        owner_name = re.sub(r"\s+(tempat|tanggal|lahir|orang|nomor|induk).*", "", owner_name, flags=re.IGNORECASE).strip()

    if not owner_name:
        m = re.search(
            r"(?:nama(?:\s+siswa|\s+lengkap|\s+peserta\s+didik|\s+pemilik)?\s*[:\-\.]\s*"
            r"|menyatakan\s+bahwa\s*[:\-\.]?\s*"
            r"|diberikan\s+kepada\s*[:\-\.]?\s*)([A-Za-z \t\.,\']+)",
            text, re.IGNORECASE
        )
        if m:
            owner_name = m.group(1).strip()
            owner_name = re.sub(
                r"\s+(tempat|tanggal|lahir|orang|tua|nomor|pada|sebagai|telah|yang).*",
                "", owner_name, flags=re.IGNORECASE
            ).strip()

    # Nomor dokumen â€” prioritas format DN- Ijazah
    m_dn = re.search(r"\b(DN[-\s]\d{2}[-\s]DI[-\s]?\d{5,10})\b", text, re.IGNORECASE)
    if m_dn:
        doc_number = m_dn.group(1).strip().upper()
    else:
        m2 = re.search(
            r"(?:nomor|no\.?|number)(?:\s+dokumen|\s+peserta|\s+ijazah|\s+surat)?\s*[:\-\.]?\s*"
            r"([A-Za-z0-9]{1,5}[\-\/][A-Za-z0-9\-\/\.]{2,}|[0-9]{4,}[A-Za-z0-9\-\/\.]*)",
            text, re.IGNORECASE
        )
        if m2:
            c = m2.group(1).strip()
            doc_number = c if re.search(r'\d', c) else ""

    # NISN / NIS
    m3 = re.search(
        r"(?:nisn|nomor\s+induk\s+siswa\s+nasional)\s*[:\-\.]?\s*([0-9]{10})"
        r"|(?:\bnis\b|nomor\s+induk)\s*[:\-\.]?\s*([0-9]{4,8})",
        text, re.IGNORECASE
    )
    if m3:
        nisn = (m3.group(1) or m3.group(2) or "").strip()
        nisn = re.sub(r"[^\d]", "", nisn)

    # Tahun
    cur = datetime.now().year
    yrs = [int(y) for y in re.findall(r"\b(20\d{2}|19\d{2})\b", text) if 1950 <= int(y) <= cur + 1]
    if yrs:
        year = str(max(yrs))

    # Instansi pengirim/sekolah
    m4 = re.search(
        r"(?:kepala|ketua|direktur|oleh|ttd|mengetahui)\s*[:\-\.]?\s*,?\s*([A-Za-z \t\.,\']+)",
        text, re.IGNORECASE
    )
    if m4:
        sender = m4.group(1).strip()
    if not sender:
        m5 = re.search(r"dari\s+([A-Za-z0-9 \t\.,\']+)", text, re.IGNORECASE)
        if m5:
            sender = m5.group(1).strip()

    # Nama sekolah / instansi dari teks
    m_school = re.search(
        r"(?:sekolah\s+menengah|smk|sma|smp|sd|madrasah|universitas|politeknik|institut)[\s\w]*",
        text, re.IGNORECASE
    )
    school_name = m_school.group(0).strip().title() if m_school else ""

    # Tahun pelajaran (untuk Ijazah/Rapor)
    m_tp = re.search(r"tahun\s+(?:pelajaran|ajaran)\s+(\d{4}[/\-]\d{4})", text, re.IGNORECASE)
    school_year = m_tp.group(1) if m_tp else year

    # â”€ Buat deskripsi kontekstual per jenis dokumen â”€
    if doc_type == "Ijazah":
        parts = [f"Ijazah"]
        if school_name:
            parts.append(f"dari {school_name}")
        if owner_name:
            parts.append(f"atas nama {owner_name}")
        if school_year:
            parts.append(f"Tahun Pelajaran {school_year}")
        if doc_number:
            parts.append(f"Nomor: {doc_number}")
        if nisn:
            parts.append(f"NIS/NISN: {nisn}")
        parts.append("Dinyatakan LULUS berdasarkan hasil Ujian Nasional.")
        desc = ". ".join(parts[:5]) + ". " + parts[-1] if len(parts) > 5 else ". ".join(parts)

    elif doc_type == "Rapor":
        m_sem = re.search(r"(?:semester|kelas)\s*[:\-\.]?\s*([\w\s]+)", text, re.IGNORECASE)
        semester_info = m_sem.group(1).strip()[:30] if m_sem else ""
        desc = f"Rapor"
        if semester_info:
            desc += f" {semester_info}"
        if owner_name:
            desc += f" atas nama {owner_name}"
        if school_name:
            desc += f" dari {school_name}"
        if school_year:
            desc += f", Tahun Pelajaran {school_year}."

    elif doc_type == "Sertifikat":
        m_kegiatan = re.search(r"(?:sertifikat|piagam)\s+([\w\s]{3,50})", text, re.IGNORECASE)
        kegiatan = m_kegiatan.group(1).strip() if m_kegiatan else ""
        desc = f"Sertifikat"
        if kegiatan:
            desc += f" {kegiatan}"
        if owner_name:
            desc += f" atas nama {owner_name}"
        if sender:
            desc += f", diterbitkan oleh {sender}"
        if year:
            desc += f" tahun {year}."

    elif doc_type in ("Surat Tugas", "Surat Keputusan", "Surat Keterangan"):
        desc = f"{doc_type}"
        if doc_number:
            desc += f" No. {doc_number}"
        if owner_name:
            desc += f" atas nama {owner_name}"
        if sender:
            desc += f" dari {sender}"
        if year:
            desc += f" tahun {year}."

    else:
        desc = f"Dokumen {doc_type}"
        if owner_name:
            desc += f" atas nama {owner_name}"
        if year:
            desc += f" tahun {year}."

    return {
        "documentType":   doc_type,
        "ownerName":      owner_name,
        "documentNumber": doc_number,
        "nisn":           nisn,
        "year":           year,
        "institution":    school_name or sender,
        "description":    desc
    }

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Confidence Calculator
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
def calculate_confidence(meta: dict) -> dict:
    fields = [
        {"field": "Nama Pemilik",  "value": meta["ownerName"],
         "confidence": 95 if meta["ownerName"] else 0, "ok": bool(meta["ownerName"])},
        {"field": "Nomor Dokumen", "value": meta["documentNumber"],
         "confidence": 92 if (meta["documentNumber"] and not meta["documentNumber"].startswith("DOC-")) else 35,
         "ok": bool(meta["documentNumber"] and not meta["documentNumber"].startswith("DOC-"))},
        {"field": "NIS/NISN",     "value": meta["nisn"],
         "confidence": 90 if meta["nisn"] else 40, "ok": bool(meta["nisn"])},
        {"field": "Tahun",        "value": meta["year"],
         "confidence": 95 if meta["year"] else 50, "ok": bool(meta["year"])},
        {"field": "Jenis Dokumen","value": meta["documentType"],
         "confidence": 96, "ok": True},
    ]
    return {"overall": int(round(sum(f["confidence"] for f in fields) / len(fields))), "fields": fields}

# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Endpoint Utama: POST /api/ocr
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
@app.post("/api/ocr")
async def process_ocr(payload: OCRRequest):
    logger.info(f"ðŸ“¥ OCR Request | File: {payload.fileName}")

    # 1. Decode Base64 (Mendukung Gambar JPG/PNG/WEBP & File PDF Multi-halaman)
    pdf_text_all_pages = ""
    try:
        b64 = payload.image.split(",")[1] if "," in payload.image else payload.image
        raw_bytes = base64.b64decode(b64)
        img = None

        is_pdf = (payload.mimeType and "pdf" in payload.mimeType.lower()) or (payload.fileName and payload.fileName.lower().endswith(".pdf")) or raw_bytes.startswith(b"%PDF")
        if is_pdf:
            if PYMUPDF_AVAILABLE:
                logger.info("ðŸ“„ File PDF terdeteksi, memproses SELURUH HALAMAN via PyMuPDF...")
                doc = fitz.open(stream=raw_bytes, filetype="pdf")
                total_pages = len(doc)
                logger.info(f"   â†³ Total halaman PDF: {total_pages} halaman")

                # 1. Ekstrak teks digital langsung dari SELURUH HALAMAN PDF
                pdf_texts = []
                for p_idx in range(total_pages):
                    p_text = doc[p_idx].get_text()
                    if p_text and p_text.strip():
                        pdf_texts.append(f"=== HALAMAN {p_idx+1}/{total_pages} ===\n" + p_text.strip())
                
                if pdf_texts:
                    pdf_text_all_pages = "\n\n".join(pdf_texts)
                    logger.info(f"âœ¨ Berhasil mengekstrak teks digital dari {len(pdf_texts)} halaman PDF ({len(pdf_text_all_pages)} karakter)!")

                # 2. Render gambar halaman pertama untuk fallback OCR/Vision (DPI tinggi untuk akurasi OCR)
                if total_pages > 0:
                    try:
                        page = doc[0]
                        pix = page.get_pixmap(dpi=250)  # 250 DPI untuk kualitas OCR lebih tajam
                        img_pil = PILImage.frombytes("RGB", [pix.width, pix.height], pix.samples)
                        img = cv2.cvtColor(np.array(img_pil), cv2.COLOR_RGB2BGR)
                        logger.info(f"   â†³ Gambar PDF hal.1 dirender: {pix.width}x{pix.height}px @250dpi")
                    except Exception as e_img:
                        logger.warning(f"âš ï¸ Gagal render gambar PDF: {e_img}")
                doc.close()
            elif PYPDF_AVAILABLE:
                logger.info("ðŸ“„ PyMuPDF tidak ada, menggunakan pypdf fallback...")
                import io as _io
                reader = pypdf.PdfReader(_io.BytesIO(raw_bytes))
                total_pages = len(reader.pages)
                pdf_texts = []
                for p_idx, page in enumerate(reader.pages):
                    p_text = page.extract_text()
                    if p_text and p_text.strip():
                        pdf_texts.append(f"=== HALAMAN {p_idx+1}/{total_pages} ===\n" + p_text.strip())
                if pdf_texts:
                    pdf_text_all_pages = "\n\n".join(pdf_texts)
                    logger.info(f"âœ¨ Berhasil mengekstrak {len(pdf_texts)} halaman via pypdf ({len(pdf_text_all_pages)} karakter)!")
                else:
                    logger.warning("âš ï¸ pypdf tidak berhasil ekstrak teks (kemungkinan PDF berbasis gambar). Mencoba konversi ke gambar via PyMuPDF...")

                # Jika teks kosong (PDF berbasis gambar), coba render ulang dengan fitz secara dinamis
                if not pdf_text_all_pages and total_pages > 0:
                    try:
                        import fitz as _fitz
                        logger.info("ðŸ–¼ï¸ Mencoba render PDF â†’ gambar via PyMuPDF (dinamis)...")
                        _doc = _fitz.open(stream=raw_bytes, filetype="pdf")
                        _page = _doc[0]
                        _pix = _page.get_pixmap(dpi=200)
                        _img_pil = PILImage.frombytes("RGB", [_pix.width, _pix.height], _pix.samples)
                        img = cv2.cvtColor(np.array(_img_pil), cv2.COLOR_RGB2BGR)
                        _doc.close()
                        logger.info(f"âœ… Berhasil render PDF via PyMuPDF (dinamis): {_pix.width}x{_pix.height}px")
                    except ImportError:
                        logger.error(
                            "âŒ PyMuPDF (fitz) tidak tersedia! "
                            "Jalankan: pip install pymupdf  lalu restart server. "
                            "PDF berbasis gambar tidak dapat diproses tanpa PyMuPDF."
                        )
                        raise ValueError(
                            "PDF berbasis gambar membutuhkan PyMuPDF. "
                            "Jalankan: pip install pymupdf  lalu restart server Python OCR."
                        )
                    except Exception as e_fitz2:
                        logger.error(f"âŒ PyMuPDF (dinamis) gagal render PDF: {e_fitz2}")
                        raise ValueError(f"Tidak dapat memproses PDF: {e_fitz2}")

            else:
                raise ValueError("Modul PDF (pymupdf / pypdf) belum terinstall di Python. Jalankan: pip install pymupdf pypdf")
        else:
            nparr = np.frombuffer(raw_bytes, np.uint8)
            img   = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if img is None and not pdf_text_all_pages:
            if is_pdf and not PYMUPDF_AVAILABLE:
                raise ValueError(
                    "PDF ini berbasis gambar (scan) dan membutuhkan PyMuPDF untuk diproses. "
                    "Jalankan: pip install pymupdf  lalu restart server."
                )
            raise ValueError("Format dokumen/PDF tidak dapat dibaca.")
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Gagal decode dokumen: {e}")

    # 1b. Google Gemini Vision AI (Jika Gemini Key tersedia)
    gemini_key = get_gemini_key(payload.geminiKey)
    if gemini_key and img is not None:
        logger.info(f"âœ¨ Gemini key terdeteksi: {gemini_key[:12]}...")
        gemini_res = call_gemini_vision_ocr(b64, payload.mimeType or "image/jpeg", gemini_key)
        if gemini_res:
            meta = map_surat_to_metadata(gemini_res)
            logger.info("âœ… Selesai via Google Gemini Vision AI!")
            return {
                "text":       f"[Google Gemini Vision AI]\n{json.dumps(gemini_res, ensure_ascii=False, indent=2)}",
                "metadata":   meta,
                "confidence": calculate_confidence(meta)
            }
        logger.warning("âš ï¸ Gemini Vision gagal/melewati, beralih ke Groq Vision...")

    # 1c. Groq Vision AI (Hanya jika Groq Key dan model Vision tersedia)
    groq_key = get_groq_key(payload.groqKey)
    if groq_key and GROQ_AVAILABLE and GROQ_VISION_MODELS and img is not None:
        logger.info(f"🔑 Groq Vision key terdeteksi: {groq_key[:12]}...")
        # Resize gambar ke max 640px + quality 75.
        # Dengan detail="low" Groq menghitung ~256 token/gambar.
        # Total request: ~256 (gambar) + ~500 (prompt) + 1024 (max output) = ~1780 token â†’ aman di bawah 8000 TPM.
        h, w = img.shape[:2]
        max_dim = 640
        if max(h, w) > max_dim:
            scale = max_dim / float(max(h, w))
            img_vision = cv2.resize(img, (int(w * scale), int(h * scale)), interpolation=cv2.INTER_AREA)
        else:
            img_vision = img

        success, encoded_img = cv2.imencode('.jpg', img_vision, [int(cv2.IMWRITE_JPEG_QUALITY), 75])
        if success:
            b64_jpg = base64.b64encode(encoded_img.tobytes()).decode('utf-8')
            groq_res = call_groq_ocr(b64_jpg, "image/jpeg", groq_key)
        else:
            groq_res = call_groq_ocr(b64, payload.mimeType or "image/jpeg", groq_key)

        if groq_res:
            meta = map_surat_to_metadata(groq_res)
            logger.info("âœ… Selesai via Groq Vision AI!")
            return {
                "text":       f"[Groq Vision AI]\n{json.dumps(groq_res, ensure_ascii=False, indent=2)}",
                "metadata":   meta,
                "confidence": calculate_confidence(meta)
            }
        logger.warning("âš ï¸ Groq Vision gagal/melewati, beralih ke EasyOCR + Groq LLM...")

    # 2. Fast-Path: Jika PDF memiliki teks digital (>= 50 karakter),
    # lewati EasyOCR sepenuhnya! Ini memangkas waktu dari 40+ detik ke < 2 detik.
    ocr_text = ''
    has_digital_pdf = bool(pdf_text_all_pages and len(pdf_text_all_pages.strip()) >= 50)

    if has_digital_pdf:
        logger.info(f'✨ PDF digital text langsung digunakan ({len(pdf_text_all_pages)} chars). Melewati EasyOCR CPU untuk kecepatan maksimal!')
    else:
        # Dokumen gambar scan atau PDF scan tanpa teks digital
        if img is not None:
            processed = apply_preprocessing(img, payload.engine) if payload.preprocess else img
            if payload.engine == 'tesseract' and tesseract_available:
                ocr_text = pytesseract.image_to_string(processed, config='--psm 3 -l ind+eng')
            else:
                logger.info('🔍 Menjalankan EasyOCR (optimized)...')
                ocr_text = run_easyocr(processed)

    # Gabungkan nama file + teks digital semua halaman PDF + teks OCR
    combined_texts = []
    if payload.fileName:
        combined_texts.append(f"NAMA FILE: {payload.fileName}")
    if pdf_text_all_pages:
        combined_texts.append(pdf_text_all_pages)
    if ocr_text:
        combined_texts.append("=== HASIL OCR GAMBAR ===\n" + ocr_text)

    text = "\n\n".join(combined_texts).strip()

    # Batasi panjang teks agar tidak overflow context window AI (maks ~12.000 karakter)
    MAX_TEXT_CHARS = 12000
    if len(text) > MAX_TEXT_CHARS:
        logger.warning(f"âš ï¸ Teks terlalu panjang ({len(text)} karakter), dipotong ke {MAX_TEXT_CHARS} karakter.")
        text = text[:MAX_TEXT_CHARS] + "\n[... TEKS TERPOTONG ...]"

    logger.info(f"ðŸ“„ Teks OCR mentah: {len(text)} karakter")

    # 2b. Coba strukturkan via Google Gemini LLM jika key ada
    if gemini_key and text.strip():
        logger.info("âœ¨ Mencoba strukturkan teks via Google Gemini LLM...")
        gemini_struct = call_gemini_text_ocr(text, gemini_key)
        if gemini_struct:
            meta = map_surat_to_metadata(gemini_struct, text)
            logger.info("âœ… Selesai via EasyOCR + Google Gemini LLM!")
            return {
                "text":       f"[EasyOCR + Google Gemini LLM]\n{json.dumps(gemini_struct, ensure_ascii=False, indent=2)}",
                "metadata":   meta,
                "confidence": calculate_confidence(meta)
            }

    # 2c. Coba strukturkan via Groq LLM jika key ada
    if groq_key and GROQ_AVAILABLE and text.strip():
        logger.info("ðŸ¤– Mencoba strukturkan teks via Groq LLM...")
        groq_struct = call_groq_text_ocr(text, groq_key)
        if groq_struct:
            meta = map_surat_to_metadata(groq_struct, text)
            logger.info("âœ… Selesai via EasyOCR + Groq LLM!")
            return {
                "text":       f"[" + ("Digital PDF" if has_digital_pdf else "EasyOCR") + f" + Groq LLM]\n{json.dumps(groq_struct, ensure_ascii=False, indent=2)}",
                "metadata":   meta,
                "confidence": calculate_confidence(meta)
            }

    # 3. Ollama AI â€” Strukturkan teks OCR
    if text.strip():
        logger.info("ðŸ¤– Mengirim teks ke Ollama untuk distrukturkan...")

        # Coba skema surat baru (nomor_surat, tanggal, pengirim, penerima, perihal, isi_ringkas, lampiran)
        structured = call_ollama_extract_surat(text)
        if structured:
            meta = map_surat_to_metadata(structured, text)
            logger.info("âœ… Selesai via EasyOCR + Ollama AI!")
            return {
                "text":       f"[EasyOCR + Ollama {OLLAMA_MODEL}]\n{json.dumps(structured, ensure_ascii=False, indent=2)}",
                "metadata":   meta,
                "confidence": calculate_confidence(meta)
            }
        logger.warning("âš ï¸ Ollama tidak menghasilkan respons terstruktur, beralih ke regex fallback...")

    # 4. Pure Regex Fallback (jika Ollama tidak jalan / gagal)
    doc_type = payload.docType
    if not doc_type or doc_type in ["Lainnya", "Dokumen Lainnya"]:
        doc_type = classify_document(text, payload.fileName)

    meta = extract_metadata_regex(text, payload.fileName, doc_type)
    logger.info("âœ… Selesai via EasyOCR + Regex fallback!")
    return {"text": text, "metadata": meta, "confidence": calculate_confidence(meta)}


# â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
# ENDPOINT BATCH ZIP â€” Ollama AI
# POST /api/ocr-zip
# â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
VALID_IMAGE_EXT = {'.png', '.jpg', '.jpeg'}
JUNK_PREFIXES   = ('__MACOSX', '.', 'Thumbs.db', 'desktop.ini')

def _is_valid_image_entry(name: str) -> bool:
    parts = name.replace('\\', '/').split('/')
    for part in parts:
        if any(part.startswith(j) for j in JUNK_PREFIXES):
            return False
    ext = os.path.splitext(name)[1].lower()
    return ext in VALID_IMAGE_EXT


def _ollama_ocr_image_bytes(image_bytes: bytes, mime_type: str) -> str:
    """
    Ekstrak teks dari gambar menggunakan EasyOCR lalu strukturkan via Ollama.
    Menggantikan _groq_ocr_image_bytes().
    Interface tetap: menerima bytes gambar, mengembalikan string teks OCR.
    """
    # Konversi bytes â†’ numpy array â†’ EasyOCR
    nparr = np.frombuffer(image_bytes, np.uint8)
    img   = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    if img is None:
        raise ValueError("Gagal decode gambar dari bytes.")

    reader   = get_easyocr_reader()
    raw_text = "\n".join([r[1] for r in reader.readtext(img)])

    if not raw_text.strip():
        raise ValueError("EasyOCR tidak berhasil mengekstrak teks dari gambar ini.")

    return raw_text


def _simulate_blockchain_tx(file_name: str, ocr_text: str) -> str:
    raw = f"{file_name}:{ocr_text[:64]}:{datetime.utcnow().isoformat()}"
    tx_hash = "0x" + hashlib.sha256(raw.encode()).hexdigest()
    return tx_hash


@app.post("/api/ocr-zip")
async def ocr_zip_batch(file: UploadFile = File(...)):
    """
    Batch OCR dari file ZIP.
    Setiap gambar (.png/.jpg/.jpeg) di dalam ZIP diproses oleh EasyOCR + Ollama AI.
    """
    original_name = file.filename or "upload.zip"
    if not original_name.lower().endswith(".zip"):
        raise HTTPException(
            status_code=400,
            detail=f"File harus berekstensi .zip. Diterima: '{original_name}'"
        )

    logger.info(f"ðŸ“¦ [ZIP-OCR] Menerima file: {original_name}")

    zip_bytes  = await file.read()
    zip_buffer = io.BytesIO(zip_bytes)

    results: List[dict] = []
    total_success = 0
    total_failed  = 0

    try:
        with zipfile.ZipFile(zip_buffer, 'r') as zf:
            all_names   = zf.namelist()
            image_names = [n for n in all_names if _is_valid_image_entry(n)]

            logger.info(f"ðŸ“‹ [ZIP-OCR] Total file: {len(all_names)} | Gambar valid: {len(image_names)}")

            if not image_names:
                raise HTTPException(
                    status_code=400,
                    detail="ZIP tidak mengandung file gambar valid (.png/.jpg/.jpeg)."
                )

            for entry_name in image_names:
                short_name = os.path.basename(entry_name)
                ext        = os.path.splitext(entry_name)[1].lower()
                mime_map   = {'.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png'}
                mime_type  = mime_map.get(ext, 'image/jpeg')

                logger.info(f"  ðŸ–¼ï¸  Memproses: {short_name}")

                try:
                    image_bytes = zf.read(entry_name)

                    if PILLOW_AVAILABLE:
                        img_pil     = PILImage.open(io.BytesIO(image_bytes)).convert("RGB")
                        buf         = io.BytesIO()
                        img_pil.save(buf, format="JPEG", quality=90)
                        image_bytes = buf.getvalue()
                        mime_type   = "image/jpeg"

                    # Ekstrak teks via EasyOCR (menggantikan Groq Vision)
                    ocr_text = _ollama_ocr_image_bytes(image_bytes, mime_type)

                    if not ocr_text.strip():
                        raise ValueError("OCR mengembalikan teks kosong.")

                    logger.info(f"     âœ… OCR berhasil: {len(ocr_text)} karakter")

                    # Opsional: strukturkan via Ollama
                    structured_info = None
                    if ocr_text.strip():
                        structured_info = call_ollama_extract_surat(ocr_text)

                    metadata = {
                        "file_name":       short_name,
                        "ocr_chars":       len(ocr_text),
                        "processed_at":    datetime.utcnow().isoformat() + "Z",
                        "ollama_model":    OLLAMA_MODEL,
                        "structured_data": structured_info,
                    }

                    tx_hash = _simulate_blockchain_tx(short_name, ocr_text)
                    logger.info(f"     â›“ï¸  TX Hash: {tx_hash[:20]}...")

                    results.append({
                        "file_name": short_name,
                        "status":    "success",
                        "ocr_text":  ocr_text,
                        "tx_hash":   tx_hash,
                        "metadata":  metadata,
                    })
                    total_success += 1

                except Exception as err:
                    logger.error(f"     âŒ Gagal proses {short_name}: {err}")
                    results.append({
                        "file_name": short_name,
                        "status":    "failed",
                        "error":     str(err),
                        "ocr_text":  None,
                        "tx_hash":   None,
                        "metadata":  None,
                    })
                    total_failed += 1

    except zipfile.BadZipFile:
        raise HTTPException(status_code=400, detail="File ZIP rusak atau tidak valid.")

    logger.info(
        f"ðŸ“Š [ZIP-OCR] Selesai | Total: {len(image_names)} "
        f"| Sukses: {total_success} | Gagal: {total_failed}"
    )

    return {
        "zip_file":      original_name,
        "total_files":   len(image_names),
        "total_success": total_success,
        "total_failed":  total_failed,
        "results":       results,
        "processed_at":  datetime.utcnow().isoformat() + "Z",
    }


# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Root & Health Check
# â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
@app.get("/")
async def root():
    return {
        "status":  "online",
        "service": "Python OCR API Service",
        "engine":  f"EasyOCR + Ollama ({OLLAMA_MODEL}) Self-hosted",
        "documentation": "/docs",
        "health":  "/health"
    }

@app.get("/health")
@app.get("/api/health")
async def health_check():
    # Cek koneksi ke Ollama
    ollama_online = False
    ollama_error  = None
    try:
        headers = {}
        if OLLAMA_API_KEY:
            headers["Authorization"] = f"Bearer {OLLAMA_API_KEY}"
        r = requests.get(f"{OLLAMA_HOST.rstrip('/')}/api/tags", headers=headers, timeout=5)
        ollama_online = r.status_code == 200
    except requests.exceptions.ConnectionError:
        ollama_error = f"Ollama tidak berjalan di {OLLAMA_HOST}. Jalankan: ollama serve"
    except requests.exceptions.Timeout:
        ollama_error = f"Ollama timeout saat health check di {OLLAMA_HOST}"
    except Exception as e:
        ollama_error = str(e)

    return {
        "status":              "healthy",
        "version":             f"6.0.0 â€” EasyOCR + Ollama ({OLLAMA_MODEL}) + Groq AI + Gemini AI",
        "ollama_host":         OLLAMA_HOST,
        "ollama_model":        OLLAMA_MODEL,
        "ollama_online":       ollama_online,
        "ollama_error":        ollama_error,
        "groq_key_loaded":    bool(get_groq_key()),
        "groq_sdk_available": GROQ_AVAILABLE,
        "gemini_key_loaded":  bool(get_gemini_key()),
        "gemini_sdk_available": GEMINI_SDK_AVAILABLE,
        "pymupdf_available":   PYMUPDF_AVAILABLE,
        "pillow_available":    PILLOW_AVAILABLE,
        "tesseract_available": tesseract_available,
        "easyocr_loaded":      easyocr_reader is not None,
        "endpoints": [
            "POST /api/ocr       â€” Single image OCR (base64)",
            "POST /api/ocr-zip   â€” Batch OCR dari file ZIP",
            "GET  /health        â€” Status server + Ollama/Groq connectivity",
            "GET  /api/test-ai   â€” Test langsung Groq AI & Gemini AI",
        ]
    }

@app.get("/api/test-ai")
async def test_ai_connectivity():
    groq_k = get_groq_key()
    gemini_k = get_gemini_key()

    test_sample = "IJAZAH\nNama: Ahmad Fauzi\nNomor: 00123/SK/2026\nNISN: 0012345678"
    res = {
        "groq": {"key_preview": (groq_k[:10] + "...") if groq_k else "NONE", "status": "untested"},
        "gemini": {"key_preview": (gemini_k[:10] + "...") if gemini_k else "NONE", "status": "untested"}
    }

    if groq_k:
        r = call_groq_text_ocr(test_sample, groq_k)
        if r:
            res["groq"]["status"] = "SUCCESS âœ…"
            res["groq"]["extracted_data"] = r
        else:
            res["groq"]["status"] = "FAILED âŒ (Cek terminal log)"

    if gemini_k:
        r = call_gemini_text_ocr(test_sample, gemini_k)
        if r:
            res["gemini"]["status"] = "SUCCESS âœ…"
            res["gemini"]["extracted_data"] = r
        else:
            res["gemini"]["status"] = "FAILED âŒ (Cek terminal log)"

    return res

class QrCodeRequest(BaseModel):
    data: str
    size: Optional[int] = 250
    with_logo: Optional[bool] = True

@app.post("/api/qrcode")
async def generate_qr_code(req: QrCodeRequest):
    try:
        import qrcode
        from qrcode.constants import ERROR_CORRECT_H
        from PIL import Image, ImageDraw

        qr = qrcode.QRCode(
            version=None,
            error_correction=ERROR_CORRECT_H,
            box_size=10,
            border=2,
        )
        qr.add_data(req.data)
        qr.make(fit=True)
        img = qr.make_image(fill_color="#0f172a", back_color="white").convert("RGBA")
        target_size = req.size if req.size else 300
        img = img.resize((target_size, target_size), Image.Resampling.LANCZOS)

        if req.with_logo:
            barcode_logo_path = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "public", "images", "barcode-logo.png")
            school_logo_path = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "public", "images", "school-logo.png")
            logo_path = barcode_logo_path if os.path.exists(barcode_logo_path) else school_logo_path
            if os.path.exists(logo_path):
                logo = Image.open(logo_path).convert("RGBA")
                badge_size = int(target_size * 0.24)
                pad = 4
                logo_inner = badge_size - (pad * 2)
                logo = logo.resize((logo_inner, logo_inner), Image.Resampling.LANCZOS)

                cx = target_size // 2
                cy = target_size // 2

                # Paste logo transparan langsung tanpa kotak putih latar belakang
                img.paste(logo, (cx - logo_inner // 2, cy - logo_inner // 2), mask=logo.split()[3])

        buf = io.BytesIO()
        img.save(buf, format="PNG")
        buf.seek(0)
        from fastapi.responses import Response
        return Response(content=buf.getvalue(), media_type="image/png")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


class SignatureDetectRequest(BaseModel):
    image_base64: Optional[str] = None
    file_path: Optional[str] = None
    mime_type: Optional[str] = "application/pdf"

@app.post("/api/detect-signature")
async def detect_signature_endpoint(req: SignatureDetectRequest):
    """
    AI Signature Slot Detector v3 - Presisi Kolom Tanda Tangan
    Menempatkan QR di RUANG KOSONG slot tanda tangan (antara jabatan dan nama/NIP),
    bukan di centroid tinta. 4 level: PDF text -> OCR image -> CV slot-above -> fallback.
    """
    try:
        cv_img = None

        # LEVEL 1: PDF dengan text layer (PyMuPDF get_text dict)
        is_pdf = (
            req.file_path and os.path.exists(req.file_path) and
            (req.file_path.lower().endswith(".pdf") or "pdf" in (req.mime_type or ""))
        )
        if is_pdf and PYMUPDF_AVAILABLE:
            try:
                import fitz
                doc_pdf = fitz.open(req.file_path)
                page = doc_pdf[-1]
                pw, ph = page.rect.width, page.rect.height

                TITLE_KW = [
                    "kepala dinas", "kepala sekolah", "kepala madrasah",
                    "an. kepala", "a.n. kepala", "an kepala",
                    "mengetahui", "menyetujui",
                    "direktur utama", "direktur", "ketua", "dekan", "rektor",
                    "wali kelas", "kepala bidang", "kepala seksi", "kepala",
                    "pimpinan",
                ]
                NIP_KW = ["nip", "nuptk", "nrk", "nik", "pembina", "penata",
                          "pengatur", "iv/", "iii/", "ii/", "golongan",
                          "196", "197", "198", "199", "200", "201"]

                text_lines = []
                for blk in page.get_text("dict", flags=fitz.TEXT_PRESERVE_WHITESPACE)["blocks"]:
                    if blk.get("type") != 0:
                        continue
                    for line in blk.get("lines", []):
                        txt = " ".join(sp.get("text", "") for sp in line.get("spans", [])).strip()
                        if txt:
                            b = line["bbox"]
                            text_lines.append({"text": txt.lower(), "bbox": b})

                found_title = None
                for kw in TITLE_KW:
                    for tl in text_lines:
                        b = tl["bbox"]
                        if kw in tl["text"] and b[3] > ph * 0.35 and b[0] > pw * 0.30:
                            if found_title is None or b[1] > found_title["bbox"][1]:
                                found_title = tl
                    if found_title:
                        break

                if found_title:
                    ft = found_title["bbox"]
                    cx = (ft[0] + ft[2]) / 2
                    slot_top = ft[3] + 2

                    candidates = [
                        tl for tl in text_lines
                        if tl["bbox"][1] > ft[3] + 2
                        and abs(tl["bbox"][0] - ft[0]) < 120
                        and any(kw in tl["text"] for kw in NIP_KW)
                    ]
                    if candidates:
                        nearest = min(candidates, key=lambda t: t["bbox"][1])
                        slot_bottom = nearest["bbox"][1] - 2
                    else:
                        slot_bottom = slot_top + 40

                    slot_cy = max(min((slot_top + slot_bottom) / 2, ph * 0.92), ph * 0.40)

                    logging.info(f"[SigSlot] PDF-text: kw='{found_title['text'][:25]}' x={cx/pw:.3f} y={slot_cy/ph:.3f}")
                    return {
                        "success": True, "found": True,
                        "method": "ai_pdf_text_signature_slot",
                        "x_ratio": float(cx / pw),
                        "y_ratio": float(slot_cy / ph),
                    }

                # PDF scan tanpa text layer -> render ke gambar
                pix = page.get_pixmap(dpi=200)
                nparr = np.frombuffer(pix.tobytes("png"), np.uint8)
                cv_img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

            except Exception as exc:
                logging.warning(f"[SigSlot] PDF level-1 error: {exc}")

        # LEVEL 2: Muat gambar jika belum ada
        if cv_img is None:
            if req.file_path and os.path.exists(req.file_path) and not req.file_path.lower().endswith(".pdf"):
                cv_img = cv2.imread(req.file_path)
            elif req.image_base64:
                b64 = req.image_base64
                if "," in b64:
                    b64 = b64.split(",")[1]
                nparr = np.frombuffer(base64.b64decode(b64), np.uint8)
                cv_img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        if cv_img is not None:
            hi, wi = cv_img.shape[:2]
            is_portrait = hi > wi

            # LEVEL 2: CV Wet Ink - Deteksi Tanda Tangan / Stempel Basah (Biru / Ungu / Merah)
            try:
                roi_x1 = int(wi * (0.42 if is_portrait else 0.52))
                roi_x2 = int(wi * (0.92 if is_portrait else 0.95))
                roi_y1 = int(hi * (0.50 if is_portrait else 0.45))
                roi_y2 = int(hi * (0.92 if is_portrait else 0.92))

                roi = cv_img[roi_y1:roi_y2, roi_x1:roi_x2]
                rh, rw = roi.shape[:2]

                hsv = cv2.cvtColor(roi, cv2.COLOR_BGR2HSV)
                mask_blue = cv2.inRange(hsv, np.array([85, 30, 40]), np.array([135, 255, 255]))
                mask_purple = cv2.inRange(hsv, np.array([135, 25, 30]), np.array([170, 255, 255]))
                mask_red1 = cv2.inRange(hsv, np.array([0, 45, 45]), np.array([15, 255, 255]))
                mask_red2 = cv2.inRange(hsv, np.array([165, 45, 45]), np.array([180, 255, 255]))
                mask_color = cv2.bitwise_or(mask_blue, cv2.bitwise_or(mask_purple, cv2.bitwise_or(mask_red1, mask_red2)))

                kern = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (3, 3))
                mask_color = cv2.morphologyEx(mask_color, cv2.MORPH_OPEN, kern)

                color_pixels = cv2.countNonZero(mask_color)
                if color_pixels > 120:
                    cnts, _ = cv2.findContours(mask_color, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
                    sig_cnts = [c for c in cnts if cv2.contourArea(c) > 15]
                    if sig_cnts:
                        all_pts = np.vstack(sig_cnts)
                        bx, by, bw, bh = cv2.boundingRect(all_pts)
                        cx = roi_x1 + bx + bw // 2
                        cy = roi_y1 + by + int(bh * 0.55)

                        cx = max(int(wi * 0.52), min(int(wi * 0.78), cx))
                        cy = max(int(hi * 0.60), min(int(hi * 0.86), cy))

                        logging.info(f"[SigSlot] Wet-ink detected: cx={cx/wi:.3f}, cy={cy/hi:.3f}")
                        return {
                            "success": True, "found": True,
                            "method": "ai_cv_wet_ink_signature",
                            "x_ratio": float(round(cx / wi, 3)),
                            "y_ratio": float(round(cy / hi, 3)),
                        }
            except Exception as exc:
                logging.warning(f"[SigSlot] Wet-ink error: {exc}")

            # LEVEL 3: CV Text Block - Deteksi Kolom Tanda Tangan (Grayscale / B&W Scan)
            try:
                gray = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
                _, bw = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)

                # Abaikan border dekoratif di tepi kanan dan bawah
                bw[:, int(rw * 0.88):] = 0
                bw[int(rh * 0.90):, :] = 0

                proj = np.sum(bw > 0, axis=1)
                active_rows = np.where(proj > rw * 0.05)[0]
                if len(active_rows) > 10:
                    sig_y_start = roi_y1 + active_rows[0]
                    sig_y_end = roi_y1 + active_rows[-1]
                    cy = (sig_y_start + sig_y_end) // 2

                    col_proj = np.sum(bw > 0, axis=0)
                    active_cols = np.where(col_proj > rh * 0.05)[0]
                    if len(active_cols) > 10:
                        cx = roi_x1 + (active_cols[0] + active_cols[-1]) // 2
                    else:
                        cx = int(wi * (0.635 if is_portrait else 0.720))

                    cx = max(int(wi * 0.52), min(int(wi * 0.78), cx))
                    cy = max(int(hi * 0.60), min(int(hi * 0.86), cy))

                    logging.info(f"[SigSlot] Text-block detected: cx={cx/wi:.3f}, cy={cy/hi:.3f}")
                    return {
                        "success": True, "found": True,
                        "method": "ai_cv_text_block_signature",
                        "x_ratio": float(round(cx / wi, 3)),
                        "y_ratio": float(round(cy / hi, 3)),
                    }
            except Exception as exc:
                logging.warning(f"[SigSlot] Text-block error: {exc}")

        # LEVEL 4: Smart Fallback - Posisi Kolom Tanda Tangan Standar Surat Resmi Indonesia (BSrE/BSSN)
        is_portrait = (cv_img is None) or (cv_img.shape[0] > cv_img.shape[1])
        dx = 0.635 if is_portrait else 0.720
        dy = 0.740 if is_portrait else 0.700

        logging.info(f"[SigSlot] Fallback x={dx} y={dy} portrait={is_portrait}")
        return {
            "success": True, "found": False,
            "method": "smart_default_signature_slot",
            "x_ratio": dx, "y_ratio": dy,
        }

    except Exception as exc:
        logging.error(f"[SigSlot] Fatal: {exc}")
        return {"success": False, "found": False, "error": str(exc), "x_ratio": 0.635, "y_ratio": 0.740}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=5000)
