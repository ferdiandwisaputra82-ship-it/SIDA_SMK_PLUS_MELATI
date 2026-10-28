import sys
import base64
import requests
import json

def test_ocr(image_path, engine="easyocr"):
    url = "http://127.0.0.1:5000/api/ocr"
    
    # Membaca gambar dan convert ke Base64
    try:
        with open(image_path, "rb") as image_file:
            encoded_string = base64.b64encode(image_file.read()).decode('utf-8')
    except FileNotFoundError:
        print(f"Error: File {image_path} tidak ditemukan!")
        return
    except Exception as e:
        print(f"Error saat membaca file gambar: {e}")
        return

    # Payload sesuai spesifikasi FastAPI server
    payload = {
        "image": encoded_string,
        "engine": engine,
        "preprocess": True
    }
    
    headers = {
        "Content-Type": "application/json"
    }

    print(f"📡 Mengirim '{image_path}' ke {url}...")
    print(f"⚙️  Engine: {engine} (Preprocessing: True)")
    
    try:
        response = requests.post(url, data=json.dumps(payload), headers=headers)
        if response.status_code == 200:
            print("\n✨ --- TEKS HASIL OCR --- ✨\n")
            print(response.json().get("text"))
            print("\n✨ ---------------------- ✨")
        else:
            print(f"❌ Gagal: Server merespons dengan status {response.status_code}")
            print(response.text)
    except Exception as e:
        print(f"❌ Gagal menghubungi server OCR: {e}")
        print("💡 Pastikan server uvicorn sudah berjalan!")
        print("👉 Jalankan perintah: python -m uvicorn server:app --host 127.0.0.1 --port 5000")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Cara penggunaan:")
        print("  python test_client.py <path_ke_gambar> [easyocr|tesseract]")
        print("Contoh:")
        print("  python test_client.py contoh_ijazah.png easyocr")
        sys.exit(1)
        
    img_path = sys.argv[1]
    ocr_engine = sys.argv[2] if len(sys.argv) > 2 else "easyocr"
    
    test_ocr(img_path, ocr_engine)
