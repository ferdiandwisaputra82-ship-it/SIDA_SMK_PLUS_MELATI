const { Contract } = require('fabric-contract-api');

class DocumentContract extends Contract {

    // 1. Cek apakah dokumen sudah terdaftar
    async DocumentExists(ctx, docId) {
        const buffer = await ctx.stub.getState(docId);
        return (!!buffer && buffer.length > 0);
    }

    // 2. Registrasi dokumen baru
    async RegisterDocument(ctx, docId, hash, ownerName, docType, year) {
        const exists = await this.DocumentExists(ctx, docId);
        if (exists) {
            throw new Error(`Dokumen dengan ID ${docId} sudah terdaftar di blockchain.`);
        }

        const document = {
            docId,
            hash,
            ownerName: ownerName || '-',
            docType: docType || 'Dokumen Lainnya',
            year: year || new Date().getFullYear().toString(),
            timestamp: new Date().toISOString(),
            txId: ctx.stub.getTxID()
        };

        // Simpan ke state ledger
        await ctx.stub.putState(docId, Buffer.from(JSON.stringify(document)));
        
        // Log event
        await ctx.stub.setEvent('RegisterDocumentEvent', Buffer.from(JSON.stringify(document)));

        return JSON.stringify(document);
    }

    // 3. Ambil detail dokumen
    async GetDocument(ctx, docId) {
        const exists = await this.DocumentExists(ctx, docId);
        if (!exists) {
            throw new Error(`Dokumen dengan ID ${docId} tidak ditemukan.`);
        }
        const buffer = await ctx.stub.getState(docId);
        return buffer.toString();
    }

    // 4. Verifikasi dokumen menggunakan hash SHA-256 (Query berdasar Rich Query / Selector)
    async VerifyDocument(ctx, hash) {
        const query = {
            selector: {
                hash: hash
            }
        };
        const iterator = await ctx.stub.getQueryResult(JSON.stringify(query));
        const results = [];

        while (true) {
            const res = await iterator.next();
            if (res.value && res.value.value.toString()) {
                const json = JSON.parse(res.value.value.toString('utf8'));
                results.push(json);
            }
            if (res.done) {
                await iterator.close();
                break;
            }
        }

        return JSON.stringify(results);
    }
}

module.exports = DocumentContract;
