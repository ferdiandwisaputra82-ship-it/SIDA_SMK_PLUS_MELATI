# Panduan Setup Hyperledger Fabric 2.5 Lokal (Vela DMS)

Gunakan panduan ini untuk mengaktifkan jaringan Hyperledger Fabric Test Network lokal menggunakan Docker + WSL2 (Windows Subsystem for Linux) atau Git Bash.

---

## Langkah 1: Persiapan Lingkungan

Pastikan Anda sudah menginstal:
1. **Docker Desktop** (dengan integrasi WSL2 aktif di Windows).
2. Terminal Linux (Ubuntu di WSL2) atau Git Bash.

---

## Langkah 2: Download Jaringan Fabric & Binaries

Buka terminal WSL2 atau Git Bash Anda, buat direktori di luar Laragon (misalnya di `$HOME/fabric-test`) lalu download script Fabric:

```bash
mkdir -p ~/hyperledger-fabric
cd ~/hyperledger-fabric

# Download binary dan docker images Hyperledger Fabric v2.5.4 & CA v1.5.7
curl -sSL https://bit.ly/2ysbOFE | bash -s -- 2.5.4 1.5.7
```

Setelah selesai, Anda akan memiliki folder `fabric-samples/` di direktori tersebut.

---

## Langkah 3: Menjalankan Test Network & Membuat Channel

Masuk ke folder `test-network` dan jalankan script untuk menyalakan node:

```bash
cd ~/hyperledger-fabric/fabric-samples/test-network

# 1. Matikan network yang lama (jika ada)
./network.sh down

# 2. Nyalakan node peer & orderer dengan Certificate Authority (CA) 
./network.sh up createChannel -c documentchannel -ca
```

*Jaringan akan berjalan dan membuat channel bernama `documentchannel`.*

---

## Langkah 4: Deploy Smart Contract (Chaincode)

Salin folder smart contract `documentCC` yang sudah kita buat di Laragon ke dalam folder `fabric-samples/asset-transfer-basic/chaincode-javascript` atau deploy langsung dengan mengarahkan path-nya:

```bash
# Deploy chaincode dari direktori Laragon Anda
./network.sh deployCC -ccn documentCC -ccp /mnt/c/laragon/www/vela-dms-laravel/chaincode/documentCC -ccl javascript
```

*Sekarang Smart Contract `documentCC` sudah aktif di blockchain!*

---

## Langkah 5: Menyalin Sertifikat Kripto ke Laravel

Untuk membuat microservice Python di port 5001 bisa terhubung dengan aman, kita perlu menyalin file sertifikat yang digenerate oleh Test Network ke folder `storage/fabric/` Laravel.

Jalankan perintah copy ini di WSL2:

```bash
# Buat folder penyimpanan sertifikat di proyek Laravel
mkdir -p /mnt/c/laragon/www/vela-dms-laravel/storage/fabric

# 1. Copy TLS CA Root Certificate
cp ~/hyperledger-fabric/fabric-samples/test-network/organizations/peerOrganizations/org1.example.com/msp/tlscacerts/tlsca.org1.example.com-cert.pem /mnt/c/laragon/www/vela-dms-laravel/storage/fabric/tls-ca-cert.pem

# 2. Copy User MSP Admin Certificate
cp ~/hyperledger-fabric/fabric-samples/test-network/organizations/peerOrganizations/org1.example.com/users/User1@org1.example.com/msp/signcerts/User1@org1.example.com-cert.pem /mnt/c/laragon/www/vela-dms-laravel/storage/fabric/admin-cert.pem

# 3. Copy User MSP Admin Private Key
cp ~/hyperledger-fabric/fabric-samples/test-network/organizations/peerOrganizations/org1.example.com/users/User1@org1.example.com/msp/keystore/* /mnt/c/laragon/www/vela-dms-laravel/storage/fabric/admin-key.pem
```

---

## Langkah 6: Menjalankan Python Fabric Service

Buka command prompt / terminal Windows baru, masuk ke folder `python-ocr`:

```cmd
cd c:\laragon\www\vela-dms-laravel\python-ocr

# 1. Pastikan virtual environment aktif
.\venv\Scripts\activate

# 2. Instal library Fabric SDK
pip install hyperledger-fabric-gateway

# 3. Jalankan server Fabric port 5001
python fabric_service.py
```

---

## Mode Auto-Simulasi (Sangat Berguna!)

Jika Anda belum sempat menyalakan Docker/Fabric:
- Sistem akan **otomatis mendeteksi** jika file di `storage/fabric/tls-ca-cert.pem` belum ada.
- Python API akan masuk ke **Mode Simulasi** dengan mengembalikan status `Simulated` dan generate hash/TX ID mirip blockchain sungguhan.
- Aplikasi Laravel Anda tetap berjalan lancar 100%!
