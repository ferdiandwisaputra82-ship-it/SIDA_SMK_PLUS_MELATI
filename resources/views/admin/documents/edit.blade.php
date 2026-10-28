@extends('layouts.admin')

@section('title', 'Edit Dokumen')

@section('content')
<div class="max-w-xl bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
    <h3 class="font-bold text-slate-800 mb-5 flex items-center gap-2 text-[15px] font-heading">
        <i data-lucide="edit" class="w-4 h-4 text-blue-600"></i> Edit Data Dokumen
    </h3>

    <form action="{{ route('admin.documents.update', $document->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        
        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Jenis Dokumen *</label>
            <select name="document_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-sm outline-none">
                @foreach($docTypes as $t)
                    <option value="{{ $t }}" {{ $document->document_type === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nama Pemilik *</label>
            <input type="text" name="owner_name" value="{{ $document->owner_name }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-800 font-semibold" required>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Nomor Dokumen *</label>
            <input type="text" name="document_number" value="{{ $document->document_number }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-800 font-semibold" required>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">NISN / NIM</label>
                <input type="text" name="nisn" value="{{ $document->nisn }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-800">
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tahun</label>
                <input type="text" name="year" value="{{ $document->year }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 text-slate-800">
            </div>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Deskripsi</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500/20 resize-none text-slate-800">{{ $document->description }}</textarea>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 text-sm outline-none">
                <option value="Terverifikasi" {{ $document->status === 'Terverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                <option value="Menunggu Verifikasi" {{ $document->status === 'Menunggu Verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="Ditolak" {{ $document->status === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
            <a href="{{ route('admin.documents') }}" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">Batal</a>
            <button type="submit" class="px-5 py-2 bg-blue-500 text-white rounded-xl text-xs font-bold hover:bg-blue-400 transition-all shadow-md">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
